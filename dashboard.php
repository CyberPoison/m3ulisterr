<?php
// M3uListerr - analytics & control dashboard.
//
// Self-contained: login, a SQLite store in the private data directory (never
// web-served), import of the playback event log and cache.json, IP
// geolocation, a config.php editor, credential management, and a JSON API the
// front-end renders as stats, a globe, charts and a sessions table.
//
// Security posture (see the SECURITY block below for specifics): no secret is
// ever sent to the browser; every byte of output is escaped or JSON-encoded;
// all state-changing requests require a CSRF token and a valid session;
// logins are rate-limited with Argon2id hashing; a strict CSP with a
// per-response nonce blocks injected scripts; and the vendored chart
// libraries are streamed from disk by this script rather than referenced from
// any CDN.

declare(strict_types=1);

// Sub-files check for this before running, so they can never be requested
// directly as their own URL.
define('M3ULISTERR_APP', true);

require_once __DIR__ . '/m3ulisterr_lib.php';
// config.php is loaded server-side only, for $apiKey (TMDB poster/title
// lookups) and the account list. It emits no output and never reaches the
// browser.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/m3ulisterr_api.php';

// ===========================================================================
// SECURITY: response headers
// ===========================================================================
$M3U_NONCE = base64_encode(random_bytes(16));
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;

function m3uSecurityHeaders(string $nonce): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
    header("Content-Security-Policy: "
        . "default-src 'none'; "
        . "script-src 'self' 'nonce-$nonce'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' https://image.tmdb.org data:; "
        . "connect-src 'self'; "
        . "font-src 'self'; "
        . "base-uri 'none'; "
        . "form-action 'self'; "
        . "frame-ancestors 'none'");
}
m3uSecurityHeaders($M3U_NONCE);

// ===========================================================================
// SECURITY: session
// ===========================================================================
$dataDir = m3uDataDir();
if ($dataDir === false) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'M3uListerr: no writable data directory. Create a writable "m3ulisterr_data" directory next to the site and reload.';
    exit;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Strict',
]);
session_name('m3ulisterr_sid');
// Sessions live in the private data directory, never the shared system temp.
$sessDir = $dataDir . '/sessions';
if (!is_dir($sessDir)) {
    @mkdir($sessDir, 0700, true);
}
if (is_dir($sessDir) && is_writable($sessDir)) {
    session_save_path($sessDir);
}
session_start();

// ===========================================================================
// Database
// ===========================================================================
function m3uDb(): PDO {
    static $db = null;
    if ($db !== null) {
        return $db;
    }
    $path = m3uPath('db');
    if ($path === false) {
        throw new RuntimeException('no database path');
    }
    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    @chmod($path, 0600);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA busy_timeout=5000');
    $db->exec('PRAGMA foreign_keys=ON');
    m3uMigrate($db);
    return $db;
}

function m3uMigrate(PDO $db): void {
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        username TEXT UNIQUE NOT NULL,
        pass_hash TEXT NOT NULL,
        created INTEGER NOT NULL,
        updated INTEGER NOT NULL
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS kv (k TEXT PRIMARY KEY, v TEXT)');
    $db->exec('CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY, ip TEXT, ts INTEGER, ok INTEGER
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_login_ip_ts ON login_attempts(ip, ts)');
    $db->exec('CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY,
        ts REAL, type TEXT, ip TEXT, ua TEXT,
        movie_id INTEGER, media TEXT, media_type TEXT, series_code TEXT,
        username TEXT, password TEXT, lang TEXT,
        provider TEXT, debrid TEXT, release TEXT, release_langs TEXT,
        resolution INTEGER, codec TEXT, source_url TEXT, result TEXT,
        cache_hit INTEGER, resolve_ms REAL, throughput REAL,
        duration REAL, audio_lang TEXT, audio_index INTEGER,
        sub_count INTEGER, subtitles TEXT,
        seg_start REAL, seg_len REAL, deliver_ms REAL, outcome TEXT, kind TEXT,
        dedup TEXT UNIQUE
    )');
    foreach (['ts', 'type', 'ip', 'media', 'movie_id', 'username'] as $c) {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_ev_$c ON events($c)");
    }
    $db->exec('CREATE TABLE IF NOT EXISTS geo (
        ip TEXT PRIMARY KEY, status TEXT, country TEXT, country_code TEXT,
        region TEXT, city TEXT, zip TEXT, lat REAL, lon REAL, isp TEXT, updated INTEGER
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS tmdb_meta (
        movie_id INTEGER PRIMARY KEY, media_type TEXT, title TEXT, year TEXT,
        poster TEXT, overview TEXT, updated INTEGER
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS cache_entries (
        cache_key TEXT PRIMARY KEY, value TEXT, added INTEGER, expires INTEGER, status TEXT
    )');
    // Durable resolved-stream cache - the source of truth that survives deploys
    // (cache.json is only the temporary hot layer). Written by play.php via
    // m3uCacheStore(); mirrors the schema created in m3ulisterr_lib.php.
    $db->exec('CREATE TABLE IF NOT EXISTS resolved_cache (
        cache_key TEXT PRIMARY KEY, value TEXT, status TEXT, added INTEGER, expires INTEGER,
        prewarmed INTEGER DEFAULT 0, movie_id INTEGER, username TEXT, lang TEXT, media_type TEXT, updated INTEGER
    )');
}

// Absolute path to the temporary hot cache file this app uses.
function m3uCacheJsonPath(): string {
    return __DIR__ . '/cache.json';
}
function m3uCacheJsonStatus(): array {
    $p = m3uCacheJsonPath();
    if (!is_file($p)) {
        return ['exists' => false, 'count' => 0];
    }
    $d = json_decode((string) @file_get_contents($p), true);
    return ['exists' => true, 'count' => is_array($d) ? count($d) : 0];
}

function m3uKvGet(PDO $db, string $k, $default = null) {
    $st = $db->prepare('SELECT v FROM kv WHERE k = ?');
    $st->execute([$k]);
    $row = $st->fetch();
    return $row ? $row['v'] : $default;
}
function m3uKvSet(PDO $db, string $k, string $v): void {
    $st = $db->prepare('INSERT INTO kv (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v');
    $st->execute([$k, $v]);
}

// ===========================================================================
// Auth
// ===========================================================================
function m3uUserCount(PDO $db): int {
    return (int) $db->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
}
function m3uCsrf(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function m3uCheckCsrf(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'CSRF check failed. Reload the page and try again.';
        exit;
    }
}
function m3uIsLoggedIn(): bool {
    if (empty($_SESSION['uid']) || empty($_SESSION['login_time'])) {
        return false;
    }
    // Absolute cap (12h) and idle cap (2h).
    $now = time();
    if ($now - (int) $_SESSION['login_time'] > 43200) {
        return false;
    }
    if ($now - (int) ($_SESSION['last_seen'] ?? $now) > 7200) {
        return false;
    }
    // Bind the session to the browser it was created in.
    if (($_SESSION['ua_hash'] ?? '') !== m3uUaHash()) {
        return false;
    }
    $_SESSION['last_seen'] = $now;
    return true;
}
function m3uUaHash(): string {
    return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|m3ulisterr');
}
function m3uLoginBlocked(PDO $db, string $ip): int {
    // More than 8 failures from one IP in 15 minutes: locked out until they
    // age out. Returns seconds remaining, 0 when not blocked.
    $since = time() - 900;
    $st = $db->prepare('SELECT COUNT(*) c, MAX(ts) m FROM login_attempts WHERE ip = ? AND ok = 0 AND ts > ?');
    $st->execute([$ip, $since]);
    $row = $st->fetch();
    if ((int) $row['c'] >= 8) {
        return max(1, 900 - (time() - (int) $row['m']));
    }
    return 0;
}
function m3uRecordAttempt(PDO $db, string $ip, bool $ok): void {
    $st = $db->prepare('INSERT INTO login_attempts (ip, ts, ok) VALUES (?, ?, ?)');
    $st->execute([$ip, time(), $ok ? 1 : 0]);
    $db->exec('DELETE FROM login_attempts WHERE ts < ' . (time() - 86400));
}

// ===========================================================================
// Importers
// ===========================================================================
function m3uNum($v) { return is_numeric($v) ? $v + 0 : null; }

function m3uImportEvents(PDO $db): int {
    $imported = 0;
    $base = m3uPath('events');
    if ($base === false) {
        return 0;
    }
    $offsets = json_decode((string) m3uKvGet($db, 'event_offsets', '{}'), true) ?: [];

    $ins = $db->prepare('INSERT OR IGNORE INTO events
        (ts, type, ip, ua, movie_id, media, media_type, series_code, username, password, lang,
         provider, debrid, release, release_langs, resolution, codec, source_url, result,
         cache_hit, resolve_ms, throughput, duration, audio_lang, audio_index, sub_count, subtitles,
         seg_start, seg_len, deliver_ms, outcome, kind, dedup)
        VALUES
        (:ts,:type,:ip,:ua,:movie_id,:media,:media_type,:series_code,:username,:password,:lang,
         :provider,:debrid,:release,:release_langs,:resolution,:codec,:source_url,:result,
         :cache_hit,:resolve_ms,:throughput,:duration,:audio_lang,:audio_index,:sub_count,:subtitles,
         :seg_start,:seg_len,:deliver_ms,:outcome,:kind,:dedup)');

    $db->beginTransaction();
    foreach ([$base, $base . '.1'] as $file) {
        if (!is_file($file)) {
            continue;
        }
        $key = $file . ':' . (@fileinode($file) ?: 0);
        $start = (int) ($offsets[$key] ?? 0);
        $size = (int) @filesize($file);
        if ($size < $start) {
            $start = 0; // rotated/shrunk
        }
        $fh = @fopen($file, 'rb');
        if (!$fh) {
            continue;
        }
        fseek($fh, $start);
        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $e = json_decode($line, true);
            if (!is_array($e) || empty($e['type'])) {
                continue;
            }
            $subs = isset($e['subtitles']) ? json_encode($e['subtitles'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            $dedup = hash('sha256', $line);
            try {
                $ins->execute([
                    ':ts' => m3uNum($e['ts'] ?? null),
                    ':type' => (string) $e['type'],
                    ':ip' => $e['ip'] ?? '',
                    ':ua' => $e['ua'] ?? '',
                    ':movie_id' => isset($e['movieId']) ? (int) $e['movieId'] : null,
                    ':media' => $e['media'] ?? '',
                    ':media_type' => $e['mediaType'] ?? '',
                    ':series_code' => $e['seriesCode'] ?? '',
                    ':username' => $e['username'] ?? '',
                    ':password' => $e['password'] ?? '',
                    ':lang' => $e['lang'] ?? ($e['audioLang'] ?? ''),
                    ':provider' => $e['provider'] ?? '',
                    ':debrid' => $e['debrid'] ?? '',
                    ':release' => $e['release'] ?? ($e['title'] ?? ''),
                    ':release_langs' => $e['releaseLangs'] ?? '',
                    ':resolution' => isset($e['resolution']) ? (int) $e['resolution'] : null,
                    ':codec' => $e['codec'] ?? '',
                    ':source_url' => $e['sourceUrl'] ?? '',
                    ':result' => $e['result'] ?? '',
                    ':cache_hit' => isset($e['cacheHit']) ? (int) (bool) $e['cacheHit'] : null,
                    ':resolve_ms' => m3uNum($e['resolveMs'] ?? null),
                    ':throughput' => m3uNum($e['throughputMbps'] ?? null),
                    ':duration' => m3uNum($e['duration'] ?? null),
                    ':audio_lang' => $e['audioLang'] ?? '',
                    ':audio_index' => isset($e['audioIndex']) ? (int) $e['audioIndex'] : null,
                    ':sub_count' => isset($e['subCount']) ? (int) $e['subCount'] : null,
                    ':subtitles' => $subs,
                    ':seg_start' => m3uNum($e['start'] ?? null),
                    ':seg_len' => m3uNum($e['len'] ?? null),
                    ':deliver_ms' => m3uNum($e['deliverMs'] ?? null),
                    ':outcome' => $e['outcome'] ?? '',
                    ':kind' => $e['kind'] ?? '',
                    ':dedup' => $dedup,
                ]);
                $imported += $ins->rowCount();
            } catch (Throwable $ex) {
                // skip malformed row
            }
        }
        $offsets[$key] = ftell($fh);
        fclose($fh);
    }
    $db->commit();
    m3uKvSet($db, 'event_offsets', json_encode($offsets));
    m3uKvSet($db, 'last_import', (string) time());
    return $imported;
}

// NOTE: cache.json is deliberately NOT imported back into SQLite. play.php
// writes resolved/failed entries straight into resolved_cache (the source of
// truth), and cache.json is only ever rebuilt FROM that table. Importing
// cache.json here would create the rebuild -> import -> rebuild loop the
// operator asked to avoid, so this step is intentionally a no-op.
function m3uImportCache(PDO $db): int {
    return 0;
}

// Geolocate any viewer IP not looked up yet, via ip-api.com's batch endpoint
// (up to 100 per call). Cached permanently in the geo table.
function m3uImportGeo(PDO $db): int {
    $rows = $db->query("SELECT DISTINCT ip FROM events WHERE ip != '' AND ip NOT IN (SELECT ip FROM geo) LIMIT 100")->fetchAll();
    $ips = array_column($rows, 'ip');
    if (empty($ips)) {
        return 0;
    }
    $payload = array_map(fn($ip) => ['query' => $ip, 'fields' => 'status,country,countryCode,regionName,city,zip,lat,lon,isp,query'], $ips);
    $ch = curl_init('http://ip-api.com/batch');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $arr = json_decode((string) $resp, true);
    if (!is_array($arr)) {
        return 0;
    }
    $ins = $db->prepare('INSERT OR REPLACE INTO geo (ip, status, country, country_code, region, city, zip, lat, lon, isp, updated) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $db->beginTransaction();
    $n = 0;
    foreach ($arr as $g) {
        if (empty($g['query'])) {
            continue;
        }
        $ins->execute([
            $g['query'], $g['status'] ?? 'fail', $g['country'] ?? '', $g['countryCode'] ?? '',
            $g['regionName'] ?? '', $g['city'] ?? '', $g['zip'] ?? '',
            m3uNum($g['lat'] ?? null), m3uNum($g['lon'] ?? null), $g['isp'] ?? '', time(),
        ]);
        $n++;
    }
    $db->commit();
    return $n;
}

// Fetch title/poster/overview for movie ids seen but not yet looked up.
function m3uImportTmdb(PDO $db): int {
    if (!isset($GLOBALS['apiKey']) || $GLOBALS['apiKey'] === '') {
        return 0;
    }
    $rows = $db->query("SELECT DISTINCT movie_id, media_type FROM events WHERE movie_id IS NOT NULL AND movie_id > 0 AND movie_id NOT IN (SELECT movie_id FROM tmdb_meta) LIMIT 20")->fetchAll();
    if (empty($rows)) {
        return 0;
    }
    $ins = $db->prepare('INSERT OR REPLACE INTO tmdb_meta (movie_id, media_type, title, year, poster, overview, updated) VALUES (?,?,?,?,?,?,?)');
    $n = 0;
    foreach ($rows as $r) {
        $id = (int) $r['movie_id'];
        $type = ($r['media_type'] === 'series') ? 'tv' : 'movie';
        $url = "https://api.themoviedb.org/3/$type/$id?api_key=" . urlencode($GLOBALS['apiKey']) . '&language=en-US';
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $d = json_decode((string) $resp, true);
        if (!is_array($d)) {
            continue;
        }
        $title = $d['title'] ?? ($d['name'] ?? ('#' . $id));
        $date = $d['release_date'] ?? ($d['first_air_date'] ?? '');
        $ins->execute([
            $id, $type, $title, substr((string) $date, 0, 4),
            $d['poster_path'] ?? '', mb_substr((string) ($d['overview'] ?? ''), 0, 600), time(),
        ]);
        $n++;
    }
    return $n;
}

function m3uRunImport(PDO $db): array {
    return [
        'events' => m3uImportEvents($db),
        'cache' => m3uImportCache($db),
        'geo' => m3uImportGeo($db),
        'tmdb' => m3uImportTmdb($db),
    ];
}

// ===========================================================================
// Request routing
// ===========================================================================
$action = $_GET['action'] ?? '';
$db = m3uDb();
$needsSetup = m3uUserCount($db) === 0;

// ---- Vendored asset route (login required) ----
if (isset($_GET['asset'])) {
    if (!m3uIsLoggedIn()) {
        http_response_code(403);
        exit;
    }
    $map = [
        'd3' => ['m3ulisterr_assets/d3.min.js', 'application/javascript'],
        'topojson' => ['m3ulisterr_assets/topojson-client.min.js', 'application/javascript'],
        'world' => ['m3ulisterr_assets/countries-110m.json', 'application/json'],
    ];
    $a = $_GET['asset'];
    if (!isset($map[$a])) {
        http_response_code(404);
        exit;
    }
    [$rel, $ctype] = $map[$a];
    $path = __DIR__ . '/' . $rel;
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $ctype);
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

// ---- POST handlers ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = m3uClientIp() ?: 'unknown';

    if ($action === 'setup' && $needsSetup) {
        m3uCheckCsrf();
        $u = trim((string) ($_POST['username'] ?? ''));
        $p = (string) ($_POST['password'] ?? '');
        $errors = m3uValidateCreds($u, $p);
        if (!$errors) {
            $st = $db->prepare('INSERT INTO users (username, pass_hash, created, updated) VALUES (?,?,?,?)');
            $st->execute([$u, password_hash($p, PASSWORD_ARGON2ID), time(), time()]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $db->lastInsertId();
            $_SESSION['uname'] = $u;
            $_SESSION['login_time'] = time();
            $_SESSION['last_seen'] = time();
            $_SESSION['ua_hash'] = m3uUaHash();
            header('Location: ?');
            exit;
        }
        $setupErrors = $errors;
    } elseif ($action === 'login' && !$needsSetup) {
        m3uCheckCsrf();
        $wait = m3uLoginBlocked($db, $ip);
        if ($wait > 0) {
            $loginError = "Too many attempts. Try again in " . ceil($wait / 60) . " minute(s).";
        } else {
            $u = trim((string) ($_POST['username'] ?? ''));
            $p = (string) ($_POST['password'] ?? '');
            $st = $db->prepare('SELECT * FROM users WHERE username = ?');
            $st->execute([$u]);
            $user = $st->fetch();
            if ($user && password_verify($p, $user['pass_hash'])) {
                m3uRecordAttempt($db, $ip, true);
                if (password_needs_rehash($user['pass_hash'], PASSWORD_ARGON2ID)) {
                    $up = $db->prepare('UPDATE users SET pass_hash = ? WHERE id = ?');
                    $up->execute([password_hash($p, PASSWORD_ARGON2ID), $user['id']]);
                }
                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $user['id'];
                $_SESSION['uname'] = $user['username'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_seen'] = time();
                $_SESSION['ua_hash'] = m3uUaHash();
                header('Location: ?');
                exit;
            }
            m3uRecordAttempt($db, $ip, false);
            $loginError = 'Invalid username or password.';
            usleep(400000);
        }
    } elseif ($action === 'logout') {
        m3uCheckCsrf();
        $_SESSION = [];
        session_destroy();
        header('Location: ?');
        exit;
    } elseif ($action === 'change_creds') {
        if (!m3uIsLoggedIn()) { http_response_code(403); exit; }
        m3uCheckCsrf();
        header('Content-Type: application/json');
        $cur = (string) ($_POST['current'] ?? '');
        $newU = trim((string) ($_POST['new_username'] ?? ''));
        $newP = (string) ($_POST['new_password'] ?? '');
        $st = $db->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $user = $st->fetch();
        if (!$user || !password_verify($cur, $user['pass_hash'])) {
            echo json_encode(['ok' => false, 'error' => 'Current password is incorrect.']);
            exit;
        }
        $uname = $newU !== '' ? $newU : $user['username'];
        $errs = [];
        if ($newU !== '' && ($newU !== $user['username'])) {
            $errs = array_merge($errs, m3uValidateUsername($newU));
            $chk = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $chk->execute([$newU, $user['id']]);
            if ($chk->fetch()) { $errs[] = 'That username is taken.'; }
        }
        if ($newP !== '') {
            $errs = array_merge($errs, m3uValidatePassword($newP));
        }
        if ($errs) {
            echo json_encode(['ok' => false, 'error' => implode(' ', $errs)]);
            exit;
        }
        $hash = $newP !== '' ? password_hash($newP, PASSWORD_ARGON2ID) : $user['pass_hash'];
        $up = $db->prepare('UPDATE users SET username = ?, pass_hash = ?, updated = ? WHERE id = ?');
        $up->execute([$uname, $hash, time(), $user['id']]);
        $_SESSION['uname'] = $uname;
        echo json_encode(['ok' => true]);
        exit;
    } elseif ($action === 'save_config') {
        if (!m3uIsLoggedIn()) { http_response_code(403); exit; }
        m3uCheckCsrf();
        header('Content-Type: application/json');
        echo json_encode(m3uSaveConfig($_POST['settings'] ?? []));
        exit;
    } elseif ($action === 'rebuild_cache') {
        if (!m3uIsLoggedIn()) { http_response_code(403); exit; }
        m3uCheckCsrf();
        header('Content-Type: application/json');
        // Recreate the temporary cache.json from the durable SQLite store.
        $written = m3uCacheRebuildJson(m3uCacheJsonPath());
        echo json_encode($written >= 0
            ? ['ok' => true, 'written' => $written]
            : ['ok' => false, 'error' => 'rebuild failed (no durable store or not writable)']);
        exit;
    }
}

// ---- JSON API (login required) ----
if ($action === 'api') {
    header('Content-Type: application/json');
    if (!m3uIsLoggedIn()) {
        http_response_code(403);
        echo json_encode(['error' => 'not authenticated']);
        exit;
    }
    echo json_encode(m3uApi($db, (string) ($_GET['q'] ?? '')));
    exit;
}

// ===========================================================================
// Config editor (safe, allow-listed scalar settings)
// ===========================================================================
function m3uEditableConfig(): array {
    // key => [label, type, secret?]. Editable through the dashboard (admin-only,
    // behind login + CSP). 'secret' fields carry live credentials - the operator
    // asked to manage them here; they are only ever sent to an authenticated
    // admin, never to a public page.
    return [
        'maxResolution' => ['Max resolution (px)', 'int'],
        'expirationHours' => ['Cache expiry (hours)', 'int'],
        'timeOut' => ['Scraper timeout (s)', 'int'],
        'totalPages' => ['Playlist pages', 'int'],
        'cacheSize' => ['Cache size (MB)', 'int'],
        'maxFileSize' => ['Max file size (MB)', 'int'],
        'usePremiumize' => ['Use Premiumize', 'bool'],
        'useRealDebrid' => ['Use Real-Debrid', 'bool'],
        'useAllDebrid' => ['Use AllDebrid', 'bool'],
        'useTorBox' => ['Use TorBox', 'bool'],
        'INCLUDE_ADULT_VOD' => ['Include adult VOD', 'bool'],
        'userCreatePlaylist' => ['Build own playlist', 'bool'],
        'language' => ['TMDB language', 'str'],
        'HeadlessVidX_Address' => ['HeadlessVidX address', 'str'],
        // Credentials / URLs (admin-only)
        'apiKey' => ['TMDB API key', 'str', true],
        'frenchAioStreamsUrl' => ['AIOStreams base URL', 'str', true],
        'premiumizeApiKey' => ['Premiumize API key (single)', 'str', true],
        'PRIVATE_TOKEN' => ['Real-Debrid token (single)', 'str', true],
        'alldebridApiKey' => ['AllDebrid API key (single)', 'str', true],
        'torboxApiKey' => ['TorBox API key (single)', 'str', true],
        'openSubtitlesApiKey' => ['OpenSubtitles API key', 'str', true],
        'openSubtitlesApiToken' => ['OpenSubtitles token', 'str', true],
    ];
}
function m3uReadConfigValues(): array {
    $src = @file_get_contents(__DIR__ . '/config.php');
    $out = [];
    if ($src === false) {
        return $out;
    }
    foreach (m3uEditableConfig() as $key => [$label, $type]) {
        if ($type === 'bool') {
            if (preg_match('/\$' . preg_quote($key, '/') . '\s*=\s*(true|false)\s*;/i', $src, $m)) {
                $out[$key] = strtolower($m[1]) === 'true';
            }
        } elseif ($type === 'int') {
            if (preg_match('/\$' . preg_quote($key, '/') . '\s*=\s*(-?\d+)\s*;/', $src, $m)) {
                $out[$key] = (int) $m[1];
            }
        } else {
            if (preg_match('/\$' . preg_quote($key, '/') . "\s*=\s*'((?:[^'\\\\]|\\\\.)*)'\s*;/", $src, $m)) {
                $out[$key] = stripslashes($m[1]);
            }
        }
    }
    return $out;
}
function m3uFindPhpCli(): string {
    $c = trim((string) @shell_exec('command -v php 2>/dev/null'));
    if ($c !== '' && @is_executable($c)) {
        return $c;
    }
    // PHP_BINARY only helps when the SAPI is the CLI (not php-fpm).
    if (defined('PHP_BINARY') && substr(PHP_BINARY, -4) === '/php' && @is_executable(PHP_BINARY)) {
        return PHP_BINARY;
    }
    foreach (['/opt/homebrew/bin/php', '/usr/local/bin/php', '/usr/bin/php', dirname(PHP_BINARY) . '/php'] as $p) {
        if (@is_executable($p)) {
            return $p;
        }
    }
    return '';
}

function m3uSaveConfig(array $settings): array {
    $path = __DIR__ . '/config.php';
    $src = @file_get_contents($path);
    if ($src === false) {
        return ['ok' => false, 'error' => 'config.php not readable'];
    }
    $editable = m3uEditableConfig();
    $changed = 0;
    foreach ($settings as $key => $val) {
        if (!isset($editable[$key])) {
            continue; // ignore anything not explicitly allow-listed
        }
        $type = $editable[$key][1];
        if ($type === 'bool') {
            $rep = (in_array($val, ['1', 'true', 'on', true, 1], true)) ? 'true' : 'false';
            $pat = '/(\$' . preg_quote($key, '/') . '\s*=\s*)(?:true|false)(\s*;)/i';
        } elseif ($type === 'int') {
            if (!is_numeric($val)) { continue; }
            $rep = (string) (int) $val;
            $pat = '/(\$' . preg_quote($key, '/') . '\s*=\s*)-?\d+(\s*;)/';
        } else {
            $rep = "'" . addslashes((string) $val) . "'";
            $pat = '/(\$' . preg_quote($key, '/') . "\s*=\s*)'(?:[^'\\\\]|\\\\.)*'(\s*;)/";
        }
        $new = preg_replace($pat, '${1}' . str_replace('\\', '\\\\', $rep) . '${2}', $src, 1, $count);
        if ($new !== null && $count > 0) {
            $src = $new;
            $changed++;
        }
    }
    // Never write a file that no longer parses.
    $tmp = $path . '.m3utmp';
    if (@file_put_contents($tmp, $src) === false) {
        return ['ok' => false, 'error' => 'cannot write temp file'];
    }
    // Lint with a real CLI php if one can be found (php-fpm's own PHP_BINARY is
    // the FPM binary, which has no -l, and bare "php" is often not on the FPM
    // PATH). When none is available, the write still can't break syntax: every
    // replacement is a tightly anchored swap of one scalar literal, so a
    // successful preg_replace leaves the surrounding code untouched.
    $phpCli = m3uFindPhpCli();
    if ($phpCli !== '') {
        $lint = shell_exec(escapeshellarg($phpCli) . ' -l ' . escapeshellarg($tmp) . ' 2>&1');
        if (strpos((string) $lint, 'No syntax errors') === false) {
            @unlink($tmp);
            return ['ok' => false, 'error' => 'refused: result would not parse'];
        }
    }
    // Timestamped backup, then atomic replace.
    $backups = m3uPath('backups');
    if ($backups && (is_dir($backups) || @mkdir($backups, 0700, true))) {
        @copy($path, $backups . '/config.' . date('Ymd-His') . '.php');
    }
    @rename($tmp, $path);
    return ['ok' => true, 'changed' => $changed];
}

// ===========================================================================
// Credential validation
// ===========================================================================
function m3uValidateUsername(string $u): array {
    $e = [];
    if (strlen($u) < 3 || strlen($u) > 32) { $e[] = 'Username must be 3-32 characters.'; }
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $u)) { $e[] = 'Username may use letters, digits, . _ - only.'; }
    return $e;
}
function m3uValidatePassword(string $p): array {
    $e = [];
    if (strlen($p) < 10) { $e[] = 'Password must be at least 10 characters.'; }
    if (strlen($p) > 200) { $e[] = 'Password is too long.'; }
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/[0-9]/', $p)) { $e[] = 'Password needs letters and digits.'; }
    return $e;
}
function m3uValidateCreds(string $u, string $p): array {
    return array_merge(m3uValidateUsername($u), m3uValidatePassword($p));
}

require __DIR__ . '/m3ulisterr_view.php';

<?php
// M3uListerr shared runtime: playback analytics events, the private data
// directory, and outbound-URL safety checks.
//
// This is loaded on hot request paths - play.php, every HLS segment, every
// video_proxy.php range request - so everything here obeys two rules: it never
// throws or produces output, and it never adds a noticeable delay. Events are
// appended as JSON lines; dashboard.php imports them into SQLite after an
// admin logs in.

if (defined('M3ULISTERR_LIB_LOADED')) {
    return;
}
define('M3ULISTERR_LIB_LOADED', true);

// Private data directory. Outside the web root whenever the server lets us
// create one there, so nothing in it is reachable by URL at all. Otherwise a
// directory inside the web root that both the root .htaccess and its own
// .htaccess refuse to serve, with secret-derived file names so a server that
// ignores .htaccess still exposes nothing guessable.
function m3uDataDir() {
    static $dir = null;
    if ($dir !== null) {
        return $dir;
    }

    $dir = false;
    // A persistent location survives deploys/redeploys - important now that the
    // SQLite database (not just cache.json) is the durable store. Set
    // M3U_DATA_DIR to a mounted volume in production (see docker-compose.yml);
    // otherwise fall back to a directory beside the app.
    $envDir = getenv('M3U_DATA_DIR');
    $candidates = [];
    if (is_string($envDir) && $envDir !== '') {
        $candidates[] = rtrim($envDir, '/');
    }
    $candidates[] = dirname(__DIR__) . '/m3ulisterr_data';
    $candidates[] = __DIR__ . '/m3ulisterr_data';
    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            if (is_writable($candidate)) {
                $dir = $candidate;
                break;
            }
            continue;
        }
        if (is_writable(dirname($candidate)) && @mkdir($candidate, 0700, true)) {
            $dir = $candidate;
            break;
        }
    }

    if ($dir !== false) {
        m3uProtectDataDir($dir);
    }
    return $dir;
}

function m3uProtectDataDir($dir) {
    if (!file_exists($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "RewriteEngine On\nRewriteRule ^ - [F]\n");
    }
    if (!file_exists($dir . '/index.php')) {
        @file_put_contents($dir . '/index.php', "<?php\nhttp_response_code(404);\n");
    }
}

// A random token generated once per installation. Stored as PHP that returns
// the value: if the file were ever requested over the web it would execute
// and print nothing, instead of being downloaded.
function m3uInstallSecret() {
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    $dir = m3uDataDir();
    if ($dir === false) {
        return $secret = '';
    }

    $path = $dir . '/secret.php';
    if (!is_file($path)) {
        $value = bin2hex(random_bytes(16));
        $handle = @fopen($path, 'x');
        if ($handle !== false) {
            fwrite($handle, "<?php\nreturn '" . $value . "';\n");
            fclose($handle);
            @chmod($path, 0600);
        }
    }

    $loaded = @include $path;
    return $secret = (is_string($loaded) && preg_match('/^[a-f0-9]{32}$/', $loaded)) ? $loaded : '';
}

function m3uPath($kind) {
    $dir = m3uDataDir();
    $secret = m3uInstallSecret();
    if ($dir === false || $secret === '') {
        return false;
    }
    $tag = substr($secret, 0, 16);
    switch ($kind) {
        case 'events':
            return $dir . '/events-' . $tag . '.jsonl';
        case 'db':
            return $dir . '/m3ulisterr-' . $tag . '.sqlite';
        case 'backups':
            return $dir . '/backups-' . $tag;
    }
    return false;
}

// ---------------------------------------------------------------------------
// Durable resolved-stream cache (SQLite)
//
// cache.json is the fast, temporary hot layer and is wiped on every deploy.
// SQLite is the durable source of truth: every resolved stream URL (and every
// prewarmed title) is also written here, so after a deploy the hot cache can be
// rebuilt from it instead of every title paying the ~13s AIOStreams cold
// resolve again. The dashboard reads this table and can rebuild cache.json on
// demand; play.php rebuilds it automatically when it finds cache.json missing.
// ---------------------------------------------------------------------------

// Opens the shared SQLite DB and ensures the resolved_cache table exists.
// Returns a PDO, or null on any failure - callers on the hot path must treat a
// null as "just use cache.json", never as an error.
function m3uCacheDb() {
    static $db = false; // false = not yet tried, null = tried and failed
    if ($db !== false) {
        return $db;
    }
    $db = null;
    try {
        $path = m3uPath('db');
        if ($path === false) {
            return $db;
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        @chmod($path, 0600);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA busy_timeout=5000');
        $pdo->exec('CREATE TABLE IF NOT EXISTS resolved_cache (
            cache_key TEXT PRIMARY KEY,
            value TEXT,
            status TEXT,
            added INTEGER,
            expires INTEGER,
            prewarmed INTEGER DEFAULT 0,
            movie_id INTEGER,
            username TEXT,
            lang TEXT,
            media_type TEXT,
            updated INTEGER
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rc_prewarmed ON resolved_cache(prewarmed)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rc_expires ON resolved_cache(expires)');
        $db = $pdo;
    } catch (Throwable $e) {
        $db = null;
    }
    return $db;
}

// Derives the TMDB id from a cache key like "157336_tmdb_url",
// "157336_fr_tmdb_url" or "157336_series_s01e01_url".
function m3uCacheKeyMovieId($key) {
    return preg_match('/^(\d+)_/', (string) $key, $m) ? (int) $m[1] : null;
}

// Persists one resolved cache entry to SQLite. Called from writeToCache after
// the cache.json write. Skips the transient "_running_" marker. Fail-safe.
function m3uCacheStore($key, $value, $expirationTime) {
    try {
        if ($value === '_running_') {
            return;
        }
        $db = m3uCacheDb();
        if ($db === null) {
            return;
        }
        $status = ($value === '_failed_') ? 'failed' : 'resolved';
        // Only a genuine resolve or a failure marker is worth persisting.
        if ($status === 'resolved' && (!is_string($value) || $value === '')) {
            return;
        }
        $prewarmed = (isset($_GET['prewarm']) && $_GET['prewarm'] !== '0') ? 1 : 0;
        // A real play must never demote a prewarmed row's flag; keep the max.
        $st = $db->prepare('INSERT INTO resolved_cache
            (cache_key, value, status, added, expires, prewarmed, movie_id, username, lang, media_type, updated)
            VALUES (:k,:v,:s,:a,:e,:p,:mid,:u,:l,:mt,:up)
            ON CONFLICT(cache_key) DO UPDATE SET
                value=excluded.value, status=excluded.status, added=excluded.added,
                expires=excluded.expires, prewarmed=MAX(resolved_cache.prewarmed, excluded.prewarmed),
                movie_id=excluded.movie_id, username=excluded.username, lang=excluded.lang,
                media_type=excluded.media_type, updated=excluded.updated');
        $st->execute([
            ':k' => $key,
            ':v' => is_string($value) ? $value : json_encode($value),
            ':s' => $status,
            ':a' => time(),
            ':e' => (int) $expirationTime,
            ':p' => $prewarmed,
            ':mid' => m3uCacheKeyMovieId($key),
            ':u' => $_GET['username'] ?? '',
            ':l' => $GLOBALS['requestedLang'] ?? '',
            ':mt' => (strpos((string) $key, '_series_') !== false) ? 'series' : 'movie',
            ':up' => time(),
        ]);
    } catch (Throwable $e) {
        // Never let cache persistence break a resolve.
    }
}

// Rebuilds cache.json from the durable SQLite rows that are still valid.
// Returns the number of entries written, or -1 on failure. Used by play.php
// (auto, when cache.json is missing) and the dashboard (manual button).
function m3uCacheRebuildJson($cacheFilePath) {
    try {
        $db = m3uCacheDb();
        if ($db === null) {
            return -1;
        }
        $now = time();
        // ONLY 'resolved' entries are rebuilt into cache.json - never 'failed'
        // (dashboard-only, informational) and never '_running_' (transient). This
        // is the single direction of flow: SQLite resolved -> cache.json. cache.json
        // is never imported back into SQLite, so there is no rebuild/import loop.
        $rows = $db->query("SELECT cache_key, value, added, expires FROM resolved_cache WHERE status = 'resolved' AND expires > " . $now)->fetchAll();
        $cache = [];
        foreach ($rows as $r) {
            // cache.json stores the value JSON-encoded (see writeToCache).
            $cache[$r['cache_key']] = [
                'value' => json_encode($r['value']),
                'addedTime' => (int) $r['added'],
                'expirationTime' => (int) $r['expires'],
            ];
        }
        $tmp = $cacheFilePath . '.rebuild.' . getmypid();
        if (@file_put_contents($tmp, json_encode($cache)) === false) {
            return -1;
        }
        @rename($tmp, $cacheFilePath);
        return count($cache);
    } catch (Throwable $e) {
        return -1;
    }
}

// If cache.json is missing (e.g. right after a deploy), recreate it from
// SQLite so the very next resolve is a hot cache hit instead of a cold
// AIOStreams round-trip. Cheap no-op when cache.json already exists.
function m3uCacheEnsureJson($cacheFilePath) {
    if (!file_exists($cacheFilePath)) {
        m3uCacheRebuildJson($cacheFilePath);
    }
}

function m3uClientIp() {
    // REMOTE_ADDR only: X-Forwarded-For is set by the client and would let
    // any viewer write whatever address they like into the logs.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

// Short, stable id for a media URL, so events from play.php, the HLS scripts
// and video_proxy.php about the same stream can be joined without copying the
// full signed URL into every line.
//
// The join only works if all three log the SAME underlying URL. play.php's
// resolve result is a wrapper - "subtitle_track_playlist.m3u8?video=<enc>" or
// "video_proxy.php?url=<enc>&..." - whereas the HLS/proxy scripts key off the
// inner video URL. So the wrapper is unwrapped to that inner URL before
// hashing, which is exactly what the downstream scripts hash.
function m3uMediaHash($url) {
    $url = (string) $url;
    if (preg_match('/[?&](?:video|url)=([^&]+)/', $url, $m)) {
        $url = urldecode($m[1]);
    }
    return substr(md5($url), 0, 16);
}

function m3uLogEvent($type, array $data = []) {
    try {
        $path = m3uPath('events');
        if ($path === false) {
            return;
        }

        $event = [
            'ts' => round(microtime(true), 3),
            'type' => (string) $type,
            'ip' => m3uClientIp(),
            'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
        ] + $data;

        $line = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($line === false) {
            return;
        }

        // Rotated rather than truncated: the dashboard tracks its read position
        // per file inode, so the rotated file is still imported to the end.
        if (@filesize($path) > 50 * 1024 * 1024) {
            @rename($path, $path . '.1');
        }
        @file_put_contents($path, $line . "\n", FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {
        // Analytics must never be the reason a stream fails.
    }
}

// Logs one resolution outcome: the movie, the account (including its password,
// which the dashboard shows per the operator's request), the provider and
// debrid service, the exact release picked, and how long resolving took. Called
// at each redirect point in play.php. $cacheHit distinguishes an instant
// cache-served redirect from a fresh resolve.
function m3uLogResolution($movieId, $result, $cacheHit) {
    $account = function_exists('getXcAccount') ? getXcAccount() : [];
    $selection = $GLOBALS['m3u_selection'] ?? [];
    $start = $GLOBALS['m3u_resolve_start'] ?? null;

    m3uLogEvent('resolve', [
        'movieId' => (int) $movieId,
        'mediaType' => $GLOBALS['type'] ?? 'movie',
        'seriesCode' => $GLOBALS['seriesCode'] ?? '',
        'username' => $_GET['username'] ?? ($account['username'] ?? ''),
        'password' => $account['password'] ?? '',
        'lang' => $GLOBALS['requestedLang'] ?? '',
        'result' => (string) $result,
        'media' => m3uMediaHash($result),
        'cacheHit' => (bool) $cacheHit,
        'resolveMs' => (!$cacheHit && $start) ? round((microtime(true) - $start) * 1000) : null,
        'provider' => $selection['provider'] ?? '',
        'debrid' => $selection['debrid'] ?? '',
        'release' => $selection['filename'] ?? '',
        'releaseLangs' => $selection['audioLangs'] ?? '',
        'resolution' => $selection['resolution'] ?? null,
        'codec' => $selection['codec'] ?? '',
        'sourceUrl' => $selection['sourceUrl'] ?? '',
        'selCached' => $selection['cached'] ?? null,
        'throughputMbps' => $selection['throughputMbps'] ?? null,
    ]);
}

// ---------------------------------------------------------------------------
// Outbound URL safety
//
// Several scripts fetch a URL that arrives in the request (video=, url=,
// data=). Without a check, anyone can point them at the server's own private
// network - localhost services, a LAN admin panel, a cloud metadata endpoint -
// and have this server fetch it on their behalf (SSRF). Only http(s) to
// public addresses is allowed.
// ---------------------------------------------------------------------------

function m3uIsPublicIp($ip) {
    if (defined('FILTER_FLAG_GLOBAL_RANGE')) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function m3uResolveHost($host) {
    static $cache = [];
    if (isset($cache[$host])) {
        return $cache[$host];
    }
    $ips = @gethostbynamel($host) ?: [];
    $aaaa = @dns_get_record($host, DNS_AAAA);
    if (is_array($aaaa)) {
        foreach ($aaaa as $record) {
            if (!empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }
    }
    return $cache[$host] = array_values(array_unique($ips));
}

// True when $url is http(s) and every address its host resolves to is public.
// The server's own address is allowed, since relative media URLs are resolved
// against this site's own base URL.
function m3uIsSafeRemoteUrl($url, &$reason = null) {
    $parts = parse_url((string) $url);
    $scheme = strtolower($parts['scheme'] ?? '');
    if ($scheme !== 'http' && $scheme !== 'https') {
        $reason = 'only http and https URLs are allowed';
        return false;
    }

    $host = trim((string) ($parts['host'] ?? ''), '[]');
    if ($host === '') {
        $reason = 'URL has no host';
        return false;
    }

    $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : m3uResolveHost($host);
    if (empty($ips)) {
        $reason = 'host does not resolve';
        return false;
    }

    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
    foreach ($ips as $ip) {
        if (!m3uIsPublicIp($ip) && $ip !== $serverAddr) {
            $reason = 'host resolves to a non-public address';
            return false;
        }
    }
    return true;
}

// Stops the request with a 400 when $url isn't safe to fetch.
function m3uRequireSafeRemoteUrl($url) {
    if (!m3uIsSafeRemoteUrl($url, $reason)) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'Refused: ' . $reason . '.';
        exit;
    }
}

// Restricts a curl handle to http/https for the first request and every
// redirect, so a URL (or a redirect it returns) can't switch to file://,
// gopher://, dict:// and similar protocols.
function m3uRestrictCurlProtocols($ch) {
    if (defined('CURLOPT_PROTOCOLS_STR')) {
        curl_setopt($ch, CURLOPT_PROTOCOLS_STR, 'http,https');
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS_STR, 'http,https');
    } else {
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    }
}

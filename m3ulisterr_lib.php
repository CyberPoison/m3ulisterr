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
        // IP access control: manual/admin blocks (from the dashboard Sessions
        // menu) and the per-IP daily request counter (rate limiting). Both
        // share this durable store so they survive deploys.
        $pdo->exec('CREATE TABLE IF NOT EXISTS blocked_ips (
            ip TEXT PRIMARY KEY,
            reason TEXT,
            auto INTEGER DEFAULT 0,
            created INTEGER
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS ip_daily (
            ip TEXT,
            day TEXT,
            count INTEGER DEFAULT 0,
            PRIMARY KEY (ip, day)
        )');
        // Per-type daily counters (movie vs series/episode) so movies and TV
        // shows can be rate-limited independently. Keyed by media_type as well.
        $pdo->exec('CREATE TABLE IF NOT EXISTS ip_usage (
            ip TEXT,
            day TEXT,
            media_type TEXT,
            count INTEGER DEFAULT 0,
            PRIMARY KEY (ip, day, media_type)
        )');
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

// ---------------------------------------------------------------------------
// IP access control: manual blocking + per-IP daily rate limiting.
// All helpers are fail-open (return "not blocked" / 0) if the DB is missing,
// so a storage hiccup never locks every viewer out of the service.
// ---------------------------------------------------------------------------

// Returns [bool blocked, string reason] for a manual/admin block on this IP.
function m3uIpBlockStatus($ip) {
    if ($ip === '') {
        return [false, ''];
    }
    $db = m3uCacheDb();
    if (!$db) {
        return [false, ''];
    }
    try {
        $st = $db->prepare('SELECT reason FROM blocked_ips WHERE ip = ?');
        $st->execute([$ip]);
        $r = $st->fetch();
        if ($r) {
            return [true, (string) ($r['reason'] ?? '')];
        }
    } catch (Throwable $e) {
        // fail open
    }
    return [false, ''];
}

// Adds (or updates) a block on an IP. $auto=true marks an automatic rate-limit
// block; $auto=false is a manual admin block from the dashboard.
function m3uBlockIp($ip, $reason = '', $auto = false) {
    $ip = trim((string) $ip);
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    $db = m3uCacheDb();
    if (!$db) {
        return false;
    }
    try {
        $st = $db->prepare('INSERT INTO blocked_ips (ip, reason, auto, created)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(ip) DO UPDATE SET reason = excluded.reason, auto = excluded.auto');
        return $st->execute([$ip, (string) $reason, $auto ? 1 : 0, time()]);
    } catch (Throwable $e) {
        return false;
    }
}

// Removes a block from an IP.
function m3uUnblockIp($ip) {
    $db = m3uCacheDb();
    if (!$db) {
        return false;
    }
    try {
        return $db->prepare('DELETE FROM blocked_ips WHERE ip = ?')->execute([(string) $ip]);
    } catch (Throwable $e) {
        return false;
    }
}

// Lists all blocked IPs (newest first) for the dashboard.
function m3uListBlockedIps() {
    $db = m3uCacheDb();
    if (!$db) {
        return [];
    }
    try {
        return $db->query('SELECT ip, reason, auto, created FROM blocked_ips ORDER BY created DESC')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

// Increments today's (UTC) request counter for an IP and returns the new count.
function m3uIpDailyIncrement($ip) {
    if ($ip === '') {
        return 0;
    }
    $db = m3uCacheDb();
    if (!$db) {
        return 0;
    }
    $day = gmdate('Y-m-d');
    try {
        $db->prepare('INSERT INTO ip_daily (ip, day, count) VALUES (?, ?, 1)
            ON CONFLICT(ip, day) DO UPDATE SET count = count + 1')->execute([$ip, $day]);
        $st = $db->prepare('SELECT count FROM ip_daily WHERE ip = ? AND day = ?');
        $st->execute([$ip, $day]);
        $r = $st->fetch();
        return $r ? (int) $r['count'] : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

// Reads today's request count for an IP without incrementing it.
function m3uIpDailyCount($ip) {
    $db = m3uCacheDb();
    if (!$db || $ip === '') {
        return 0;
    }
    try {
        $st = $db->prepare('SELECT count FROM ip_daily WHERE ip = ? AND day = ?');
        $st->execute([$ip, gmdate('Y-m-d')]);
        $r = $st->fetch();
        return $r ? (int) $r['count'] : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

// Normalizes a request's media type to 'movie' or 'series' for counting.
function m3uNormalizeMediaType($type) {
    return ((string) $type === 'series') ? 'series' : 'movie';
}

// Increments today's (UTC) per-type counter for an IP and returns the new
// count for that media type ('movie' or 'series').
function m3uIpUsageIncrement($ip, $mediaType) {
    if ($ip === '') {
        return 0;
    }
    $db = m3uCacheDb();
    if (!$db) {
        return 0;
    }
    $mediaType = m3uNormalizeMediaType($mediaType);
    $day = gmdate('Y-m-d');
    try {
        $db->prepare('INSERT INTO ip_usage (ip, day, media_type, count) VALUES (?, ?, ?, 1)
            ON CONFLICT(ip, day, media_type) DO UPDATE SET count = count + 1')
           ->execute([$ip, $day, $mediaType]);
        $st = $db->prepare('SELECT count FROM ip_usage WHERE ip = ? AND day = ? AND media_type = ?');
        $st->execute([$ip, $day, $mediaType]);
        $r = $st->fetch();
        return $r ? (int) $r['count'] : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

// Today's per-type counts for an IP: ['movie' => n, 'series' => n, 'total' => n].
function m3uIpUsageToday($ip) {
    $out = ['movie' => 0, 'series' => 0, 'total' => 0];
    $db = m3uCacheDb();
    if (!$db || $ip === '') {
        return $out;
    }
    try {
        $st = $db->prepare('SELECT media_type, count FROM ip_usage WHERE ip = ? AND day = ?');
        $st->execute([$ip, gmdate('Y-m-d')]);
        foreach ($st->fetchAll() as $row) {
            $mt = m3uNormalizeMediaType($row['media_type']);
            $out[$mt] = (int) $row['count'];
        }
        $out['total'] = $out['movie'] + $out['series'];
    } catch (Throwable $e) {
        // fail open
    }
    return $out;
}

// Picks a raster format GD can actually encode on THIS host. Some PHP/GD
// builds are compiled without libjpeg (seen in production: gd's core drawing
// functions - imagecreatetruecolor, imagestring, etc. - all work fine, but
// imagejpeg() itself is undefined, which is a fatal error, not a warning, the
// first time anything tries to call it). PNG is preferred as the fallback
// because libpng support ships with GD in effectively every PHP build, and is
// actually the better lossless choice for this flat-color/text content anyway.
// Returns 'image/jpeg', 'image/png', or null if GD can encode neither (so a
// caller can fall back further, e.g. to plain text).
function m3uPickGdOutputMime() {
    if (function_exists('imagejpeg')) {
        return 'image/jpeg';
    }
    if (function_exists('imagepng')) {
        return 'image/png';
    }
    return null;
}

// Encodes a GD image with whatever format m3uPickGdOutputMime() picked, either
// to a file ($path given) or straight to stdout ($path null - caller must set
// the Content-Type header first). No-op if $mime is null.
function m3uEncodeGdImage($img, $mime, $path = null) {
    if ($mime === 'image/png') {
        imagepng($img, $path, 6);
    } elseif ($mime === 'image/jpeg') {
        imagejpeg($img, $path, 90);
    }
}

// Renders the "blocked" notice as a GD true-color image resource (caller owns
// it - imagedestroy() when done), or null if the GD extension isn't available.
// Shared by the direct image fallback (m3uServeBlockScreen) and the video
// pipeline (m3uServeBlockVideo), so both always show identical wording.
function m3uRenderBlockImage($message, $title = 'Access blocked') {
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    $message = trim((string) $message);
    if ($message === '') {
        $message = 'Your IP has been blocked due to too many movie / TV show requests.';
    }

    $w = 1280;
    $h = 720;
    $img = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($img, 17, 18, 22);      // near-black
    $accent = imagecolorallocate($img, 229, 57, 53); // red
    $fg = imagecolorallocate($img, 240, 240, 245);   // off-white
    $muted = imagecolorallocate($img, 150, 152, 160);
    imagefilledrectangle($img, 0, 0, $w, $h, $bg);
    // Top accent bar.
    imagefilledrectangle($img, 0, 0, $w, 8, $accent);

    // Title (built-in font 5, scaled up by drawing larger via imagestring
    // is limited; keep it readable and centered).
    $titleText = strtoupper($title);
    $tw = imagefontwidth(5) * strlen($titleText);
    imagestring($img, 5, (int) (($w - $tw) / 2), 250, $titleText, $accent);

    // Word-wrap the message across the middle of the screen.
    $font = 4;
    $charW = imagefontwidth($font);
    $maxChars = (int) (($w - 160) / $charW);
    $lines = [];
    foreach (explode("\n", wordwrap($message, $maxChars, "\n", true)) as $ln) {
        $lines[] = $ln;
    }
    $y = 310;
    foreach ($lines as $ln) {
        $lw = $charW * strlen($ln);
        imagestring($img, $font, (int) (($w - $lw) / 2), $y, $ln, $fg);
        $y += imagefontheight($font) + 8;
    }

    $footer = 'Contact the administrator if you believe this is a mistake.';
    $fw = imagefontwidth(2) * strlen($footer);
    imagestring($img, 2, (int) (($w - $fw) / 2), $h - 60, $footer, $muted);

    return $img;
}

// Serves a full-screen "blocked" notice to the client as a still IMAGE, then
// exits. Falls back to a plain-text screen when the GD image extension is
// unavailable. Used as the last-resort fallback when the video pipeline
// (m3uServeBlockVideo, below) can't produce a video - e.g. no ffmpeg on the
// host. Never returns.
function m3uServeBlockScreen($message, $title = 'Access blocked') {
    // Avoid any caching of the notice by players / CDNs.
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    $mime = m3uPickGdOutputMime();
    $img = ($mime !== null) ? m3uRenderBlockImage($message, $title) : null;
    if ($img !== null) {
        if (!headers_sent()) {
            header('Content-Type: ' . $mime);
        }
        m3uEncodeGdImage($img, $mime, null);
        imagedestroy($img);
        exit();
    }

    // No GD, or GD can't encode any raster format we know: plain-text fallback.
    $message = trim((string) $message);
    if ($message === '') {
        $message = 'Your IP has been blocked due to too many movie / TV show requests.';
    }
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo strtoupper($title) . "\n\n" . $message . "\n";
    exit();
}

// ---------------------------------------------------------------------------
// Block VIDEO: the actual notice most players/viewers see. A still JPEG is
// technically a valid response, but many IPTV/VOD apps flash it for a split
// second (or fail to render a bare image at all) since they expect an actual
// video stream at a play.php-style URL - not enough time for anyone to read
// why they were blocked. So the notice is instead delivered as a real
// ~10-minute H.264 MP4 (the wrapped GD notice image, held for the full
// duration, with a silent audio track for player compatibility), which any
// video player displays and holds on screen long enough to read.
//
// Generation is cached to disk (keyed by the exact wording, so admins editing
// the block reason automatically get a freshly rendered video) under the
// public, persistent videos/ directory - the same directory already used for
// the how-to video, and mounted as a durable Docker volume so cached notice
// videos survive redeploys. A file lock prevents duplicate concurrent ffmpeg
// runs when several requests hit a freshly-blocked IP at once. Falls back to
// the still-image screen (m3uServeBlockScreen) if ffmpeg/GD is unavailable or
// encoding fails/times out, so a broken host never leaves the viewer with
// nothing at all.
// ---------------------------------------------------------------------------

define('M3U_BLOCK_VIDEO_SECONDS', 600); // 10 minutes
define('M3U_BLOCK_VIDEO_VERSION', 'v1'); // bump to invalidate all cached videos

// Locates the ffmpeg binary. `command -v` alone can miss it under PHP-FPM,
// whose worker PATH is often a bare minimal default (no /opt/homebrew/bin,
// /usr/local/bin, etc.) even though the same binary works fine from a shell -
// so this also tries the common absolute install locations, the same
// resilience pattern as m3uFindPhpCli() in dashboard.php.
function m3uFindFfmpeg() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $c = trim((string) @shell_exec('command -v ffmpeg 2>/dev/null'));
    if ($c !== '' && @is_executable($c)) {
        return $cached = $c;
    }
    foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/homebrew/bin/ffmpeg'] as $p) {
        if (@is_executable($p)) {
            return $cached = $p;
        }
    }
    return $cached = '';
}

function m3uBlockVideoDir() {
    $dir = __DIR__ . '/videos/blocked';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return is_dir($dir) ? $dir : null;
}

// Removes the oldest cached notice videos once the cache grows past $keep
// distinct messages, so an admin who cycles through many custom block reasons
// over time doesn't accumulate mp4s forever. Cheap and rarely triggered.
function m3uPruneBlockVideos($dir, $keep = 50) {
    $files = glob($dir . '/*.mp4');
    if (!is_array($files) || count($files) <= $keep) {
        return;
    }
    usort($files, function ($a, $b) { return filemtime($a) <=> filemtime($b); });
    foreach (array_slice($files, 0, count($files) - $keep) as $old) {
        @unlink($old);
    }
}

// Runs ffmpeg with a hard wall-clock deadline (macOS/some minimal images lack
// the `timeout` binary, so this is done in PHP with proc_open). Returns true
// on a clean exit(0), false otherwise (including on timeout, where the process
// is force-killed so it never lingers).
function m3uRunWithDeadline(array $cmd, $deadlineSeconds) {
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($proc)) {
        return false;
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $deadline = microtime(true) + $deadlineSeconds;
    do {
        $status = proc_get_status($proc);
        if (!$status['running']) {
            break;
        }
        usleep(100000); // 100ms
    } while (microtime(true) < $deadline);

    $status = proc_get_status($proc);
    $ok = false;
    if ($status['running']) {
        // Timed out - SIGKILL so a stuck ffmpeg never lingers (proc_close
        // alone can block for minutes on a wedged child, as seen with the
        // parallel ffprobe duration checks elsewhere in this codebase).
        @proc_terminate($proc, 9);
        usleep(200000);
    } else {
        $ok = ($status['exitcode'] === 0);
    }
    foreach ($pipes as $p) {
        if (is_resource($p)) {
            @fclose($p);
        }
    }
    @proc_close($proc);
    return $ok;
}

// Serves the ~10-minute notice video for $message/$title, generating and
// caching it on first use, then exits (redirects to the cached static file so
// Apache - not PHP - handles Range/seek requests, the same pattern already
// used for the how-to video). Falls back to the still-image screen if a video
// can't be produced. Never returns.
function m3uServeBlockVideo($message, $title = 'Access blocked') {
    $message = trim((string) $message);
    if ($message === '') {
        $message = 'Your IP has been blocked due to too many movie / TV show requests.';
    }

    $dir = m3uBlockVideoDir();
    if ($dir === null) {
        m3uServeBlockScreen($message, $title); // exits
    }

    $hash = substr(sha1(M3U_BLOCK_VIDEO_VERSION . '|' . $title . '|' . $message), 0, 24);
    $path = $dir . '/' . $hash . '.mp4';

    if (is_file($path) && filesize($path) > 0) {
        m3uRedirectToBlockVideo($path); // exits
    }

    // Not cached yet - generate it. A lock prevents a stampede of concurrent
    // ffmpeg runs when several requests hit a freshly-blocked IP at once.
    $lockPath = $path . '.lock';
    $lockFp = @fopen($lockPath, 'c');
    if ($lockFp && flock($lockFp, LOCK_EX)) {
        // Another request may have finished generating it while we waited.
        if (is_file($path) && filesize($path) > 0) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            m3uRedirectToBlockVideo($path); // exits
        }

        $ffmpegPath = m3uFindFfmpeg();
        $imgMime = m3uPickGdOutputMime();
        $img = ($ffmpegPath !== '' && $imgMime !== null) ? m3uRenderBlockImage($message, $title) : null;

        if ($img !== null) {
            // ffmpeg reads either format fine as a looped image input.
            $imgExt = ($imgMime === 'image/png') ? 'png' : 'jpg';
            $imgPath = $dir . '/' . $hash . '.src.' . $imgExt;
            m3uEncodeGdImage($img, $imgMime, $imgPath);
            imagedestroy($img);

            $tmpPath = $path . '.tmp-' . getmypid() . '.mp4';
            $cmd = [
                $ffmpegPath, '-y',
                // The framerate MUST be set on the image INPUT (-framerate),
                // not as an output-side -r filter: the latter forces ffmpeg
                // through a frame-duplication/rate-conversion path that is
                // ~4x slower for a long loop of one still image (measured
                // ~19s vs ~6s for a 600s encode) for identical output.
                '-framerate', '5', '-loop', '1', '-i', $imgPath,
                '-f', 'lavfi', '-i', 'anullsrc=channel_layout=stereo:sample_rate=44100',
                '-t', (string) M3U_BLOCK_VIDEO_SECONDS,
                // No scale/pad filter needed - m3uRenderBlockImage() always
                // produces an exact 1280x720 source image.
                '-c:v', 'libx264', '-preset', 'veryfast', '-tune', 'stillimage', '-crf', '30',
                '-pix_fmt', 'yuv420p', '-g', '50',
                '-c:a', 'aac', '-b:a', '48k',
                '-movflags', '+faststart',
                $tmpPath,
            ];
            // Encoding a static image is cheap (a few seconds), but never let
            // a wedged ffmpeg hold the viewer's request open indefinitely -
            // fall back to the still image instead.
            $ok = m3uRunWithDeadline($cmd, 25);
            @unlink($imgPath);

            if ($ok && is_file($tmpPath) && filesize($tmpPath) > 0) {
                @rename($tmpPath, $path);
                m3uPruneBlockVideos($dir);
                flock($lockFp, LOCK_UN);
                fclose($lockFp);
                m3uRedirectToBlockVideo($path); // exits
            }
            @unlink($tmpPath);
        }

        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }

    // ffmpeg missing, GD missing, or encoding failed/timed out - never leave
    // the viewer with nothing.
    m3uServeBlockScreen($message, $title); // exits
}

// Redirects to a cached notice video so Apache serves the static file
// directly (native Range/seek support, no PHP in the streaming path). Never
// returns.
function m3uRedirectToBlockVideo($path) {
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
    $rel = 'videos/blocked/' . basename($path);
    $url = function_exists('locateBaseURL') ? (locateBaseURL() . $rel) : ('/' . $rel);
    header('Location: ' . $url, true, 302);
    exit();
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

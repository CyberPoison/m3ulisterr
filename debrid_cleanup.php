<?php
// Automatic debrid account cleanup.
//
// When a debrid service reports that the account is FULL (active download / store
// limit reached, too many active torrents, quota exceeded ...), every torrent and
// every unrestricted link on that service is deleted, so the next resolve starts
// from an empty account instead of failing with "Debrid limit reached" for hours.
// Requested behaviour: it deletes EVERYTHING on the account (all torrents, all
// downloads/links) - it is not limited to what this project added.
//
// Safety rails:
//  - Only runs when a service's OWN answer says it is full (limitReachedText()),
//    never on a plain "not cached" or an auth error.
//  - Cooldown: at most one cleanup per service every 30 minutes (a cleanup that
//    does not fix the problem must not loop).
//  - Runs in its own web request, fired by the server at itself with an HMAC token
//    (see debridCleanupFire()): the play request is never delayed, no shell is
//    used, and the public endpoint refuses anything without a valid token.
//  - Capped at 1500 deletions per run, throttled below every service's rate limit;
//    what is left is picked up by the next trigger.
//  - $debridAutoCleanOnLimit = false (config.php) switches it off.
//    $debridCleanupCooldownMinutes changes the cooldown. Both optional: this file
//    is self-contained on purpose (config.php is a bind-mounted host file).
//
// CLI:  php debrid_cleanup.php <realdebrid|premiumize|alldebrid|torbox> [--dry-run]

if (!defined('DEBRID_CLEANUP_MAX_DELETES')) {
    define('DEBRID_CLEANUP_MAX_DELETES', 1500);
}
if (!defined('DEBRID_CLEANUP_LOG')) {
    define('DEBRID_CLEANUP_LOG', sys_get_temp_dir() . '/debrid_cleanup.log');
}

// API base urls (overridable so the tests can point them at a mock).
function debridCleanupBase($service) {
    $override = getenv('DEBRID_CLEANUP_BASE_' . strtoupper($service));
    if ($override !== false && $override !== '') {
        return rtrim($override, '/');
    }
    return [
        'realdebrid' => 'https://api.real-debrid.com/rest/1.0',
        'premiumize' => 'https://www.premiumize.me/api',
        'alldebrid' => 'https://api.alldebrid.com/v4',
        'torbox' => 'https://api.torbox.app/v1/api',
    ][$service] ?? '';
}

function debridCleanupEnabled() {
    global $debridAutoCleanOnLimit;
    return !isset($debridAutoCleanOnLimit) || $debridAutoCleanOnLimit !== false;
}

function debridCleanupLog($line) {
    @file_put_contents(DEBRID_CLEANUP_LOG, date('c') . ' ' . $line . "\n", FILE_APPEND);
}

// Does a service answer (JSON/text/slate url) say "this account is full"?
// Deliberately narrower than debridResponseIsKeyExhausted() (which also matches
// auth failures and generic rate limits): only the full-account family.
function debridLimitReachedText($text) {
    if (!is_string($text) || $text === '') {
        return false;
    }
    $t = strtolower(urldecode(str_replace('+', ' ', $text)));
    return (bool) preg_match('/limit (has been )?(reached|exceeded)|(download|store|storage|torrent|transfer)s? limit|too[ _]many[ _]active|active[ _](download|torrent|transfer)s?[ _]limit|max(imum)?[ _]active|quota (exceeded|reached)|storage (is )?full|not enough (space|storage)|account is full|limit_reached|too_many_active_downloads/', $t);
}

// Which service does an API url belong to?
function debridServiceFromUrl($url) {
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (strpos($host, 'real-debrid') !== false) {
        return 'realdebrid';
    }
    if (strpos($host, 'premiumize') !== false) {
        return 'premiumize';
    }
    if (strpos($host, 'alldebrid') !== false) {
        return 'alldebrid';
    }
    if (strpos($host, 'torbox') !== false) {
        return 'torbox';
    }
    return '';
}

function debridCleanupKeys($service) {
    if (function_exists('debridKeysFor')) {
        return debridKeysFor($service);
    }
    global $PRIVATE_TOKEN, $premiumizeApiKey, $alldebridApiKey, $torboxApiKey;
    $single = ['realdebrid' => $PRIVATE_TOKEN ?? '', 'premiumize' => $premiumizeApiKey ?? '', 'alldebrid' => $alldebridApiKey ?? '', 'torbox' => $torboxApiKey ?? ''];
    return !empty($single[$service]) ? [trim($single[$service])] : [];
}

// Called from every place that sees a service's answer. Cheap when nothing matches.
function debridCleanupMaybeTrigger($service, $text, $where = '') {
    if ($service === '' || !debridLimitReachedText($text)) {
        return false;
    }
    return debridCleanupTrigger($service, ($where !== '' ? $where . ': ' : '') . substr((string) $text, 0, 160));
}

// Starts the cleanup of one service in a detached background process, at most
// once per cooldown. Returns true when a cleanup was started.
function debridCleanupTrigger($service, $reason = '') {
    global $debridCleanupCooldownMinutes;
    if (!debridCleanupEnabled() || !in_array($service, ['realdebrid', 'premiumize', 'alldebrid', 'torbox'], true)) {
        return false;
    }
    if (empty(debridCleanupKeys($service))) {
        return false;
    }
    $cooldown = (isset($debridCleanupCooldownMinutes) ? max(1, (int) $debridCleanupCooldownMinutes) : 30) * 60;
    $lock = sys_get_temp_dir() . '/debrid_cleanup_' . $service . '.lock';
    if (is_file($lock) && time() - (int) @filemtime($lock) < $cooldown) {
        return false;
    }
    @touch($lock);
    debridCleanupLog("TRIGGER $service - $reason");
    if (!empty($GLOBALS['DEBUG'])) {
        echo 'Debrid cleanup started in the background for ' . htmlspecialchars($service) . ' (' . htmlspecialchars(substr($reason, 0, 120)) . ')</br></br>';
    }
    debridCleanupFire($service);
    return true;
}

// --------------------------------------------------------- authenticated self-call
// The cleanup runs in its OWN web request, fired by the server at itself (no
// shell, no exec). The request is authenticated with an HMAC of the service and
// a 10-minute window, keyed with a random secret kept in a 0600 file next to the
// other temp files: nobody who can reach the public endpoint can start a
// deletion, a replayed token dies after 10 minutes, and a token is only good for
// the service it was issued for.
function debridCleanupSecret() {
    $file = sys_get_temp_dir() . '/debrid_cleanup.secret';
    $secret = @file_get_contents($file);
    if (is_string($secret) && strlen($secret) >= 32) {
        return trim($secret);
    }
    $secret = bin2hex(random_bytes(32));
    @file_put_contents($file, $secret, LOCK_EX);
    @chmod($file, 0600);
    return $secret;
}

function debridCleanupToken($service, $bucketOffset = 0) {
    return hash_hmac('sha256', $service . '|' . (intdiv(time(), 600) - $bucketOffset), debridCleanupSecret());
}

function debridCleanupTokenValid($service, $token) {
    if (!is_string($token) || $token === '') {
        return false;
    }
    return hash_equals(debridCleanupToken($service, 0), $token) || hash_equals(debridCleanupToken($service, 1), $token);
}

function debridCleanupSelfUrl() {
    $override = getenv('DEBRID_CLEANUP_SELF_URL');
    if ($override !== false && $override !== '') {
        return $override;
    }
    return rtrim(locateBaseURL(), '/') . '/debrid_cleanup.php';
}

// Fire-and-forget: the answer is not waited for (400 ms), the cleanup keeps
// running in the other request (ignore_user_abort there).
function debridCleanupFire($service) {
    $ch = curl_init(debridCleanupSelfUrl());
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['service' => $service, 'token' => debridCleanupToken($service)]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 400,
        CURLOPT_CONNECTTIMEOUT_MS => 400,
        CURLOPT_NOSIGNAL => 1,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// -------------------------------------------------------------------- HTTP
function debridCleanupHttp($method, $url, $headers = [], $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, is_string($body) ? $body : ''];
}

function debridCleanupJson($method, $url, $headers = [], $post = null) {
    [$code, $body] = debridCleanupHttp($method, $url, $headers, $post);
    $data = json_decode($body, true);
    return [$code, is_array($data) ? $data : null];
}

function debridCleanupPause() {
    usleep(getenv('DEBRID_CLEANUP_NO_PAUSE') ? 0 : 280000); // < 250 requests/minute (Real-Debrid's limit)
}

// -------------------------------------------------------------- per service
// Every cleaner returns ['listed' => n, 'deleted' => n, 'failed' => n] and never
// deletes more than $budget items. $dry = true lists but deletes nothing.

function debridCleanupRealDebrid($key, $budget, $dry) {
    $base = debridCleanupBase('realdebrid');
    $h = ['Authorization: Bearer ' . $key];
    $r = ['listed' => 0, 'deleted' => 0, 'failed' => 0];
    foreach (['torrents', 'downloads'] as $kind) {
        for ($page = 1; $page <= 30 && $r['deleted'] < $budget; $page++) {
            [$code, $items] = debridCleanupJson('GET', "$base/$kind?limit=100&page=$page", $h);
            if ($code !== 200 || empty($items)) {
                break;
            }
            $deletedOnPage = 0;
            foreach ($items as $it) {
                if (empty($it['id'])) {
                    continue;
                }
                $r['listed']++;
                if ($dry) {
                    continue;
                }
                if ($r['deleted'] >= $budget) {
                    break 2;
                }
                [$c] = debridCleanupHttp('DELETE', "$base/$kind/delete/" . rawurlencode($it['id']), $h);
                if ($c === 204 || $c === 200) {
                    $r['deleted']++;
                    $deletedOnPage++;
                } else {
                    $r['failed']++;
                }
                debridCleanupPause();
            }
            if ($dry) {
                continue;
            }
            if ($deletedOnPage === 0) {
                break; // nothing could be deleted on this page: stop, never loop
            }
            $page = 0; // deleted items shift the list: always re-read page 1
        }
    }
    return $r;
}

function debridCleanupPremiumize($key, $budget, $dry) {
    $base = debridCleanupBase('premiumize');
    $r = ['listed' => 0, 'deleted' => 0, 'failed' => 0];
    // 1. transfers (the torrents)
    [$code, $d] = debridCleanupJson('POST', "$base/transfer/list", [], http_build_query(['apikey' => $key]));
    foreach (($d['transfers'] ?? []) as $t) {
        if (empty($t['id'])) {
            continue;
        }
        $r['listed']++;
        if ($dry || $r['deleted'] >= $budget) {
            continue;
        }
        [$c, $x] = debridCleanupJson('POST', "$base/transfer/delete", [], http_build_query(['apikey' => $key, 'id' => $t['id']]));
        ($x['status'] ?? '') === 'success' ? $r['deleted']++ : $r['failed']++;
        debridCleanupPause();
    }
    // 2. the cloud files / folders those transfers (and direct downloads) created
    [$code, $d] = debridCleanupJson('POST', "$base/folder/list", [], http_build_query(['apikey' => $key]));
    foreach (($d['content'] ?? []) as $it) {
        if (empty($it['id'])) {
            continue;
        }
        $r['listed']++;
        if ($dry || $r['deleted'] >= $budget) {
            continue;
        }
        $endpoint = ($it['type'] ?? '') === 'folder' ? 'folder/delete' : 'item/delete';
        [$c, $x] = debridCleanupJson('POST', "$base/$endpoint", [], http_build_query(['apikey' => $key, 'id' => $it['id']]));
        ($x['status'] ?? '') === 'success' ? $r['deleted']++ : $r['failed']++;
        debridCleanupPause();
    }
    return $r;
}

function debridCleanupAllDebrid($key, $budget, $dry) {
    $base = debridCleanupBase('alldebrid');
    $q = 'agent=m3ulisterr&apikey=' . urlencode($key);
    $r = ['listed' => 0, 'deleted' => 0, 'failed' => 0];
    // magnets (the torrents)
    [$code, $d] = debridCleanupJson('GET', str_replace('/v4', '/v4.1', $base) . "/magnet/status?$q");
    $magnets = $d['data']['magnets'] ?? [];
    if (isset($magnets['id'])) {
        $magnets = [$magnets];
    }
    foreach ($magnets as $m) {
        if (empty($m['id'])) {
            continue;
        }
        $r['listed']++;
        if ($dry || $r['deleted'] >= $budget) {
            continue;
        }
        [$c, $x] = debridCleanupJson('GET', "$base/magnet/delete?$q&id=" . urlencode($m['id']));
        ($x['status'] ?? '') === 'success' ? $r['deleted']++ : $r['failed']++;
        debridCleanupPause();
    }
    // saved links
    [$code, $d] = debridCleanupJson('GET', "$base/user/links?$q");
    $links = array_column($d['data']['links'] ?? [], 'link');
    $r['listed'] += count($links);
    if (!$dry && $links) {
        $params = $q;
        foreach (array_slice($links, 0, $budget) as $l) {
            $params .= '&links[]=' . urlencode($l);
        }
        [$c, $x] = debridCleanupJson('GET', "$base/user/links/delete?$params");
        ($x['status'] ?? '') === 'success' ? $r['deleted'] += min(count($links), $budget) : $r['failed'] += count($links);
        debridCleanupPause();
    }
    // download history
    if (!$dry) {
        debridCleanupJson('GET', "$base/user/history/delete?$q");
    }
    return $r;
}

function debridCleanupTorBox($key, $budget, $dry) {
    $base = debridCleanupBase('torbox');
    $h = ['Authorization: Bearer ' . $key];
    $r = ['listed' => 0, 'deleted' => 0, 'failed' => 0];
    foreach ([['torrents', 'torrent_id'], ['webdl', 'webdl_id'], ['usenet', 'usenet_id']] as [$kind, $idField]) {
        [$code, $d] = debridCleanupJson('GET', "$base/$kind/mylist?bypass_cache=true", $h);
        foreach (($d['data'] ?? []) as $it) {
            if (empty($it['id'])) {
                continue;
            }
            $r['listed']++;
            if ($dry || $r['deleted'] >= $budget) {
                continue;
            }
            [$c, $x] = debridCleanupJson('POST', "$base/$kind/control" . ($kind === 'torrents' ? 'torrent' : ($kind === 'webdl' ? 'webdownload' : 'usenetdownload')),
                array_merge($h, ['Content-Type: application/json']), json_encode([$idField => $it['id'], 'operation' => 'delete', 'all' => false]));
            !empty($x['success']) ? $r['deleted']++ : $r['failed']++;
            debridCleanupPause();
        }
    }
    return $r;
}

// Runs the cleanup of one service for every configured key. Returns the summary.
function debridCleanupRun($service, $dry = false) {
    $fn = ['realdebrid' => 'debridCleanupRealDebrid', 'premiumize' => 'debridCleanupPremiumize', 'alldebrid' => 'debridCleanupAllDebrid', 'torbox' => 'debridCleanupTorBox'][$service] ?? null;
    $total = ['listed' => 0, 'deleted' => 0, 'failed' => 0];
    if ($fn === null) {
        return $total;
    }
    $budget = DEBRID_CLEANUP_MAX_DELETES;
    foreach (debridCleanupKeys($service) as $key) {
        $r = $fn($key, $budget, $dry);
        foreach ($r as $k => $v) {
            $total[$k] += $v;
        }
        $budget = max(0, $budget - $r['deleted']);
    }
    debridCleanupLog(($dry ? 'DRY-RUN ' : 'DONE ') . $service . ' listed=' . $total['listed'] . ' deleted=' . $total['deleted'] . ' failed=' . $total['failed']);
    return $total;
}

// ---------------------------------------------------------------------- CLI
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    chdir(__DIR__);
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/debrid_cleanup.php';
    $_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? 80;
    error_reporting(E_ALL & ~E_DEPRECATED);
    require_once __DIR__ . '/config.php';
    @require_once __DIR__ . '/debrid.php';
    $service = $argv[1] ?? '';
    $dry = in_array('--dry-run', $argv, true);
    $res = debridCleanupRun($service, $dry);
    echo json_encode(['service' => $service, 'dryRun' => $dry] + $res) . "\n";
}

// ------------------------------------------------------- web endpoint (token only)
// Only when this file is the requested script. POST + valid token for that exact
// service, otherwise 403 and nothing happens. One run at a time per service.
if (PHP_SAPI !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'debrid_cleanup.php') {
    header('Content-Type: application/json');
    error_reporting(0); // the answer is JSON only, never PHP notices
    $svc = (string) ($_POST['service'] ?? '');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
        || !in_array($svc, ['realdebrid', 'premiumize', 'alldebrid', 'torbox'], true)
        || !debridCleanupTokenValid($svc, (string) ($_POST['token'] ?? ''))) {
        http_response_code(403);
        debridCleanupLog('REFUSED web call (bad method, service or token) from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        echo '{"error":"forbidden"}';
        exit;
    }
    ignore_user_abort(true);
    set_time_limit(1500); // 1500 deletions at ~0.3s each
    $running = sys_get_temp_dir() . '/debrid_cleanup_' . $svc . '.running';
    if (is_file($running) && time() - (int) @filemtime($running) < 1800) {
        echo '{"status":"already running"}';
        exit;
    }
    @touch($running);
    require_once __DIR__ . '/config.php';
    @require_once __DIR__ . '/debrid.php';
    echo json_encode(['service' => $svc] + debridCleanupRun($svc, false));
    @unlink($running);
}

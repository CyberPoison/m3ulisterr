<?php
// ============================================================================
//  debrid.php — unified multi-key debrid layer
// ----------------------------------------------------------------------------
//  Adds AllDebrid + TorBox alongside the pre-existing Real-Debrid / Premiumize
//  code in play.php, and lets every service hold MORE THAN ONE API key so the
//  resolver can fall back to the next key when one hits its quota / fair-use /
//  hoster limit.
//
//  Key list resolution (debridKeysFor):
//   - starts with the legacy single-value settings from config.php
//       realdebrid -> $PRIVATE_TOKEN
//       premiumize -> $premiumizeApiKey
//       alldebrid  -> $alldebridApiKey
//       torbox     -> $torboxApiKey
//   - then appends every entry from $debridApiKeys[<service>] (deduped).
//  So a user can keep using the single settings, the array, or both.
//
//  IMPORTANT ARCHITECTURE NOTE: these direct-debrid clients are only used on
//  the torrentSites path (magnet/hash -> direct link). The Unlimited /
//  UnlimitedFR / TVShows accounts resolve through AIOStreams, which performs
//  its OWN debriding server-side with the keys configured in the AIOStreams
//  dashboard — that path does NOT call anything in this file.
// ============================================================================

if (!defined('DEBRID_PHP_LOADED')) {
    define('DEBRID_PHP_LOADED', 1);

/**
 * Returns the ordered, de-duplicated list of API keys for a debrid service.
 * Merges the legacy single-key config value (first) with the $debridApiKeys
 * array. Empty/blank entries are dropped.
 *
 * @param string $service one of: realdebrid, premiumize, alldebrid, torbox
 * @return string[]
 */
function debridKeysFor($service)
{
    global $PRIVATE_TOKEN, $premiumizeApiKey, $alldebridApiKey, $torboxApiKey, $debridApiKeys;

    $service = strtolower(trim($service));
    $keys = [];

    // Legacy single-value settings first (so existing setups keep working).
    $singleMap = [
        'realdebrid' => isset($PRIVATE_TOKEN) ? $PRIVATE_TOKEN : '',
        'premiumize' => isset($premiumizeApiKey) ? $premiumizeApiKey : '',
        'alldebrid'  => isset($alldebridApiKey) ? $alldebridApiKey : '',
        'torbox'     => isset($torboxApiKey) ? $torboxApiKey : '',
    ];
    if (!empty($singleMap[$service])) {
        $keys[] = trim($singleMap[$service]);
    }

    // Then the multi-key array.
    if (isset($debridApiKeys[$service]) && is_array($debridApiKeys[$service])) {
        foreach ($debridApiKeys[$service] as $k) {
            $k = trim((string) $k);
            if ($k !== '') {
                $keys[] = $k;
            }
        }
    }

    // De-dupe, keep order.
    return array_values(array_unique($keys));
}

/**
 * True when a raw debrid API response body looks like a quota / fair-use /
 * rate-limit / auth failure — i.e. a reason to rotate to the next key rather
 * than treat the torrent as simply "not cached".
 */
function debridResponseIsKeyExhausted($service, $rawBody)
{
    if ($rawBody === false || $rawBody === null || $rawBody === '') {
        return false;
    }
    $b = strtolower((string) $rawBody);

    // Generic markers seen across providers.
    $markers = [
        'fair use', 'fair-use', 'quota', 'limit reached', 'too many requests',
        'rate limit', 'rate-limit', 'daily limit', 'you have reached',
        'auth_bad_apikey', 'auth_missing_apikey', 'auth_blocked',
        'must be premium', 'not premium', 'no_server', 'link_host_unavailable',
        'bad_token', 'permission_denied', 'account is locked',
    ];
    foreach ($markers as $m) {
        if (strpos($b, $m) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Small curl helper returning [httpCode, body]. Never throws.
 */
function debridHttp($url, $method = 'GET', $postFields = null, $headers = [], $timeout = 30)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postFields !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

// ---------------------------------------------------------------------------
//  AllDebrid  (API v4 — https://docs.alldebrid.com/)
// ---------------------------------------------------------------------------

/**
 * Resolve one info-hash to a direct link via a single AllDebrid key.
 *
 * @return array [bool ready, string|false link, bool keyExhausted]
 */
function allDebridResolveHashOnce($hash, $apiKey, $seriesCode = '', $type = 'movie')
{
    $agent = 'tmdbvod';
    $base = 'https://api.alldebrid.com/v4';

    // 1) Upload the magnet (idempotent — if already known it is returned).
    $magnet = 'magnet:?xt=urn:btih:' . strtolower($hash);
    list($code, $body) = debridHttp(
        $base . '/magnet/upload?agent=' . $agent . '&apikey=' . urlencode($apiKey),
        'POST',
        http_build_query(['magnets' => [$magnet]])
    );
    if (debridResponseIsKeyExhausted('alldebrid', $body)) {
        return [false, false, true];
    }
    $j = json_decode($body, true);
    if (!isset($j['status']) || $j['status'] !== 'success') {
        return [false, false, false];
    }
    $mag = isset($j['data']['magnets'][0]) ? $j['data']['magnets'][0] : null;
    if (!$mag || !isset($mag['id'])) {
        return [false, false, false];
    }
    $magnetId = $mag['id'];

    // 2) Poll status briefly (cached torrents flip to Ready almost instantly;
    //    we do NOT wait for a cold download here — that would block playback).
    $links = [];
    $attempts = 0;
    do {
        list($sc, $sb) = debridHttp(
            $base . '/magnet/status?agent=' . $agent . '&apikey=' . urlencode($apiKey) . '&id=' . urlencode($magnetId)
        );
        if (debridResponseIsKeyExhausted('alldebrid', $sb)) {
            return [false, false, true];
        }
        $sj = json_decode($sb, true);
        $m = isset($sj['data']['magnets']) ? $sj['data']['magnets'] : null;
        // status endpoint may return a single object or a list depending on id
        if (isset($m['status'])) {
            $statusText = strtolower($m['status']);
            $links = isset($m['links']) ? $m['links'] : [];
        } elseif (is_array($m) && isset($m[0])) {
            $statusText = strtolower($m[0]['status']);
            $links = isset($m[0]['links']) ? $m[0]['links'] : [];
        } else {
            $statusText = '';
        }
        if ($statusText === 'ready') {
            break;
        }
        // Not cached / still downloading — give up quickly so we can try the
        // next candidate torrent rather than stalling the viewer.
        if ($attempts >= 1) {
            return [false, false, false];
        }
        usleep(400000);
        $attempts++;
    } while ($attempts <= 2);

    if (empty($links)) {
        return [true, false, false];
    }

    // 3) Pick the right file (episode match for series) and unlock it.
    foreach ($links as $lnk) {
        $filename = isset($lnk['filename']) ? $lnk['filename'] : '';
        $rawLink = isset($lnk['link']) ? $lnk['link'] : '';
        if ($rawLink === '') {
            continue;
        }
        if ($type === 'series' && $seriesCode !== '') {
            $stripped = preg_replace('/[^a-zA-Z0-9]/', '', $filename);
            if (stripos(strtolower($stripped), strtolower($seriesCode)) === false) {
                continue;
            }
        }
        // Unlock to a streamable direct URL.
        list($uc, $ub) = debridHttp(
            $base . '/link/unlock?agent=' . $agent . '&apikey=' . urlencode($apiKey) . '&link=' . urlencode($rawLink)
        );
        if (debridResponseIsKeyExhausted('alldebrid', $ub)) {
            return [false, false, true];
        }
        $uj = json_decode($ub, true);
        if (isset($uj['data']['link']) && !empty($uj['data']['link'])) {
            return [true, $uj['data']['link'], false];
        }
    }

    return [true, false, false];
}

// ---------------------------------------------------------------------------
//  TorBox  (API v1 — https://api.torbox.app/)
// ---------------------------------------------------------------------------

/**
 * Resolve one info-hash to a direct link via a single TorBox key.
 *
 * @return array [bool ready, string|false link, bool keyExhausted]
 */
function torBoxResolveHashOnce($hash, $apiKey, $seriesCode = '', $type = 'movie')
{
    $base = 'https://api.torbox.app/v1/api';
    $auth = ['Authorization: Bearer ' . $apiKey];

    // 1) Cached check first — cheap, and tells us the file list.
    list($cc, $cb) = debridHttp(
        $base . '/torrents/checkcached?hash=' . urlencode(strtolower($hash)) . '&format=object&list_files=true',
        'GET', null, $auth
    );
    if (debridResponseIsKeyExhausted('torbox', $cb)) {
        return [false, false, true];
    }
    $cj = json_decode($cb, true);
    $isCached = isset($cj['data']) && !empty($cj['data']);
    if (!$isCached) {
        // Not instantly available — skip (don't trigger a cold download).
        return [false, false, false];
    }

    // 2) Create/attach the torrent to the account so we can request a link.
    $magnet = 'magnet:?xt=urn:btih:' . strtolower($hash);
    list($ac, $ab) = debridHttp(
        $base . '/torrents/createtorrent',
        'POST',
        http_build_query(['magnet' => $magnet, 'allow_zip' => 'false']),
        $auth
    );
    if (debridResponseIsKeyExhausted('torbox', $ab)) {
        return [false, false, true];
    }
    $aj = json_decode($ab, true);
    $torrentId = null;
    if (isset($aj['data']['torrent_id'])) {
        $torrentId = $aj['data']['torrent_id'];
    } elseif (isset($aj['data']['id'])) {
        $torrentId = $aj['data']['id'];
    }
    if ($torrentId === null) {
        return [true, false, false];
    }

    // 3) Read the file list for this torrent, choose the target file.
    list($lc, $lb) = debridHttp(
        $base . '/torrents/mylist?id=' . urlencode($torrentId) . '&bypass_cache=true',
        'GET', null, $auth
    );
    $lj = json_decode($lb, true);
    $files = [];
    if (isset($lj['data']['files']) && is_array($lj['data']['files'])) {
        $files = $lj['data']['files'];
    } elseif (isset($lj['data'][0]['files'])) {
        $files = $lj['data'][0]['files'];
    }

    $videoExt = ['mp4', 'mkv', 'avi', 'mov', 'flv', 'wmv', 'mpg', 'mpeg', 'm4v'];
    $chosenFileId = null;
    foreach ($files as $f) {
        $name = isset($f['name']) ? $f['name'] : (isset($f['short_name']) ? $f['short_name'] : '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $videoExt)) {
            continue;
        }
        if ($type === 'series' && $seriesCode !== '') {
            $stripped = preg_replace('/[^a-zA-Z0-9]/', '', $name);
            if (stripos(strtolower($stripped), strtolower($seriesCode)) === false) {
                continue;
            }
        }
        $chosenFileId = isset($f['id']) ? $f['id'] : null;
        if ($chosenFileId !== null) {
            break;
        }
    }

    // 4) Request the direct download link.
    $dlUrl = $base . '/torrents/requestdl?token=' . urlencode($apiKey) . '&torrent_id=' . urlencode($torrentId);
    if ($chosenFileId !== null) {
        $dlUrl .= '&file_id=' . urlencode($chosenFileId);
    }
    list($dc, $db) = debridHttp($dlUrl, 'GET', null, $auth);
    if (debridResponseIsKeyExhausted('torbox', $db)) {
        return [false, false, true];
    }
    $dj = json_decode($db, true);
    if (isset($dj['data']) && is_string($dj['data']) && $dj['data'] !== '') {
        return [true, $dj['data'], false];
    }
    if (isset($dj['data']['url']) && !empty($dj['data']['url'])) {
        return [true, $dj['data']['url'], false];
    }

    return [true, false, false];
}

// ---------------------------------------------------------------------------
//  Multi-key wrappers — iterate keys, rotate on quota/auth exhaustion.
// ---------------------------------------------------------------------------

/**
 * @return string|false direct link, or false if no key produced one.
 */
function allDebridResolveHash($hash, $seriesCode = '', $type = 'movie')
{
    foreach (debridKeysFor('alldebrid') as $key) {
        list($ready, $link, $exhausted) = allDebridResolveHashOnce($hash, $key, $seriesCode, $type);
        if ($link) {
            return $link;
        }
        if ($exhausted) {
            continue; // try next key
        }
        if ($ready) {
            // Reachable, just no usable file for this torrent — next torrent.
            return false;
        }
    }
    return false;
}

/**
 * @return string|false direct link, or false if no key produced one.
 */
function torBoxResolveHash($hash, $seriesCode = '', $type = 'movie')
{
    foreach (debridKeysFor('torbox') as $key) {
        list($ready, $link, $exhausted) = torBoxResolveHashOnce($hash, $key, $seriesCode, $type);
        if ($link) {
            return $link;
        }
        if ($exhausted) {
            continue;
        }
        if ($ready) {
            return false;
        }
    }
    return false;
}

} // DEBRID_PHP_LOADED

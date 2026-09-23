<?php
// "Does this movie have at least one usable, already-cached candidate?" - the
// cheap yes/no behind player_api.php?action=get_vod_availability, used by
// Decypharr to leave titles that could never play out of its WebDAV/mount.
//
// Deliberately self-contained and read-only: it asks AIOStreams for the same
// candidate list play.php's aioStreamsFindAudioLanguage() would, applies the
// same language/year/zero-weight rules, but never probes, resolves or
// proxies anything - so it costs one AIOStreams round-trip (cached for
// hours), not a full resolve. Lives in its own file, NOT config.php: on a
// real deploy config.php is a bind-mounted host file the image never
// overwrites, so any function added there silently doesn't exist in
// production.

// Quality ladder, best first. Only these combinations count as "available":
// a cached release outside them (SD, AV1, unknown codec) is reported as
// other_cached but does not make a title available.
const AIO_AVAILABILITY_LADDER = [
    '1080p_x264' => [1080, 'AVC'],
    '720p_x264'  => [720, 'AVC'],
    '4k_x264'    => [2160, 'AVC'],
    '4k_x265'    => [2160, 'HEVC'],
    '1080p_x265' => [1080, 'HEVC'],
    '720p_x265'  => [720, 'HEVC'],
];

const AIO_AVAILABILITY_TTL = 6 * 3600;

function aioAvailabilityCacheDir() {
    static $dir = null;
    if ($dir !== null) {
        return $dir;
    }
    $candidates = [];
    $env = getenv('M3U_DATA_DIR');
    if (is_string($env) && $env !== '') {
        $candidates[] = rtrim($env, '/') . '/availability';
    }
    $candidates[] = sys_get_temp_dir() . '/aio_availability';
    foreach ($candidates as $candidate) {
        if (is_dir($candidate) || @mkdir($candidate, 0700, true)) {
            if (is_writable($candidate)) {
                return $dir = $candidate;
            }
        }
    }
    return $dir = false;
}

// Maps one AIOStreams stream to what availability cares about, or null when it
// can't be a candidate for $languageName (no direct url, language absent).
function aioAvailabilityClassify(array $stream, $languageName) {
    if (empty($stream['url'])) {
        return null;
    }
    $description = $stream['description'] ?? '';
    if (!preg_match('/🌎([^\n📝]*)/u', $description, $langMatch)) {
        return null;
    }
    if (stripos(trim($langMatch[1]), $languageName) === false) {
        return null;
    }

    $name = $stream['name'] ?? '';
    preg_match('/(2160|1080|720|480|360)p/i', $name, $resMatch);
    preg_match('/\x{1F39E}\x{FE0F}?\s*([A-Za-z0-9]+)/u', $description, $codecMatch);
    $debrid = preg_match('/\[([A-Za-z]{2,4})\s*[\x{26A1}\x{23F3}]/u', $name, $svcMatch) ? strtoupper($svcMatch[1]) : '';

    return [
        'resolution' => isset($resMatch[1]) ? intval($resMatch[1]) : 0,
        'codec' => isset($codecMatch[1]) ? strtoupper($codecMatch[1]) : '',
        'cached' => strpos($name, "\u{26A1}") !== false,
        'debrid' => $debrid,
        'filename' => $stream['behaviorHints']['filename'] ?? '',
    ];
}

function aioAvailabilityLadderKey($resolution, $codec) {
    foreach (AIO_AVAILABILITY_LADDER as $key => [$res, $cod]) {
        if ($resolution === $res && $codec === $cod) {
            return $key;
        }
    }
    return null;
}

function aioAvailabilityDebridService($debrid) {
    $map = ['AD' => 'alldebrid', 'PM' => 'premiumize', 'RD' => 'realdebrid', 'TB' => 'torbox'];
    return $map[$debrid] ?? strtolower($debrid);
}

// Pure function over an already-fetched AIOStreams response body - the part
// worth unit testing. $releaseYear '' = unknown (year check skipped);
// $zeroWeightServices are services explicitly set to weight 0 in
// $aioDebridWeights, which play.php also removes from consideration.
function aioAvailabilityFromStreams(array $data, $languageName, $releaseYear = '', array $zeroWeightServices = []) {
    $counts = array_fill_keys(array_keys(AIO_AVAILABILITY_LADDER), 0);
    $otherCached = 0;
    $total = 0;

    foreach ($data['streams'] ?? [] as $stream) {
        $c = aioAvailabilityClassify($stream, $languageName);
        if ($c === null) {
            continue;
        }
        // Same saga/pack guard as play.php: only an EXPLICIT different year
        // rejects; a filename with no year is unknown, not a mismatch.
        if ($releaseYear !== '' && preg_match('/(19|20)\d{2}/', $c['filename'], $ym) && $ym[0] !== $releaseYear) {
            continue;
        }
        if (in_array(aioAvailabilityDebridService($c['debrid']), $zeroWeightServices, true)) {
            continue;
        }
        $total++;
        if (!$c['cached']) {
            continue;
        }
        $key = aioAvailabilityLadderKey($c['resolution'], $c['codec']);
        if ($key === null) {
            $otherCached++;
        } else {
            $counts[$key]++;
        }
    }

    $best = null;
    $cachedInLadder = 0;
    foreach ($counts as $key => $n) {
        $cachedInLadder += $n;
        if ($best === null && $n > 0) {
            $best = $key;
        }
    }

    return [
        'available' => $cachedInLadder > 0,
        'best' => $best,
        'counts' => $counts,
        'cached_candidates' => $cachedInLadder,
        'other_cached' => $otherCached,
        'language_matches' => $total,
    ];
}

function aioAvailabilityFetchList($url) {
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $status === 200) {
            return $body;
        }
    }
    return false;
}

function aioAvailabilityReleaseYear($tmdbId) {
    global $apiKey;
    if (empty($apiKey) || !function_exists('makeGetRequest')) {
        return '';
    }
    $body = makeGetRequest("https://api.themoviedb.org/3/movie/{$tmdbId}?api_key={$apiKey}");
    $details = $body ? json_decode($body, true) : null;
    if (is_array($details) && !empty($details['release_date'])) {
        return substr($details['release_date'], 0, 4);
    }
    return '';
}

// Full lookup: cached result if fresh, otherwise AIOStreams. Returns
// [httpStatus, array]. A failure to reach AIOStreams is 502 with
// available=null and is never cached, so the caller treats it as "unknown"
// rather than "nothing available".
function aioAvailabilityLookup($tmdbId, $languageName, $refresh = false) {
    global $frenchAioStreamsUrl, $aioDebridWeights;

    $tmdbId = intval($tmdbId);
    $base = ['tmdb_id' => $tmdbId, 'language' => $languageName];

    if (empty($frenchAioStreamsUrl)) {
        return [200, $base + ['available' => null, 'error' => 'aiostreams_not_configured']];
    }

    $dir = aioAvailabilityCacheDir();
    $cachePath = $dir !== false ? $dir . '/' . $tmdbId . '_' . strtolower($languageName) . '.json' : false;
    if (!$refresh && $cachePath !== false && is_file($cachePath) && (time() - filemtime($cachePath)) < AIO_AVAILABILITY_TTL) {
        $hit = json_decode((string) @file_get_contents($cachePath), true);
        if (is_array($hit)) {
            return [200, $hit + ['from_cache' => true]];
        }
    }

    $response = aioAvailabilityFetchList(rtrim($frenchAioStreamsUrl, '/') . '/stream/movie/tmdb:' . $tmdbId . '.json');
    if ($response === false) {
        return [502, $base + ['available' => null, 'error' => 'aiostreams_unreachable']];
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [502, $base + ['available' => null, 'error' => 'aiostreams_bad_response']];
    }

    $weights = is_array($aioDebridWeights ?? null) ? $aioDebridWeights : [];
    $zero = array_keys(array_filter($weights, function ($w) {
        return (float) $w === 0.0;
    }));

    $result = $base + aioAvailabilityFromStreams($data, $languageName, aioAvailabilityReleaseYear($tmdbId), $zero);
    $result['total_streams'] = count($data['streams'] ?? []);
    $result['checked_at'] = time();

    if ($cachePath !== false) {
        $tmp = $cachePath . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, json_encode($result)) !== false) {
            @rename($tmp, $cachePath);
        }
    }
    return [200, $result + ['from_cache' => false]];
}

<?php
// "Does this title have at least one usable, already-cached candidate?" - the
// yes/no behind player_api.php?action=get_vod_availability and
// get_availability_batch, used by Decypharr to leave titles that could never
// play out of its WebDAV/mount.
//
// Read-only and self-contained: it asks AIOStreams for the same candidate
// list play.php's aioStreamsFindAudioLanguage() would and applies the same
// language/year/zero-weight rules, but never probes, resolves or proxies
// anything. Lives in its own file, NOT config.php: on a real deploy
// config.php is a bind-mounted host file the image never overwrites, so any
// function added there silently doesn't exist in production. Every tunable
// below is an OPTIONAL config.php variable read with a default for exactly
// that reason.
//
// Safety properties this file exists to guarantee (all learned the hard way -
// the public ElfHosted AIOStreams allows 100 searches then 1 per MINUTE per
// client IP, and when exceeded it answers HTTP 200 with one fake "rate-limit
// exceeded" stream instead of an error):
//  - a throttled or partially failed AIOStreams answer is "unknown", never
//    "nothing available" (which would hide real titles);
//  - unknown/throttled answers are never cached;
//  - a token bucket caps upstream calls so a sweep cannot drain the rate
//    limit that real playback (play.php, same server IP) needs;
//  - after a throttle response all upstream calls stop for a cooldown.

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
const AIO_AVAILABILITY_MAX_BATCH = 100;
const AIO_AVAILABILITY_COOLDOWN = 300;

// Series have no single "the" episode to ask about: a show counts as available
// when any of these probes has a cached candidate (S01E01, then S02E01 for
// shows whose first season isn't cached but later ones are).
const AIO_AVAILABILITY_SERIES_PROBES = [[1, 1], [2, 1]];

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

// Optional config.php variables (all have defaults, so a stale host config.php
// keeps working):
//   $availabilityAioStreamsUrl  a DEDICATED AIOStreams (private/self-hosted)
//                               used only for these checks. When unset the
//                               playback instance is used and the limits below
//                               default to very conservative values.
//   $availabilityMaxPerMinute   sustained upstream lookups per minute
//   $availabilityBurst          bucket size (lookups allowed in a burst)
//   $availabilityParallel       simultaneous upstream lookups per request
function aioAvailabilityConfig() {
    global $frenchAioStreamsUrl, $availabilityAioStreamsUrl, $availabilityMaxPerMinute, $availabilityBurst, $availabilityParallel;
    $dedicated = !empty($availabilityAioStreamsUrl);
    $url = $dedicated ? $availabilityAioStreamsUrl : ($frenchAioStreamsUrl ?? '');
    return [
        'url' => rtrim((string) $url, '/'),
        'dedicated' => $dedicated,
        'perMinute' => (float) ($availabilityMaxPerMinute ?? ($dedicated ? 3000 : 0.5)),
        'burst' => (float) ($availabilityBurst ?? ($dedicated ? 300 : 5)),
        'parallel' => max(1, (int) ($availabilityParallel ?? ($dedicated ? 64 : 4))),
    ];
}

// ---------------------------------------------------------------- parsing --

// One AIOStreams stream -> language-independent facts, or null when it can't
// be a candidate at all (no direct url, no audio-language line).
function aioAvailabilityParseStream(array $stream) {
    if (empty($stream['url'])) {
        return null;
    }
    $description = $stream['description'] ?? '';
    if (!preg_match('/🌎([^\n📝]*)/u', $description, $langMatch)) {
        return null;
    }
    $name = $stream['name'] ?? '';
    preg_match('/(2160|1080|720|480|360)p/i', $name, $resMatch);
    preg_match('/\x{1F39E}\x{FE0F}?\s*([A-Za-z0-9]+)/u', $description, $codecMatch);
    return [
        'langs' => trim($langMatch[1]),
        'resolution' => isset($resMatch[1]) ? intval($resMatch[1]) : 0,
        'codec' => isset($codecMatch[1]) ? strtoupper($codecMatch[1]) : '',
        'cached' => strpos($name, "\u{26A1}") !== false,
        'debrid' => preg_match('/\[([A-Za-z]{2,4})\s*[\x{26A1}\x{23F3}]/u', $name, $svc) ? strtoupper($svc[1]) : '',
        'filename' => $stream['behaviorHints']['filename'] ?? '',
    ];
}

// The public instance's throttle answer is a normal-looking 200 with one fake
// stream ("[🐢] AIOStreams", "...rate-limit exceeded", url .../rate-limit-
// exceeded.mp4).
function aioAvailabilityStreamIsThrottle(array $stream) {
    return strpos($stream['name'] ?? '', "\u{1F422}") !== false
        || stripos($stream['title'] ?? '', 'rate-limit') !== false
        || stripos($stream['url'] ?? '', 'rate-limit-exceeded') !== false;
}

// Language-independent, compact digest of one AIOStreams response. This is
// what gets cached, so every account/language derives its answer from ONE
// upstream lookup per title.
//   throttled  the response was the rate-limit stub
//   degraded   an addon reported a real failure (timeout, 5xx...) - the list
//              may be incomplete. "Failed to get metadata" is NOT degradation:
//              that is what a title an addon simply doesn't know looks like.
function aioAvailabilitySummarize(array $data) {
    $throttled = false;
    $degraded = false;
    $candidates = [];
    foreach ($data['streams'] ?? [] as $stream) {
        if (!is_array($stream)) {
            continue;
        }
        if (aioAvailabilityStreamIsThrottle($stream)) {
            $throttled = true;
            continue;
        }
        if (($stream['streamData']['type'] ?? '') === 'error') {
            $desc = $stream['streamData']['error']['description'] ?? ($stream['description'] ?? '');
            if (stripos($desc, 'metadata') === false) {
                $degraded = true;
            }
            continue;
        }
        $c = aioAvailabilityParseStream($stream);
        if ($c !== null) {
            $candidates[] = $c;
        }
    }
    return [
        'throttled' => $throttled,
        'degraded' => $degraded,
        'total_streams' => count($data['streams'] ?? []),
        'candidates' => $candidates,
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

// Verdict for one language from a summary. $releaseYear '' = unknown (year
// check skipped); $zeroWeightServices are services explicitly set to weight 0
// in $aioDebridWeights, which play.php also removes from consideration.
// 'state' is definitive | rate_limited | degraded: only "definitive" may be
// trusted as a true/false answer (see aioAvailabilityFinalAvailable()).
function aioAvailabilityVerdict(array $summary, $languageName, $releaseYear = '', array $zeroWeightServices = []) {
    $counts = array_fill_keys(array_keys(AIO_AVAILABILITY_LADDER), 0);
    $otherCached = 0;
    $matches = 0;

    foreach ($summary['candidates'] as $c) {
        if (stripos($c['langs'], $languageName) === false) {
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
        $matches++;
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

    $available = $cachedInLadder > 0;
    // A positive answer stands even if some addon failed. A negative one from
    // a throttled or partially failed lookup is not evidence of anything.
    $state = 'definitive';
    if (!$available) {
        if (!empty($summary['throttled'])) {
            $state = 'rate_limited';
        } elseif (!empty($summary['degraded'])) {
            $state = 'degraded';
        }
    }

    return [
        'available' => $available,
        'best' => $best,
        'counts' => $counts,
        'cached_candidates' => $cachedInLadder,
        'other_cached' => $otherCached,
        'language_matches' => $matches,
        'state' => $state,
    ];
}

// Raw response -> verdict in one step (the unit-tested seam).
function aioAvailabilityFromStreams(array $data, $languageName, $releaseYear = '', array $zeroWeightServices = []) {
    return aioAvailabilityVerdict(aioAvailabilitySummarize($data), $languageName, $releaseYear, $zeroWeightServices);
}

// true/false only for a definitive verdict, else null ("unknown").
function aioAvailabilityFinalAvailable(array $verdict) {
    return $verdict['state'] === 'definitive' ? $verdict['available'] : null;
}

// ------------------------------------------------- cache, budget, cooldown --

function aioAvailabilitySummaryPath($kind, $id, $probe) {
    $dir = aioAvailabilityCacheDir();
    if ($dir === false) {
        return false;
    }
    $shard = $dir . '/s' . ($id % 100);
    if (!is_dir($shard) && !@mkdir($shard, 0700, true) && !is_dir($shard)) {
        return false;
    }
    return $shard . '/' . $kind . '_' . $id . '_' . $probe . '.json';
}

function aioAvailabilityReadSummary($path) {
    if ($path === false || !is_file($path) || (time() - filemtime($path)) >= AIO_AVAILABILITY_TTL) {
        return null;
    }
    $s = json_decode((string) @file_get_contents($path), true);
    return is_array($s) && isset($s['candidates']) ? $s : null;
}

function aioAvailabilityWriteSummary($path, array $summary) {
    if ($path === false) {
        return;
    }
    $tmp = $path . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, json_encode($summary)) !== false) {
        @rename($tmp, $path);
    }
}

// Cooldown after a throttle answer: no upstream calls at all until it ends.
function aioAvailabilityCooldownRemaining() {
    $dir = aioAvailabilityCacheDir();
    $f = $dir === false ? false : $dir . '/cooldown';
    if ($f === false || !is_file($f)) {
        return 0;
    }
    return max(0, intval(@file_get_contents($f)) - time());
}

function aioAvailabilityStartCooldown($seconds = AIO_AVAILABILITY_COOLDOWN) {
    $dir = aioAvailabilityCacheDir();
    if ($dir !== false) {
        @file_put_contents($dir . '/cooldown', (string) (time() + $seconds));
    }
}

// Token bucket shared by every request (flock-serialized). Returns
// [granted, retryAfterSeconds]: how many of $want upstream lookups may go out
// now, and if fewer than wanted, roughly when the next token appears.
function aioAvailabilityTakeTokens($want, array $cfg) {
    $dir = aioAvailabilityCacheDir();
    if ($dir === false) {
        return [$want, 0]; // no shared state possible; don't block outright
    }
    $fh = @fopen($dir . '/budget', 'c+');
    if (!$fh || !flock($fh, LOCK_EX)) {
        return [0, 5];
    }
    $now = microtime(true);
    $state = json_decode((string) stream_get_contents($fh), true);
    $tokens = is_array($state) ? (float) $state['tokens'] : $cfg['burst'];
    $ts = is_array($state) ? (float) $state['ts'] : $now;
    $tokens = min($cfg['burst'], $tokens + max(0.0, $now - $ts) * $cfg['perMinute'] / 60.0);
    $granted = (int) min($want, floor($tokens));
    $tokens -= $granted;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode(['tokens' => $tokens, 'ts' => $now]));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $retry = 0;
    if ($granted < $want) {
        $retry = $cfg['perMinute'] > 0 ? (int) ceil((1.0 - $tokens) * 60.0 / $cfg['perMinute']) : 3600;
    }
    return [$granted, max(1, $retry)];
}

// --------------------------------------------------------------- upstream --

// Sliding-window curl_multi: up to $parallel lookups in flight at once.
// $urls is key => url; returns key => ['status' => int, 'body' => string|false].
function aioAvailabilityFetchMany(array $urls, $parallel) {
    $results = [];
    if (!$urls) {
        return $results;
    }
    $queue = array_keys($urls);
    $attempts = [];
    $mh = curl_multi_init();
    $inflight = [];
    $start = function ($key) use (&$mh, &$inflight, &$attempts, $urls) {
        $ch = curl_init($urls[$key]);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        curl_multi_add_handle($mh, $ch);
        $inflight[(int) $ch] = [$ch, $key];
        $attempts[$key] = ($attempts[$key] ?? 0) + 1;
    };
    while ($queue && count($inflight) < $parallel) {
        $start(array_shift($queue));
    }
    while ($inflight) {
        curl_multi_exec($mh, $running);
        while ($info = curl_multi_info_read($mh)) {
            $ch = $info['handle'];
            [, $key] = $inflight[(int) $ch];
            unset($inflight[(int) $ch]);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $body = $info['result'] === CURLE_OK ? curl_multi_getcontent($ch) : false;
            curl_multi_remove_handle($mh, $ch);
            if (($body === false || $status >= 500 || $status === 0) && $attempts[$key] < 2) {
                $queue[] = $key; // one retry for a transport failure / 5xx
            } else {
                $results[$key] = ['status' => $status, 'body' => $body];
            }
            while ($queue && count($inflight) < $parallel) {
                $start(array_shift($queue));
            }
        }
        if ($inflight) {
            curl_multi_select($mh, 0.2);
        }
    }
    curl_multi_close($mh);
    return $results;
}

function aioAvailabilityStreamUrl(array $cfg, $kind, $id, $probe) {
    if ($kind === 'series') {
        return $cfg['url'] . '/stream/series/tmdb:' . $id . ':' . $probe[0] . ':' . $probe[1] . '.json';
    }
    return $cfg['url'] . '/stream/movie/tmdb:' . $id . '.json';
}

// Summaries for many (kind,id,probe) lookups: cache first, then the budgeted,
// parallel upstream. Returns [summaries keyed by request key (null = no
// answer), throttled bool, retryAfter seconds|null].
function aioAvailabilityGetSummaries(array $requests, array $cfg, $refresh) {
    $summaries = [];
    $need = [];
    foreach ($requests as $key => [$kind, $id, $probe]) {
        $path = aioAvailabilitySummaryPath($kind, $id, $probe[0] . 'x' . $probe[1]);
        $hit = $refresh ? null : aioAvailabilityReadSummary($path);
        if ($hit !== null) {
            $summaries[$key] = $hit;
        } else {
            $summaries[$key] = null;
            $need[$key] = $path;
        }
    }
    if (!$need) {
        return [$summaries, false, null];
    }
    if ($cfg['url'] === '') {
        return [$summaries, false, null];
    }

    $cool = aioAvailabilityCooldownRemaining();
    if ($cool > 0) {
        return [$summaries, true, $cool];
    }
    [$granted, $retry] = aioAvailabilityTakeTokens(count($need), $cfg);
    $go = array_slice($need, 0, $granted, true);
    $retryAfter = $granted < count($need) ? $retry : null;

    $urls = [];
    foreach ($go as $key => $_) {
        [$kind, $id, $probe] = $requests[$key];
        $urls[$key] = aioAvailabilityStreamUrl($cfg, $kind, $id, $probe);
    }
    $throttled = false;
    foreach (aioAvailabilityFetchMany($urls, $cfg['parallel']) as $key => $r) {
        if ($r['body'] === false || $r['status'] !== 200) {
            continue;
        }
        $data = json_decode($r['body'], true);
        if (!is_array($data)) {
            continue;
        }
        $summary = aioAvailabilitySummarize($data);
        if ($summary['throttled']) {
            $throttled = true;
            continue; // never cached, never trusted
        }
        // Degraded (partial) answers are usable for a positive verdict but
        // must not be remembered as the last word for hours.
        if (!$summary['degraded']) {
            aioAvailabilityWriteSummary($go[$key], $summary);
        }
        $summaries[$key] = $summary;
    }
    if ($throttled) {
        aioAvailabilityStartCooldown();
        $retryAfter = AIO_AVAILABILITY_COOLDOWN;
    }
    return [$summaries, $throttled, $retryAfter];
}

// Availability for a batch of titles of one kind ('movie' | 'series') in one
// language. $items: list of ['id' => int, 'year' => string]. Returns
// ['results' => [...], 'throttled' => bool, 'retry_after' => int|null,
//  'deferred' => int]. Every result has available true|false|null; null is
// "unknown - ask again later" (throttled, over budget, upstream failure).
function aioAvailabilityBatch(array $items, $kind, $languageName, $refresh = false) {
    global $aioDebridWeights;
    $cfg = aioAvailabilityConfig();
    $weights = is_array($aioDebridWeights ?? null) ? $aioDebridWeights : [];
    $zero = array_keys(array_filter($weights, function ($w) {
        return (float) $w === 0.0;
    }));
    $probes = $kind === 'series' ? AIO_AVAILABILITY_SERIES_PROBES : [[0, 0]];

    $verdicts = []; // id => list of per-probe verdicts (null = unknown)
    $anyThrottled = false;
    $retryAfter = null;
    $pending = [];  // ids still needing their next probe
    $years = [];
    foreach ($items as $it) {
        $pending[$it['id']] = 0;
        $verdicts[$it['id']] = [];
        $years[$it['id']] = $it['year'] ?? '';
    }

    for ($wave = 0; $wave < count($probes) && $pending; $wave++) {
        $requests = [];
        foreach ($pending as $id => $_) {
            $requests[$id] = [$kind, $id, $probes[$wave]];
        }
        [$summaries, $throttled, $ra] = aioAvailabilityGetSummaries($requests, $cfg, $refresh);
        $anyThrottled = $anyThrottled || $throttled;
        if ($ra !== null) {
            $retryAfter = max($retryAfter ?? 0, $ra);
        }
        $next = [];
        foreach ($pending as $id => $_) {
            $verdict = $summaries[$id] === null ? null
                : aioAvailabilityVerdict($summaries[$id], $languageName, $kind === 'movie' ? (string) $years[$id] : '', $zero);
            $verdicts[$id][] = $verdict;
            // Only a definite "not available" earns a further probe.
            if ($verdict !== null && aioAvailabilityFinalAvailable($verdict) === false) {
                $next[$id] = 0;
            }
        }
        $pending = $next;
    }

    $results = [];
    $deferred = 0;
    foreach ($items as $it) {
        $list = $verdicts[$it['id']];
        $best = null;
        $unknown = false;
        foreach ($list as $v) {
            if ($v === null || aioAvailabilityFinalAvailable($v) === null) {
                $unknown = true;
                continue;
            }
            if ($v['available'] && $best === null) {
                $best = $v;
            }
        }
        $row = ['tmdb_id' => $it['id'], 'language' => $languageName];
        if ($best !== null) {
            $row += ['available' => true] + array_intersect_key($best, array_flip(['best', 'counts', 'cached_candidates', 'other_cached', 'language_matches']));
        } elseif ($unknown || !$list) {
            $row += ['available' => null, 'error' => $anyThrottled ? 'aiostreams_rate_limited' : ($retryAfter !== null ? 'availability_budget' : 'aiostreams_unreachable')];
            $deferred++;
        } else {
            $last = end($list);
            $row += ['available' => false] + array_intersect_key($last, array_flip(['best', 'counts', 'cached_candidates', 'other_cached', 'language_matches']));
        }
        $results[] = $row;
    }
    return ['results' => $results, 'throttled' => $anyThrottled, 'retry_after' => $retryAfter, 'deferred' => $deferred];
}

// Single movie, the original endpoint's contract: [httpStatus, array]. 502 =
// upstream unusable, 429 = throttled/over budget; never cached; available is
// then null ("unknown"), not false.
function aioAvailabilityLookup($tmdbId, $languageName, $refresh = false, $year = '') {
    global $apiKey;
    $tmdbId = intval($tmdbId);
    $cfg = aioAvailabilityConfig();
    $base = ['tmdb_id' => $tmdbId, 'language' => $languageName];
    if ($cfg['url'] === '') {
        return [200, $base + ['available' => null, 'error' => 'aiostreams_not_configured']];
    }
    if ($year === '' && !empty($apiKey) && function_exists('makeGetRequest')) {
        $body = makeGetRequest("https://api.themoviedb.org/3/movie/{$tmdbId}?api_key={$apiKey}");
        $details = $body ? json_decode($body, true) : null;
        if (is_array($details) && !empty($details['release_date'])) {
            $year = substr($details['release_date'], 0, 4);
        }
    }
    $batch = aioAvailabilityBatch([['id' => $tmdbId, 'year' => (string) $year]], 'movie', $languageName, $refresh);
    $row = $batch['results'][0];
    if ($row['available'] !== null) {
        return [200, $row + ['checked_at' => time()]];
    }
    if ($batch['throttled'] || $batch['retry_after'] !== null) {
        return [429, $row + ['error' => $batch['throttled'] ? 'aiostreams_rate_limited' : 'availability_budget_exhausted', 'retry_after' => $batch['retry_after']]];
    }
    return [502, $row + ['error' => 'aiostreams_unreachable']];
}

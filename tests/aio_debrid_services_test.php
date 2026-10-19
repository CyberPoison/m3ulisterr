<?php
// Tests for how play.php's AIOStreams path treats the debrid services behind it:
// AllDebrid (AD) and Premiumize (PM) as before, plus Real-Debrid (RD).
// Run: php tests/aio_debrid_services_test.php
//
// play.php runs its request handling when included, so the functions under
// test are pulled out of its source by name and defined here verbatim - the
// tests always exercise the shipped code, not a copy.
// play.php still calls curl_close(), deprecated (a no-op) since PHP 8.5; keep the test output readable.
error_reporting(E_ALL & ~E_DEPRECATED);
$failures = 0;
function check($label, $cond) {
    global $failures;
    echo ($cond ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$cond) {
        $failures++;
    }
}

$src = file_get_contents(__DIR__ . '/../play.php');
function grabFunction($src, $name) {
    $pos = strpos($src, "function $name(");
    if ($pos === false) {
        fwrite(STDERR, "function $name not found in play.php\n");
        exit(2);
    }
    $depth = 0;
    for ($j = strpos($src, '{', $pos), $n = strlen($src); $j < $n; $j++) {
        if ($src[$j] === '{') {
            $depth++;
        } elseif ($src[$j] === '}' && --$depth === 0) {
            return substr($src, $pos, $j - $pos + 1);
        }
    }
    exit(2);
}
foreach ([
    'aioCandidateService', 'aioAudioCompatRank', 'aioVideoIsHeavy', 'aioPickWeightedService', 'aioPickVerificationWindow', 'aioServiceResolvesSlowly',
    'checkAioStreamsLinksPlayableBatchOnce', 'checkAioStreamsLinksPlayableBatch',
    'measureAioStreamsThroughputMbps', 'isAioStreamsThroughputOk', 'aioProbeDurationAndThroughput', 'fetchAioStreamsList', 'checkLinkStatusCode', 'checkCachedLinkStatus', 'checkVideoProxyUpstreamAlive', 'wrapWithVideoProxy',
] as $fn) {
    eval(grabFunction($src, $fn));
}
$hlsSrc = file_get_contents(__DIR__ . '/../hls_shared.php');
eval(grabFunction($hlsSrc, 'plainVideoFallbackUrl'));
$DEBUG = false;
define('AIO_LIST_CACHE_TTL', 600);
function aioListCacheDir() { return false; } // no cache: every call really fetches
$aioStreamsProxy = '';

function cand($debrid, $cached = true, $n = 0) {
    return ['debrid' => $debrid, 'cached' => $cached, 'url' => "https://x/$debrid/" . ($cached ? 'c' : 'u') . "/$n"];
}

// ---------------------------------------------------------------- services
check('service names: AD / PM / RD / TB', aioCandidateService(['debrid' => 'AD']) === 'alldebrid'
    && aioCandidateService(['debrid' => 'PM']) === 'premiumize'
    && aioCandidateService(['debrid' => 'RD']) === 'realdebrid'
    && aioCandidateService(['debrid' => 'TB']) === 'torbox');

check('only Real-Debrid resolves slowly (AD / PM do not)', aioServiceResolvesSlowly('RD') && aioServiceResolvesSlowly('rd')
    && !aioServiceResolvesSlowly('AD') && !aioServiceResolvesSlowly('PM') && !aioServiceResolvesSlowly(''));

// ---------------------------------------------------------------- weighted draw
mt_srand(12345);
$both = [cand('RD'), cand('PM')];
$rd = 0;
for ($i = 0; $i < 20000; $i++) {
    $rd += aioPickWeightedService($both, ['realdebrid' => 80, 'premiumize' => 20]) === 'realdebrid' ? 1 : 0;
}
check(sprintf('RD 80 / PM 20 draws Real-Debrid %.1f%% of the time (want 78-82%%)', $rd / 200), $rd / 20000 > 0.78 && $rd / 20000 < 0.82);

$ad = 0;
for ($i = 0; $i < 20000; $i++) {
    $ad += aioPickWeightedService([cand('AD'), cand('PM')], ['alldebrid' => 70, 'premiumize' => 30]) === 'alldebrid' ? 1 : 0;
}
check(sprintf('AllDebrid logic unchanged: AD 70 / PM 30 draws AllDebrid %.1f%% (want 68-72%%)', $ad / 200), $ad / 20000 > 0.68 && $ad / 20000 < 0.72);

check('a single service present has nothing to prefer (null)', aioPickWeightedService([cand('RD'), cand('RD', true, 1)], ['realdebrid' => 80]) === null);

$counts = ['alldebrid' => 0, 'realdebrid' => 0, 'premiumize' => 0];
for ($i = 0; $i < 20000; $i++) {
    $counts[aioPickWeightedService([cand('AD'), cand('RD'), cand('PM')], ['realdebrid' => 80, 'premiumize' => 20])]++;
}
check('AD left out of the weights defaults to 50 and still takes part (AD ~33%, RD ~53%, PM ~13%)',
    abs($counts['alldebrid'] / 200 - 33.3) < 2.5 && abs($counts['realdebrid'] / 200 - 53.3) < 2.5 && abs($counts['premiumize'] / 200 - 13.3) < 2.5);

// ---------------------------------------------------------------- audio compatibility
$d = fn($audio) => "\u{1F3A5} BluRay \u{1F39E}\u{FE0F} AVC\n\u{1F3A7} $audio \u{1F50A} 5.1\n\u{1F4E6} 9 GB (12 Mbps)";
check('audio: AAC / DD (AC3) are rank 0', aioAudioCompatRank($d('AAC')) === 0 && aioAudioCompatRank($d('DD')) === 0);
check('audio: DD+ and plain DTS are rank 1', aioAudioCompatRank($d('DD+')) === 1 && aioAudioCompatRank($d('DTS')) === 1);
check('audio: DTS-HD MA / TrueHD / Atmos / PCM are rank 2',
    aioAudioCompatRank($d('DTS-HD MA')) === 2 && aioAudioCompatRank($d('TrueHD')) === 2
    && aioAudioCompatRank($d('Atmos')) === 2 && aioAudioCompatRank($d('PCM')) === 2);
check('audio: a multi-track release takes its most compatible track (TrueHD | DD -> 0; DD+ | AAC | DD -> 0)',
    aioAudioCompatRank($d('TrueHD | DD')) === 0 && aioAudioCompatRank($d('DD+ | AAC | DD')) === 0 && aioAudioCompatRank($d('TrueHD | Atmos')) === 2);
check('audio: no field -> filename decides (DTS-HDMA remux = 2, x264 AC3 = 0, DDP = 1, nothing = 1)',
    aioAudioCompatRank('', 'John.Wick.Chapter.2.2017.MULTI.VFF.1080p.BluRay.REMUX.AVC.DTS-HDMA.5.1-ROMK.mkv') === 2
    && aioAudioCompatRank('', 'John.Wick.Chapter.2.2017.MULTi-VF2.1080p.BluRay.x264.AC3-JAQC.mkv') === 0
    && aioAudioCompatRank('', 'Movie.2020.1080p.WEB-DL.DDP5.1.H264.mkv') === 1
    && aioAudioCompatRank('', 'Movie.2020.1080p.mkv') === 1);

// ---------------------------------------------------------------- video weight
check('video: 2160p is heavy, 1080p/720p x264 is not', aioVideoIsHeavy(2160, 'Movie.2020.mkv') && !aioVideoIsHeavy(1080, 'Movie.2020.1080p.BluRay.x264.mkv') && !aioVideoIsHeavy(720, 'Movie.720p.mkv'));
check('video: 10-bit / HDR / Dolby Vision in the filename are heavy even at 1080p',
    aioVideoIsHeavy(1080, 'Spider-Man Homecoming 2017 BR EAC3 VFF VFQ VO 1080p x265 10Bits T0M.mkv')
    && aioVideoIsHeavy(1080, 'Movie.2020.1080p.UHD.BluRay.DV.HDR10.x265.mkv') && aioVideoIsHeavy(1080, 'Movie 1080p DOLBY VISION x265.mkv')
    && aioVideoIsHeavy(1080, 'Movie.1080p.Hi10P.mkv'));
check('video: the description visual line counts too, and SDR does not', aioVideoIsHeavy(1080, 'Movie.mkv', "\u{1F4FA} HDR10 | HDR \u{1F3A7} DD") && !aioVideoIsHeavy(1080, 'Movie.mkv', "\u{1F4FA} SDR \u{1F3A7} DD+"));
check('video: ordinary releases (x265 8-bit 1080p, WEB-DL, REMUX AVC) are not heavy',
    !aioVideoIsHeavy(1080, 'Movie.2020.1080p.WEB-DL.DDP5.1.H264-GRP.mkv') && !aioVideoIsHeavy(1080, 'Movie.2020.1080p.BluRay.REMUX.AVC.DTS-HD.MA.mkv'));

// ---------------------------------------------------------------- verification window
$pmFirst = [];
for ($i = 0; $i < 80; $i++) {
    $pmFirst[] = cand('PM', true, $i);
}
for ($i = 0; $i < 19; $i++) {
    $pmFirst[] = cand('RD', true, $i);
}
$win = aioPickVerificationWindow($pmFirst, 15, 3);
$winRd = count(array_filter($win, fn($c) => $c['debrid'] === 'RD'));
check('window keeps the limit (15) when one service has 80 cached candidates', count($win) === 15);
check('...and still reserves Real-Debrid slots when Premiumize is the preferred service (>=3 RD)', $winRd >= 3);
check('...and the preferred service still leads the window', $win[0]['debrid'] === 'PM' && $win[0]['url'] === 'https://x/PM/c/0');
$pos = array_map(fn($c) => array_search($c, $pmFirst, true), $win);
check('...and the priority order is preserved', $pos === array_values(array_unique($pos)) && $pos === (function ($p) { sort($p); return $p; })($pos));

$small = [cand('RD'), cand('PM'), cand('RD', true, 1)];
check('a list within the limit is returned untouched', aioPickVerificationWindow($small, 15, 3) === $small);

$onlyUncachedRd = [];
for ($i = 0; $i < 20; $i++) {
    $onlyUncachedRd[] = cand('PM', true, $i);
}
for ($i = 0; $i < 10; $i++) {
    $onlyUncachedRd[] = cand('RD', false, $i);
}
$win2 = aioPickVerificationWindow($onlyUncachedRd, 15, 3);
check('a service with nothing cached gets no reserved slots (requesting not-cached links starts downloads)',
    count($win2) === 15 && count(array_filter($win2, fn($c) => $c['debrid'] === 'RD')) === 0);

$three = [];
foreach (['AD', 'PM', 'RD'] as $s) {
    for ($i = 0; $i < 30; $i++) {
        $three[] = cand($s, true, $i);
    }
}
$win3 = aioPickVerificationWindow($three, 15, 3);
$by = array_count_values(array_column($win3, 'debrid'));
check('AD + PM + RD all cached: every service is represented (>=3 each)', ($by['AD'] ?? 0) >= 3 && ($by['PM'] ?? 0) >= 3 && ($by['RD'] ?? 0) >= 3 && count($win3) === 15);

// ---------------------------------------------------------------- playability check vs a mock CDN
$port = 18441;
$mock = proc_open(['php', '-d', 'opcache.enable=0', '-d', 'opcache.enable_cli=0', '-S', "127.0.0.1:$port", __DIR__ . '/mock_aio_playback.php'],
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null, ['PATH' => getenv('PATH'), 'PHP_CLI_SERVER_WORKERS' => '8']);
register_shutdown_function(function () use ($mock) {
    proc_terminate($mock);
});
for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) {
    usleep(100000);
}
$u = fn($p) => "http://127.0.0.1:$port$p";

$r = checkAioStreamsLinksPlayableBatch([$u('/fast')], [], 2, 6)[$u('/fast')];
check('normal CDN video/mp4 answer is playable', $r['ok'] && strpos($r['finalUrl'], '/cdn/video.mp4') !== false && $r['totalBytes'] === 50000000);

$r = checkAioStreamsLinksPlayableBatch([$u('/rd_force')], [], 2, 6)[$u('/rd_force')];
check('Real-Debrid CDN answer (application/force-download) is playable', $r['ok'] && strpos($r['finalUrl'], '/cdn/force.mkv') !== false);

$r = checkAioStreamsLinksPlayableBatch([$u('/hang')], [], 1, 1)[$u('/hang')];
check('a link that never answers is NOT playable (used to be accepted with its unresolved url)', !$r['ok'] && $r['transient'] && $r['finalUrl'] === $u('/hang'));

$r = checkAioStreamsLinksPlayableBatch([$u('/rd_slow')], [], 1, 6)[$u('/rd_slow')];
check('a slow first resolve is rejected when the service is not marked slow (retry only gets the normal timeout)', !$r['ok']);

$t = microtime(true);
$r = checkAioStreamsLinksPlayableBatch([$u('/rd_slow')], [$u('/rd_slow')], 1, 6)[$u('/rd_slow')];
check('...but is collected by the longer retry when the service IS marked slow (Real-Debrid)', $r['ok'] && strpos($r['finalUrl'], '/cdn/force.mkv') !== false);
check(sprintf('...and the retry returns as soon as the link resolves (%.1fs, not the full retry window)', microtime(true) - $t), microtime(true) - $t < 5.5);

$all = [$u('/fast'), $u('/rd_force'), $u('/slate'), $u('/json200'), $u('/e404')];
$res = checkAioStreamsLinksPlayableBatch($all, [], 2, 6);
check('slate redirect is rejected and not retried', !$res[$u('/slate')]['ok'] && !$res[$u('/slate')]['transient']);
check('a non-video 200 (json) is rejected', !$res[$u('/json200')]['ok'] && !$res[$u('/json200')]['transient']);
check('a 404 is rejected', !$res[$u('/e404')]['ok']);
check('in the same batch the good links still pass (fast + Real-Debrid)', $res[$u('/fast')]['ok'] && $res[$u('/rd_force')]['ok']);

$mbps = measureAioStreamsThroughputMbps($res[$u('/rd_force')]['finalUrl']);
check(sprintf('throughput is measured against the resolved Real-Debrid CDN url (%.0f Mbps)', $mbps), $mbps > 5 && isAioStreamsThroughputOk($mbps, 8.0));


// ---------------------------------------------------------------- fast start: progressive verification
$t = microtime(true);
$r = checkAioStreamsLinksPlayableBatchOnce([$u('/rd_slow'), $u('/fast')], [], 12, true, 1.0);
$el = microtime(true) - $t;
check(sprintf('early exit: a good link ranked 2nd is taken once the cold 1st is past its grace (%.1fs, not the 3s resolve)', $el),
    $r[$u('/fast')]['ok'] && !empty($r[$u('/rd_slow')]['skipped']) && $el < 2.5);

$t = microtime(true);
$r = checkAioStreamsLinksPlayableBatchOnce([$u('/rd_slow'), $u('/fast')], [], 12, true, 6.0);
$el = microtime(true) - $t;
check(sprintf('early exit: ...but a longer grace waits for the better-ranked link and takes it (%.1fs)', $el),
    $r[$u('/rd_slow')]['ok'] && empty($r[$u('/rd_slow')]['skipped']) && $el >= 2.5);

$t = microtime(true);
$r = checkAioStreamsLinksPlayableBatchOnce([$u('/slate'), $u('/fast'), $u('/hang')], [], 12, true, 3.0);
$el = microtime(true) - $t;
check(sprintf('early exit: slate rejected, 2nd link ok, the hanging 3rd is not waited for (%.1fs, not 8s+)', $el),
    !$r[$u('/slate')]['ok'] && empty($r[$u('/slate')]['skipped']) && $r[$u('/fast')]['ok'] && !empty($r[$u('/hang')]['skipped']) && $el < 2.0);
check('early exit: results keep the priority order of the urls', array_keys($r) === [$u('/slate'), $u('/fast'), $u('/hang')]);

$r = checkAioStreamsLinksPlayableBatchOnce([$u('/slate'), $u('/e404')], [], 12, true, 3.0);
check('early exit: when nothing is playable every link gets a real verdict (no skipped)',
    !$r[$u('/slate')]['ok'] && !$r[$u('/e404')]['ok'] && empty($r[$u('/slate')]['skipped']) && empty($r[$u('/e404')]['skipped']));

$t = microtime(true);
$r = checkAioStreamsLinksPlayableBatch([$u('/hang')], [$u('/hang')], 1, 6, true)[$u('/hang')];
$el = microtime(true) - $t;
check(sprintf('early-exit batch does NOT retry a transient failure (%.1fs, the complete pass does that)', $el), !$r['ok'] && $r['transient'] && $el < 2.5);

[$durOk, $mbps2] = aioProbeDurationAndThroughput($u('/rd_force'));
check(sprintf('duration + throughput probe: unprobeable body is let through, throughput measured (%.0f Mbps)', $mbps2), $durOk === true && $mbps2 > 5);


// ---------------------------------------------------------------- fast start: hedged catalog fetch
$k = 'slow' . getmypid();
@unlink(sys_get_temp_dir() . '/mock_catalog_' . $k);
$t = microtime(true);
$body = fetchAioStreamsList($u('/catalog?k=' . $k . '&mode=slow'));
$el = microtime(true) - $t;
check(sprintf('catalog: a stalled first request is overtaken by a second one after ~5s (%.1fs, not 8s)', $el),
    is_string($body) && strpos($body, '"ok"') !== false && $el > 4.5 && $el < 7.5);

$k = 'fail' . getmypid();
@unlink(sys_get_temp_dir() . '/mock_catalog_' . $k);
$t = microtime(true);
$body = fetchAioStreamsList($u('/catalog?k=' . $k . '&mode=fail'));
$el = microtime(true) - $t;
check(sprintf('catalog: a failed first request is replaced immediately, not after a wait (%.1fs)', $el), is_string($body) && strpos($body, '"ok"') !== false && $el < 2);
@unlink(sys_get_temp_dir() . '/mock_catalog_slow' . getmypid());
@unlink(sys_get_temp_dir() . '/mock_catalog_fail' . getmypid());


// ---------------------------------------------------------------- never hand a player a raw IP-locked url
function locateBaseURL() { return 'http://127.0.0.1:18441/'; }
$rawElf = 'https://aiostreams.elfhosted.com/playback/abc/def/Movie%20x264.mkv';
$fb = plainVideoFallbackUrl($rawElf);
check('fallback: a raw AIOStreams url is wrapped in the server byte proxy, not given to the player',
    strpos($fb, 'http://127.0.0.1:18441/video_proxy.php?url=' . urlencode($rawElf)) === 0 && strpos($fb, 'auto=1') !== false);
check('fallback: an url that is already video_proxy / one of our own endpoints is left alone',
    plainVideoFallbackUrl('http://127.0.0.1:18441/video_proxy.php?url=x') === 'http://127.0.0.1:18441/video_proxy.php?url=x'
    && plainVideoFallbackUrl('http://127.0.0.1:18441/hls_proxy.php?x=1') === 'http://127.0.0.1:18441/hls_proxy.php?x=1');

$aliveHls = 'subtitle_track_playlist.m3u8?video=' . urlencode('https://aiostreams.elfhosted.com/playback/live/Movie.mkv') . '&imdb=tt1&lang=en';
$deadHls = 'subtitle_track_playlist.m3u8?video=' . urlencode('https://aiostreams.elfhosted.com/playback/dead/Movie.mkv') . '&imdb=tt1&lang=en';
check('cache: a cached HLS entry whose source is alive is served', checkCachedLinkStatus($aliveHls) === true);
check('cache: a cached HLS entry whose source is DEAD is a miss (re-resolved), not served', checkCachedLinkStatus($deadHls) === false);
check('FRESH result: an HLS url is accepted by checkLinkStatusCode even if a HEAD on its source would fail (a verified stream is never thrown away)', checkLinkStatusCode($deadHls) === true);
check('cache: an HLS entry with no video param is still trusted', checkCachedLinkStatus('subtitle_track_playlist.m3u8?imdb=tt1') === true);

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);

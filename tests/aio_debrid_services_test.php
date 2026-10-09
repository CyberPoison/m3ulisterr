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
    'aioCandidateService', 'aioAudioCompatRank', 'aioPickWeightedService', 'aioPickVerificationWindow', 'aioServiceResolvesSlowly',
    'checkAioStreamsLinksPlayableBatchOnce', 'checkAioStreamsLinksPlayableBatch',
    'measureAioStreamsThroughputMbps', 'isAioStreamsThroughputOk',
] as $fn) {
    eval(grabFunction($src, $fn));
}
$DEBUG = false;

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

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);

<?php
// Unit tests for aio_availability.php's pure logic. Run: php tests/aio_availability_test.php
putenv('M3U_DATA_DIR=' . sys_get_temp_dir() . '/aio_avail_unit_' . getmypid());
require __DIR__ . '/../aio_availability.php';

$failures = 0;
function check($label, $cond) {
    global $failures;
    echo ($cond ? "PASS " : "FAIL ") . $label . "\n";
    if (!$cond) {
        $failures++;
    }
}

function mk($tag, $res, $codec, $langs = 'French | English', $filename = '', $extra = '') {
    return [
        'url' => 'https://example.invalid/playback/x',
        'name' => "[$tag] Torrentio {$res}p",
        'description' => "🎞️ $codec 🔊 AC3 $extra\n🌎 $langs 📝 English",
        'behaviorHints' => ['filename' => $filename],
    ];
}
function run($streams, $lang = 'French', $year = '', $zero = []) {
    return aioAvailabilityFromStreams(['streams' => $streams], $lang, $year, $zero);
}

$r = run([mk('AD⚡', 1080, 'AVC')]);
check('cached 1080p AVC -> available, best 1080p_x264', $r['available'] && $r['best'] === '1080p_x264');

$r = run([mk('AD⏳', 1080, 'AVC')]);
check('only uncached -> not available', !$r['available'] && $r['best'] === null && $r['language_matches'] === 1);

$r = run([mk('AD⚡', 1080, 'AVC', 'English')]);
check('requested language absent -> not available', !$r['available'] && $r['language_matches'] === 0);

$r = run([['url' => 'https://x', 'name' => '[AD⚡] 1080p', 'description' => "🎞️ AVC\n🌎 English 📝 French"]]);
check('language only in subtitles (📝) does not count', !$r['available']);

$r = run([mk('AD⚡', 2160, 'HEVC'), mk('AD⚡', 720, 'AVC')]);
check('ladder order: 720p x264 beats 4K x265', $r['best'] === '720p_x264' && $r['cached_candidates'] === 2);

foreach ([
    ['1080p_x264', 1080, 'AVC'], ['720p_x264', 720, 'AVC'], ['4k_x264', 2160, 'AVC'],
    ['4k_x265', 2160, 'HEVC'], ['1080p_x265', 1080, 'HEVC'], ['720p_x265', 720, 'HEVC'],
] as [$key, $res, $codec]) {
    $r = run([mk('AD⚡', $res, $codec)]);
    check("each ladder class is available alone: $key", $r['available'] && $r['best'] === $key && $r['counts'][$key] === 1);
}

$want = ['1080p_x264', '720p_x264', '4k_x264', '4k_x265', '1080p_x265', '720p_x265'];
check('ladder constant is in the requested priority order', array_keys(AIO_AVAILABILITY_LADDER) === $want);

$r = run([mk('AD⚡', 480, 'AVC'), mk('AD⚡', 1080, 'AV1')]);
check('SD / AV1 cached only -> not available, counted as other_cached', !$r['available'] && $r['other_cached'] === 2);

$r = run([mk('AD⚡', 1080, 'AVC', 'French', 'Movie.1999.1080p.mkv')], 'French', '2023');
check('explicit different year in filename is rejected (saga/pack guard)', !$r['available']);

$r = run([mk('AD⚡', 1080, 'AVC', 'French', 'Movie.2023.1080p.mkv')], 'French', '2023');
check('matching year kept', $r['available']);

$r = run([mk('AD⚡', 1080, 'AVC', 'French', 'Movie.1080p.mkv')], 'French', '2023');
check('filename without a year is unknown, not a mismatch', $r['available']);

$r = run([mk('AD⚡', 1080, 'AVC', 'French', 'Movie.1999.mkv')], 'French', '');
check('unknown release year skips the year check', $r['available']);

$r = run([mk('AD⚡', 1080, 'AVC'), mk('PM⚡', 720, 'AVC')], 'French', '', ['alldebrid']);
check('zero-weight service is excluded', $r['available'] && $r['best'] === '720p_x264' && $r['cached_candidates'] === 1);

$r = run([mk('AD⚡', 1080, 'AVC')], 'French', '', ['alldebrid']);
check('only candidate on a zero-weight service -> not available', !$r['available']);

$r = run([['name' => '[AD⚡] 1080p', 'description' => "🎞️ AVC\n🌎 French"]]);
check('stream without a direct url is skipped', !$r['available']);

$r = run([mk('AD⚡', 1080, 'AVC', 'Dubbed | English | French')]);
check('"Dubbed | English | French" still matches French', $r['available']);

$r = aioAvailabilityFromStreams([], 'French');
check('empty response -> not available (not an error)', !$r['available'] && $r['language_matches'] === 0);


// ---- safety: throttled / degraded answers are UNKNOWN, never "none" ----------
$throttleStub = [['name' => '[🐢] AIOStreams', 'title' => 'AIOStreams public rate-limit exceeded 🐢',
    'url' => 'https://elfhosted.com/assets/public-rate-limit-exceeded.mp4']];
$r = run($throttleStub);
check('rate-limit stub is not a candidate and is flagged', !$r['available'] && $r['state'] === 'rate_limited');
check('rate-limit stub yields unknown (null), not false', aioAvailabilityFinalAvailable($r) === null);

$errStream = function ($desc) {
    return ['name' => '[❌] Torrentio', 'description' => $desc, 'streamData' => ['type' => 'error', 'error' => ['title' => '[❌] Torrentio', 'description' => $desc]]];
};
$r = run([$errStream('Request timed out')]);
check('addon failure with no candidates is degraded -> unknown', $r['state'] === 'degraded' && aioAvailabilityFinalAvailable($r) === null);
$r = run([$errStream('Failed to get metadata'), $errStream('Failed to get metadata')]);
check('"Failed to get metadata" only = unknown title -> definitive none', $r['state'] === 'definitive' && aioAvailabilityFinalAvailable($r) === false);
$r = run([$errStream('Request timed out'), mk('AD⚡', 1080, 'AVC')]);
check('a positive answer stands despite an addon failure', $r['available'] && aioAvailabilityFinalAvailable($r) === true);
$r = run([$throttleStub[0], mk('AD⚡', 1080, 'AVC')]);
check('positive answer stands next to a throttle stub too', aioAvailabilityFinalAvailable($r) === true);
check('empty response is a definitive none', aioAvailabilityFinalAvailable(run([])) === false);

// ---- one summary serves every language ---------------------------------------
$sum = aioAvailabilitySummarize(['streams' => [mk('AD⚡', 1080, 'AVC', 'French'), mk('PM⚡', 720, 'AVC', 'English')]]);
check('summary is language independent (French sees 1080p)', aioAvailabilityVerdict($sum, 'French')['best'] === '1080p_x264');
check('summary is language independent (English sees 720p)', aioAvailabilityVerdict($sum, 'English')['best'] === '720p_x264');
check('summary is compact (no urls kept)', strpos(json_encode($sum), 'example.invalid') === false);

// ---- token bucket -------------------------------------------------------------
$cfg = ['perMinute' => 60.0, 'burst' => 5.0];
@unlink(aioAvailabilityCacheDir() . '/budget');
[$g] = aioAvailabilityTakeTokens(3, $cfg);
check('bucket starts full: 3 of 5 granted', $g === 3);
[$g, $retry] = aioAvailabilityTakeTokens(10, $cfg);
check('bucket grants only what is left (2), advises a retry', $g === 2 && $retry >= 1);
[$g] = aioAvailabilityTakeTokens(1, $cfg);
check('empty bucket grants nothing', $g === 0);
usleep(1200000);
[$g] = aioAvailabilityTakeTokens(5, $cfg);
check('bucket refills with time (~1 token/s at 60/min)', $g >= 1 && $g <= 2);
[$g] = aioAvailabilityTakeTokens(2, ['perMinute' => 0.5, 'burst' => 5.0]);
check('shared-instance defaults cannot burst past their tiny bucket', $g <= 2);

// ---- cooldown ------------------------------------------------------------------
@unlink(aioAvailabilityCacheDir() . '/cooldown');
check('no cooldown initially', aioAvailabilityCooldownRemaining() === 0);
aioAvailabilityStartCooldown(120);
check('cooldown is active after a throttle', aioAvailabilityCooldownRemaining() > 100);
@unlink(aioAvailabilityCacheDir() . '/cooldown');
aioAvailabilityResetCooldownLevel();
$secs = [];
for ($i = 0; $i < 7; $i++) {
    aioAvailabilityStartCooldown();
    $secs[] = aioAvailabilityCooldownRemaining();
}
check('cooldown escalates 5m -> 10m -> 20m -> 40m -> 60m and stays capped',
    $secs[0] > 290 && $secs[0] <= 300 && $secs[1] > 590 && $secs[2] > 1190 && $secs[3] > 2390 && $secs[4] > 3590 && $secs[5] > 3590 && $secs[6] <= 3600);
aioAvailabilityResetCooldownLevel();
aioAvailabilityStartCooldown();
check('a successful lookup resets the escalation back to 5 minutes', aioAvailabilityCooldownRemaining() <= 300);
@unlink(aioAvailabilityCacheDir() . '/cooldown');
aioAvailabilityResetCooldownLevel();

// ---- config defaults: conservative on the shared instance, fast on a dedicated one
$frenchAioStreamsUrl = 'https://aiostreams.elfhosted.com/stremio/x/y';
$c = aioAvailabilityConfig();
check('shared instance: tiny budget, low parallelism', !$c['dedicated'] && $c['perMinute'] < 1 && $c['parallel'] <= 4 && $c['url'] !== '');
$availabilityAioStreamsUrl = 'https://my.private.aiostreams/stremio/z';
$availabilityProxy = 'http://user:pass@proxy.example.com:8080';
$c = aioAvailabilityConfig();
check('dedicated instance: high budget, high parallelism', $c['dedicated'] && $c['perMinute'] >= 1000 && $c['parallel'] >= 32 && strpos($c['url'], 'private') !== false);
check('proxy setting propagated to config', $c['proxy'] === 'http://user:pass@proxy.example.com:8080');
unset($availabilityAioStreamsUrl, $availabilityProxy);

echo $failures === 0 ? "\nALL PASSED\n" : "\n$failures FAILED\n";
exit($failures === 0 ? 0 : 1);

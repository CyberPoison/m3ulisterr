<?php
// Unit tests for aio_availability.php's pure logic. Run: php tests/aio_availability_test.php
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

echo $failures === 0 ? "\nALL PASSED\n" : "\n$failures FAILED\n";
exit($failures === 0 ? 0 : 1);

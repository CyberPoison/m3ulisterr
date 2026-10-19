<?php
// Tests the one-retry wrapper around the segment ffmpeg (hls_shared.php).
// Run: php tests/hls_segment_retry_test.php   (needs ffmpeg on PATH)
error_reporting(E_ALL & ~E_DEPRECATED);
$failures = 0;
function check($label, $cond) {
    global $failures;
    echo ($cond ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$cond) {
        $failures++;
    }
}
$ffmpeg = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
if ($ffmpeg === '') {
    echo "SKIP ffmpeg not installed\n";
    exit(0);
}
require_once __DIR__ . '/../hls_shared.php';

$dir = sys_get_temp_dir() . '/seg_retry_' . getmypid();
@mkdir($dir);
$log = $dir . '/ffmpeg.log';
$gen = escapeshellarg($ffmpeg) . ' -y -v error -f lavfi -i "sine=frequency=440:duration=12" -c:a aac -b:a 192k -f mp4 -movflags frag_keyframe+empty_moov+default_base_moof';

// 1. fails the first time (marker absent), works the second time
$marker = $dir . '/marker1';
$raw = $dir . '/out1.raw';
$cmd = ['sh', '-c', "if [ -f " . escapeshellarg($marker) . " ]; then $gen " . escapeshellarg($raw) . "; else touch " . escapeshellarg($marker) . "; exit 1; fi"];
$r = runFragmentedMp4Segment($cmd, $raw, $log, $dir . '/err1');
check('a first failure is retried and the second attempt delivers a fragment', $r['fragment'] !== false && strlen($r['fragment']) > 4096);

// 2. works first time: runs once only
$counter = $dir . '/count2';
$raw2 = $dir . '/out2.raw';
$cmd = ['sh', '-c', "echo x >> " . escapeshellarg($counter) . "; $gen " . escapeshellarg($raw2)];
$r = runFragmentedMp4Segment($cmd, $raw2, $log, $dir . '/err2');
check('a healthy first attempt is not retried', $r['fragment'] !== false && count(file($counter)) === 1);

// 3. always fails: exactly two attempts, failure reported with both outcomes
$counter3 = $dir . '/count3';
$cmd = ['sh', '-c', "echo x >> " . escapeshellarg($counter3) . "; exit 1"];
$r = runFragmentedMp4Segment($cmd, $dir . '/out3.raw', $log, $dir . '/err3');
check('a permanent failure is attempted exactly twice', $r['fragment'] === false && count(file($counter3)) === 2);
check('...and reports both outcomes (' . ($r['outcome'] ?? '') . ')', ($r['outcome'] ?? '') === 'no_output_file_retry_no_output_file');


// 4. a legitimately tiny (near-silent) fragment is a valid segment, not a failure
$silentGen = escapeshellarg($ffmpeg) . ' -y -v error -f lavfi -i "anullsrc=r=48000:cl=stereo" -t 2 -c:a aac -b:a 192k -f mp4 -movflags frag_keyframe+empty_moov+default_base_moof';
$rawS = $dir . '/silent.raw';
$r = runFragmentedMp4Segment(['sh', '-c', "$silentGen " . escapeshellarg($rawS)], $rawS, $log, $dir . '/errS');
check('a tiny near-silent fragment (' . (is_string($r['fragment'] ?? false) ? strlen($r['fragment']) : 0) . ' bytes) is accepted (old 4 KB floor rejected it)',
    $r['fragment'] !== false && strlen($r['fragment']) < 4096);

shell_exec('rm -rf ' . escapeshellarg($dir));
echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);

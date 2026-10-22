<?php
// php tests/webvtt_clean_test.php - hlsCleanWebVtt() turns the glued-together WebVTT of the OpenSubtitles
// proxy (two WEBVTT headers, ad cue, BOM) into a single clean document.
require_once __DIR__ . '/../hls_shared.php';

$fail = 0;
function check($name, $cond) { global $fail; echo ($cond ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$cond) { $fail++; } }

$raw = "WEBVTT\n\nheader\n00:00:01.000 --> 00:00:06.000\n&gt;&gt;OpenSubtitles v3+ v0.0.4&lt;&lt;\n<u>=&gt;Black.Widow.srt</u>\n\nWEBVTT\n\n\xEF\xBB\xBF1\n00:00:48.384 --> 00:00:56.384\nHello\n@someone\n\n2\r\n00:01:06.608 --> 00:01:07.609\r\nOi, Nat!\r\n\nNOTE a comment\n\n3\n00:01:37.055 --> 00:01:38.682\nTwo\nlines\n";
$out = hlsCleanWebVtt($raw);

check('starts with a single WEBVTT header', strpos($out, "WEBVTT\n\n") === 0 && substr_count($out, 'WEBVTT') === 1);
check('no BOM left', strpos($out, "\xEF\xBB\xBF") === false);
check('advertisement cue removed', stripos($out, 'OpenSubtitles v3') === false);
check('three real cues kept', substr_count($out, '-->') === 3);
check('CRLF normalised', strpos($out, "\r") === false);
check('NOTE block dropped', strpos($out, 'NOTE') === false);
check('multi-line cue intact', strpos($out, "Two\nlines") !== false);
check('empty input gives a valid empty document', hlsCleanWebVtt('') === "WEBVTT\n\n");

exit($fail ? 1 : 0);

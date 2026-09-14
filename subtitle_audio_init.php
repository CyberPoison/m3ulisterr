<?php
// Serves the CMAF/fMP4 initialization segment (ftyp+moov) for one AUDIO
// rendition of a source - referenced via #EXT-X-MAP in
// subtitle_audio_playlist.php. Two renditions exist per source:
//   codec=original - the source audio untouched (e.g. TrueHD Atmos 7.1)
//   codec=aac    - transcoded to AAC stereo
// as separate selectable HLS AUDIO tracks, not a single muxed-in track,
// because plenty of real players (VLC's own 'mlpa' decoder gap confirmed
// directly) can't decode TrueHD-in-MP4 at all - the AAC rendition is what
// lets them play with working audio instead of none.
//
// Params: video - urlencoded source video URL. codec - 'original' or 'aac'.

error_reporting(0);
set_time_limit(0);
ob_end_clean();

require_once 'config.php';
require_once 'hls_shared.php';

if (!isset($_GET['video']) || empty($_GET['video'])) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Missing the video parameter.";
    exit;
}

$codec = $_GET['codec'] ?? '';
if (!in_array($codec, ['original', 'aac'], true)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Invalid or missing codec parameter.";
    exit;
}

$videoUrl = $_GET['video'];
$mediaTitle = $_GET['title'] ?? '';
$mediaYear = $_GET['year'] ?? '';
$mediaEncoder = $_GET['encoder'] ?? '';
// Must match the track subtitle_audio_segment.php maps, or this init header
// describes a different stream than the segments that follow it.
$audioIndex = isset($_GET['aidx']) ? max(0, (int) $_GET['aidx']) : 0;
if (!preg_match('#^https?://#i', $videoUrl)) {
    $videoUrl = rtrim(locateBaseURL(), '/') . '/' . ltrim($videoUrl, '/');
}

$ffmpegPath = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
if (empty($ffmpegPath)) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "ffmpeg not available.";
    exit;
}

$videoDir = getRamCacheDir() . '/' . md5($videoUrl);
if (!is_dir($videoDir)) {
    @mkdir($videoDir, 0755, true);
}
$cachePath = $videoDir . '/audio_init_' . $codec . '_a' . $audioIndex . '.mp4';

if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 7200) {
    header('Content-Type: video/mp4');
    header('Cache-Control: no-cache');
    header('Content-Length: ' . filesize($cachePath));
    readfile($cachePath);
    exit;
}

if ($codec === 'original') {
    // -strict -2: TrueHD-in-MP4 is flagged experimental by ffmpeg's muxer.
    // delay_moov: required alongside empty_moov specifically for TrueHD -
    // the muxer needs to see the first audio packet before it can finalize
    // the track's codec config, so a truly immediate empty moov isn't
    // possible for it.
    $codecArgs = ['-c:a', 'copy', '-strict', '-2'];
    $movflags = 'frag_keyframe+empty_moov+delay_moov+default_base_moof';
} else {
    $codecArgs = ['-c:a', 'aac', '-ac', '2', '-b:a', '192k'];
    $movflags = 'frag_keyframe+empty_moov+default_base_moof';
}

// Only needs a sliver of real media (-t 2) to force ffmpeg to emit the
// ftyp+moov header followed by at least one fragment - splitFmp4InitAndFragment()
// below then discards everything from the first 'moof' onward, keeping just
// the header every real segment needs.
//
// title/date metadata (when provided) is set here too, same reasoning as
// subtitle_video_init.php - a player may read its "now playing" info from
// whichever rendition's init segment it happens to load first, so both need
// it rather than just the video one.
$cmd = array_merge(
    [$ffmpegPath, '-y', '-i', $videoUrl, '-t', '2', '-map', '0:a:' . $audioIndex],
    $codecArgs,
    buildMp4MetadataArgs($mediaTitle, $mediaYear, $mediaEncoder),
    ['-f', 'mp4', '-movflags', $movflags, 'pipe:1']
);
$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'a']];
$process = proc_open($cmd, $descriptors, $pipes);

if (!is_resource($process)) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "Failed to start ffmpeg.";
    exit;
}

fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);
proc_close($process);

if ($output === false || strlen($output) < 8) {
    http_response_code(502);
    header('Content-Type: text/plain');
    echo "ffmpeg produced no output for the init segment.";
    exit;
}

list($init, $fragment) = splitFmp4InitAndFragment($output);

if (strlen($init) < 8 || $fragment === '') {
    http_response_code(502);
    header('Content-Type: text/plain');
    echo "Could not locate the init segment boundary.";
    exit;
}

$tmpPath = $cachePath . '.tmp.' . getmypid();
@file_put_contents($tmpPath, $init);
@rename($tmpPath, $cachePath);

header('Content-Type: video/mp4');
header('Cache-Control: no-cache');
echo $init;

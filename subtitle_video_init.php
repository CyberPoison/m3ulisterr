<?php
// Serves the CMAF/fMP4 initialization segment (ftyp+moov: codec/track setup,
// no media data) that every subtitle_video_segment.m4s fragment for this
// source depends on - referenced via #EXT-X-MAP in subtitle_video_playlist.php.
// Video-only: audio is a separate HLS AUDIO rendition group (see
// subtitle_audio_init.php / subtitle_audio_playlist.php) so a player can pick
// between the untouched TrueHD track and an AAC stereo fallback - VLC (among
// others) can't decode TrueHD-in-MP4 ('mlpa') at all, so it can't be muxed
// into the one track every player is forced to use.
//
// Generated once per source and cached long-term: the codec parameters
// (HEVC profile/level, timescale) are constant throughout a single file,
// unlike segments which are cut per time window.
//
// Params: video - urlencoded source video URL.

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

$videoUrl = $_GET['video'];
$mediaTitle = $_GET['title'] ?? '';
$mediaYear = $_GET['year'] ?? '';
$mediaEncoder = $_GET['encoder'] ?? '';
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

// Same RAM-backed cache root and per-source subdirectory as the other HLS
// scripts (see getRamCacheDir()). enforceStreamCacheBudget() protects any
// filename containing 'init' from eviction.
$videoDir = getRamCacheDir() . '/' . md5($videoUrl);
if (!is_dir($videoDir)) {
    @mkdir($videoDir, 0755, true);
}
$cachePath = $videoDir . '/video_init.mp4';

if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 7200) {
    header('Content-Type: video/mp4');
    header('Cache-Control: no-cache');
    header('Content-Length: ' . filesize($cachePath));
    readfile($cachePath);
    exit;
}

// Only needs a sliver of real media (-t 2) to force ffmpeg to emit the
// ftyp+moov header followed by at least one fragment - splitFmp4InitAndFragment()
// below then discards everything from the first 'moof' onward, keeping just
// the header every real segment needs.
//
// title/date metadata (when provided) is what makes VLC and other players
// show the actual movie name and year in their media info panel instead of
// just the raw source filename - it has to be set here, not anywhere else
// downstream, since this init segment's moov is the only place in the whole
// HLS output where MP4-level metadata can live.
$cmd = array_merge(
    [$ffmpegPath, '-y', '-i', $videoUrl, '-t', '2', '-map', '0:v:0', '-c:v', 'copy'],
    buildMp4MetadataArgs($mediaTitle, $mediaYear, $mediaEncoder),
    ['-f', 'mp4', '-movflags', 'frag_keyframe+empty_moov+default_base_moof', 'pipe:1']
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

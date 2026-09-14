<?php
// Media playlist for one AUDIO rendition of the source (see
// subtitle_audio_init.php for why there are two: codec=original untouched,
// codec=aac a stereo fallback), segmented via subtitle_audio_segment.php.
// Referenced from the master playlist's #EXT-X-MEDIA:TYPE=AUDIO entries,
// tied to the video-only variant via the AUDIO group attribute on
// #EXT-X-STREAM-INF.
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

if (!preg_match('#^https?://#i', $videoUrl)) {
    $videoUrl = rtrim(locateBaseURL(), '/') . '/' . ltrim($videoUrl, '/');
}

$ffprobePath = trim((string) shell_exec('command -v ffprobe 2>/dev/null'));
if (empty($ffprobePath)) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "ffprobe not available.";
    exit;
}

// Reuses the same cached duration probe subtitle_video_playlist.php and
// subtitle_track_playlist.php already trigger for this source - almost
// always a cache hit, not a fresh ffprobe spawn.
$duration = getSourceDuration($videoUrl, $ffprobePath);
if ($duration === false) {
    http_response_code(502);
    header('Content-Type: text/plain');
    echo "Could not determine source duration.";
    exit;
}

// Seek reliability is already checked once, upstream, by
// subtitle_track_playlist.php before any HLS playlist (video or audio) is
// ever handed to the client - see the comment there for why that decision
// can't be made per-variant after the fact.

$segmentLength = 8; // seconds - matches the video variant's target length.

// MUST reuse the exact same (start, length) boundaries the video variant
// uses - not just the same nominal segment length. Segments are cut on the
// VIDEO track's real keyframes (see getKeyframeTimestamps() /
// buildKeyframeAlignedSegments() in hls_shared.php - audio has no keyframe
// constraint of its own, but if this playlist picked its own independent
// boundaries, audio and video segments would cover different real time
// spans and drift out of sync with each other.
list($keyframeTimes, $keyframeCacheWasCold) = getKeyframeTimestampsForResponse($videoUrl, $ffprobePath);
$segments = buildKeyframeAlignedSegments($keyframeTimes, $duration, $segmentLength);
$maxSegmentLength = $segmentLength;
foreach ($segments as $seg) {
    if ($seg[1] > $maxSegmentLength) {
        $maxSegmentLength = $seg[1];
    }
}

$videoEnc = urlencode($videoUrl);
$codecEnc = urlencode($codec);

// Which audio track of the source to serve, chosen by language up in
// subtitle_track_playlist.php and threaded down from there - the init
// segment and every media segment must all map the SAME track or the
// rendition's own init header describes a stream its segments aren't.
$audioIndex = isset($_GET['aidx']) ? max(0, (int) $_GET['aidx']) : 0;

$initUrl = 'subtitle_audio_init.mp4?video=' . $videoEnc . '&codec=' . $codecEnc . '&aidx=' . $audioIndex;
if ($mediaTitle !== '') {
    $initUrl .= '&title=' . urlencode($mediaTitle);
}
if ($mediaYear !== '') {
    $initUrl .= '&year=' . urlencode($mediaYear);
}
if ($mediaEncoder !== '') {
    $initUrl .= '&encoder=' . urlencode($mediaEncoder);
}

$lines = [];
$lines[] = '#EXTM3U';
$lines[] = '#EXT-X-VERSION:7';
$lines[] = '#EXT-X-TARGETDURATION:' . (int) ceil($maxSegmentLength);
$lines[] = '#EXT-X-PLAYLIST-TYPE:VOD';
$lines[] = '#EXT-X-MEDIA-SEQUENCE:0';
$lines[] = '#EXT-X-MAP:URI="' . $initUrl . '"';

// Explicit &seq=, and &start=/&len= at 6 decimals not 3 - see
// subtitle_video_playlist.php for why (a 3-decimal round can land just
// under a real keyframe's true timestamp, which sends -ss to the WRONG,
// much earlier keyframe instead).
foreach ($segments as $seq => $seg) {
    list($start, $thisLen) = $seg;
    $lines[] = '#EXTINF:' . number_format($thisLen, 3, '.', '') . ',';
    $lines[] = 'subtitle_audio_segment.m4s?video=' . $videoEnc . '&codec=' . $codecEnc
        . '&aidx=' . $audioIndex
        . '&start=' . number_format($start, 6, '.', '') . '&len=' . number_format($thisLen, 6, '.', '') . '&seq=' . $seq;
}

$lines[] = '#EXT-X-ENDLIST';

header('Content-Type: application/vnd.apple.mpegurl');
header('Cache-Control: no-cache');
echo implode("\n", $lines) . "\n";

// See subtitle_video_playlist.php for why this only ever runs AFTER the
// client already has this response - and why a real player fetching BOTH
// playlists for the same cold source relies on scheduleKeyframeProbe()'s
// own lock to avoid running the same slow probe twice.
if ($keyframeCacheWasCold) {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    scheduleKeyframeProbe($videoUrl, $ffprobePath);
}

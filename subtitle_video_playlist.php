<?php
// Video-only media playlist for the subtitle-track HLS setup: segments the
// source's video track via plain stream copy (subtitle_video_segment.php),
// no re-encoding, so HDR10/DV metadata passes through untouched. Audio is a
// separate HLS AUDIO rendition group (subtitle_audio_playlist.php), tied to
// this variant via the AUDIO attribute on #EXT-X-STREAM-INF in the master
// playlist - not muxed in here, so a player can choose between the
// untouched original audio (e.g. TrueHD Atmos 7.1) and an AAC stereo
// fallback instead of being forced into whichever one got baked in.
// Segments are fMP4 rather than MPEG-TS specifically because MPEG-TS/ADTS
// cannot carry the original audio codec at all - fMP4 requires a shared
// init segment (the codec/track setup every fragment depends on) referenced
// via #EXT-X-MAP, generated separately by subtitle_video_init.php.
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

function fallbackToPlainVideoRedirectSVP($videoUrl) {
    header('HTTP/1.1 302 Found');
    header('Location: ' . $videoUrl);
    exit;
}

$ffprobePath = trim((string) shell_exec('command -v ffprobe 2>/dev/null'));
$ffmpegPath = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
if (empty($ffprobePath) || empty($ffmpegPath)) {
    fallbackToPlainVideoRedirectSVP($videoUrl);
}

$duration = getSourceDuration($videoUrl, $ffprobePath);
if ($duration === false) {
    fallbackToPlainVideoRedirectSVP($videoUrl);
}

// Seek reliability for this source is already checked by
// subtitle_track_playlist.php before it ever hands the client the master
// playlist that points here - a player that reached this file has already
// committed to HLS, and an #EXT-X-STREAM-INF variant URI redirecting
// straight to a raw multi-GB source file instead of resolving to a playlist
// is invalid HLS that fails to open. So that decision has to be made
// upstream, before any playlist is served, not here.

// Shorter than the earlier 20s: each segment now streams progressively as
// soon as ffmpeg produces it (see subtitle_video_segment.php), so segment
// length mostly affects seek granularity and how much a single failed/slow
// fetch can stall, not raw throughput. 8s keeps individual requests quick.
$segmentLength = 8; // seconds, target only - see buildKeyframeAlignedSegments().

// Segments MUST be cut on real keyframes, not blind fixed-length
// boundaries - -c:v copy can't start output mid-GOP, so a nominal boundary
// that doesn't land on one forces that segment to repeat whatever content
// the previous segment's tail already showed. See the big comment on
// getKeyframeTimestamps() in hls_shared.php - confirmed directly this
// isn't a rare edge case (roughly 1 in 10 keyframe gaps on a real BluRay
// source exceeded this segment length).
list($keyframeTimes, $keyframeCacheWasCold) = getKeyframeTimestampsForResponse($videoUrl, $ffprobePath);
$segments = buildKeyframeAlignedSegments($keyframeTimes, $duration, $segmentLength);
$maxSegmentLength = $segmentLength;
foreach ($segments as $seg) {
    if ($seg[1] > $maxSegmentLength) {
        $maxSegmentLength = $seg[1];
    }
}

$videoEnc = urlencode($videoUrl);

$initUrl = 'subtitle_video_init.mp4?video=' . $videoEnc;
if ($mediaTitle !== '') {
    $initUrl .= '&title=' . urlencode($mediaTitle);
}
if ($mediaYear !== '') {
    $initUrl .= '&year=' . urlencode($mediaYear);
}
if ($mediaEncoder !== '') {
    $initUrl .= '&encoder=' . urlencode($mediaEncoder);
}

// Version 7, not 3: EXT-X-MAP for fMP4 segments requires it.
$lines = [];
$lines[] = '#EXTM3U';
$lines[] = '#EXT-X-VERSION:7';
// TARGETDURATION must be >= every actual segment's duration - a keyframe gap
// longer than $segmentLength (real sources have these - see above) means
// some segments genuinely run longer than the nominal target.
$lines[] = '#EXT-X-TARGETDURATION:' . (int) ceil($maxSegmentLength);
$lines[] = '#EXT-X-PLAYLIST-TYPE:VOD';
$lines[] = '#EXT-X-MEDIA-SEQUENCE:0';
$lines[] = '#EXT-X-MAP:URI="' . $initUrl . '"';

// Explicit &seq=, not derived from $start: segments are no longer uniform
// 8s apart (see buildKeyframeAlignedSegments()), so a receiver-side guess
// like round($start/8) can't be trusted to stay monotonically exact.
//
// &start=/&len= need 6 decimals, NOT the 3 #EXTINF itself uses - confirmed
// directly this is not just cosmetic: a real keyframe measured at
// 24.274250 rounds to "24.274" at 3 decimals, which is a smidge LESS than
// the keyframe's real timestamp. ffmpeg's -ss seeks to "the keyframe at or
// before the target," so asking for 24.274 when the real keyframe sits at
// 24.274250 correctly (per ffmpeg's own logic) skips right past it and
// lands on the PREVIOUS keyframe instead - which on this source was a full
// 10.4s earlier. That's not a small rounding error, it's the exact
// mechanism that reintroduced the "video jumps back" bug this whole
// keyframe-alignment change exists to fix, just one layer further down the
// stack than the tfdt patch alone could catch. 6 decimals (sub-microsecond
// at any real-world timescale) leaves no meaningful gap left to fall into.
foreach ($segments as $seq => $seg) {
    list($start, $thisLen) = $seg;
    $lines[] = '#EXTINF:' . number_format($thisLen, 3, '.', '') . ',';
    $lines[] = 'subtitle_video_segment.m4s?video=' . $videoEnc
        . '&start=' . number_format($start, 6, '.', '') . '&len=' . number_format($thisLen, 6, '.', '') . '&seq=' . $seq;
}

$lines[] = '#EXT-X-ENDLIST';

header('Content-Type: application/vnd.apple.mpegurl');
header('Cache-Control: no-cache');
echo implode("\n", $lines) . "\n";

// Only reachable AFTER the client already has this (fixed-length-fallback)
// playlist in hand - see scheduleKeyframeProbe() in hls_shared.php for why
// the real, slow keyframe probe must never run before that point.
if ($keyframeCacheWasCold) {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    scheduleKeyframeProbe($videoUrl, $ffprobePath);
}

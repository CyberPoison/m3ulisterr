<?php
// Renders one time-window fragment of one AUDIO rendition of the source as
// a CMAF/fMP4 segment (moof+mdat) for subtitle_audio_playlist.php's HLS
// playlist. Two renditions exist per source (see subtitle_audio_init.php
// for why): codec=original (untouched stream copy) and codec=aac (transcoded
// stereo fallback for players that can't decode TrueHD-in-MP4).
//
// Every real segment needs the matching init segment
// (subtitle_audio_init.php?codec=...) served separately via #EXT-X-MAP -
// see runFragmentedMp4Segment() in hls_shared.php for why ffmpeg's output
// here is written to a real file (not piped) and has its own throwaway
// ftyp+moov discarded, keeping only the moof+mdat+mfra fragment.
//
// Params: video (urlencoded), start (seconds), len (seconds), codec
// ('original' or 'aac').

error_reporting(0);
set_time_limit(0);
ob_end_clean();
ignore_user_abort(true);

require_once 'config.php';
require_once 'hls_shared.php';
require_once 'm3ulisterr_lib.php';

function logSegmentOutcome($kind, $start, $len, $outcome, $elapsedMs) {
    $logPath = sys_get_temp_dir() . '/subtitle_track_segment_outcomes.log';
    if (@filesize($logPath) > 5 * 1024 * 1024) {
        @file_put_contents($logPath, '');
    }
    $line = date('Y-m-d H:i:s') . " kind={$kind} start={$start} len={$len} outcome={$outcome} elapsed_ms={$elapsedMs}\n";
    @file_put_contents($logPath, $line, FILE_APPEND);

    // Per-segment analytics: start/len feed the dashboard's furthest-position
    // (playback %) and h:mm:ss reached; elapsed_ms is the time to build and
    // deliver this segment to the device. $videoUrl is a file-scope variable
    // here, so it is reachable via $GLOBALS from inside this function.
    if (function_exists('m3uLogEvent')) {
        m3uLogEvent('segment', [
            'media' => m3uMediaHash($GLOBALS['videoUrl'] ?? ''),
            'kind' => $kind,
            'start' => $start,
            'len' => $len,
            'outcome' => $outcome,
            'deliverMs' => $elapsedMs,
        ]);
    }
}

$requestStartTime = microtime(true);

if (!isset($_GET['video']) || empty($_GET['video']) || !isset($_GET['start'])) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Missing parameters.";
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
$start = (float) $_GET['start'];
$len = isset($_GET['len']) ? (float) $_GET['len'] : 8;

if (!preg_match('#^https?://#i', $videoUrl)) {
    $videoUrl = rtrim(locateBaseURL(), '/') . '/' . ltrim($videoUrl, '/');
}

$ffmpegPath = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
$ffprobePath = trim((string) shell_exec('command -v ffprobe 2>/dev/null'));
if (empty($ffmpegPath)) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "ffmpeg not available.";
    exit;
}

// Bump this whenever the ffmpeg command below changes in a way that affects
// segment content - otherwise a stale segment built by the old command
// keeps being served from cache for up to its TTL after deploy.
// v11: audio is no longer hardwired to 0:a:0 - the track is chosen by
// language (see pickAudioStreamForLanguage() in hls_shared.php), so a
// segment cached under the old command would be the wrong track entirely.
$segmentCmdVersion = 11;

// Which audio track to map, threaded down from subtitle_track_playlist.php.
$audioIndex = isset($_GET['aidx']) ? max(0, (int) $_GET['aidx']) : 0;

$cacheRoot = getRamCacheDir();
$videoDir = $cacheRoot . '/' . md5($videoUrl);
if (!is_dir($videoDir)) {
    @mkdir($videoDir, 0755, true);
}
$cacheKey = md5($videoUrl . '|audio|' . $codec . '|a' . $audioIndex . '|' . $start . '|' . $len . '|v' . $segmentCmdVersion);
$cachePath = $videoDir . '/' . $cacheKey . '.m4s';

if (mt_rand(1, 20) === 1) {
    sweepAbandonedStreamCaches($cacheRoot);
}

if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 300) {
    header('Content-Type: video/mp4');
    header('Cache-Control: no-cache');
    header('Content-Length: ' . filesize($cachePath));
    readfile($cachePath);
    logSegmentOutcome('audio_' . $codec, $start, $len, 'cache_hit', round((microtime(true) - $requestStartTime) * 1000));
    exit;
}

$rawTmpPath = $videoDir . '/' . $cacheKey . '.raw.tmp.' . getmypid();

if ($codec === 'original') {
    // -c:a copy: byte-for-byte passthrough, no re-encode. -strict -2:
    // TrueHD-in-MP4 is flagged experimental by ffmpeg's muxer. delay_moov:
    // required alongside empty_moov specifically for TrueHD - the muxer
    // needs to see the first audio packet before it can finalize the
    // track's codec config.
    $codecArgs = ['-c:a', 'copy', '-strict', '-2'];
    $movflags = 'frag_keyframe+empty_moov+delay_moov+default_base_moof';
} else {
    // Universally-compatible stereo fallback - this is what makes audio
    // work at all for players (VLC included) that can't decode the
    // 'original' (e.g. TrueHD/'mlpa') rendition above.
    $codecArgs = ['-c:a', 'aac', '-ac', '2', '-b:a', '192k'];
    $movflags = 'frag_keyframe+empty_moov+default_base_moof';
}

// -copyts + -to (not plain relative timestamps + -t) - see the detailed
// explanation in subtitle_video_segment.php. Short version: relative
// per-segment timestamps made ffmpeg's own HLS demuxer log a timestamp
// discontinuity at every segment boundary and apply a compounding
// correction that eventually stalled decode outright (confirmed around
// 52-53s into real playback). -copyts fixes that but reintroduces a
// separate confirmed muxer bug - the first fragment's tfdt gets written as
// 0 regardless of the real seek position - which patchFirstFragmentTfdt()
// below corrects afterward.
// -debug_ts: MP4/MOV input-side seeking picks its target position from a
// reference stream (typically the video stream, regardless of which stream
// is actually being mapped/output) - confirmed directly that requesting
// -ss 24 on an audio-only map still lands the real first audio packet at
// the same ~13.8s a video-only map of the same request landed at, not
// anywhere near 24. So audio needs the same real-landing-pts measurement
// video does - see extractRealFragmentStartPtsTime() in hls_shared.php.
//
// -ss uses seekTargetWithMargin($start), NOT $start directly - see that
// function in hls_shared.php for the confirmed Matroska seek-index quirk
// this works around.
$cmd = array_merge(
    [$ffmpegPath, '-y', '-debug_ts', '-copyts', '-ss', (string) seekTargetWithMargin($start), '-i', $videoUrl, '-to', (string) ($start + $len), '-map', '0:a:' . $audioIndex],
    $codecArgs,
    ['-f', 'mp4', '-movflags', $movflags, $rawTmpPath]
);

$ffmpegLogPath = sys_get_temp_dir() . '/subtitle_track_segment_ffmpeg.log';
if (@filesize($ffmpegLogPath) > 5 * 1024 * 1024) {
    @file_put_contents($ffmpegLogPath, '');
}

$stderrCapturePath = $videoDir . '/' . $cacheKey . '.stderr.tmp.' . getmypid();
$result = runFragmentedMp4Segment($cmd, $rawTmpPath, $ffmpegLogPath, $stderrCapturePath);

if ($result['fragment'] === false) {
    logSegmentOutcome('audio_' . $codec, $start, $len, $result['outcome'], round((microtime(true) - $requestStartTime) * 1000));
    if ($result['outcome'] === 'client_aborted') {
        exit;
    }
    http_response_code(502);
    header('Content-Type: text/plain');
    echo "Could not produce this audio segment.";
    exit;
}

// See subtitle_video_segment.php for why this patch exists, why it's
// 0-based, and why &seq= (not round($start / 8)) is the real index now.
$sequenceNumber = isset($_GET['seq']) ? (int) $_GET['seq'] : (int) round($start / 8);
// Released from $result as soon as it is handed over - see the matching
// comment in subtitle_video_segment.php for why a second reference to a
// multi-megabyte fragment is worth avoiding.
$fragment = $result['fragment'];
unset($result['fragment']);
patchFragmentSequenceNumbers($fragment, $sequenceNumber);

// See subtitle_video_segment.php for why EVERY internal fragment gets
// patched (not just the first), why segment 0 needs no measurement, why the
// REAL measured landing pts is used instead of nominal $start, and why the
// timescale comes from THIS invocation's own generated mdhd rather than
// being probed from the source.
$audioTimescale = $result['timescale'];
if ($audioTimescale > 0) {
    if ($start > 0) {
        $realStartPtsTime = extractRealFragmentStartPtsTime($result['stderr'] ?? '', 'audio');
        $tfdtBaseSeconds = $realStartPtsTime !== null ? $realStartPtsTime : $start;
    } else {
        $tfdtBaseSeconds = 0;
    }
    patchAllFragmentTfdts($fragment, $tfdtBaseSeconds * $audioTimescale);
}

$tmpCachePath = $cachePath . '.tmp.' . getmypid();
@file_put_contents($tmpCachePath, $fragment);
@rename($tmpCachePath, $cachePath);

header('Content-Type: video/mp4');
header('Cache-Control: no-cache');
header('Content-Length: ' . strlen($fragment));
echo $fragment;

// See the matching comment in subtitle_video_segment.php: everything below
// is post-response bookkeeping the CLIENT never needs to wait on -
// getSourceDurationAndBitrate() pays a full fresh ffprobe spawn on a cold
// cache (a newly-resolved candidate's first segment), which was blocking
// the response before this.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

$probeResult = !empty($ffprobePath) ? getSourceDurationAndBitrate($videoUrl, $ffprobePath) : false;
$budgetBytes = computeStreamCacheBudgetBytes($probeResult !== false ? $probeResult['bitRateBps'] : 0);
enforceStreamCacheBudget($videoDir, $budgetBytes, $cachePath);

logSegmentOutcome('audio_' . $codec, $start, $len, 'success', round((microtime(true) - $requestStartTime) * 1000));

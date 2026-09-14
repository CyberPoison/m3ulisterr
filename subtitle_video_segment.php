<?php
// Renders one time-window fragment of the source's VIDEO track as a
// CMAF/fMP4 segment (moof+mdat) for subtitle_video_playlist.php's HLS
// playlist, via plain stream copy - no re-encoding, so HEVC/HDR10/DV
// metadata passes through byte-for-byte.
//
// Video-only: audio is a separate HLS AUDIO rendition group
// (subtitle_audio_segment.php) so a player can pick between the untouched
// TrueHD track and an AAC stereo fallback, instead of being forced into
// whichever one got muxed into this track - VLC (among others) can't
// decode TrueHD-in-MP4 ('mlpa') at all, so that could never be the only
// option.
//
// Every real segment needs the init segment (subtitle_video_init.php) served
// separately via #EXT-X-MAP - see runFragmentedMp4Segment() in
// hls_shared.php for why ffmpeg's output here is written to a real file
// (not piped) and has its own throwaway ftyp+moov discarded, keeping only
// the moof+mdat+mfra fragment.
//
// Params: video (urlencoded), start (seconds), len (seconds).
//
// Cache writes are atomic (temp file + rename) and only committed once the
// output was validated. Without this, an interrupted request (timeout,
// disconnect) or a seek landing on an awkward boundary can leave a
// broken/partial segment cached, which then gets served identically to
// every future request for it until the cache entry expires - exactly what
// a permanent "stuck" freeze or missing video looks like from the player.

error_reporting(0);
set_time_limit(0);
ob_end_clean();
// true, not the default false: we need the script to keep running past the
// point the client disconnects so runFragmentedMp4Segment()'s own
// connection_aborted() checks can fire and clean up (kill ffmpeg, discard
// the temp file) - with the default, PHP kills the script on the next
// failed write instead, abandoning the ffmpeg subprocess and leaving a
// stale temp file behind.
ignore_user_abort(true);

require_once 'config.php';
require_once 'hls_shared.php';
require_once 'm3ulisterr_lib.php';

// Lightweight, size-bounded outcome log - the goal isn't verbose debugging,
// it's being able to tell after the fact WHICH failure mode actually
// happened (timeout waiting on the source? disk full so nothing got
// cached? a stream came back invalid?) instead of only being able to guess
// from "it stopped working" reports with no data behind them.
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
// segment content (container format, codec handling, timestamp mode, output
// target, etc.) - otherwise a stale segment built by the old command keeps
// being served from cache for up to its TTL after deploy instead of being
// regenerated under the new logic.
$segmentCmdVersion = 16;

// Cache lives in RAM (getRamCacheDir() prefers /dev/shm), not the server's
// small SSD - segments are pure cache data, regenerable at any time, so
// there's no reason to spend disk space keeping them around. Each source
// gets its own subdirectory so enforceStreamCacheBudget() can size and prune
// its footprint independently, proportional to that source's own bitrate
// (see computeStreamCacheBudgetBytes()) rather than every title sharing one
// flat cap regardless of how large one segment of it actually is.
$cacheRoot = getRamCacheDir();
$videoDir = $cacheRoot . '/' . md5($videoUrl);
if (!is_dir($videoDir)) {
    @mkdir($videoDir, 0755, true);
}
$cacheKey = md5($videoUrl . '|video|' . $start . '|' . $len . '|v' . $segmentCmdVersion);
$cachePath = $videoDir . '/' . $cacheKey . '.m4s';

// Low-probability periodic sweep for whole per-source directories nobody's
// requested from in a while (abandoned/finished playback) - the per-request
// budget enforcement below only bounds a source that's still being actively
// fetched, it doesn't reclaim one that just stopped.
if (mt_rand(1, 20) === 1) {
    sweepAbandonedStreamCaches($cacheRoot);
}

// Cache hit: stream the existing file straight out. Short TTL relative to
// the old disk-cache version - RAM is the scarcer resource here, and a
// player revisiting a segment more than a couple minutes after it was first
// generated (rather than via normal forward playback) is closer to a
// deliberate backward seek than a cache-friendly access pattern anyway.
if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 300) {
    header('Content-Type: video/mp4');
    header('Cache-Control: no-cache');
    header('Content-Length: ' . filesize($cachePath));
    readfile($cachePath);
    logSegmentOutcome('video', $start, $len, 'cache_hit', round((microtime(true) - $requestStartTime) * 1000));
    exit;
}

$rawTmpPath = $videoDir . '/' . $cacheKey . '.raw.tmp.' . getmypid();

// -map 0:v:0 -c:v copy: zero re-encode cost, HDR10/DV metadata untouched.
//
// -copyts + -to (not the plain relative timestamps + -t an earlier version
// of this file used): real decode testing (opening the actual master
// playlist through ffmpeg's own HLS demuxer, well past just concatenating
// segment files - see below for why that earlier test method was invalid)
// showed that plain relative per-segment timestamps make ffmpeg's HLS
// demuxer log a "timestamp discontinuity" at EVERY segment boundary and
// apply a correction offset that compounds across segments until decode
// stalls outright (confirmed: consistently around 52-53s into playback,
// out_time simply stopped advancing). -copyts sidesteps that by giving every
// sample real absolute timestamps instead of ones that reset near zero each
// segment, so there's no discontinuity for the demuxer to "correct" in the
// first place. With -copyts, a duration limit has to be an absolute end
// time (-to), not a relative one (-t) - the preserved timestamps are
// measured from the source's own timeline, not from the seek point.
//
// -copyts alone isn't sufficient on its own, though: it reintroduces a
// separate, confirmed ffmpeg fragmented-MP4 muxer bug where the FIRST
// fragment of any -ss'd + -copyts'd output gets an incorrect tfdt (fragment
// decode time) regardless of the real seek position - reproduced on multiple
// unrelated sources via direct box-inspection, including a second internal
// fragment within the SAME segment being wrong in its own way. See
// patchAllFragmentTfdts() in hls_shared.php for the full fix (every internal
// fragment gets patched, chained from this one measured real start time) and
// extractMdhdTimescale() for why the timescale it needs comes from this
// invocation's own generated output, not a guess from the source.
//
// (For context: the earlier "no -copyts" version of this file was reverted
// FROM here based on a test that concatenated two segment files together
// (`cat seg0 seg1 | ffmpeg -i -`) and checked for decode warnings - not a
// valid stand-in for how a real HLS demuxer consumes multiple segments via
// an actual .m3u8, which is what actually caught both bugs described above.)
// -debug_ts is what makes it possible to learn where -ss REALLY landed - see
// extractRealFragmentStartPtsTime() in hls_shared.php for why that can't just
// be assumed to equal $start. CONTRARY to what this comment used to claim,
// it is NOT free: confirmed directly (real timed A/B runs against a real
// remote source) that it roughly DOUBLES this invocation's wall-clock time
// even for a genuine seek (11.5s -> 23.1s for the same -ss 60 request) - real
// per-segment cost paid on every single segment for the whole runtime of a
// title, not a one-off.
//
// -ss uses seekTargetWithMargin($start), NOT $start directly - see that
// function in hls_shared.php for why (a real Matroska seek-index quirk
// confirmed on a real source: landing exactly ON a keyframe's own timestamp
// is unreliable for MKV, landing a bit past it is not).
//
// Segment 0 skips BOTH -ss and -debug_ts entirely, rather than passing the
// literal values this comment used to: -ss 0 measured SLOWER than a genuine
// seek to a real non-zero position (confirmed directly: 22-35s for -ss 0 vs
// 11.5s for -ss 60 against the same source) - ffmpeg's demuxer apparently
// has costly special-case handling for seeking to exactly the start, not the
// fast no-op it conceptually is. And -debug_ts's real seek-landing pts is
// meaningless for segment 0 anyway - no seek happens, so the real start IS
// exactly 0 (see the tfdt-patching comment below) - so there's nothing here
// worth paying its overhead for.
$cmd = [$ffmpegPath, '-y'];
if ($start > 0) {
    $cmd[] = '-debug_ts';
}
$cmd[] = '-copyts';
if ($start > 0) {
    $cmd[] = '-ss';
    $cmd[] = (string) seekTargetWithMargin($start);
}
$cmd = array_merge($cmd, [
    '-i', $videoUrl,
    '-to', (string) ($start + $len),
    '-map', '0:v:0',
    '-c:v', 'copy',
    '-f', 'mp4',
    '-movflags', 'frag_keyframe+empty_moov+default_base_moof',
    $rawTmpPath,
]);

// Same unbounded-growth problem as the segment cache above, opened in append
// mode forever - keep it, since it's genuinely useful when something needs
// debugging, but cap its size so it can't become another slow disk-fill leak.
$ffmpegLogPath = sys_get_temp_dir() . '/subtitle_track_segment_ffmpeg.log';
if (@filesize($ffmpegLogPath) > 5 * 1024 * 1024) {
    @file_put_contents($ffmpegLogPath, '');
}

$stderrCapturePath = $videoDir . '/' . $cacheKey . '.stderr.tmp.' . getmypid();
$result = runFragmentedMp4Segment($cmd, $rawTmpPath, $ffmpegLogPath, $stderrCapturePath);

if ($result['fragment'] === false) {
    logSegmentOutcome('video', $start, $len, $result['outcome'], round((microtime(true) - $requestStartTime) * 1000));
    if ($result['outcome'] === 'client_aborted') {
        exit;
    }
    http_response_code(502);
    header('Content-Type: text/plain');
    echo "Could not produce this video segment.";
    exit;
}

// Used only to number this fragment consistently with its neighbors, not to
// affect timing/content. 0-based, not 1-based: confirmed directly in a real
// player's debug log that its internal counter expects the very first
// fragment it ever sees to be numbered 0 ("Fragment sequence discontinuity
// detected 1 != 0" logged against exactly that first fragment when this was
// 1-based). subtitle_video_playlist.php passes the real segment index
// explicitly via &seq= - segments are no longer uniform 8s apart (see
// buildKeyframeAlignedSegments() in hls_shared.php), so round($start / 8)
// can no longer be trusted to reconstruct it; that old formula remains only
// as a fallback for a request that somehow arrives without &seq=.
$sequenceNumber = isset($_GET['seq']) ? (int) $_GET['seq'] : (int) round($start / 8);

// Trim the successor's frames back off before anything else looks at the
// fragment - see dropOvershootFragments() in hls_shared.php. The small
// tolerance absorbs float noise in $len only; a real overshoot runs a whole
// inter-frame gap or more past the end, never a few microseconds.
// Handed over and immediately released from $result: these fragments run to
// tens of megabytes on a high-bitrate source, and holding a second reference
// for the rest of the request is exactly the kind of duplication that blew
// the memory limit before (see overwriteBytes() in hls_shared.php).
$fragment = $result['fragment'];
unset($result['fragment']);

if ($result['timescale'] > 0) {
    dropOvershootFragments($fragment, (int) round(($len + 0.005) * $result['timescale']));
}

patchFragmentSequenceNumbers($fragment, $sequenceNumber);

// Every internal fragment's tfdt gets patched, not just the first - see the
// big comment on patchAllFragmentTfdts() in hls_shared.php for why: a single
// segment can come back as MORE THAN ONE internal moof (frag_keyframe can
// split mid-segment), and the second one's tfdt turned out to be just as
// unreliable as the first's, just wrong in a different way (a
// fragment-relative value instead of absolute, rather than a flat 0).
//
// The base value fed in for the FIRST fragment is the REAL measured
// seek-landing pts (from this same invocation's own -debug_ts output) for
// $start > 0, NOT the nominal $start - input-side -ss with -c:v copy snaps
// back to the nearest keyframe, and that gap can be many seconds on some
// sources (confirmed directly: -ss 24 landing at real pts_time 13.89 on one
// real encode). Patching to nominal $start in that case would tell the
// demuxer this segment starts ~10s later than its content actually does,
// which is exactly what showed up as the picture visibly jumping backward
// at every segment boundary. Segment 0 needs no such measurement - -ss 0
// means no seek happened at all, so its real start IS exactly 0. Falls back
// to nominal $start only if the real pts couldn't be parsed out of stderr
// (should not happen in practice) - still better than not patching at all.
//
// $result['timescale'] comes straight from THIS invocation's own generated
// mdhd (see extractMdhdTimescale() in hls_shared.php) - NOT probed from the
// source, which would be wrong for an MKV source (confirmed: 1/1000
// reported for every stream regardless of real codec rate, while the real
// muxer output here was 1/16000 for this video track).
$videoTimescale = $result['timescale'];
if ($videoTimescale > 0) {
    if ($start > 0) {
        $realStartPtsTime = extractRealFragmentStartPtsTime($result['stderr'] ?? '', 'video');
        $tfdtBaseSeconds = $realStartPtsTime !== null ? $realStartPtsTime : $start;
    } else {
        $tfdtBaseSeconds = 0;
    }
    patchAllFragmentTfdts($fragment, $tfdtBaseSeconds * $videoTimescale);
}

// Temp file lives in the same directory as the final cache path so the
// rename() below is on the same filesystem, which is what makes it atomic -
// a reader either sees the old/no file or the complete new one, never a
// partial write.
$tmpCachePath = $cachePath . '.tmp.' . getmypid();
@file_put_contents($tmpCachePath, $fragment);
@rename($tmpCachePath, $cachePath);

header('Content-Type: video/mp4');
header('Cache-Control: no-cache');
header('Content-Length: ' . strlen($fragment));
echo $fragment;

// Everything below is post-response bookkeeping the CLIENT never needs to
// wait on - confirmed directly that it was blocking the response anyway
// without this: getSourceDurationAndBitrate() below is only "almost always a
// cache hit" AFTER a source's first segment, so the very first segment of
// any newly-resolved candidate pays a full fresh ffprobe spawn before the
// player ever saw its bytes, on top of everything else already slow about a
// cold request. Same fastcgi_finish_request() pattern already used for the
// keyframe probe in hls_shared.php.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Keep this source's RAM footprint proportional to its own bitrate (higher-
// bitrate remuxes get more headroom, since one segment of them is genuinely
// larger) rather than a flat per-title cap. Cheap to call here - the
// duration/bitrate probe is cached for 2 hours, so this is almost always a
// cache hit, not a fresh ffprobe spawn.
$probeResult = !empty($ffprobePath) ? getSourceDurationAndBitrate($videoUrl, $ffprobePath) : false;
$budgetBytes = computeStreamCacheBudgetBytes($probeResult !== false ? $probeResult['bitRateBps'] : 0);
enforceStreamCacheBudget($videoDir, $budgetBytes, $cachePath);

logSegmentOutcome('video', $start, $len, 'success', round((microtime(true) - $requestStartTime) * 1000));

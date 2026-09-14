<?php
// Trivial single-segment VOD subtitle media playlist wrapping one external
// .vtt URL, so it can be referenced by an #EXT-X-MEDIA:TYPE=SUBTITLES entry
// in subtitle_track_playlist.php's master playlist - HLS requires that URI
// to point at a playlist, not a raw subtitle file, directly.
//
// Params: sub (urlencoded .vtt URL), dur (total duration in seconds - the
// master playlist already knows this, so it's passed through rather than
// re-probed here).

error_reporting(0);
set_time_limit(0);
ob_end_clean();

// Either an upstream .vtt URL (the AIOStreams route) or an OpenSubtitles
// file id (the direct-API fallback - see getPortugueseSubtitlesWithFallback()
// in subtitle_track_playlist.php). The id stays unresolved here on purpose:
// turning it into a real file costs a quota'd download, so that waits until
// the segment below is actually requested.
$osFileId = isset($_GET['osfile']) ? (int) $_GET['osfile'] : 0;
$subUrl = $_GET['sub'] ?? '';

if ($osFileId <= 0 && empty($subUrl)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Missing the sub parameter.";
    exit;
}
// Fallback covers any reasonable movie/episode length if duration wasn't
// passed through for some reason.
$duration = isset($_GET['dur']) && (float) $_GET['dur'] > 0 ? (float) $_GET['dur'] : 4 * 3600;

$lines = [];
$lines[] = '#EXTM3U';
$lines[] = '#EXT-X-VERSION:3';
$lines[] = '#EXT-X-TARGETDURATION:' . (int) ceil($duration);
$lines[] = '#EXT-X-PLAYLIST-TYPE:VOD';
$lines[] = '#EXT-X-MEDIA-SEQUENCE:0';
// subtitle_vtt_segment.vtt (not the raw upstream URL directly) - see that
// file for why: the upstream URL ends in a query string, not ".vtt", which
// strict HLS parsers (confirmed against ffmpeg's own HLS demuxer) reject
// outright as "not a real segment", failing this whole playlist to parse.
$lines[] = '#EXTINF:' . number_format($duration, 3, '.', '') . ',';
$lines[] = $osFileId > 0
    ? 'subtitle_vtt_segment.vtt?osfile=' . urlencode((string) $osFileId)
    : 'subtitle_vtt_segment.vtt?sub=' . urlencode($subUrl);
$lines[] = '#EXT-X-ENDLIST';

header('Content-Type: application/vnd.apple.mpegurl');
header('Cache-Control: no-cache');
echo implode("\n", $lines) . "\n";

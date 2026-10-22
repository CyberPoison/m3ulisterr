<?php
// Redirects to the real upstream .vtt URL under a same-origin URL that
// itself ends in .vtt - referenced as the single "segment" in
// subtitle_vtt_playlist.php's media playlist.
//
// This exists because strict HLS parsers (confirmed directly against
// ffmpeg's own HLS demuxer, and VLC shares enough lineage that it's the
// leading suspect for a total playback stall reported against it) reject a
// declared segment URI outright if it doesn't look like a recognized
// extension - the raw OpenSubtitles URL
// ("https://.../sub.vtt/?lang_code=...&sub_id=...") ends in its query
// string, not ".vtt", which such a parser reads as "not a real .vtt
// segment" and can fail the ENTIRE subtitle rendition's playlist to parse -
// and on some players that failure isn't graceful (skip just that track),
// it takes the whole master playlist down with it, matching a report of no
// video/audio ever starting at all. A redirect is enough to fix this: the
// extension check happens on the URI as written in the playlist, before any
// request is made, so a same-origin .vtt-suffixed URL passes it regardless
// of where it then redirects to - no need to actually proxy/re-serve the
// subtitle bytes ourselves.
//
// Params: sub - urlencoded upstream .vtt URL.

error_reporting(0);

// OpenSubtitles-API fallback track: resolve the file id to real WebVTT and
// serve it inline rather than redirecting. It can't be a redirect like the
// branch below - the API hands back a temporary link to an SRT, which no
// player would accept as a WebVTT segment. openSubtitlesFetchVtt() does the
// conversion and caches the result so the 20/day download quota is only ever
// touched the first time anyone opens this particular subtitle.
if (isset($_GET['osfile']) && (int) $_GET['osfile'] > 0) {
    require_once 'config.php';
    require_once 'hls_shared.php';
    require_once 'm3ulisterr_lib.php';

    $vtt = openSubtitlesFetchVtt((int) $_GET['osfile']);
    if (function_exists('m3uLogEvent')) {
        m3uLogEvent('subtitle', ['source' => 'opensubtitles-api', 'osfile' => (int) $_GET['osfile'], 'ok' => $vtt !== false]);
    }

    header('Content-Type: text/vtt; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    // An empty-but-valid cue list beats a 4xx here: a failed subtitle should
    // leave the movie playing without captions, not fail the track and send
    // the player cascading through every remaining option.
    echo $vtt !== false ? $vtt : "WEBVTT\n\n";
    exit;
}

if (!isset($_GET['sub']) || empty($_GET['sub'])) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Missing the sub parameter.";
    exit;
}

$subUrl = $_GET['sub'];
if (!preg_match('#^https?://#i', $subUrl)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Invalid sub URL.";
    exit;
}

@include_once 'm3ulisterr_lib.php';
if (function_exists('m3uLogEvent')) {
    m3uLogEvent('subtitle', ['source' => 'aiostreams', 'sub' => substr($subUrl, 0, 300)]);
}

// Fetch, normalise and serve the WebVTT ourselves (cached for a day): the upstream proxy glues two WEBVTT
// documents together and ExoPlayer renders no cue from that - see hlsCleanWebVtt(). Falls back to the plain
// redirect (what every player got before) when the upstream cannot be fetched.
require_once 'hls_shared.php';
$cacheFile = sys_get_temp_dir() . '/subtitle_vtt_clean_' . sha1($subUrl) . '.vtt';
$clean = null;
if (is_file($cacheFile) && filesize($cacheFile) > 0 && (time() - filemtime($cacheFile)) < 86400) {
    $clean = (string) file_get_contents($cacheFile);
} else {
    $safe = true;
    if (function_exists('m3uIsSafeRemoteUrl')) {
        $safe = m3uIsSafeRemoteUrl($subUrl);
    }
    if ($safe) {
        $ch = curl_init($subUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw !== false && $status === 200 && strpos((string) $raw, '-->') !== false) {
            $clean = hlsCleanWebVtt($raw);
            @file_put_contents($cacheFile, $clean);
        }
    }
}

if ($clean !== null) {
    header('Content-Type: text/vtt; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    header('Content-Length: ' . strlen($clean));
    echo $clean;
    exit;
}

header('HTTP/1.1 302 Found');
header('Location: ' . $subUrl);

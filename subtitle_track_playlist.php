<?php
// Master HLS playlist: exposes every available European Portuguese (pt-PT)
// AND Brazilian Portuguese (pt-BR) subtitle from OpenSubtitles (via
// AIOStreams) as its own selectable #EXT-X-MEDIA subtitle rendition (named
// after its real release title), alongside a single video variant that's a
// plain stream-copy passthrough of the source.
//
// This replaces the earlier "burn subtitles into the video" approach: that
// meant decoding and re-encoding every frame (real CPU/RAM cost per viewer).
// This approach never touches the video bytes at all - the player gets the
// original stream (via subtitle_video_playlist.php's stream-copy segments)
// and picks whichever subtitle track it wants, like any normal multi-track
// video. The trade-off: the player, not the server, has to decode whatever
// codec the source actually uses.
//
// Params:
//   video - urlencoded source video URL
//   imdb  - IMDb id (tt...), needed for the /subtitles lookup (that AIOStreams
//           resource only accepts imdb/kitsu ids, not tmdb:)
//   type, season, episode - only present/used for series

error_reporting(0);
set_time_limit(0);
ob_end_clean();

require_once 'config.php';
require_once 'hls_shared.php';
require_once 'm3ulisterr_lib.php';

if (!isset($_GET['video']) || empty($_GET['video'])) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Missing the video parameter.";
    exit;
}

$videoUrl = $_GET['video'];
$imdbId = $_GET['imdb'] ?? '';
$type = $_GET['type'] ?? 'movies';
$season = $_GET['season'] ?? '';
$episode = $_GET['episode'] ?? '';
$mediaTitle = $_GET['title'] ?? '';
$mediaYear = $_GET['year'] ?? '';

if (!preg_match('#^https?://#i', $videoUrl)) {
    $videoUrl = rtrim(locateBaseURL(), '/') . '/' . ltrim($videoUrl, '/');
}

function fallbackToPlainVideoRedirectTP($videoUrl) {
    header('HTTP/1.1 302 Found');
    header('Location: ' . $videoUrl);
    exit;
}

$ffprobePath = trim((string) shell_exec('command -v ffprobe 2>/dev/null'));
$ffmpegPath = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
if (empty($ffprobePath) || empty($ffmpegPath)) {
    fallbackToPlainVideoRedirectTP($videoUrl);
}

// Started now (async) and only waited on after the subtitle lookup below, so
// the ffprobe call and the AIOStreams API call run concurrently instead of
// back to back - meaningfully faster time-to-first-byte on playlist load.
$durationProbe = startDurationProbe($videoUrl, $ffprobePath);

// Every European (pt-pt) AND Brazilian (pt-br) Portuguese subtitle
// AIOStreams' OpenSubtitles integration has for this title, kept as its own
// selectable option rather than picking just one - subtitle-to-release sync
// is often imperfect, so letting the viewer pick the one that actually
// matches what they're watching (and which dialect they prefer) beats
// guessing on their behalf.
function getAllPortugueseSubtitles($imdbId, $type, $season, $episode) {
    global $frenchAioStreamsUrl;

    if (empty($frenchAioStreamsUrl) || empty($imdbId)) {
        return [];
    }

    $baseUrl = rtrim($frenchAioStreamsUrl, '/');
    if ($type == 'series' && $season !== '' && $episode !== '') {
        $subsUrl = $baseUrl . '/subtitles/series/' . $imdbId . ':' . $season . ':' . $episode . '.json';
    } else {
        $subsUrl = $baseUrl . '/subtitles/movie/' . $imdbId . '.json';
    }

    $response = curlGetSimple($subsUrl);
    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);
    if (!$data || empty($data['subtitles'])) {
        return [];
    }

    // 'pt-pt' (European Portuguese) and 'pt-br' (Brazilian Portuguese) are
    // both offered as selectable tracks - explicitly requested, on top of
    // pt-pt-only being what this used to do. Each keeps its own real HLS
    // LANGUAGE tag (see $hlsLanguageTag below) rather than being merged into
    // one, since they're genuinely different dialects a viewer would want to
    // tell apart.
    $acceptedLangCodes = ['pt-pt', 'pt-br'];

    $results = [];
    foreach ($data['subtitles'] as $sub) {
        if (empty($sub['url'])) {
            continue;
        }
        $langCode = strtolower($sub['lang_code'] ?? '');
        if (!in_array($langCode, $acceptedLangCodes, true)) {
            continue;
        }
        $name = trim((string) ($sub['title'] ?? $sub['id'] ?? 'Portuguese'));
        if ($name === '') {
            $name = 'Portuguese';
        }
        $results[] = [
            'url' => $sub['url'],
            'name' => $name,
            'trusted' => !empty($sub['from_trusted']),
            'langCode' => $langCode,
            'hlsLanguageTag' => $langCode === 'pt-br' ? 'pt-BR' : 'pt-PT',
        ];
    }

    // Trusted OpenSubtitles uploads first, so the DEFAULT-selected track (the
    // first one) is a reasonable one; pt-PT ranks ahead of pt-BR as a
    // tie-break (the original, primary target of this feature), but every
    // option in both dialects remains listed.
    usort($results, function ($a, $b) {
        if ($a['trusted'] !== $b['trusted']) {
            return ($b['trusted'] ? 1 : 0) <=> ($a['trusted'] ? 1 : 0);
        }
        return ($a['langCode'] === 'pt-br' ? 1 : 0) <=> ($b['langCode'] === 'pt-br' ? 1 : 0);
    });

    // Capped, not unlimited: confirmed directly in a real player's debug log
    // that when an early subtitle choice fails (the free OpenSubtitles proxy
    // this depends on drops/rate-limits a real fraction of requests - seen
    // as TLS handshake failures, not anything on our end), the player
    // cascades through EVERY remaining option one at a time before it even
    // starts fetching video/audio. With titles that have dozens of pt-pt
    // matches, that cascade alone measured well over a minute of dead time
    // before playback began - worse the more options are listed, since
    // every one of them is a potential point of failure in that chain.
    // Trusted-first sorting above means capping still keeps the most likely-
    // good options.
    return array_slice($results, 0, 5);
}

// AIOStreams bundles its own OpenSubtitles provider, and when that provider
// is down it answers with a single pseudo-subtitle whose "lang" reads
// "[X] OpenSubtitles V3+ - fetch failed" (or "- 500 - Internal"). It carries
// no lang_code, so getAllPortugueseSubtitles() correctly discards it - and
// the movie then silently plays with no subtitle tracks whatsoever, which is
// exactly how a real "Black Widow has no subtitles" report started. Going
// straight to OpenSubtitles' own API in that case restores the tracks.
//
// Capped tighter than the AIOStreams path (3 vs 5): each of these costs a
// quota'd download the first time a viewer opens it, so a shorter list keeps
// a player's failure-cascade from spending the day's allowance.
function getPortugueseSubtitlesWithFallback($imdbId, $type, $season, $episode) {
    $subtitles = getAllPortugueseSubtitles($imdbId, $type, $season, $episode);
    if (!empty($subtitles)) {
        return $subtitles;
    }

    return array_slice(openSubtitlesSearchPortuguese($imdbId), 0, 3);
}

$subtitles = getPortugueseSubtitlesWithFallback($imdbId, $type, $season, $episode);

// Only now do we actually wait on the probe, if it hasn't finished already -
// see the comment where it was started above.
$probeResult = finishDurationProbe($durationProbe);
if ($probeResult === false) {
    fallbackToPlainVideoRedirectTP($videoUrl);
}
$duration = $probeResult['duration'];

// Must be decided here, before any HLS playlist is handed to the client -
// an #EXT-X-STREAM-INF variant URI has to resolve to another playlist per
// the HLS spec, so once a player has committed to this master playlist it's
// too late to bail out to a raw-file redirect at the video-playlist stage
// (that's exactly what broke playback: a client parses this file expecting
// HLS all the way down, then hits a 302 straight to a multi-GB source file
// where it expected #EXTM3U text, which isn't valid HLS and fails to open).
// Checking here means an unreliable file skips HLS altogether - the client
// never even sees a playlist for it.
if (!isSeekReliable($videoUrl, $duration, $ffmpegPath, $ffprobePath)) {
    fallbackToPlainVideoRedirectTP($videoUrl);
}

// Two selectable audio renditions: the source audio untouched (whatever
// codec it actually is - often something like TrueHD Atmos 7.1 in this
// catalog) and an AAC stereo fallback. Not muxed together into one track:
// plenty of real players can't decode a codec like TrueHD-in-MP4 ('mlpa')
// at all - confirmed directly against VLC, which threw exactly that "codec
// not supported" error - so forcing everyone onto a single track would mean
// many players get no audio at all instead of a working stereo fallback.
// Which audio track to actually serve. A multi-audio release routinely puts
// something other than the requested language first, so this picks by the
// track's own language tag instead of taking 0:a:0 on faith - see
// pickAudioStreamForLanguage() in hls_shared.php for the reports behind it.
$requestedAudioLang = isset($_GET['lang']) && trim($_GET['lang']) !== ''
    ? strtolower(trim($_GET['lang']))
    : 'en';
list($audioTrackIndex, $audioTrackLanguage) = pickAudioStreamForLanguage(
    $probeResult['audioStreams'] ?? [],
    $requestedAudioLang
);

// The HLS LANGUAGE attribute now reflects what the track REALLY is. It used
// to be hardcoded "eng" for every source, which is what made a wrong-language
// file look like a correct one to the viewer - the player dutifully showed
// "English" over Italian audio, so the substitution was invisible until
// someone listened. An untagged track stays "und" rather than being claimed
// as English.
$audioHlsLanguage = $audioTrackLanguage !== '' ? $audioTrackLanguage : 'und';

$originalAudioName = 'Original';
if (!empty($probeResult['audioCodecName'])) {
    $originalAudioName = buildAudioRenditionName(
        $probeResult['audioCodecName'],
        $probeResult['audioChannels'],
        $probeResult['audioChannelLayout'],
        $probeResult['audioProfile']
    );
}
// AAC first and DEFAULT=YES: this is what the overwhelming majority of
// players/devices actually need to get working audio at all - the
// original-codec track is a selectable extra for the minority of setups
// (mainly AVR/soundbar passthrough) that can use it, not the default
// everyone is silently opted into.
$audioRenditions = [
    ['codec' => 'aac', 'name' => 'Stereo (AAC)', 'default' => true],
    ['codec' => 'original', 'name' => $originalAudioName, 'default' => false],
];

// Release-group tag (e.g. "GeneMige") comes straight off the source
// filename, not from TMDB, so it's derived here rather than threaded down
// from play.php like title/year are.
$mediaEncoder = extractReleaseGroupFromUrl($videoUrl);

// Carried through to the video/audio media playlists and from there into
// the init-segment generators (subtitle_video_init.php,
// subtitle_audio_init.php) - MP4 metadata (title/date/encoder) can only
// live in a moov box, which only the init segments have, so it has to be
// threaded all the way down rather than set once here.
$metadataQueryString = '';
if ($mediaTitle !== '') {
    $metadataQueryString .= '&title=' . urlencode($mediaTitle);
}
if ($mediaYear !== '') {
    $metadataQueryString .= '&year=' . urlencode($mediaYear);
}
if ($mediaEncoder !== '') {
    $metadataQueryString .= '&encoder=' . urlencode($mediaEncoder);
}

$lines = [];
$lines[] = '#EXTM3U';
$lines[] = '#EXT-X-VERSION:7';

foreach ($audioRenditions as $rendition) {
    $safeName = str_replace(['"', "\r", "\n"], '', $rendition['name']);
    $audioPlaylistUrl = 'subtitle_audio_playlist.m3u8?video=' . urlencode($videoUrl) . '&codec=' . urlencode($rendition['codec']) . '&aidx=' . $audioTrackIndex . $metadataQueryString;
    $lines[] = '#EXT-X-MEDIA:TYPE=AUDIO,GROUP-ID="audio",NAME="' . $safeName . '",LANGUAGE="' . $audioHlsLanguage . '",DEFAULT='
        . ($rendition['default'] ? 'YES' : 'NO') . ',AUTOSELECT=YES,URI="' . $audioPlaylistUrl . '"';
}

foreach ($subtitles as $index => $sub) {
    // HLS attribute-list values are quoted strings - strip characters that
    // would break that quoting or the line itself.
    $safeName = str_replace(['"', "\r", "\n"], '', $sub['name']);
    // Fallback entries (see getPortugueseSubtitlesWithFallback()) have no URL
    // yet - only a file id, which stays unresolved until the viewer opens the
    // track, because resolving it spends a quota'd download.
    $subSource = isset($sub['osFileId'])
        ? 'osfile=' . urlencode((string) $sub['osFileId'])
        : 'sub=' . urlencode($sub['url']);
    $subPlaylistUrl = 'subtitle_vtt_playlist.m3u8?' . $subSource
        . '&dur=' . urlencode(number_format($duration, 3, '.', ''));
    // Each track keeps its own real language tag (pt-PT vs pt-BR) rather
    // than everything being labeled pt-PT, now that both dialects are
    // offered - a player's language picker should show them as genuinely
    // different options, not two identical-looking "Portuguese" entries.
    $languageTag = $sub['hlsLanguageTag'] ?? 'pt-PT';
    $lines[] = '#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID="subs",NAME="' . $safeName . '",LANGUAGE="' . $languageTag . '",DEFAULT='
        . ($index === 0 ? 'YES' : 'NO') . ',AUTOSELECT=YES,FORCED=NO,URI="' . $subPlaylistUrl . '"';
}

$videoPlaylistUrl = 'subtitle_video_playlist.m3u8?video=' . urlencode($videoUrl) . $metadataQueryString;

// RESOLUTION and VIDEO-RANGE matter beyond being informational: strict HLS
// clients (Apple's AVFoundation - QuickTime, Safari, tvOS - in particular)
// can default to assuming SDR for HDR10/PQ content when VIDEO-RANGE is
// missing, which renders as a washed-out, wrong-hue image rather than
// either failing cleanly or tone-mapping correctly.
$streamInfAttrs = ['BANDWIDTH=8000000'];
if (!empty($probeResult['width']) && !empty($probeResult['height'])) {
    $streamInfAttrs[] = 'RESOLUTION=' . $probeResult['width'] . 'x' . $probeResult['height'];
}
if (!empty($probeResult['colorTransfer'])) {
    $streamInfAttrs[] = 'VIDEO-RANGE=' . hlsVideoRangeFromColorTransfer($probeResult['colorTransfer']);
}
$streamInfAttrs[] = 'AUDIO="audio"';
if (!empty($subtitles)) {
    $streamInfAttrs[] = 'SUBTITLES="subs"';
}
$lines[] = '#EXT-X-STREAM-INF:' . implode(',', $streamInfAttrs);
$lines[] = $videoPlaylistUrl;

// Analytics: the audio track and subtitle tracks this player was actually
// offered, plus the source duration (the dashboard divides the furthest
// segment position by it to get playback %). Subtitle names/langs are recorded
// so the operator can see which subtitle was picked up per release.
if (function_exists('m3uLogEvent')) {
    m3uLogEvent('playlist', [
        'media' => m3uMediaHash($videoUrl),
        'imdb' => $imdbId,
        'title' => $mediaTitle,
        'year' => $mediaYear,
        'mediaType' => $type,
        'duration' => round((float) $duration, 3),
        'audioLang' => $audioHlsLanguage,
        'audioIndex' => $audioTrackIndex,
        'subtitles' => array_map(function ($sub) {
            return ['lang' => $sub['hlsLanguageTag'] ?? ($sub['langCode'] ?? ''), 'name' => $sub['name'] ?? '', 'osfile' => $sub['osFileId'] ?? null];
        }, $subtitles),
        'subCount' => count($subtitles),
    ]);
}

header('Content-Type: application/vnd.apple.mpegurl');
header('Cache-Control: no-cache');
echo implode("\n", $lines) . "\n";

// Kicked off as early as possible, not left until subtitle_video_playlist.php
// first asks for it - see scheduleKeyframeProbe() in hls_shared.php for why
// it must never block a response, and getKeyframeTimestamps() for why it
// can't be made fast (confirmed directly: this demuxer needs to read
// essentially the WHOLE file - measured 2.2GB for one real BluRay remux -
// to reliably flag keyframes, there's no shortcut). This IS the master
// playlist, the very first thing a player requests, well before it ever
// asks for the video variant playlist that actually needs this data - so
// starting the probe here instead of there gives it a real head start
// (this script's own response time, PLUS whatever the player takes to move
// from master to variant playlist) against that 30-40s cost, without
// changing anything about the fallback behavior itself for whichever
// request the probe doesn't finish before.
if (empty(getKeyframeTimestamps($videoUrl, $ffprobePath))) {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    scheduleKeyframeProbe($videoUrl, $ffprobePath);
}

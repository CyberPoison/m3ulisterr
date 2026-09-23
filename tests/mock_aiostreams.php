<?php
// Router for `php -S`: a fake AIOStreams that serves /stream/movie/tmdb:<id>.json
// from fixtures and counts hits per id (hits/<id>) so tests can prove caching.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!preg_match('#/stream/movie/tmdb:(\d+)\.json$#', $path, $m)) {
    http_response_code(404);
    exit;
}
$id = (int) $m[1];
$dir = getenv('MOCK_STATE_DIR') ?: sys_get_temp_dir();
@mkdir("$dir/hits", 0777, true);
file_put_contents("$dir/hits/$id", "x", FILE_APPEND);

function s($tag, $res, $codec, $langs, $filename = '') {
    return ['url' => 'https://example.invalid/playback/' . md5("$tag$res$codec$langs"),
        'name' => "[$tag] Torrentio {$res}p",
        'description' => "🎞️ $codec 🔊 AC3\n🌎 $langs 📝 English",
        'behaviorHints' => ['filename' => $filename]];
}
header('Content-Type: application/json');
switch ($id) {
    case 1001: echo json_encode(['streams' => [s('AD⚡', 1080, 'AVC', 'French | English')]]); break;
    case 1002: echo json_encode(['streams' => [s('AD⏳', 1080, 'AVC', 'French')]]); break;
    case 1003: http_response_code(500); echo 'boom'; break;
    case 1004: echo json_encode(['streams' => []]); break;
    case 1005: echo json_encode(['streams' => [s('PM⚡', 720, 'HEVC', 'French'), s('AD⚡', 480, 'AVC', 'French')]]); break;
    case 1006: echo json_encode(['streams' => [s('AD⚡', 1080, 'AVC', 'English')]]); break;
    case 1007: echo 'not json at all'; break;
    case 1008: echo json_encode(['streams' => [s('AD⚡', 480, 'AVC', 'French')]]); break;
    // Real TMDB id (Oppenheimer, 2023): one release of another year, one right.
    case 872585: echo json_encode(['streams' => [s('AD⚡', 1080, 'AVC', 'French', 'Oppenheimer.1995.1080p.mkv'), s('PM⚡', 720, 'AVC', 'French', 'Oppenheimer.2023.720p.mkv')]]); break;
    default: echo json_encode(['streams' => []]);
}

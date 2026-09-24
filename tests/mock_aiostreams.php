<?php
// Router for `php -S`: a fake AIOStreams that serves /stream/movie/tmdb:<id>.json
// from fixtures and counts hits per id (hits/<id>) so tests can prove caching.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$kind = 'movie';
$season = 0;
$episode = 0;
if (preg_match('#/stream/series/tmdb:(\d+):(\d+):(\d+)\.json$#', $path, $m)) {
    $kind = 'series';
    [$season, $episode] = [(int) $m[2], (int) $m[3]];
} elseif (!preg_match('#/stream/movie/tmdb:(\d+)\.json$#', $path, $m)) {
    http_response_code(404);
    exit;
}
$id = (int) $m[1];
if ($id >= 4000 && $id < 4100) {
    usleep(1000000); // 1s upstream latency, for the parallelism timing test
    header('Content-Type: application/json');
    echo json_encode(['streams' => [s('AD⚡', 1080, 'AVC', 'French')]]);
    exit;
}
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
function errStream($desc) { return ['name' => '[❌] Torrentio', 'description' => $desc, 'streamData' => ['type' => 'error', 'error' => ['title' => '[❌] Torrentio', 'description' => $desc]]]; }
if ($kind === 'series') {
    // 3001: S1E1 cached. 3002: only S2E1 cached. 3003: nothing. 3004: S1E1 uncached only, S2E1 nothing.
    $has = ($id === 3001 && $season === 1) || ($id === 3002 && $season === 2);
    if ($id === 3004 && $season === 1) { echo json_encode(['streams' => [s('AD⏳', 1080, 'AVC', 'French')]]); exit; }
    echo json_encode(['streams' => $has ? [s('AD⚡', 1080, 'AVC', 'French')] : []]);
    exit;
}
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
    // 5001: blank on the FIRST ask only (a transient empty), then normal. 5002: always blank (a real "none").
    case 5001: echo json_encode(['streams' => filesize("$dir/hits/$id") === 1 ? [] : [s('AD⚡', 1080, 'AVC', 'French')]]); break;
    case 5002: echo json_encode(['streams' => []]); break;
    case 2001: echo json_encode(['streams' => [['name' => '[🐢] AIOStreams', 'title' => 'AIOStreams public rate-limit exceeded 🐢', 'url' => 'https://elfhosted.com/assets/public-rate-limit-exceeded.mp4']]]); break;
    case 2002: echo json_encode(['streams' => [errStream('Request timed out')]]); break;
    case 2003: echo json_encode(['streams' => [errStream('Failed to get metadata'), errStream('Failed to get metadata')]]); break;
    case 2004: echo json_encode(['streams' => [errStream('Request timed out'), s('AD⚡', 1080, 'AVC', 'French')]]); break;
    default: echo json_encode(['streams' => []]);
}

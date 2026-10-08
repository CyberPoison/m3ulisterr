<?php
// Mock AIOStreams /playback/ + debrid CDN for tests/aio_debrid_services_test.php.
// Run: php -S 127.0.0.1:<port> tests/mock_aio_playback.php   (needs several workers:
// PHP_CLI_SERVER_WORKERS=8, because some endpoints deliberately sleep).
//
//   /fast        307 -> /cdn/video.mp4     (what AllDebrid / Premiumize look like)
//   /rd_force    307 -> /cdn/force.mkv     (Real-Debrid CDN: Content-Type application/force-download)
//   /rd_slow     sleeps 3s, then like /rd_force  (Real-Debrid's slow FIRST resolve)
//   /hang        never answers within a test's timeout
//   /slate       307 -> a url containing slate.elfhosted.com (AIOStreams' error placeholder)
//   /json200     200 application/json              (an error body that is not a video)
//   /e404        404
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = 'http://127.0.0.1:' . $_SERVER['SERVER_PORT'];

// A 50 MB file of zeros that honours Range, like a real CDN.
function mockCdn($contentType) {
    $total = 50 * 1000 * 1000;
    $start = 0;
    $end = $total - 1;
    if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'] ?? '', $m)) {
        $start = (int) $m[1];
        $end = $m[2] !== '' ? min((int) $m[2], $total - 1) : $total - 1;
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$total");
    }
    header('Content-Type: ' . $contentType);
    header('Accept-Ranges: bytes');
    $len = $end - $start + 1;
    header('Content-Length: ' . $len);
    for ($sent = 0; $sent < $len; $sent += 65536) {
        echo str_repeat("\0", min(65536, $len - $sent));
    }
}

switch ($path) {
    case '/fast':
        header('Location: ' . $base . '/cdn/video.mp4', true, 307);
        break;
    case '/rd_force':
        header('Location: ' . $base . '/cdn/force.mkv', true, 307);
        break;
    case '/rd_slow':
        sleep(3);
        header('Location: ' . $base . '/cdn/force.mkv', true, 307);
        break;
    case '/hang':
        sleep(8);
        break;
    case '/slate':
        header('Location: ' . $base . '/x/slate.elfhosted.com/s.mp4', true, 307);
        break;
    case '/json200':
        header('Content-Type: application/json');
        echo '{"error":"nope"}';
        break;
    case '/e404':
        http_response_code(404);
        break;
    case '/cdn/video.mp4':
    case '/x/slate.elfhosted.com/s.mp4':
        mockCdn('video/mp4');
        break;
    case '/cdn/force.mkv':
        mockCdn('application/force-download');
        break;
    default:
        http_response_code(404);
}

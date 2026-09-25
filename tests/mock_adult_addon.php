<?php
// Mock Stremio adult addon for tests/adult_addon_test.php. Serves a manifest,
// catalog searches and stream lists from fixtures, under a fake config token
// (TOKEN_SECRET) that must never surface anywhere in the app's own output.
// Run: php -S 127.0.0.1:<port> tests/mock_adult_addon.php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$log = getenv('MOCK_ADDON_LOG');
if ($log) {
    file_put_contents($log, $path . "\n", FILE_APPEND);
}
header('Content-Type: application/json');
$H1 = str_repeat('a', 40); $H2 = str_repeat('b', 40); $H3 = str_repeat('c', 40); $H4 = str_repeat('d', 40);

if (preg_match('#/manifest\.json$#', $path)) {
    if (getenv('MOCK_ADDON_DOWN')) { http_response_code(500); exit; }
    echo json_encode([
        'id' => 'mock.adult', 'resources' => ['catalog', ['name' => 'stream', 'types' => ['Porn']]],
        'catalogs' => [
            ['type' => 'Porn', 'id' => 'recent', 'extra' => [['name' => 'skip']]],
            ['type' => 'Porn', 'id' => 'search', 'extra' => [['name' => 'search', 'isRequired' => true]]],
            ['type' => 'Porn', 'id' => 'tpdb_search', 'extra' => [['name' => 'search']]],
            ['type' => 'Live', 'id' => 'cams_search', 'extra' => [['name' => 'search']]],
        ],
    ]);
    exit;
}
if (preg_match('#/catalog/Porn/(tpdb_search|search)/search=(.*)\.json$#', $path, $m)) {
    $q = strtolower($m[2]);
    if ($m[1] === 'tpdb_search' && str_contains($q, 'latina pink')) {
        echo json_encode(['metas' => [
            ['id' => 'porndb:1', 'name' => 'Latina Pink'],
            ['id' => 'porndb:2', 'name' => 'Something Unrelated Entirely'],
        ]]);
    } elseif ($m[1] === 'search' && str_contains($q, 'latina pink')) {
        echo json_encode(['metas' => [['id' => 'jstrm:xyz', 'name' => 'Latina Pink 1080p']]]);
    } else {
        echo json_encode(['metas' => []]);
    }
    exit;
}
if (preg_match('#/stream/Porn/porndb:1\.json$#', $path)) {
    echo json_encode(['streams' => [
        // addon-play link: hash must be extracted, URL (with token) discarded
        ['name' => '[PM] 720p', 'title' => 'Latina.Pink.720p 1.2 GiB 40 seeds', 'url' => "https://mock.invalid/TOKEN_SECRET/_play/pmKey/$H1"],
        ['name' => '🧲 Torrent', 'title' => 'Latina.Pink.1080p 2.1 GiB 12 seeds', 'infoHash' => strtoupper($H2)],
        ['name' => 'tiny', 'title' => 'Latina.Pink.trailer 4 MB 900 seeds', 'infoHash' => $H3],
        ['name' => 'no hash', 'title' => 'x', 'url' => 'https://mock.invalid/TOKEN_SECRET/other'],
    ]]);
    exit;
}
if (preg_match('#/stream/Porn/jstrm:xyz\.json$#', $path)) {
    echo json_encode(['streams' => [['name' => '🧲', 'title' => 'dup 1080p 2.1 GiB 12 seeds', 'infoHash' => $H2], ['name' => '🧲', 'title' => 'other 480p 700 MB 3 seeds', 'infoHash' => $H4]]]);
    exit;
}
http_response_code(404);
echo '{}';

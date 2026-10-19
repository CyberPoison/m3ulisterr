<?php
// Mock of the four debrid APIs, just enough for tests/debrid_cleanup_test.php.
// Run: MOCK_DEBRID_STATE=/tmp/state.json php -S 127.0.0.1:<port> tests/mock_debrid_api.php
// State file (JSON): {"rd_torrents":[ids],"rd_downloads":[ids],"pm_transfers":[ids],"pm_items":[{"id":..,"type":..}],
//                     "ad_magnets":[ids],"ad_links":[urls],"ad_history_cleared":false,"tb_torrents":[ids],"calls":[...]}
$stateFile = getenv('MOCK_DEBRID_STATE');
$state = json_decode((string) @file_get_contents($stateFile), true) ?: [];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$state['calls'][] = "$method $path";
$save = function () use (&$state, $stateFile) {
    file_put_contents($stateFile, json_encode($state));
};
$out = function ($data, $code = 200) use ($save) {
    $save();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
};
$ids = function ($key) use (&$state) {
    return array_map(fn($i) => ['id' => $i], array_values($state[$key] ?? []));
};
$drop = function ($key, $id) use (&$state) {
    $state[$key] = array_values(array_filter($state[$key] ?? [], fn($x) => (string) (is_array($x) ? $x['id'] : $x) !== (string) $id));
};
$body = json_decode((string) file_get_contents('php://input'), true) ?: [];

// ---- Real-Debrid
if (preg_match('#^/rd/(torrents|downloads)$#', $path, $m)) {
    $page = (int) ($_GET['page'] ?? 1);
    $out($page === 1 ? array_slice($ids('rd_' . $m[1]), 0, 100) : []);
}
if ($method === 'DELETE' && preg_match('#^/rd/(torrents|downloads)/delete/(.+)$#', $path, $m)) {
    $drop('rd_' . $m[1], urldecode($m[2]));
    $save();
    http_response_code(204);
    exit;
}
// ---- Premiumize
if ($path === '/pm/transfer/list') {
    $out(['status' => 'success', 'transfers' => $ids('pm_transfers')]);
}
if ($path === '/pm/transfer/delete') {
    $drop('pm_transfers', $_POST['id'] ?? '');
    $out(['status' => 'success']);
}
if ($path === '/pm/folder/list') {
    $out(['status' => 'success', 'content' => $state['pm_items'] ?? []]);
}
if ($path === '/pm/item/delete' || $path === '/pm/folder/delete') {
    $drop('pm_items', $_POST['id'] ?? '');
    $out(['status' => 'success']);
}
// ---- AllDebrid
if ($path === '/ad/v4.1/magnet/status') {
    $out(['status' => 'success', 'data' => ['magnets' => $ids('ad_magnets')]]);
}
if ($path === '/ad/v4/magnet/delete') {
    $drop('ad_magnets', $_GET['id'] ?? '');
    $out(['status' => 'success']);
}
if ($path === '/ad/v4/user/links') {
    $out(['status' => 'success', 'data' => ['links' => array_map(fn($l) => ['link' => $l], $state['ad_links'] ?? [])]]);
}
if ($path === '/ad/v4/user/links/delete') {
    $state['ad_links'] = [];
    $out(['status' => 'success']);
}
if ($path === '/ad/v4/user/history/delete') {
    $state['ad_history_cleared'] = true;
    $out(['status' => 'success']);
}
// ---- TorBox
if (preg_match('#^/tb/(torrents|webdl|usenet)/mylist$#', $path, $m)) {
    $out(['success' => true, 'data' => $m[1] === 'torrents' ? $ids('tb_torrents') : []]);
}
if ($path === '/tb/torrents/controltorrent') {
    if (($body['operation'] ?? '') === 'delete') {
        $drop('tb_torrents', $body['torrent_id'] ?? '');
    }
    $out(['success' => true]);
}
$out(['error' => 'unknown route ' . $path], 404);

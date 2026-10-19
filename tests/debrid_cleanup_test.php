<?php
// Tests for debrid_cleanup.php against a mock of the four debrid APIs
// (tests/mock_debrid_api.php) - nothing here ever touches a real account.
// Run: php tests/debrid_cleanup_test.php
error_reporting(E_ALL & ~E_DEPRECATED);
$failures = 0;
function check($label, $cond) {
    global $failures;
    echo ($cond ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$cond) {
        $failures++;
    }
}

$apiPort = 18461;
$appPort = 18462;
$stateFile = sys_get_temp_dir() . '/mock_debrid_state_' . getmypid() . '.json';
$tmp = sys_get_temp_dir();
$env = ['PATH' => getenv('PATH'), 'TMPDIR' => $tmp, 'MOCK_DEBRID_STATE' => $stateFile, 'PHP_CLI_SERVER_WORKERS' => '4',
    'DEBRID_CLEANUP_NO_PAUSE' => '1',
    'DEBRID_CLEANUP_BASE_REALDEBRID' => "http://127.0.0.1:$apiPort/rd", 'DEBRID_CLEANUP_BASE_PREMIUMIZE' => "http://127.0.0.1:$apiPort/pm",
    'DEBRID_CLEANUP_BASE_ALLDEBRID' => "http://127.0.0.1:$apiPort/ad/v4", 'DEBRID_CLEANUP_BASE_TORBOX' => "http://127.0.0.1:$apiPort/tb",
    'DEBRID_CLEANUP_SELF_URL' => "http://127.0.0.1:$appPort/debrid_cleanup.php"];
foreach ($env as $k => $v) {
    putenv("$k=$v");
}
$procs = [];
$start = function ($port, $router, $cwd) use (&$procs, $env) {
    $args = ['php', '-d', 'opcache.enable=0', '-d', 'opcache.enable_cli=0', '-S', "127.0.0.1:$port"];
    if ($router) {
        $args[] = $router;
    }
    $procs[] = proc_open($args, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, $cwd, $env);
    for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) {
        usleep(100000);
    }
};
register_shutdown_function(function () use (&$procs, $stateFile, $tmp, $apiPort, $appPort) {
    foreach ($procs as $p) {
        @proc_terminate($p);
    }
    // php -S leaves its worker children behind when only the parent is terminated
    @exec('pkill -f ' . escapeshellarg("php.*-S 127.0.0.1:$apiPort") . ' ; pkill -f ' . escapeshellarg("php.*-S 127.0.0.1:$appPort"));
    @unlink($stateFile);
    foreach (['realdebrid', 'premiumize', 'alldebrid', 'torbox'] as $s) {
        @unlink("$tmp/debrid_cleanup_$s.lock");
        @unlink("$tmp/debrid_cleanup_$s.running");
    }
});
$start($apiPort, __DIR__ . '/mock_debrid_api.php', __DIR__);
$start($appPort, null, dirname(__DIR__));

define('DEBRID_CLEANUP_MAX_DELETES', 5);
define('DEBRID_CLEANUP_LOG', $tmp . '/debrid_cleanup_test.log');
$PRIVATE_TOKEN = 'rd-test-key';
$premiumizeApiKey = 'pm-test-key';
$alldebridApiKey = 'ad-test-key';
$torboxApiKey = 'tb-test-key';
require dirname(__DIR__) . '/debrid_cleanup.php';
function locateBaseURL() { return 'http://127.0.0.1/'; }
$setState = function (array $s) use ($stateFile) {
    file_put_contents($stateFile, json_encode($s));
};
$getState = function () use ($stateFile) {
    return json_decode((string) file_get_contents($stateFile), true) ?: [];
};
foreach (['realdebrid', 'premiumize', 'alldebrid', 'torbox'] as $s) {
    @unlink("$tmp/debrid_cleanup_$s.lock");
    @unlink("$tmp/debrid_cleanup_$s.running");
}

// ------------------------------------------------ what counts as "the account is full"
foreach ([
    'ElfHosted slate "Debrid limit reached"' => 'https://slate.elfhosted.com/cache/x/slate.mp4?preset=default&w=1280&h=720&title=Debrid+limit+reached&body=Your+debrid+service+reported+that+its+active+download+or+store+limit+has+been+exceeded.',
    'Real-Debrid too_many_active_downloads' => '{"error":"too_many_active_downloads","error_code":21}',
    'plain sentence' => 'Your active download or store limit has been exceeded',
    'quota exceeded' => '{"status":"error","message":"Quota exceeded"}',
] as $label => $text) {
    check("full account detected: $label", debridLimitReachedText($text) === true);
}
foreach ([
    'torrent not cached' => 'No links available (torrent not cached)',
    'Premiumize not logged in' => '{"status":"error","message":"Not logged in","code":"authentication_failed"}',
    'plain rate limit' => 'Too many requests',
    'infringing file' => '{"error":"infringing_file","error_code":35}',
    'something went wrong slate' => 'title=Something+went+wrong&body=AIOStreams+hit+an+unexpected+error',
    'empty' => '',
] as $label => $text) {
    check("NOT treated as full: $label", debridLimitReachedText($text) === false);
}
check('service from an api url', debridServiceFromUrl('https://api.real-debrid.com/rest/1.0/x') === 'realdebrid' && debridServiceFromUrl('https://www.premiumize.me/api/x') === 'premiumize'
    && debridServiceFromUrl('https://api.alldebrid.com/v4/x') === 'alldebrid' && debridServiceFromUrl('https://api.torbox.app/v1/api/x') === 'torbox' && debridServiceFromUrl('https://example.com/') === '');

// ------------------------------------------------ the token
$tok = debridCleanupToken('realdebrid');
check('token: valid for its service', debridCleanupTokenValid('realdebrid', $tok));
check('token: NOT valid for another service', !debridCleanupTokenValid('premiumize', $tok));
check('token: previous 10-minute window still accepted, two windows back is not', debridCleanupTokenValid('realdebrid', debridCleanupToken('realdebrid', 1)) && !debridCleanupTokenValid('realdebrid', debridCleanupToken('realdebrid', 2)));
check('token: garbage / empty refused', !debridCleanupTokenValid('realdebrid', 'abc') && !debridCleanupTokenValid('realdebrid', ''));
check('secret file is 0600', (fileperms(sys_get_temp_dir() . '/debrid_cleanup.secret') & 0777) === 0600);

// ------------------------------------------------ the web endpoint refuses what is not authorised
$setState(['rd_torrents' => ['a', 'b', 'c'], 'rd_downloads' => ['d1']]);
$post = function ($fields) use ($appPort) {
    $ch = curl_init("http://127.0.0.1:$appPort/debrid_cleanup.php");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $b = curl_exec($ch);
    return [curl_getinfo($ch, CURLINFO_HTTP_CODE), $b];
};
[$code] = $post(['service' => 'realdebrid']);
check('endpoint: no token -> 403', $code === 403);
[$code] = $post(['service' => 'realdebrid', 'token' => 'nope']);
check('endpoint: wrong token -> 403', $code === 403);
[$code] = $post(['service' => 'premiumize', 'token' => debridCleanupToken('realdebrid')]);
check('endpoint: token of another service -> 403', $code === 403);
$ch = curl_init("http://127.0.0.1:$appPort/debrid_cleanup.php?service=realdebrid&token=" . debridCleanupToken('realdebrid'));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
curl_exec($ch);
check('endpoint: GET (even with a valid token) -> 403', curl_getinfo($ch, CURLINFO_HTTP_CODE) === 403);
check('...and after all those refusals NOTHING was deleted', count($getState()['rd_torrents'] ?? []) === 3 && count($getState()['rd_downloads'] ?? []) === 1);
[$code, $b] = $post(['service' => 'realdebrid', 'token' => debridCleanupToken('realdebrid')]);
check('endpoint: valid POST + token runs the cleanup', $code === 200 && json_decode($b, true)['deleted'] === 4);
check('...Real-Debrid torrents AND downloads are empty', ($getState()['rd_torrents'] ?? ['x']) === [] && ($getState()['rd_downloads'] ?? ['x']) === []);

// ------------------------------------------------ each service, directly
$setState(['rd_torrents' => ['t1', 't2'], 'rd_downloads' => ['d1']]);
$r = debridCleanupRun('realdebrid', true);
check('dry run lists but deletes nothing (Real-Debrid)', $r['listed'] === 3 && $r['deleted'] === 0 && count($getState()['rd_torrents']) === 2);
$r = debridCleanupRun('realdebrid');
check('Real-Debrid: every torrent and download deleted', $r['deleted'] === 3 && $getState()['rd_torrents'] === [] && $getState()['rd_downloads'] === []);

$setState(['pm_transfers' => ['x1', 'x2'], 'pm_items' => [['id' => 'f1', 'type' => 'folder'], ['id' => 'i1', 'type' => 'file']]]);
$r = debridCleanupRun('premiumize');
check('Premiumize: transfers AND cloud items deleted', $r['deleted'] === 4 && $getState()['pm_transfers'] === [] && $getState()['pm_items'] === []);

$setState(['ad_magnets' => [11, 12], 'ad_links' => ['https://l/1', 'https://l/2']]);
$r = debridCleanupRun('alldebrid');
check('AllDebrid: magnets, saved links and history cleared', $r['deleted'] >= 3 && $getState()['ad_magnets'] === [] && $getState()['ad_links'] === [] && !empty($getState()['ad_history_cleared']));

$setState(['tb_torrents' => [5, 6, 7]]);
$r = debridCleanupRun('torbox');
check('TorBox: torrents deleted', $r['deleted'] === 3 && $getState()['tb_torrents'] === []);

$setState(['rd_torrents' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']]);
$r = debridCleanupRun('realdebrid');
check('cap: at most DEBRID_CLEANUP_MAX_DELETES (5) deletions per run, the rest waits for the next trigger', $r['deleted'] === 5 && count($getState()['rd_torrents']) === 3);

// ------------------------------------------------ trigger rules
@unlink("$tmp/debrid_cleanup_realdebrid.lock");
$setState(['rd_torrents' => ['a', 'b']]);
check('trigger: a "limit reached" answer starts a cleanup', debridCleanupMaybeTrigger('realdebrid', '{"error":"too_many_active_downloads"}', 'test') === true);
for ($i = 0; $i < 40 && ($getState()['rd_torrents'] ?? ['x']) !== []; $i++) {
    usleep(150000);
}
check('...which really empties the account through the authenticated self-call', ($getState()['rd_torrents'] ?? ['x']) === []);
$setState(['rd_torrents' => ['z']]);
check('cooldown: a second trigger within 30 minutes does nothing', debridCleanupMaybeTrigger('realdebrid', 'limit reached', 'test') === false);
usleep(500000);
check('...and the account is left alone', ($getState()['rd_torrents'] ?? []) === ['z']);
@unlink("$tmp/debrid_cleanup_realdebrid.lock");
check('no trigger for an answer that is not "full" (not cached / auth / rate limit)', debridCleanupMaybeTrigger('realdebrid', 'Too many requests', 'test') === false && debridCleanupMaybeTrigger('realdebrid', '{"error":"infringing_file"}', 'test') === false);
$debridAutoCleanOnLimit = false;
check('switched off with $debridAutoCleanOnLimit = false', debridCleanupMaybeTrigger('realdebrid', 'limit reached', 'test') === false);
$debridAutoCleanOnLimit = true;
$PRIVATE_TOKEN = '';
check('no key configured for the service -> nothing is started', debridCleanupMaybeTrigger('realdebrid', 'limit reached', 'test') === false);

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);

<?php
// Tests for adult_addon.php: pure logic, then the real lookup against a mock
// Stremio addon (tests/mock_adult_addon.php) with stubbed debrid helpers.
// Run: php tests/adult_addon_test.php
$failures = 0;
function check($label, $cond) {
    global $failures;
    echo ($cond ? "PASS " : "FAIL ") . $label . "\n";
    if (!$cond) {
        $failures++;
    }
}

// Debrid stubs: the real ones call live services. Premiumize answers "cached"
// only for the hash of $cachedHash; every call is recorded.
$usePremiumize = true; $useAllDebrid = false; $useTorBox = false; $useRealDebrid = false;
$premiumizeApiKey = 'test'; $cachedHash = ''; $pmCalls = [];
function instantAvailability_PM($hashes, $torrents) {
    global $cachedHash, $pmCalls;
    $pmCalls[] = $hashes;
    return array_values(array_filter($torrents, fn($t) => $t['hash'] === $cachedHash));
}
function getStreamingLink_PM($t, $magnet, $tag) {
    return 'https://cdn.example.invalid/direct/' . $t[0]['hash'] . '.mp4';
}

$mockPort = 18431;
$logFile = sys_get_temp_dir() . '/adult_addon_mock_' . getmypid() . '.log';
@unlink($logFile);
$mock = proc_open(['php', '-d', 'opcache.enable=0', '-d', 'opcache.enable_cli=0', '-S', "127.0.0.1:$mockPort", __DIR__ . '/mock_adult_addon.php'],
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null, ['MOCK_ADDON_LOG' => $logFile, 'PATH' => getenv('PATH')]);
register_shutdown_function(function () use ($mock, $logFile) {
    proc_terminate($mock);
    @unlink($logFile);
});
for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $mockPort); $i++) {
    usleep(100000);
}

require __DIR__ . '/../adult_addon.php';

// --- pure logic
$adultAddonUrl = '';
check('unset addon url -> disabled', !adultAddonEnabled() && adultAddonResolve('Latina Pink') === false);
$adultAddonUrl = "http://127.0.0.1:$mockPort/TOKEN_SECRET/manifest.json";
check('manifest.json suffix stripped', adultAddonBase() === "http://127.0.0.1:$mockPort/TOKEN_SECRET" && adultAddonEnabled());

check('title score: qualifiers (resolution) ignored', adultAddonTitleScore('Latina Pink', 'Latina Pink 1080p') == 1.0);
check('title score: filler words ignored', adultAddonTitleScore('The Art of Love', 'Art Love') == 1.0);
check('title score: unrelated is 0', adultAddonTitleScore('Latina Pink', 'Something Unrelated') == 0.0);
check('title score: same words buried in a much longer title is below the cutoff',
    adultAddonTitleScore('Latina Pink', 'Latina Emily Pink Gets Hard Fucked In Black Nylons') < ADULT_ADDON_MIN_TITLE_SCORE);
check('title score: a different number is a different scene', adultAddonTitleScore('Women Seeking Women 122', 'Women Seeking Women #102 - Scene 2') == 0.0);
check('title score: "Volume 58 - Scene 2" still matches 58', adultAddonTitleScore('Women Seeking Women 58', 'Women Seeking Women Volume 58 - Scene 2') >= ADULT_ADDON_MIN_TITLE_SCORE);
check('title score: a spin-off ("- Interviews", one word off) is rejected', adultAddonTitleScore('Girl Lovin Girls', 'Girls Lovin Girls - Interviews') == 0.0);
check('query variants: as-is, stripped, first four words',
    adultAddonQueryVariants("Miss Jane's Big: Adventure In The Sun") === ["Miss Jane's Big: Adventure In The Sun", 'Miss Jane s Big Adventure In The Sun', 'miss janes big adventure']);
check('size parse GiB / MB', adultAddonParseSize('x 2.1 GiB y') === (int) (2.1 * 1073741824) && adultAddonParseSize('689,5 MB') === (int) (689.5 * 1048576));

$c = adultAddonStreamToCandidate(['infoHash' => strtoupper(str_repeat('a', 40)), 'title' => 'T 720p 10 seeds']);
check('infoHash normalised to lowercase, resolution + seeds parsed', $c['hash'] === str_repeat('a', 40) && $c['resolution'] === 720 && $c['seeds'] === 10);
$c = adultAddonStreamToCandidate(['url' => 'https://h/TOKEN_SECRET/_play/pmKey/' . str_repeat('b', 40), 'name' => '[PM] 1080p']);
check('hash extracted from _play url, url itself not kept', $c['hash'] === str_repeat('b', 40) && !str_contains(json_encode($c), 'TOKEN_SECRET'));
check('stream with no hash -> null', adultAddonStreamToCandidate(['url' => 'https://h/other']) === null);

$ranked = adultAddonRankCandidates([
    ['hash' => 'a', 'title' => '', 'resolution' => 480, 'seeds' => 99, 'bytes' => 0],
    ['hash' => 'b', 'title' => '', 'resolution' => 1080, 'seeds' => 1, 'bytes' => 0],
    ['hash' => 'c', 'title' => '', 'resolution' => 1080, 'seeds' => 9, 'bytes' => 0],
    ['hash' => 'd', 'title' => '', 'resolution' => 2160, 'seeds' => 5, 'bytes' => 0],
    ['hash' => 'e', 'title' => '', 'resolution' => 1080, 'seeds' => 50, 'bytes' => 4 * 1048576],
]);
check('rank: 1080 by seeds, then 4K, then 480; tiny clip dropped', array_column($ranked, 'hash') === ['c', 'b', 'd', 'a']);

// --- manifest-driven catalog choice
$manifest = adultAddonManifest();
check('manifest fetched and usable', is_array($manifest) && in_array('stream', adultAddonResourceNames($manifest), true));
check('search catalogs: tpdb first, generic search second, no Live / non-search', adultAddonSearchCatalogs($manifest) === [['Porn', 'tpdb_search'], ['Porn', 'search']]);

// --- lookup against the mock addon
$cands = adultAddonFindCandidates('Latina Pink', $manifest);
$hashes = array_column($cands, 'hash');
check('candidates: 1080p torrent first, 720p _play hash next, dup and tiny clip removed',
    $hashes[0] === str_repeat('b', 40) && $hashes[1] === str_repeat('a', 40) && !in_array(str_repeat('c', 40), $hashes, true) && count($hashes) === count(array_unique($hashes)));
check('unrelated meta (low title score) was not fetched', !str_contains((string) @file_get_contents($logFile), 'porndb:2'));
check('no candidate carries the addon token', !str_contains(json_encode($cands), 'TOKEN_SECRET'));
check('a title the addon does not know -> no candidates', adultAddonFindCandidates('Nothing Matches This Name', $manifest) === []);
check('_play links are never requested', !str_contains((string) @file_get_contents($logFile), '_play'));

// --- debrid step (cache-only)
$cachedHash = '';
check('nothing cached -> false', adultAddonResolve('Latina Pink') === false);
$cachedHash = str_repeat('a', 40);
$link = adultAddonResolve('Latina Pink');
check('cached hash -> direct debrid link', $link === 'https://cdn.example.invalid/direct/' . str_repeat('a', 40) . '.mp4');
check('returned link does not contain the addon token or host', !str_contains($link, 'TOKEN_SECRET') && !str_contains($link, '127.0.0.1'));
check('Premiumize cache checked in ONE batch call', count($pmCalls) === 2 && count($pmCalls[1]) === count($hashes));
check('unknown title -> false even with a cached hash configured', adultAddonResolve('Nothing Matches This Name') === false);

// --- addon down: manifest unavailable -> false, no exception
$adultAddonUrl = 'http://127.0.0.1:1/dead/manifest.json';
check('unreachable addon -> false (caller falls back to scrapers)', adultAddonResolve('Latina Pink') === false);

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);

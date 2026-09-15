<?php
// M3uListerr prewarmer - resolves titles ahead of time so the FIRST viewer
// play is an instant cache hit instead of a ~13s AIOStreams round-trip.
//
// WHY: a cold resolve spends ~12.5s inside the AIOStreams API (it re-scrapes
// indexers and re-checks debrid cache every call). That latency is external
// and unavoidable per title - but if this script has already resolved the
// title, play.php serves the cached URL in ~0.1s. Run it on a cron a little
// more often than the resolved-URL cache TTL (config: $expirationHours, 3h by
// default) so popular titles stay warm.
//
// COST / FAIR-USE WARNING: each resolve runs the normal playability pipeline,
// which downloads ~10MB per title to verify throughput. Prewarming a large
// catalog therefore pulls real GB through your debrid provider and CAN exhaust
// its fair-use allowance (which has broken playback here before). Keep the id
// list curated (popular / recent / most-watched), not the whole 50k catalog.
//
// USAGE (CLI only - refuses to run over the web):
//   php prewarm.php --ids=157336,27205,603 [--accounts=Unlimited,UnlimitedFR]
//   php prewarm.php --file=ids.txt [--workers=6] [--limit=500]
//   php prewarm.php --playlist=top --limit=300         # newest N from playlist.json
//   php prewarm.php --series=1396:1:1,66732:4:1         # series as tmdbId:season:episode
//
// Options:
//   --base=http://127.0.0.1   server base URL (default localhost)
//   --accounts=...            comma list of account usernames (default Unlimited)
//   --workers=N               parallel resolves (default 6)
//   --limit=N                 cap how many titles to process
//   --skip-cached=1           skip titles already resolved (default 1)
//   --dry-run=1               list what would be done, resolve nothing

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("prewarm.php runs from the command line only.\n");
}

error_reporting(E_ALL & ~E_DEPRECATED);

$opts = getopt('', ['ids::', 'file::', 'playlist::', 'series::', 'base::', 'accounts::', 'workers::', 'limit::', 'skip-cached::', 'dry-run::']);

$base = rtrim($opts['base'] ?? 'http://127.0.0.1', '/');
$accounts = array_filter(array_map('trim', explode(',', $opts['accounts'] ?? 'Unlimited')));
$workers = max(1, (int) ($opts['workers'] ?? 6));
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$skipCached = ($opts['skip-cached'] ?? '1') !== '0';
$dryRun = isset($opts['dry-run']) && $opts['dry-run'] !== '0';

// ---- Build the work list: each item is [type, movieId, data-or-null, label] ----
$targets = [];

if (!empty($opts['ids'])) {
    foreach (array_filter(array_map('trim', explode(',', $opts['ids']))) as $id) {
        if (ctype_digit($id)) {
            $targets[] = ['movie', $id, null, "movie $id"];
        }
    }
}

if (!empty($opts['file']) && is_file($opts['file'])) {
    foreach (file($opts['file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $id = trim($line);
        if (ctype_digit($id)) {
            $targets[] = ['movie', $id, null, "movie $id"];
        }
    }
}

if (!empty($opts['series'])) {
    // tmdbId:season:episode  -> the data param play.php expects
    foreach (array_filter(array_map('trim', explode(',', $opts['series']))) as $spec) {
        $p = explode(':', $spec);
        if (count($p) === 3 && ctype_digit($p[0]) && ctype_digit($p[1]) && ctype_digit($p[2])) {
            $data = base64_encode("tt0:{$p[0]}/season/{$p[1]}/episode/{$p[2]}");
            $targets[] = ['series', $p[0], $data, "series {$p[0]} S{$p[1]}E{$p[2]}"];
        }
    }
}

if (($opts['playlist'] ?? '') === 'top') {
    $pl = @json_decode((string) @file_get_contents(__DIR__ . '/playlist.json'), true);
    if (is_array($pl)) {
        foreach ($pl as $row) {
            if (!empty($row['stream_id']) && ($row['stream_type'] ?? '') === 'movie') {
                $targets[] = ['movie', (string) $row['stream_id'], null, trim(($row['name'] ?? '') . ' (' . $row['stream_id'] . ')')];
            }
        }
    }
}

if (empty($targets)) {
    fwrite(STDERR, "No targets. Pass --ids=, --file=, --series= or --playlist=top.\n");
    exit(1);
}

if ($limit > 0) {
    $targets = array_slice($targets, 0, $limit);
}

// Expand each target across the requested accounts.
$jobs = [];
foreach ($targets as [$type, $movieId, $data, $label]) {
    foreach ($accounts as $account) {
        // &prewarm=1 tags the resolved SQLite row as prewarmed so the dashboard
        // can show what was warmed ahead of time vs resolved by real viewers.
        $url = $type === 'series'
            ? "$base/play.php?type=series&movieId=$movieId&data=" . rawurlencode($data) . "&username=" . rawurlencode($account) . "&prewarm=1"
            : "$base/play.php?movieId=$movieId&username=" . rawurlencode($account) . "&prewarm=1";
        $jobs[] = ['url' => $url, 'label' => "$label [$account]", 'account' => $account, 'movieId' => $movieId, 'type' => $type];
    }
}

$total = count($jobs);
fwrite(STDERR, "Prewarm: $total resolve(s) across " . count($targets) . " title(s) x " . count($accounts)
    . " account(s), $workers workers, base=$base" . ($dryRun ? " [DRY RUN]\n" : "\n"));

if ($dryRun) {
    foreach ($jobs as $j) {
        fwrite(STDERR, "  would warm: {$j['label']}\n");
    }
    exit(0);
}

// ---- Run with a bounded worker pool via curl_multi ----
$done = 0; $ok = 0; $skip = 0; $fail = 0;
$started = microtime(true);
$mh = curl_multi_init();
$active = [];
$queue = $jobs;

$launch = function () use (&$queue, &$active, $mh, $skipCached, $base) {
    $j = array_shift($queue);
    // Optional cheap skip: a HEAD that follows the redirect chain would itself
    // trigger a resolve, so instead we just let play.php's own cache short-
    // circuit make an already-warm title return in well under a second.
    $ch = curl_init($j['url']);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,          // HEAD: trigger resolve + cache write, don't pull the movie
        CURLOPT_FOLLOWLOCATION => false, // the 301 to the playlist IS the success signal
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    curl_multi_add_handle($mh, $ch);
    $active[(int) $ch] = ['ch' => $ch, 'job' => $j, 't0' => microtime(true)];
};

for ($i = 0; $i < min($workers, count($queue)); $i++) {
    $launch();
}

do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh, 1);
    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        $meta = $active[(int) $ch];
        unset($active[(int) $ch]);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $secs = microtime(true) - $meta['t0'];
        $done++;
        if ($code === 301 || $code === 302) {
            $ok++;
            $tag = $secs < 1.0 ? 'cached' : sprintf('warmed %.1fs', $secs);
            fwrite(STDERR, sprintf("[%d/%d] OK   %-45s %s\n", $done, $total, $meta['job']['label'], $tag));
        } elseif ($code === 404) {
            $skip++;
            fwrite(STDERR, sprintf("[%d/%d] 404  %-45s no source\n", $done, $total, $meta['job']['label']));
        } else {
            $fail++;
            fwrite(STDERR, sprintf("[%d/%d] FAIL %-45s http=%d\n", $done, $total, $meta['job']['label'], $code));
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        if (!empty($queue)) {
            $launch();
        }
    }
} while ($running > 0 || !empty($active));

curl_multi_close($mh);
$elapsed = round(microtime(true) - $started, 1);
fwrite(STDERR, "\nDone in {$elapsed}s: $ok warmed/cached, $skip no-source, $fail failed.\n");

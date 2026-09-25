<?php
// Resolves an adult-movies.json title through a Stremio addon instead of
// scraping tube-site pages: the addon is used only as an INDEX (title ->
// torrent info hashes), and every hash then goes through this project's own
// debrid layer (Premiumize / AllDebrid / TorBox / Real-Debrid), which checks
// the debrid cache first and returns a direct link.
//
// Why it is built this way (all confirmed against a real addon):
//  - The addon's own stream URLs (".../<config>/_play/pmKey/<hash>") carry the
//    addon's whole config token in the path - which embeds the operator's
//    debrid and indexer keys - so they must never be handed to a viewer's
//    player. Only the resulting debrid link is returned.
//  - Requesting such a URL for an uncached torrent makes the debrid service
//    START DOWNLOADING it and answers with a "caching.mp4" placeholder clip,
//    and the addon's stream list does not say which streams are cached. So
//    nothing from the addon is ever requested except its JSON manifest,
//    catalogs and stream lists.
//  - The catalogs, their types and which of them support search come from the
//    addon's manifest.json, not from hard-coded names.
//
// Lives in its own file, not config.php: config.php is a bind-mounted host
// file the image never overwrites, so a function added there does not exist in
// production. The one setting, $adultAddonUrl, is optional; when it is empty
// this file does nothing and playAdultVideo() keeps its original behavior.

const ADULT_ADDON_MANIFEST_TTL = 21600; // 6h
const ADULT_ADDON_MAX_CATALOGS = 3;
const ADULT_ADDON_MAX_METAS_PER_SEARCH = 4;
const ADULT_ADDON_MAX_CANDIDATES = 8;
const ADULT_ADDON_MIN_TITLE_SCORE = 0.5;

// Base URL of the addon (everything before "/manifest.json"), or '' if unset.
function adultAddonBase() {
    global $adultAddonUrl;
    $u = trim((string) ($adultAddonUrl ?? ''));
    if ($u === '') {
        return '';
    }
    $u = preg_replace('#/manifest\.json/?$#i', '', $u);
    return rtrim($u, '/');
}

function adultAddonEnabled() {
    return adultAddonBase() !== '';
}

// GET a URL and decode JSON. null on any failure. Never follows a redirect
// off the addon (the manifest, catalogs and stream lists are plain JSON).
function adultAddonGetJson($url, $timeout = 45) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($body === false || $status !== 200) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

// Many GETs at once (curl_multi): key => decoded JSON or null. Same rules as
// adultAddonGetJson(): no redirects, non-200 or non-JSON is null.
function adultAddonGetManyJson(array $urls, $timeout = 45) {
    $out = array_fill_keys(array_keys($urls), null);
    if (!$urls) {
        return $out;
    }
    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $key => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    do {
        curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 0.2);
        }
    } while ($running);
    foreach ($handles as $key => $ch) {
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = $status === 200 ? json_decode((string) curl_multi_getcontent($ch), true) : null;
        $out[$key] = is_array($data) ? $data : null;
        curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);
    return $out;
}

function adultAddonManifest() {
    $base = adultAddonBase();
    if ($base === '') {
        return null;
    }
    $cache = sys_get_temp_dir() . '/adult_addon_manifest_' . md5($base) . '.json';
    if (is_file($cache) && (time() - filemtime($cache)) < ADULT_ADDON_MANIFEST_TTL) {
        $hit = json_decode((string) @file_get_contents($cache), true);
        if (is_array($hit) && !empty($hit['catalogs'])) {
            return $hit;
        }
    }
    $manifest = adultAddonGetJson($base . '/manifest.json');
    if (!is_array($manifest) || empty($manifest['catalogs']) || !in_array('stream', adultAddonResourceNames($manifest), true)) {
        return null;
    }
    @file_put_contents($cache, json_encode($manifest));
    return $manifest;
}

// A manifest resource is either a name or {"name": ...}.
function adultAddonResourceNames(array $manifest) {
    $names = [];
    foreach ($manifest['resources'] ?? [] as $r) {
        $names[] = is_array($r) ? ($r['name'] ?? '') : $r;
    }
    return $names;
}

// The catalogs worth searching, best first, as [type, id]: those whose extra
// list a "search" parameter that is not the only way in ("required" ones are
// fine - we always search), preferring ThePornDB ones (exact scene titles)
// over generic torrent search, and never live-cam catalogs.
function adultAddonSearchCatalogs(array $manifest) {
    $found = [];
    foreach ($manifest['catalogs'] ?? [] as $c) {
        $type = $c['type'] ?? '';
        $id = $c['id'] ?? '';
        if ($type === '' || $id === '' || strcasecmp($type, 'Live') === 0) {
            continue;
        }
        $searchable = false;
        foreach ($c['extra'] ?? [] as $e) {
            if (($e['name'] ?? '') === 'search') {
                $searchable = true;
            }
        }
        if (!$searchable) {
            continue;
        }
        $rank = 2;
        if (stripos($id, 'tpdb') !== false || stripos($id, 'porndb') !== false) {
            $rank = 0;
        } elseif (strcasecmp($id, 'search') === 0) {
            $rank = 1;
        }
        $found[] = [$rank, [$type, $id]];
    }
    usort($found, function ($a, $b) {
        return $a[0] <=> $b[0];
    });
    return array_slice(array_map(function ($f) {
        return $f[1];
    }, $found), 0, ADULT_ADDON_MAX_CATALOGS);
}

// Lowercased alphanumeric words, minus filler words.
function adultAddonWords($s) {
    static $stop = ['the', 'a', 'an', 'in', 'of', 'and', 'with', 'to', 'for', 'on', 'at', 'is'];
    $s = strtolower(strtr($s, ["\u{2013}" => ' ', "\u{2014}" => ' ', "\u{2019}" => '', "\u{2018}" => '', "'" => '']));
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
    $words = array_values(array_filter(explode(' ', $s), function ($w) use ($stop) {
        return $w !== '' && !in_array($w, $stop, true);
    }));
    return $words;
}

// Words that only qualify a scene ("Volume 58 - Scene 2", "S41:E5", "1080p").
function adultAddonQualifierWord($w) {
    return in_array($w, ['scene', 'scenes', 'vol', 'volume', 'part', 'pt', 'episode', 'ep', 'xxx'], true)
        || preg_match('/^(s\d+|e\d+|s\d+e\d+|\d{3,4}p)$/', $w) === 1;
}

// How well a search result's title matches the wanted title, 0..1. The store
// is fuzzy ("Latina Pink" also finds "Latina Emily Pink Gets Hard Fucked...")
// and a wrong scene is worse than no answer, so: at least 80% of the wanted
// words must be present, every number in the wanted title must appear in the
// candidate ("... 122" is not "#102"), and extra words in the candidate lower
// the score (Jaccard, after dropping scene/volume/resolution qualifiers).
function adultAddonTitleScore($wanted, $candidate) {
    $keep = function ($s) {
        return array_values(array_unique(array_filter(adultAddonWords($s), function ($w) {
            return !adultAddonQualifierWord($w);
        })));
    };
    $a = $keep($wanted);
    $b = $keep($candidate);
    if (!$a || !$b) {
        return 0.0;
    }
    foreach ($a as $w) {
        if (ctype_digit($w) && !in_array($w, $b, true)) {
            return 0.0;
        }
    }
    $common = count(array_intersect($a, $b));
    if ($common / count($a) < 0.8) {
        return 0.0;
    }
    return $common / count(array_unique(array_merge($a, $b)));
}

// The title as-is, punctuation-free, and its first four words: recall on real
// titles went from 45% to 60% with these three (measured on 20 random entries).
function adultAddonQueryVariants($name) {
    $variants = [trim($name), trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $name))];
    $words = adultAddonWords($name);
    if (count($words) > 4) {
        $variants[] = implode(' ', array_slice($words, 0, 4));
    }
    $out = [];
    foreach ($variants as $v) {
        $v = trim(preg_replace('/\s+/', ' ', $v));
        if ($v !== '' && !in_array($v, $out, true)) {
            $out[] = $v;
        }
    }
    return $out;
}

// "2.1 GiB", "689.44 MB" anywhere in a stream's description/name -> bytes.
function adultAddonParseSize($text) {
    if (preg_match('/([\d.,]+)\s*[\x{a0} ]?\s*(TiB|GiB|MiB|TB|GB|MB)/iu', $text, $m)) {
        $n = (float) str_replace(',', '.', $m[1]);
        $mult = ['t' => 1099511627776, 'g' => 1073741824, 'm' => 1048576][strtolower($m[2][0])];
        return (int) ($n * $mult);
    }
    return 0;
}

// One Stremio stream -> a torrent candidate, or null. The hash comes from
// infoHash, or from the 40-hex segment of the addon's "_play/<svc>/<hash>" URL.
// The URL itself is NOT kept (it embeds the addon's config token).
function adultAddonStreamToCandidate(array $stream) {
    $hash = strtolower((string) ($stream['infoHash'] ?? ''));
    if (!preg_match('/^[a-f0-9]{40}$/', $hash)) {
        $hash = '';
        if (!empty($stream['url']) && preg_match('#/_play/[^/]+/([a-fA-F0-9]{40})(?:[/?]|$)#', $stream['url'], $m)) {
            $hash = strtolower($m[1]);
        }
    }
    if ($hash === '') {
        return null;
    }
    $label = ($stream['name'] ?? '') . ' ' . ($stream['title'] ?? '') . ' ' . ($stream['description'] ?? '');
    preg_match('/(2160|1080|720|480|360)p/i', $label, $res);
    preg_match('/(\d+)\s*seed/i', $label, $seeds);
    return [
        'hash' => $hash,
        'title' => trim(preg_replace('/\s+/', ' ', $stream['title'] ?? ($stream['name'] ?? ''))),
        'resolution' => isset($res[1]) ? (int) $res[1] : 0,
        'seeds' => isset($seeds[1]) ? (int) $seeds[1] : 0,
        'bytes' => adultAddonParseSize($label),
    ];
}

// Best first: 1080p, then 720p, then 4K, then anything else; ties by seeders.
// Zero-size entries and tiny clips are not scenes worth a debrid call.
function adultAddonRankCandidates(array $candidates) {
    $rank = [1080 => 0, 720 => 1, 2160 => 2, 480 => 3, 360 => 4, 0 => 5];
    $candidates = array_values(array_filter($candidates, function ($c) {
        return $c['bytes'] === 0 || $c['bytes'] >= 30 * 1048576;
    }));
    usort($candidates, function ($a, $b) use ($rank) {
        return [$rank[$a['resolution']] ?? 5, -$a['seeds']] <=> [$rank[$b['resolution']] ?? 5, -$b['seeds']];
    });
    return $candidates;
}

// Title -> ranked, de-duplicated torrent candidates from the addon. Per query
// variant: every search catalog is asked at once, the best-matching items are
// picked, and all their stream lists are fetched at once - so a lookup costs
// two network round-trips per variant instead of one per request.
function adultAddonFindCandidates($name, array $manifest) {
    $base = adultAddonBase();
    $catalogs = adultAddonSearchCatalogs($manifest);
    $candidates = [];
    $seenHash = [];
    $seenMeta = [];
    foreach (adultAddonQueryVariants($name) as $query) {
        $searchUrls = [];
        foreach ($catalogs as $i => [$type, $catalogId]) {
            $searchUrls[$i] = $base . '/catalog/' . rawurlencode($type) . '/' . rawurlencode($catalogId) . '/search=' . rawurlencode($query) . '.json';
        }
        $results = adultAddonGetManyJson($searchUrls);

        $picked = []; // [type, meta], catalog preference order, best title match first within a catalog
        foreach ($catalogs as $i => [$type]) {
            $scored = [];
            foreach ($results[$i]['metas'] ?? [] as $meta) {
                $id = $meta['id'] ?? '';
                $score = adultAddonTitleScore($name, $meta['name'] ?? '');
                if ($id !== '' && $score >= ADULT_ADDON_MIN_TITLE_SCORE && !isset($seenMeta[$id])) {
                    $scored[] = [$score, $meta];
                }
            }
            usort($scored, function ($a, $b) {
                return $b[0] <=> $a[0];
            });
            foreach (array_slice($scored, 0, ADULT_ADDON_MAX_METAS_PER_SEARCH) as [, $meta]) {
                $seenMeta[$meta['id']] = true;
                $picked[] = [$type, $meta];
            }
        }
        if (!$picked) {
            continue;
        }

        $streamUrls = [];
        foreach ($picked as $i => [$type, $meta]) {
            $streamUrls[$i] = $base . '/stream/' . rawurlencode($type) . '/' . rawurlencode($meta['id']) . '.json';
        }
        foreach (adultAddonGetManyJson($streamUrls) as $streams) {
            foreach ($streams['streams'] ?? [] as $s) {
                $c = is_array($s) ? adultAddonStreamToCandidate($s) : null;
                if ($c !== null && !isset($seenHash[$c['hash']])) {
                    $seenHash[$c['hash']] = true;
                    $candidates[] = $c;
                }
            }
        }
        if ($candidates) {
            break; // a query that found something is good enough; don't widen it
        }
    }
    return array_slice(adultAddonRankCandidates($candidates), 0, ADULT_ADDON_MAX_CANDIDATES);
}

// Candidates -> a direct debrid link, or false. Cache-only on every service:
// nothing here can start a download. Premiumize first (one batched cache check,
// then a direct link for the cached ones), then AllDebrid, TorBox, Real-Debrid.
function adultAddonDebridLink(array $candidates) {
    global $usePremiumize, $useAllDebrid, $useTorBox, $useRealDebrid, $premiumizeApiKey;
    $debug = !empty($GLOBALS['DEBUG']);

    if (!empty($usePremiumize) && !empty($premiumizeApiKey) && function_exists('instantAvailability_PM')) {
        $torrents = array_map(function ($c) {
            return ['hash' => $c['hash'], 'extracted_title' => $c['title']];
        }, $candidates);
        $cached = instantAvailability_PM(array_column($torrents, 'hash'), $torrents);
        foreach ($cached as $t) {
            $link = getStreamingLink_PM([$t], 'magnet:?xt=urn:btih:' . $t['hash'], 'adultAddon');
            if ($link) {
                if ($debug) {
                    echo "Adult addon: cached on Premiumize - {$t['hash']}</br></br>";
                }
                return $link;
            }
        }
    }
    foreach ($candidates as $c) {
        if (!empty($useAllDebrid) && function_exists('allDebridResolveHash')) {
            $link = allDebridResolveHash($c['hash'], '', 'movies');
            if ($link) {
                return $link;
            }
        }
        if (!empty($useTorBox) && function_exists('torBoxResolveHash')) {
            $link = torBoxResolveHash($c['hash'], '', 'movies');
            if ($link) {
                return $link;
            }
        }
        if (!empty($useRealDebrid) && function_exists('instantAvailability_RD')) {
            $link = instantAvailability_RD([['hash' => $c['hash'], 'extracted_title' => $c['title']]], 'adultAddon');
            if ($link) {
                return $link;
            }
        }
    }
    return false;
}

// Entry point used by playAdultVideo(): a playable debrid link for this
// title's name, or false (addon off, nothing found, or nothing cached).
function adultAddonResolve($name) {
    if (!adultAddonEnabled()) {
        return false;
    }
    $debug = !empty($GLOBALS['DEBUG']);
    $manifest = adultAddonManifest();
    if ($manifest === null) {
        if ($debug) {
            echo "Adult addon: manifest unavailable</br></br>";
        }
        return false;
    }
    $candidates = adultAddonFindCandidates($name, $manifest);
    if ($debug) {
        echo 'Adult addon: ' . count($candidates) . ' torrent candidate(s) for ' . htmlspecialchars($name) . '</br></br>';
    }
    if (!$candidates) {
        return false;
    }
    return adultAddonDebridLink($candidates);
}

<?php
// M3uListerr JSON API - included by dashboard.php only. Every function reads
// from the SQLite store and returns plain arrays for JSON encoding; nothing
// here emits output or trusts request input beyond a whitelisted query name.

if (!defined('M3ULISTERR_APP')) {
    http_response_code(403);
    exit;
}

// Flag emoji from a 2-letter ISO country code (regional indicator letters);
// no network, no image files.
function m3uFlag(string $cc): string {
    $cc = strtoupper(trim($cc));
    if (strlen($cc) !== 2 || !ctype_alpha($cc)) {
        return '';
    }
    return mb_convert_encoding('&#' . (127397 + ord($cc[0])) . ';', 'UTF-8', 'HTML-ENTITIES')
         . mb_convert_encoding('&#' . (127397 + ord($cc[1])) . ';', 'UTF-8', 'HTML-ENTITIES');
}

// One playback "session" = one (ip, media) pairing. Aggregates the resolve
// event (release/provider/debrid/account), the playlist event (duration,
// audio, subtitles) and the furthest segment reached (playback %). This is
// the backbone of the sessions table and most stats.
function m3uSessions(PDO $db, int $limit = 500): array {
    $sql = "
        WITH seg AS (
            SELECT media, ip,
                   MAX(COALESCE(seg_start,0) + COALESCE(seg_len,0)) AS furthest,
                   MAX(deliver_ms) AS max_deliver,
                   AVG(deliver_ms) AS avg_deliver,
                   COUNT(*) AS seg_count
            FROM events WHERE type='segment' GROUP BY media, ip
        ),
        pl AS (
            SELECT media, MAX(ts) AS ts, duration, audio_lang, sub_count, subtitles
            FROM (SELECT * FROM events WHERE type='playlist' ORDER BY ts) GROUP BY media
        ),
        rs AS (
            SELECT media, ip, MAX(ts) AS ts, movie_id, media_type, series_code, username, password, lang,
                   provider, debrid, release, release_langs, resolution, codec, source_url,
                   cache_hit, resolve_ms, throughput
            FROM (SELECT * FROM events WHERE type='resolve' ORDER BY ts) GROUP BY media, ip
        ),
        -- A cache-hit resolve carries no release/debrid metadata (the AIOStreams
        -- selection only runs on the ORIGINAL grab), so those fields come back
        -- empty on every replay of a title. This recovers them per media from
        -- whichever resolve event for the same release DID capture them - the
        -- values are stable per release, so any non-empty one is correct.
        enrich AS (
            SELECT media,
                   MAX(CASE WHEN debrid != '' THEN debrid END) AS debrid,
                   MAX(CASE WHEN release != '' THEN release END) AS release,
                   MAX(CASE WHEN provider != '' THEN provider END) AS provider,
                   MAX(CASE WHEN release_langs != '' THEN release_langs END) AS release_langs,
                   MAX(CASE WHEN resolution IS NOT NULL AND resolution > 0 THEN resolution END) AS resolution,
                   MAX(CASE WHEN codec != '' THEN codec END) AS codec,
                   MAX(CASE WHEN movie_id IS NOT NULL AND movie_id > 0 THEN movie_id END) AS movie_id,
                   MAX(CASE WHEN media_type != '' THEN media_type END) AS media_type,
                   MAX(CASE WHEN series_code != '' THEN series_code END) AS series_code
            FROM events WHERE type='resolve' GROUP BY media
        )
        SELECT
            rs.media, rs.ip,
            COALESCE(NULLIF(rs.movie_id,0), en.movie_id) AS movie_id,
            COALESCE(NULLIF(rs.media_type,''), en.media_type) AS media_type,
            COALESCE(NULLIF(rs.series_code,''), en.series_code) AS series_code,
            rs.username, rs.password, rs.lang,
            COALESCE(NULLIF(rs.provider,''), en.provider) AS provider,
            COALESCE(NULLIF(rs.debrid,''), en.debrid) AS debrid,
            COALESCE(NULLIF(rs.release,''), en.release) AS release,
            COALESCE(NULLIF(rs.release_langs,''), en.release_langs) AS release_langs,
            COALESCE(NULLIF(rs.resolution,0), en.resolution) AS resolution,
            COALESCE(NULLIF(rs.codec,''), en.codec) AS codec,
            rs.source_url, rs.cache_hit, rs.resolve_ms, rs.throughput, rs.ts AS resolved_ts,
            pl.duration, pl.audio_lang, pl.sub_count, pl.subtitles,
            seg.furthest, seg.avg_deliver, seg.max_deliver, seg.seg_count,
            g.country, g.country_code, g.city, g.zip, g.region, g.lat, g.lon, g.isp,
            tm.title, tm.year, tm.poster, tm.overview,
            ua.ua
        FROM rs
        LEFT JOIN enrich en ON en.media = rs.media
        LEFT JOIN pl ON pl.media = rs.media
        LEFT JOIN seg ON seg.media = rs.media AND seg.ip = rs.ip
        LEFT JOIN geo g ON g.ip = rs.ip
        LEFT JOIN tmdb_meta tm ON tm.movie_id = COALESCE(NULLIF(rs.movie_id,0), en.movie_id)
        LEFT JOIN (SELECT ip, media, MAX(ts) t, ua FROM events WHERE ua != '' GROUP BY ip, media) ua
               ON ua.ip = rs.ip AND ua.media = rs.media
        ORDER BY rs.ts DESC
        LIMIT " . (int) $limit;
    $rows = $db->query($sql)->fetchAll();

    foreach ($rows as &$r) {
        $dur = (float) ($r['duration'] ?? 0);
        $far = (float) ($r['furthest'] ?? 0);
        $r['playback_pct'] = $dur > 0 ? min(100, round($far / $dur * 100, 1)) : null;
        $r['playback_hms'] = m3uHms($far);
        $r['duration_hms'] = m3uHms($dur);
        $r['flag'] = m3uFlag((string) ($r['country_code'] ?? ''));
        $r['device'] = m3uDevice((string) ($r['ua'] ?? ''));
        $r['subtitles_list'] = $r['subtitles'] ? (json_decode($r['subtitles'], true) ?: []) : [];
        $r['poster_url'] = !empty($r['poster']) ? ('https://image.tmdb.org/t/p/w154' . $r['poster']) : '';
        $r['is_series'] = ($r['media_type'] === 'series' || !empty($r['series_code']));
        $r['kind_label'] = $r['is_series'] ? 'TV' : 'Movie';
        unset($r['subtitles']);
    }
    unset($r);
    return $rows;
}

function m3uHms(float $seconds): string {
    if ($seconds <= 0) {
        return '0:00:00';
    }
    $s = (int) round($seconds);
    return sprintf('%d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
}

// Coarse device/client class from the user agent - enough to chart "what kind
// of thing requested each movie" without a heavy UA-parsing dependency.
function m3uDevice(string $ua): string {
    $u = strtolower($ua);
    $checks = [
        'Apple TV' => 'appletv', 'Android TV' => 'android tv', 'Roku' => 'roku',
        'Fire TV' => 'aftt', 'Smart TV' => 'smart-tv', 'Tizen' => 'tizen', 'Web OS' => 'webos',
        'VLC' => 'vlc', 'Kodi' => 'kodi', 'Infuse' => 'infuse', 'MX Player' => 'mxplayer',
        'ffmpeg' => 'ffmpeg', 'Exoplayer' => 'exoplayer', 'IINA' => 'iina', 'mpv' => 'mpv',
        'iPhone' => 'iphone', 'iPad' => 'ipad', 'Android' => 'android',
        'Windows' => 'windows', 'Macintosh' => 'macintosh', 'Linux' => 'linux',
        'curl' => 'curl', 'Chrome' => 'chrome', 'Firefox' => 'firefox', 'Safari' => 'safari',
    ];
    foreach ($checks as $label => $needle) {
        if (strpos($u, $needle) !== false) {
            return $label;
        }
    }
    return $ua === '' ? 'Unknown' : 'Other';
}

function m3uCountBy(array $sessions, string $key, int $top = 12): array {
    $counts = [];
    foreach ($sessions as $s) {
        $v = trim((string) ($s[$key] ?? ''));
        if ($v === '') {
            $v = 'Unknown';
        }
        $counts[$v] = ($counts[$v] ?? 0) + 1;
    }
    arsort($counts);
    $out = [];
    foreach (array_slice($counts, 0, $top, true) as $label => $n) {
        $out[] = ['label' => $label, 'count' => $n];
    }
    return $out;
}

function m3uApi(PDO $db, string $q): array {
    // Refresh the store from the log on demand; cheap because imports are
    // incremental (only new event-log bytes are read).
    if ($q === 'refresh' || $q === 'overview') {
        try {
            $imp = m3uRunImport($db);
        } catch (Throwable $e) {
            $imp = ['error' => 'import failed'];
        }
    }

    $sessions = m3uSessions($db, 1000);

    switch ($q) {
        case 'overview':
        case 'refresh':
            $resolves = (int) $db->query("SELECT COUNT(*) c FROM events WHERE type='resolve'")->fetch()['c'];
            $uniqueIps = (int) $db->query("SELECT COUNT(DISTINCT ip) c FROM events WHERE ip != ''")->fetch()['c'];
            $countries = (int) $db->query("SELECT COUNT(DISTINCT country_code) c FROM geo WHERE country_code != ''")->fetch()['c'];
            // Durable SQLite cache (source of truth), not the temporary cache.json snapshot.
            $now = time();
            $failed = (int) $db->query("SELECT COUNT(*) c FROM resolved_cache WHERE status='failed'")->fetch()['c'];
            $resolvedCache = (int) $db->query("SELECT COUNT(*) c FROM resolved_cache WHERE status='resolved' AND expires > $now")->fetch()['c'];
            $prewarmedCount = (int) $db->query("SELECT COUNT(*) c FROM resolved_cache WHERE prewarmed=1 AND status='resolved' AND expires > $now")->fetch()['c'];
            $cacheJson = m3uCacheJsonStatus();
            $avgResolve = $db->query("SELECT AVG(resolve_ms) a FROM events WHERE type='resolve' AND resolve_ms IS NOT NULL AND cache_hit=0")->fetch()['a'];
            $avgDeliver = $db->query("SELECT AVG(deliver_ms) a FROM events WHERE type='segment' AND outcome='success'")->fetch()['a'];

            // A timeline of resolves per day for the trend chart.
            $trend = $db->query("SELECT strftime('%Y-%m-%d', datetime(ts,'unixepoch')) d, COUNT(*) c
                                 FROM events WHERE type='resolve' AND ts IS NOT NULL
                                 GROUP BY d ORDER BY d DESC LIMIT 30")->fetchAll();

            $globe = [];
            foreach ($sessions as $s) {
                if ($s['lat'] !== null && $s['lon'] !== null) {
                    $globe[] = [
                        'lat' => (float) $s['lat'], 'lon' => (float) $s['lon'],
                        'city' => $s['city'], 'country' => $s['country'], 'flag' => $s['flag'],
                        'title' => $s['title'] ?: ('#' . $s['movie_id']), 'ip' => $s['ip'],
                    ];
                }
            }

            return [
                'ok' => true,
                'import' => $imp ?? null,
                'lastImport' => (int) m3uKvGet($db, 'last_import', '0'),
                'stats' => [
                    'resolves' => $resolves,
                    'sessions' => count($sessions),
                    'uniqueIps' => $uniqueIps,
                    'countries' => $countries,
                    'failedCache' => $failed,
                    'resolvedCache' => $resolvedCache,
                    'prewarmedCount' => $prewarmedCount,
                    'avgResolveMs' => $avgResolve ? round((float) $avgResolve) : null,
                    'avgDeliverMs' => $avgDeliver ? round((float) $avgDeliver) : null,
                ],
                'cacheJson' => $cacheJson,
                'charts' => [
                    'debrid' => m3uCountBy($sessions, 'debrid'),
                    'provider' => m3uCountBy($sessions, 'provider'),
                    'device' => m3uCountBy($sessions, 'device'),
                    'country' => m3uCountBy($sessions, 'country'),
                    'account' => m3uCountBy($sessions, 'username'),
                    'lang' => m3uCountBy($sessions, 'lang'),
                    'isp' => m3uCountBy($sessions, 'isp'),
                    'kind' => m3uCountBy($sessions, 'kind_label'),
                    'trend' => array_reverse($trend),
                ],
                'topMovies' => m3uTopMovies($sessions),
                'globe' => $globe,
            ];

        case 'sessions':
            return ['ok' => true, 'sessions' => $sessions];

        case 'config':
            return ['ok' => true, 'fields' => m3uEditableConfig(), 'values' => m3uReadConfigValues()];

        case 'cache':
            // Durable SQLite store (survives deploys), enriched with title/poster.
            $rows = $db->query("
                SELECT rc.cache_key, rc.value, rc.status, rc.added, rc.expires, rc.prewarmed,
                       rc.movie_id, rc.username, rc.lang, rc.media_type,
                       tm.title, tm.year, tm.poster
                FROM resolved_cache rc
                LEFT JOIN tmdb_meta tm ON tm.movie_id = rc.movie_id
                ORDER BY rc.updated DESC LIMIT 800")->fetchAll();
            foreach ($rows as &$r) {
                $r['poster_url'] = !empty($r['poster']) ? ('https://image.tmdb.org/t/p/w92' . $r['poster']) : '';
                $r['is_series'] = ($r['media_type'] === 'series');
                $r['expired'] = ((int) $r['expires']) <= time();
            }
            unset($r);
            return ['ok' => true, 'entries' => $rows, 'cacheJson' => m3uCacheJsonStatus()];

        case 'prewarmed':
            $now = time();
            $rows = $db->query("
                SELECT rc.cache_key, rc.value, rc.status, rc.added, rc.expires,
                       rc.movie_id, rc.username, rc.lang, rc.media_type,
                       tm.title, tm.year, tm.poster, tm.overview
                FROM resolved_cache rc
                LEFT JOIN tmdb_meta tm ON tm.movie_id = rc.movie_id
                WHERE rc.prewarmed=1
                ORDER BY rc.updated DESC LIMIT 500")->fetchAll();
            foreach ($rows as &$r) {
                $r['poster_url'] = !empty($r['poster']) ? ('https://image.tmdb.org/t/p/w154' . $r['poster']) : '';
                $r['is_series'] = ($r['media_type'] === 'series');
                $r['kind_label'] = $r['is_series'] ? 'TV' : 'Movie';
                $r['expired'] = ((int) $r['expires']) <= $now;
                $r['fresh'] = !$r['expired'] && $r['status'] === 'resolved';
            }
            unset($r);
            return ['ok' => true, 'entries' => $rows, 'cacheJson' => m3uCacheJsonStatus()];

        default:
            return ['ok' => false, 'error' => 'unknown query'];
    }
}

function m3uTopMovies(array $sessions): array {
    $byId = [];
    foreach ($sessions as $s) {
        $id = (int) $s['movie_id'];
        if ($id <= 0) {
            continue;
        }
        if (!isset($byId[$id])) {
            $byId[$id] = [
                'movie_id' => $id,
                'title' => $s['title'] ?: ('#' . $id),
                'year' => $s['year'] ?? '',
                'poster_url' => $s['poster_url'] ?? '',
                'overview' => $s['overview'] ?? '',
                'kind_label' => $s['kind_label'] ?? 'Movie',
                'is_series' => !empty($s['is_series']),
                'plays' => 0, 'debrid' => [], 'langs' => [],
            ];
        }
        $byId[$id]['plays']++;
        if (!empty($s['debrid'])) {
            $byId[$id]['debrid'][$s['debrid']] = ($byId[$id]['debrid'][$s['debrid']] ?? 0) + 1;
        }
        if (!empty($s['lang'])) {
            $byId[$id]['langs'][$s['lang']] = true;
        }
    }
    usort($byId, fn($a, $b) => $b['plays'] <=> $a['plays']);
    foreach ($byId as &$m) {
        arsort($m['debrid']);
        $m['debrid'] = array_keys($m['debrid']);
        $m['langs'] = array_keys($m['langs']);
    }
    unset($m);
    return array_slice(array_values($byId), 0, 24);
}

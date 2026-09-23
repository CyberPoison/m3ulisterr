# 🚀 Release Notes


<details><summary><b>🌐 English Changelog</b></summary>


### 📅 Update 09/23/2026

**New: `get_vod_availability` / `get_availability_batch` - tell a client which movies and shows have a cached candidate (used by Decypharr to hide titles that could never play):**
- **What it does:** `player_api.php?action=get_availability_batch&type=movie|series&items=<tmdb id>[:<year>],...` (up to 100 titles; `get_vod_availability&vod_id=` for one movie) asks AIOStreams for each title (same account-language rules, year/pack guard and zero-weight-service exclusion as the real resolver in `play.php`) and answers `available: true|false|null` per title. It only counts candidates already **cached** on a debrid service that sit on the ladder 1080p x264 -> 720p x264 -> 4K x264 -> 4K x265 -> 1080p x265 -> 720p x265 (a cached release outside the ladder - SD, AV1, unknown codec - is reported as `other_cached` but does not make a title available). A show counts as available when S01E01 or S02E01 has a cached candidate.
- **Fast when the upstream allows it:** up to `$availabilityParallel` lookups run at once inside one request (curl_multi), the result of each upstream lookup is cached **language-independently** (one lookup serves every account/language) for 6 hours in `M3U_DATA_DIR/availability`, and the release year comes from the client, so no TMDB call is made.
- **Never confuses "unknown" with "nothing available":** the public ElfHosted AIOStreams allows 100 searches then 1 per minute per client IP and, when exceeded, answers HTTP 200 with a single fake "rate-limit exceeded" stream. That answer - and any answer where an addon reported a real failure and nothing cached was found - is reported as `available: null` (HTTP 429 with `Retry-After` on the single endpoint), is never cached, and starts a 5-minute cooldown during which no upstream call is made. A title the addons simply do not know ("Failed to get metadata") is a definite "none".
- **Protects real playback:** a token bucket caps upstream lookups made for availability checks, because `play.php` uses the same server IP and the same rate limit. Defaults are deliberately tiny (0.5 lookups/minute, burst 5, 4 in parallel) unless a **dedicated** AIOStreams is configured. Optional `config.php` variables, all read with defaults so an older host `config.php` keeps working: `$availabilityAioStreamsUrl` (a private/self-hosted AIOStreams used only for these checks; unlocks the fast defaults of 3000 lookups/minute, burst 300, 64 in parallel), `$availabilityMaxPerMinute`, `$availabilityBurst`, `$availabilityParallel`.
- **Lives in its own shipped file (`aio_availability.php`), not `config.php`** - `config.php` is a bind-mounted host file the image never overwrites, so any function added there does not exist in a real deployment until someone edits the host copy by hand.
- **Verified:** `php tests/aio_availability_test.php` (41 unit checks: every ladder rung and the ladder order, uncached, SD/AV1, language only in subtitles, year guard, zero-weight service, throttle stub, degraded vs metadata-only errors, token bucket, cooldown, config defaults) and `bash tests/aio_availability_e2e.sh` (real `player_api.php` on dev servers against a mock AIOStreams: French vs English sharing one lookup, 502/429 handling, throttling mid-batch, series probes, 40 lookups of 1s finishing in about 2s, and the tiny shared-instance budget enforced).

- **`config.php` documents the new optional availability settings** (`$availabilityAioStreamsUrl`, `$availabilityMaxPerMinute`, `$availabilityBurst`, `$availabilityParallel`) as a commented-out block. Nothing changes until they are uncommented; remember that `config.php` is a host file in production, so they must be added to the host copy by hand.

**Correction to the earlier entry below (`get_vod_info`/`get_series_info` returning empty):** the stated root cause - `file_get_contents()` failing in production - was never actually reproduced and should not be relied on. The empty responses were reproduced locally with the exact production image and the real production `config.php`: the host `config.php` was missing functions the shipped code calls (`tmdbRegionForLang`/`tmdbCountryNames`/`tmdbPlatformNames`, and after that change also `makeGetRequest`), so PHP threw an uncaught `Error` that `error_reporting(0)` hid, giving HTTP 200 and an empty body. Any function a request depends on that lives in `config.php` must be copied into the host's `config.php` by hand; that is why new logic now goes in its own shipped file.

**`get_vod_info`/`get_series_info` were silently returning empty responses in production - fixed:**
- **Root cause:** both endpoints fetched TMDB movie/series details with a plain `@file_get_contents()` call. Confirmed directly against production (PHP 8.2.33 in the real container): this call fatals silently there - `error_reporting(0)` in production hides it, so the client just gets an HTTP 200 with a completely empty body and default (`text/html`) headers, meaning the fatal happens before any output is ever written. Not reproducible on a local PHP 8.5 install with the exact same code and TMDB response, so this is a production-environment-specific failure of `file_get_contents()`, not a logic bug in the endpoint itself.
- **Downstream impact:** this is why an external catalog consumer (a Decypharr-based WebDAV/rclone bridge for Plex) showed every TV show with zero episodes - `get_series_info` returning nothing meant there was nothing to list under Season → Episode, regardless of how many episodes the show actually has (confirmed on "Frontline" (1983), 45 seasons/855 episodes on TMDB, and reproduced identically on a plain 5-season show). `get_vod_info` (movie metadata) was equally affected.
- **Fix:** moved the existing, already-production-proven curl-based `makeGetRequest()` helper (previously only in `play.php`, which already uses it successfully for the same kind of TMDB calls) into `config.php` so `player_api.php` can use it too, and switched every TMDB `file_get_contents()` call in `get_vod_info`, `get_series_info` (both the single-batch and >20-season multi-batch paths), and `getTMDBTrailer()` to use it instead.
- **Tested locally**, including specifically reproducing the >20-season batch path (Frontline, 45 seasons) which previously would have returned nothing, and confirming `get_vod_info`/`get_series_info` now return the full expected payload (episodes, country, platform, imdb_id) for both a small and a large series.

### 📅 Update 09/22/2026

**New: `get_vod_info`/`get_series_info` now expose real country and streaming-platform data:**
- **`"country"` (production country names) and `"platform"` (current subscription-streaming services, region-matched to the requesting account's language) added to both endpoints' `info` object** - sourced directly from TMDB (`production_countries` and `watch/providers`, both already one API call away since `get_vod_info`/`get_series_info` already fetch TMDB details) - purely additive, existing clients that don't read these fields are unaffected. Built to support an external catalog consumer organizing titles by country/platform in addition to genre, without needing its own separate TMDB key.
- **Region-aware:** `platform` reflects the actual account's own region (`UnlimitedFR` sees French streaming availability, not US) via a new `tmdbRegionForLang()` mapping in `config.php`.
- **Tested locally** against real TMDB data for both a movie (Oppenheimer - confirmed different platform results between the English/US and French/FR accounts) and a series (Breaking Bad - confirmed Netflix correctly reported).

**Xtream Codes API (`player_api.php`) was completely broken - fixed two stacked bugs:**
- **A required file was never committed to this repo:** `generate_live_playlist.php` exists in the maintainer's own working copy but had never actually been added to git - so `player_api.php`'s very first `require_once` fatally errored on every single deployed instance, before any request (login, VOD listing, anything) could run at all. Added it.
- **Non-English accounts (e.g. `UnlimitedFR`) additionally hit a silent memory exhaustion** once the missing-file issue above is fixed: `get_vod_streams`/`get_series` load the full movie/series playlist (tens of MB of JSON) into memory, and appending `&lang=...` to every stream URL for a non-English account runs a `preg_replace` over the whole thing (see `injectStreamLang()`) - on a stock 128M `memory_limit` this fatals with "Allowed memory size exhausted", silently (production runs with `error_reporting(0)`), so the client gets an HTTP 200 with a completely empty body and no movies/shows at all. An English account never hit this, since it skips that regex entirely - which is why this looked account-specific. Fixed by giving `player_api.php` explicit headroom for its own known-heavy operation, and by no longer re-reading the same large file straight back off disk right after writing it (a wasteful second full in-memory copy).
- **Tested locally**, including specifically reproducing the exhaustion under the original 128M limit and confirming both `get_vod_streams` and `get_series` now return the full, correct payload (with `&lang=fr` correctly appended) under that same constrained limit.

### 📅 Update 09/17/2026

**AIOStreams language-match policy expanded — Multi/French-tagged candidates now accepted even when French is not the default track:**
- **Policy change (explicit operator decision):** French and proxy-mode candidates are now accepted if the requested language is present *anywhere* in the file (any audio track), not only when it is the default/first track. This lets the player switch tracks rather than the server rejecting a perfectly valid release. The previous behavior was overly strict and discarded good sources.
- **English gets a true last-resort tier:** for `Unlimited` / `?lang=eng` requests, if no English-tagged or Multi candidate is available, the best remaining candidate is now served in its original language rather than failing outright — a foreign-language film with no English dub can still play.

**Saga/franchise pack mismatch fix — AIOStreams now correctly rejects wrong-film candidates from multi-film packs:**
- **Root cause confirmed live on production:** AIOStreams (via mediafusion/comet) sometimes returns a candidate whose `behaviorHints.filename` is a *different* film in the same saga pack (e.g. a query for Harry Potter: Chamber of Secrets returned files from the Deathly Hallows Part 2 in the same 8-film pack). Nothing was checking the resolved candidate's identity before caching and serving it.
- **Fix:** a lightweight year check on `behaviorHints.filename` — if the filename contains an explicit year AND it differs from the requested title's year, the candidate is rejected. A filename with no year at all is let through (many legitimate single-film releases omit the year). Different films in a saga almost always differ by year; this reliably catches the mismatch without breaking French-named releases (which `filterCompareTitles()` would reject as it is language-sensitive).
- **Regression found and fixed same day after deploying to production:** the first version of the check was too strict (rejecting any filename that simply lacked the year, not just one carrying a confirmed different year). Re-verified on all 8 real Harry Potter films on both English and French accounts.

**Dashboard: `&dev=true` resolves were silently never logged (fixed):**
- **Bug:** every debug-mode branch in `play.php` called `writeToCache()` but skipped `m3uLogResolution()`. Any URL first resolved via `&dev=true` would permanently show blank Release and Debrid columns on the dashboard for every real viewer's subsequent cache hit — and AllDebrid never appeared in the Overview aggregate chart even when it was confirmed picked on every test resolve.
- **Fix:** added `m3uLogResolution()` into all four DEBUG branches (cache-hit and fresh-resolve, movies and series) before their `exit()`. Debug-mode trace output is otherwise unchanged.

**Weighted AllDebrid/Premiumize preference — now forceful, tier-overriding, and verified live:**
- **`$aioDebridWeights` is now a genuine per-resolve preference, not a tie-break:** a weight like `alldebrid => 70, premiumize => 30` makes AllDebrid win roughly 70% of resolves, overriding both quality rank AND language-match tier (tiers 0–2 grouped — a French "Multi" release can now beat a French confirmed-default one from the other service) to keep call volume off a quota-limited service. A cached candidate still always beats a not-yet-cached one regardless of weight. Tier 3 (no language match at all) stays a hard last-resort regardless of weight.
- **A weight of exactly 0 is a hard exclusion:** removes that service's candidates entirely before tiers and quality are computed. If the other service has nothing, the resolve falls through to the next provider rather than ever using the zeroed-out service.
- **Fixed a cache-hit link-liveness check that was a no-op:** the check used to unconditionally trust any `video_proxy.php` URL — which is virtually every AIOStreams candidate — without actually issuing a real probe. A cached link that went dead upstream was blindly served to real players. Now issues a real HEAD check via `checkVideoProxyUpstreamAlive()` and triggers a fresh resolve on a confirmed dead link.
- **Verified against live production data**, including forcing and confirming failure and recovery of a real dead cached link.

**Block/limit notice and "not available" notice now play as real video on all IPTV players:**
- **Fixed "Source Error" on IMPlayer, MyTVOnline3, STBEMU, etc.:** the notice screen was previously delivered as `multipart/x-mixed-replace` (MJPEG — a browser-only IP-camera technique). Real video players reject this immediately. It is now a live-generated H.264/AAC MPEG-TS stream (piped through ffmpeg, nothing written to disk), which every real player decodes correctly.
- **New "not available yet" notice:** a title with no available stream now shows a branded notice video instead of a bare player error — covers movies, TV episodes, and adult content.
- **Tested locally:** both notices decode cleanly via `ffprobe`/`ffmpeg` frame extraction and are correctly paced to real-time.

### 📅 Update 09/16/2026

**AllDebrid fixes across both resolve paths, plus AIOStreams reliability improvements:**
- **AllDebrid/TorBox were silently unreachable via `torrentSites` (fixed):** three functions in `play.php` were missing `$useAllDebrid`/`$useTorBox` in their `global` declarations, so every check silently evaluated to `false`. AllDebrid and TorBox via the direct-torrent path never actually ran regardless of what keys were configured.
- **AllDebrid v4.1 API migration (fixed):** AllDebrid discontinued `/v4/magnet/status`. Migrated to `/v4.1/magnet/status` with its new `files[].n`/`.l` response shape. Also added a video-extension allowlist so the file picker no longer accidentally picks a subtitle or poster file from a multi-file torrent.
- **AIOStreams playable-check false rejections (fixed):** two bugs in the batch playability check were incorrectly rejecting valid candidates — (1) transient elfhosted 5xx responses treated as permanent failures; (2) `curl_getinfo()` returns `false` (not `''`) for a missing Content-Type header, which was incorrectly treated as a wrong-type rejection. Fixed: retries once for genuinely transient failures only; non-transient rejections (4xx, slate page, confirmed wrong type) are never retried.
- **AIOStreams candidate shuffling before sort:** when multiple candidates from different debrid services have the same tier and quality, they are now shuffled before the final sort so the selection is random rather than always landing on whichever service AIOStreams happened to list first.
- **Prewarm `--expiring` mode (new):** `prewarm.php --expiring[=N]` reads its target list from the durable `resolved_cache` SQLite table and re-warms entries that have already expired or will within N minutes. Default cron args now use `--expiring=150` ahead of `--playlist=top`, so proven-demand titles are re-warmed before speculative seeding. Each entry is re-warmed under its original account and language — no cross-account multiplication.
- **Loopback self-block fix:** the prewarm cron calls `play.php` via `127.0.0.1`. If that IP had tripped the daily rate limit or been manually blocked, every prewarm job silently got the 10-minute block-notice stream instead of a real resolve. Fixed: `127.0.0.1`/`::1` are now unconditionally trusted before any block/rate-limit check — the server calling itself cannot be spoofed via `REMOTE_ADDR`.
- **Verified:** AllDebrid v4.1 end-to-end with a real magnet (upload → status → unlock → streamable link including range requests); playable-check fixes verified with a mock HTTP server covering all four cases; prewarm `--expiring` verified locally end-to-end including a confirmed cold re-resolve refreshing the durable cache row.

### 📅 Update 09/16/2026

**AllDebrid fixes, both on the AIOStreams path and the direct-torrent path:**
- **AIOStreams no longer favors Premiumize by default:** when AIOStreams offers multiple equally-good cached streams (same tier, same quality) from different debrid services, candidates are now shuffled before the final sort so the tie is broken at random instead of always landing on whichever service the AIOStreams API happened to list first. Genuine quality/cache-status differences still decide the winner every time — this only spreads selection across debrid services (AllDebrid included) when they're truly tied, giving AllDebrid a fair shot instead of Premiumize winning by array-order accident.
- **Direct-AllDebrid resolving fixed (`torrentSites` path, `debrid.php`):** AllDebrid discontinued their `/v4/magnet/status` endpoint, which silently broke every direct AllDebrid resolve (magnets uploaded fine, but status checks always failed). Migrated to the new `/v4.1/magnet/status` endpoint and its different response shape. Also fixed a related bug this migration exposed: the file list returned by that endpoint includes every file in a torrent (subtitles, posters, samples...), not just the video, so a missing extension filter could pick a non-video file — now only actual video files are considered.
- **Verified against the live AllDebrid API** with a real magnet end-to-end (upload → status → unlock → final streamable link, including HTTP range-request support needed for seeking) and against real AIOStreams data confirming Premiumize/AllDebrid ties genuinely occur on real titles.

### 📅 Update 09/14/2026

**A large reliability, language, subtitle, analytics, and security pass. Highlights:**
- **M3uListerr analytics dashboard (new):** a self-contained `dashboard.php` that logs and visualizes every playback. See [M3uListerr Dashboard](#-m3ulisterr-analytics-dashboard).
- **Correct-language audio:** multi-audio releases no longer play the wrong language. The picked audio track is now chosen from the file's own header (e.g., an "ITA ENG" release plays English, not Italian) and the HLS `LANGUAGE` tag reports what actually plays instead of always claiming English.
- **French account / `?lang=fr`:** a dedicated French path that only accepts releases whose default audio is really French, preferring cached torrents and an x264 1080p→720p→SD quality ladder. French streams are served through a lightweight byte-proxy (no ffmpeg) that resolves the source once and streams ranges, avoiding debrid IP rate-limiting.
- **Subtitles:** both European (pt-PT) and Brazilian (pt-BR) Portuguese subtitle tracks are offered, with a direct OpenSubtitles API fallback for when the bundled provider is down, and full subtitle support for TV series episodes.
- **Playback stability:** fixed duplicated frames / timestamp discontinuities at segment boundaries, and fixed a memory-exhaustion bug that silently produced empty segments (endless buffering) on high-bitrate 4K/remux sources.
- **Security hardening:** blocked public access to sensitive files (`.git`, `cache.json`, logs, `config.php`, the dashboard's private data), added an SSRF guard to the video proxy, and a strict Content-Security-Policy, CSRF protection, session hardening, and login lockout on the dashboard.
- **Multi-service debrid with key fallback (new):** the torrent resolve path now supports **Real-Debrid, Premiumize, AllDebrid and TorBox** (`debrid.php`), tried in order until one returns a working link. Each service can hold **as many API keys as you like** via `$debridApiKeys` in `config.php`; when one key hits its quota / fair-use / hoster limit the resolver automatically rotates to the next key for that service.
- **Faster first play + durable cache (new):** resolved stream URLs are now persisted to a durable SQLite store (source of truth) that survives deploys, in addition to the temporary hot `cache.json`.
- **Config editor upgrades:** the dashboard `config.php` editor can now edit credentials and URLs with show/hide masking.
- **IP blocking & per-IP daily rate limit (new):** block any abusive client IP with one click from the dashboard *Sessions* table. Separately, configurable per-IP daily request limits in `config.php` cap how many titles a single IP may request per day, with separate caps for movies and TV episodes.
- **IP whitelist (new):** whitelist your own office/VPN/dev IP with one click from the dashboard *Sessions* table (blue shield button).
- **Adult content in the dashboard (new):** Sessions and Titles now show the correct poster, name, and id for adult titles too.

### 📅 Update 09/28/2025

- **Live TV:** Fixed the Live TV section and added DrewLive, a massive all in one source of 7,000+ channels.
- **Read Debrid:** Fixed Read Debrid cache checks and added Streamio Sites as a debrid source.
- **Stream sources:** Cleaned up and removed several direct stream sources in both the main script and HeadlessVidX to improve reliability.
- **Adult VOD:** Fixed the Adult VOD source, the 10,000 title adult movie library now refreshes automatically every Sunday.
- **HeadlessVidX:** Major overhaul and bug fixes. The software had numerous issues and I spent several weeks stabilizing it.
- **Overall:** Much of the project had broken after more than a year without updates. Things are working much better now, and I’ve got plans to add more features in upcoming releases.

### 📌 Previous Changes and Additions
- Added the Premiumize service as an alternative to Real-Debrid. (used only with torrent sites)
- Added threads when searching torrent sites for magnet links. (speeds up the time it takes to find a link)
- Added and fixed direct movie and TV show sources as well as more link extractors.
- Added TheTvApp sports section in the Live TV Playlist (set your app to load EPG and playlist every 12 hours or less.)
- Added PlutoTV to the live TV playlist (Multi Languages Here: https://github.com/matthuisman/i.mjh.nz)
- Redesigned the Live TV and DaddyLive functions and playlist. (all of the images in the playlist are working)
- Fixed a lot of bugs in the torrent search and filtering functions. (it finds links much more often now)
- Fixed the sorting by resolution and more likely to get higher quality links (torrent sites)
- Added adult movies to vod (disabled by default)

---


</details>

<details><summary><b>🌐 AR Changelog</b></summary>


### تحديث 14/09/2026

تحديث كبير يركز على الموثوقية، اللغة، الترجمة، التحليلات والأمان. أبرز الميزات:

- <strong>لوحة تحكم تحليلات M3uListerr (جديدة):</strong> ملف `dashboard.php` مستقل يسجل ويصور كل عملية تشغيل — كرة أرضية تفاعلية، رسوم بيانية وجدول جلسات يعرض العنوان/الملصق، الفيلم مقابل المسلسل التلفزيوني، الحساب، اللغة المطلوبة والصوتية، الإصدار الدقيق وخدمة debrid المستخدمة، تقدم التشغيل (النسبة المئوية و h:mm:ss)، الترجمات المعروضة، البلد/المدينة/مزود خدمة الإنترنت، الجهاز ووكيل المستخدم، عنوان IP، ووقت الاستجابة. محمي بتسجيل دخول مع متجر SQLite خاص، بالإضافة إلى محرر `config.php` مدمج وإدارة بيانات الاعتماد.
- <strong>صوت باللغة الصحيحة:</strong> لم تعد الإصدارات متعددة الصوتيات تشغل لغة خاطئة. يتم الآن اختيار المسار الصوتي من رأس الملف نفسه (على سبيل المثال، إصدار "ITA ENG" يعرض الإنجليزية، وليس الإيطالية) وتعرض علامة `LANGUAGE` في HLS ما يتم تشغيله فعليًا بدلاً من الادعاء دائمًا أنها إنجليزية.
- <strong>حساب فرنسي / `?lang=fr`:</strong> مسار فرنسي مخصص يقبل فقط الإصدارات التي يكون الصوت الافتراضي فيها فرنسيًا حقًا (يتم التحقق منه من رأس الحاوية، وليس فقط من العلامة)، مع تفضيل التورنت المخزن مؤقتًا وسلم جودة x264 1080p→720p→SD (يؤدي `?codec=x265` إلى التبديل إلى سلم يفضل HEVC أولاً). يتم تقديم التدفقات الفرنسية من خلال وكيل بايت خفيف الوزن (بدون ffmpeg) يحل المصدر مرة واحدة ويبث النطاقات، متجنبًا تقييد معدل عنوان IP الخاص بـ debrid.
- <strong>الترجمات:</strong> يتم تقديم مسارات الترجمة البرتغالية الأوروبية (pt-PT) والبرازيلية (pt-BR)، مع واجهة برمجة تطبيقات احتياطية مباشرة من OpenSubtitles عندما يكون المزود المدمج معطلاً، ودعم كامل للترجمة لحلقات المسلسلات التلفزيونية.
- <strong>استقرار التشغيل:</strong> تم إصلاح الإطارات المكررة / الانقطاعات الزمنية عند حدود المقاطع، وتم إصلاح خطأ استنفاد الذاكرة الذي أنتج بصمت مقاطع فارغة (تخزين مؤقت لا نهاية له) على مصادر 4K / remux ذات معدل البت العالي.
- <strong>تعزيز الأمان:</strong> منع الوصول العام إلى الملفات الحساسة (`.git`، `cache.json`، السجلات، `config.php`، البيانات الخاصة بلوحة التحكم)، تمت إضافة حارس SSRF إلى وكيل الفيديو، وسياسة Content-Security-Policy صارمة، وحماية من CSRF، وتقوية الجلسة وقفل تسجيل الدخول على لوحة التحكم.

### تحديث 28/09/2025

- <strong>البث التلفزيوني المباشر:</strong> تم إصلاح قسم البث التلفزيوني المباشر وإضافة DrewLive، وهو مصدر شامل ضخم يضم أكثر من 7000 قناة.
- <strong>Read Debrid:</strong> تم إصلاح فحوصات ذاكرة التخزين المؤقت لـ Read Debrid وإضافة Streamio Sites كمصدر debrid (سيتم دعم المزيد من خدمات debrid قريبًا).
- <strong>مصادر البث:</strong> تم تنظيف وإزالة العديد من مصادر البث المباشر في كل من البرنامج النصي الرئيسي و HeadlessVidX لتحسين الموثوقية.
- <strong>VOD للبالغين:</strong> تم إصلاح مصدر VOD للبالغين، حيث يتم الآن تحديث مكتبة أفلام البالغين التي تضم 10000 عنوان تلقائيًا كل يوم أحد.
- <strong>HeadlessVidX:</strong> إصلاح شامل وإصلاحات للأخطاء. كان البرنامج يعاني من العديد من المشكلات وقضيت عدة أسابيع في تثبيته ورفعه إلى المستوى الذي أردته.
- <strong>بشكل عام:</strong> تعطل جزء كبير من المشروع بعد أكثر من عام دون تحديثات. تعمل الأمور بشكل أفضل بكثير الآن، ولدي خطط لإضافة المزيد من الميزات في الإصدارات القادمة.

### التغييرات والإضافات السابقة

- تمت إضافة خدمة Premiumize كبديل لـ Real-Debrid. (تستخدم فقط مع مواقع التورنت)
- تمت إضافة خيوط عند البحث في مواقع التورنت عن روابط المغناطيس. (يسرع الوقت المستغرق للعثور على رابط)
- تمت إضافة وإصلاح مصادر الأفلام والبرامج التلفزيونية المباشرة بالإضافة إلى المزيد من مستخرجات الروابط.
- تمت إضافة قسم الرياضة TheTvApp في قائمة تشغيل البث التلفزيوني المباشر (اضبط تطبيقك لتحميل EPG وقائمة التشغيل كل 12 ساعة أو أقل).
- تمت إضافة PlutoTV إلى قائمة تشغيل البث التلفزيوني المباشر (لغات متعددة هنا: https://github.com/matthuisman/i.mjh.nz)
- إعادة تصميم وظائف البث التلفزيوني المباشر و DaddyLive وقائمة التشغيل. (جميع الصور في قائمة التشغيل تعمل)
- تم إصلاح الكثير من الأخطاء في وظائف البحث عن التورنت وتصفيتها. (يجد الروابط في كثير من الأحيان الآن)
- تم إصلاح الفرز حسب الدقة ومن المرجح أن تحصل على روابط عالية الجودة (مواقع التورنت)
- تمت إضافة أفلام البالغين إلى vod (معطلة افتراضيًا)


</details>

<details><summary><b>🌐 DE Changelog</b></summary>


### Update 17.09.2026

**AIOStreams-Sprachübereinstimmungsrichtlinie erweitert — Multi/French-Kandidaten werden jetzt auch akzeptiert, wenn die Zielsprache nicht die Standardspur ist:**
- **Richtlinienänderung (explizite Entscheidung des Betreibers):** Französische und Proxy-Kandidaten werden jetzt akzeptiert, wenn die angeforderte Sprache *irgendwo* in der Datei vorhanden ist (auf einer beliebigen Audiospur), nicht nur wenn sie die Standardspur ist. Der Player kann Spuren wechseln, anstatt dass der Server eine gültige Quelle ablehnt.
- **Englisch erhält einen echten Letzten-Ausweg-Tier:** Wenn keine englisch- oder Multi-markierten Kandidaten verfügbar sind, wird der beste verbleibende Kandidat in seiner Originalsprache geliefert, anstatt vollständig zu scheitern.

**Saga/Pack-Abweichungs-Korrektur — AIOStreams lehnt jetzt falsche Film-Kandidaten aus Multi-Film-Packs ab:**
- **Ursache in der Produktion bestätigt:** AIOStreams gab manchmal einen Kandidaten zurück, dessen `behaviorHints.filename` ein *anderer* Film aus demselben Saga-Pack war (z.B. Abfrage für Harry Potter: Kammer des Schreckens lieferte Dateien von den Heiligtümern des Todes aus demselben 8-Film-Pack).
- **Korrektur:** Leichte Jahresprüfung in `behaviorHints.filename` — wenn der Dateiname ein explizites Jahr enthält, das vom angeforderten Titeljar abweicht, wird der Kandidat abgelehnt. Ein Dateiname ohne Jahr wird akzeptiert.
- **Regression am selben Tag nach Produktionsbereitstellung gefunden und behoben.** Für alle 8 Harry-Potter-Filme auf beiden Konten re-verifiziert.

**Dashboard: `&dev=true`-Auflösungen wurden nie protokolliert (behoben):**
- **Bug:** Debug-Zweige in `play.php` riefen `writeToCache()` auf, übersprangen aber `m3uLogResolution()`. Jede URL, die zuerst über `&dev=true` aufgelöst wurde, zeigte permanent leere Release- und Debrid-Spalten im Dashboard, und AllDebrid erschien nie in der Gesamtstatistik.
- **Korrektur:** `m3uLogResolution()` wurde in alle vier DEBUG-Zweige vor ihren `exit()`-Aufrufen eingefügt.

**Gewichtete AllDebrid/Premiumize-Präferenz — jetzt durchsetzend, überschreibt Tier, in der Produktion verifiziert:**
- **`$aioDebridWeights` ist jetzt eine echte Präferenz pro Auflösung:** Ein Gewicht wie `alldebrid => 70, premiumize => 30` lässt AllDebrid ~70% der Auflösungen gewinnen, überschreibt sowohl Qualitätsrang als auch Sprachübereinstimmungs-Tier (Tiers 0–2 gruppiert). Ein gecachter Kandidat schlägt immer einen nicht gecachten, unabhängig vom Gewicht. Tier 3 bleibt absoluter letzter Ausweg.
- **Ein Gewicht von genau 0 ist ein harter Ausschluss:** Entfernt die Kandidaten dieses Dienstes vor der Tier- und Qualitätsberechnung.
- **Korrektur der Link-Liveness-Prüfung für Cache-Treffer, die wirkungslos war:** Die Prüfung vertraute bedingungslos jeder `video_proxy.php`-URL ohne echte Überprüfung. Behoben via `checkVideoProxyUpstreamAlive()`.

**Blockierungs-/Limit-Benachrichtigung wird jetzt als echtes Video auf allen IPTV-Playern wiedergegeben:**
- **"Source Error" auf IMPlayer, MyTVOnline3, STBEMU usw. behoben:** Der Benachrichtigungsbildschirm wurde als `multipart/x-mixed-replace` (MJPEG — nur für Browser) geliefert. Jetzt ist es ein live generierter H.264/AAC MPEG-TS-Stream via ffmpeg, es werden keine Dateien auf die Festplatte geschrieben.
- **Neue "Noch nicht verfügbar"-Benachrichtigung:** Ein Titel ohne verfügbaren Stream zeigt jetzt einen Benachrichtigungs-Videobildschirm anstatt eines nackten Player-Fehlers.

### Update 16.09.2026

**AllDebrid-Korrekturen auf beiden Auflösungspfaden, plus AIOStreams-Zuverlässigkeitsverbesserungen:**
- **AllDebrid/TorBox über `torrentSites` unerreichbar (behoben):** Drei Funktionen in `play.php` fehlten `$useAllDebrid`/`$useTorBox` in ihren `global`-Deklarationen — alle Prüfungen werteten stillschweigend `false` aus. AllDebrid und TorBox über den direkten Torrent-Pfad liefen nie tatsächlich.
- **AllDebrid v4.1 API-Migration (behoben):** AllDebrid hat `/v4/magnet/status` eingestellt. Migriert zu `/v4.1/magnet/status` mit neuer `files[].n`/`.l`-Antwortform. Videoerweiterungs-Allowlist hinzugefügt, um keine Untertitel- oder Coverdateien auszuwählen.
- **Falsche Ablehnungen bei AIOStreams-Wiedergabeprüfung (behoben):** Transiente 5xx-Fehler als permanent behandelt, und `curl_getinfo()` das `false` für fehlenden Content-Type zurückgibt, als falscher Typ behandelt. Behoben: ein einzelner Wiederholungsversuch nur für echte transiente Fehler.
- **Prewarm `--expiring`-Modus (neu):** `prewarm.php --expiring[=N]` wärmt dauerhafter Cache-Einträge vor, bevor sie ablaufen, unter Verwendung des Original-Kontos und -Sprache jedes Eintrags. Standard-Cron-Argumente jetzt mit `--expiring=150`.
- **Loopback-Selbst-Blockierungs-Korrektur:** `127.0.0.1`/`::1` werden jetzt bedingungslos vertraut, bevor eine Block-/Rate-Limit-Prüfung erfolgt — der Server, der sich selbst anruft, kann nicht über `REMOTE_ADDR` gefälscht werden.

### Update 14.09.2026

Ein großes Update für Zuverlässigkeit, Sprachen, Untertitel, Analysen und Sicherheit. Highlights:

- **M3uListerr Analyse-Dashboard (neu):** ein eigenständiges `dashboard.php`, das jede Wiedergabe protokolliert und visualisiert — ein interaktiver Globus, Diagramme und eine Sitzungstabelle mit Titel/Poster, Film vs. TV-Serie, Konto, angeforderter & Audio-Sprache, dem genauen Release und verwendetem Debrid-Dienst, Wiedergabefortschritt (% und h:mm:ss), angebotenen Untertiteln, Land/Stadt/ISP, Gerät und User-Agent, IP und Auflösungszeit. Login-geschützt mit einem privaten SQLite-Speicher, plus integriertem `config.php`-Editor und Anmeldeinformationsverwaltung. Siehe [M3uListerr Dashboard](#m3ulisterr-analyse-dashboard).
- **Audio in der richtigen Sprache:** Multi-Audio-Releases spielen nicht mehr die falsche Sprache ab. Die ausgewählte Audiospur wird nun aus dem Header der Datei selbst gewählt (z.B. spielt ein "ITA ENG"-Release Englisch, nicht Italienisch) und der HLS `LANGUAGE`-Tag meldet, was tatsächlich gespielt wird, anstatt immer Englisch zu behaupten.
- **Französisches Konto / `?lang=fr`:** ein dedizierter französischer Pfad, der nur Releases akzeptiert, deren Standard-Audio wirklich Französisch ist (verifiziert über den Container-Header, nicht nur den Tag), bevorzugt gecachte Torrents und eine x264 1080p→720p→SD Qualitätsleiter (`?codec=x265` wechselt zu einer HEVC-zuerst Leiter). Französische Streams werden über einen leichtgewichtigen Byte-Proxy (kein ffmpeg) bereitgestellt, der die Quelle einmal auflöst und Bereiche streamt, wodurch die IP-Ratenbegrenzung von Debrid vermieden wird.
- **Untertitel:** Es werden sowohl europäische (pt-PT) als auch brasilianische (pt-BR) portugiesische Untertitelspuren angeboten, mit einem direkten OpenSubtitles-API-Fallback für den Fall, dass der gebündelte Anbieter ausgefallen ist, und voller Untertitelunterstützung für TV-Serien-Episoden.
- **Wiedergabestabilität:** Duplizierte Frames / Zeitstempel-Diskontinuitäten an Segmentgrenzen behoben und ein Fehler bei der Speicherauslastung behoben, der bei hochratigen 4K/Remux-Quellen unbemerkt leere Segmente (endloses Puffern) erzeugte.
- **Sicherheitsverbesserungen:** Öffentlicher Zugriff auf sensible Dateien blockiert (`.git`, `cache.json`, Protokolle, `config.php`, die privaten Daten des Dashboards), ein SSRF-Schutz für den Videoproxy hinzugefügt und eine strikte Content-Security-Policy, CSRF-Schutz, Sitzungshärtung und Login-Sperre auf dem Dashboard.

### Update 28.09.2025

- **Live-TV:** Die Live-TV-Sektion wurde repariert und DrewLive hinzugefügt, eine riesige All-in-One-Quelle mit über 7.000 Kanälen.
- **Read Debrid:** Read Debrid Cache-Prüfungen repariert und Streamio Sites als Debrid-Quelle hinzugefügt (Unterstützung für weitere Debrid-Dienste folgt in Kürze).
- **Stream-Quellen:** Mehrere direkte Stream-Quellen im Hauptskript und HeadlessVidX aufgeräumt und entfernt, um die Zuverlässigkeit zu verbessern.
- **Erwachsenen-VOD:** Die Quelle für Erwachsenen-VOD repariert, die Bibliothek mit 10.000 Filmen für Erwachsene wird jetzt jeden Sonntag automatisch aktualisiert.
- **HeadlessVidX:** Größere Überarbeitung und Fehlerbehebungen. Die Software hatte zahlreiche Probleme, und ich verbrachte mehrere Wochen damit, sie zu stabilisieren und auf den von mir gewünschten Standard zu bringen.
- **Insgesamt:** Ein großer Teil des Projekts war nach mehr als einem Jahr ohne Updates kaputtgegangen. Die Dinge funktionieren jetzt viel besser, und ich habe Pläne, in kommenden Releases weitere Funktionen hinzuzufügen.

## 📝 Änderungen und Ergänzungen

- Der Premiumize-Dienst wurde als Alternative zu Real-Debrid hinzugefügt. (nur bei Torrent-Seiten verwendet)
- Threads beim Durchsuchen von Torrent-Seiten nach Magnet-Links hinzugefügt. (beschleunigt die Zeit, um einen Link zu finden)
- Direkte Film- und TV-Show-Quellen sowie weitere Link-Extraktoren hinzugefügt und repariert.
- TheTvApp Sport-Bereich in der Live-TV Playlist hinzugefügt (stellen Sie Ihre App so ein, dass sie EPG und Playlist alle 12 Stunden oder weniger lädt.)
- PlutoTV zur Live-TV-Playlist hinzugefügt (Mehrere Sprachen hier: https://github.com/matthuisman/i.mjh.nz)
- Die Live-TV- und DaddyLive-Funktionen und -Playlists neu gestaltet. (alle Bilder in der Playlist funktionieren)
- Viele Fehler in den Torrent-Such- und Filterfunktionen behoben. (es findet jetzt viel häufiger Links)
- Sortierung nach Auflösung repariert und höhere Wahrscheinlichkeit, qualitativ hochwertigere Links zu erhalten (Torrent-Seiten)
- Erwachsenenfilme zum VOD hinzugefügt (standardmäßig deaktiviert)


</details>

<details><summary><b>🌐 EL Changelog</b></summary>


### Ενημέρωση 14/09/2026
Ένα μεγάλο update για αξιοπιστία, γλώσσα, υπότιτλους, analytics και ασφάλεια. Κύρια σημεία:
- **Πίνακας ελέγχου analytics M3uListerr (νέο):** Διαδραστική υδρόγειος, γραφήματα, και αναλυτικός πίνακας συνεδριών.
- **Σωστή γλώσσα ήχου:** Το σύστημα διαβάζει τα δεδομένα ήχου μέσα από τα αρχεία για τέλεια επιλογή γλώσσας, όχι μόνο από την ετικέτα.
- **Γαλλικός λογαριασμός / `?lang=fr`:** Μια ειδική διαδρομή που διασφαλίζει ότι η προεπιλεγμένη γλώσσα είναι τα γαλλικά, αποτρέποντας προβλήματα rate-limiting από υπηρεσίες debrid μέσω byte-proxy.
- **Υπότιτλοι:** Υποστήριξη για Ευρωπαϊκά (pt-PT) και Βραζιλιάνικα (pt-BR) Πορτογαλικά, με άμεση εναλλακτική λύση μέσω OpenSubtitles API.
- **Σταθερότητα αναπαραγωγής:** Διορθώθηκαν διπλότυπα καρέ / ασυνέχειες χρονικής σήμανσης (timestamp discontinuities) και σφάλματα εξάντλησης μνήμης σε πηγές 4K/remux υψηλού bitrate.
- **Ενίσχυση ασφάλειας:** Προστασία ευαίσθητων αρχείων (`.git`, `cache.json`, καταγραφές κ.α.), προσθήκη SSRF guard στον video proxy και αυστηρό CSP στο dashboard.

### Ενημέρωση 28/09/2025
- **Ζωντανή Τηλεόραση:** Διορθώθηκε η ενότητα Ζωντανής Τηλεόρασης και προστέθηκε το DrewLive, μια τεράστια πηγή "όλα σε ένα" με 7.000+ κανάλια.
- **Read Debrid:** Διορθώθηκαν οι έλεγχοι της μνήμης cache και προστέθηκε το Streamio Sites ως πηγή debrid.
- **Πηγές ροής (Stream):** Εκκαθαρίστηκαν αρκετές άμεσες πηγές για βελτίωση της συνολικής αξιοπιστίας.
- **Ενηλίκων VOD:** Διορθώθηκε η πηγή VOD ενηλίκων, η βιβλιοθήκη ταινιών ανανεώνεται τώρα αυτόματα κάθε Κυριακή (10.000+ τίτλοι).
- **HeadlessVidX:** Σημαντική ανανέωση και διορθώσεις σφαλμάτων για ενίσχυση της σταθερότητας.

---


</details>

<details><summary><b>🌐 FR Changelog</b></summary>


### Mise à jour 09/17/2026

**Extension de la politique de correspondance linguistique AIOStreams — candidats Multi/tagués French acceptés même si le français n'est pas la piste par défaut :**
- **Changement de politique (décision explicite de l'opérateur) :** les candidats français et en mode proxy sont désormais acceptés si la langue demandée est présente *n'importe où* dans le fichier (toute piste audio), et non uniquement lorsqu'elle est la piste par défaut. Le lecteur peut changer de piste plutôt que le serveur rejeter une source valide.
- **L'anglais obtient un vrai niveau de dernier recours :** pour les requêtes `Unlimited`/`?lang=eng`, si aucun candidat anglais ou Multi n'est disponible, le meilleur candidat restant est servi dans sa langue d'origine plutôt que d'échouer.

**Correction du décalage saga/pack — AIOStreams rejette désormais les candidats du mauvais film dans les packs multi-films :**
- **Cause confirmée en production :** AIOStreams renvoyait parfois un candidat issu d'un film *différent* du même pack saga (ex : requête pour Harry Potter: La Chambre des Secrets → fichiers des Reliques de la Mort du même pack 8 films).
- **Correction :** vérification légère de l'année sur `behaviorHints.filename` — si le nom de fichier contient une année explicite différant de celle du titre demandé, le candidat est rejeté. Un nom de fichier sans année est accepté.
- **Régression trouvée et corrigée le même jour** après déploiement en production. Re-vérifiée sur les 8 films Harry Potter réels sur les deux comptes.

**Tableau de bord : les résolutions `&dev=true` n'étaient jamais enregistrées (corrigé) :**
- **Bug :** les branches debug appelaient `writeToCache()` mais ignoraient `m3uLogResolution()`. Toute URL résolue via `&dev=true` affichait des colonnes Release et Debrid vides sur le tableau de bord, et AllDebrid n'apparaissait jamais dans les statistiques globales.
- **Correction :** `m3uLogResolution()` ajouté dans les quatre branches DEBUG avant leur `exit()`.

**Préférence pondérée AllDebrid/Premiumize — désormais contraignante, remplace les niveaux, vérifiée en production :**
- **`$aioDebridWeights` est une vraie préférence par résolution :** un poids comme `alldebrid => 70, premiumize => 30` fait gagner AllDebrid sur ~70% des résolutions, surpassant aussi bien le rang de qualité que le niveau de correspondance linguistique (niveaux 0–2 groupés). Un candidat en cache bat toujours un non mis en cache quel que soit le poids. Le niveau 3 reste un dernier recours absolu.
- **Un poids de 0 est une exclusion stricte :** supprime les candidats de ce service avant tout calcul de niveau ou de qualité.
- **Correction du contrôle de validité des liens mis en cache :** le contrôle faisait confiance inconditionnellement à toute URL `video_proxy.php` sans la vérifier. Corrigé via `checkVideoProxyUpstreamAlive()`.

**Écran de blocage/limite désormais lu comme une vraie vidéo sur tous les lecteurs IPTV :**
- **Corrigé "Source Error" sur IMPlayer, MyTVOnline3, STBEMU, etc. :** l'écran de notification était livré en `multipart/x-mixed-replace` (MJPEG — technique réservée aux navigateurs). C'est maintenant un flux MPEG-TS H.264/AAC généré en direct via ffmpeg, rien n'est écrit sur le disque.
- **Nouveau écran "non disponible pour le moment" :** un titre sans flux disponible affiche une notification vidéo au lieu d'une erreur brute dans le lecteur, pour les films, séries et contenu adulte.

### Mise à jour 09/16/2026

**Corrections AllDebrid sur les deux chemins de résolution, plus améliorations de fiabilité AIOStreams :**
- **AllDebrid/TorBox injoignables via `torrentSites` (corrigé) :** trois fonctions dans `play.php` manquaient `$useAllDebrid`/`$useTorBox` dans leurs déclarations `global` — tous les contrôles évaluaient silencieusement `false`. AllDebrid et TorBox via le chemin torrent direct ne s'exécutaient jamais réellement.
- **Migration API AllDebrid v4.1 (corrigé) :** AllDebrid a arrêté `/v4/magnet/status`. Migré vers `/v4.1/magnet/status` avec sa nouvelle forme de réponse `files[].n`/`.l`. Ajout d'une liste d'extensions vidéo pour éviter de sélectionner des fichiers de sous-titres ou d'affiches.
- **Fausses rejections du contrôle de lecture AIOStreams (corrigé) :** erreurs 5xx transitoires traitées comme permanentes, et `curl_getinfo()` renvoyant `false` pour un Content-Type absent traité comme un mauvais type. Corrigé : réessai unique pour les échecs vraiment transitoires.
- **Mode `--expiring` du préchauffage (nouveau) :** `prewarm.php --expiring[=N]` re-chauffe les entrées du cache durable avant leur expiration, en utilisant le compte et la langue d'origine de chaque entrée. Cron par défaut désormais avec `--expiring=150`.
- **Correction de l'auto-blocage de la boucle locale :** `127.0.0.1`/`::1` sont désormais inconditionnellement approuvés avant tout contrôle de blocage/limite — le serveur qui s'appelle lui-même ne peut pas être usurpé via `REMOTE_ADDR`.

### Mise à jour 09/14/2026
Une mise à jour majeure concernant la fiabilité, les langues, les sous-titres, l'analyse et la sécurité. Points forts :
- **Tableau de bord analytique M3uListerr (nouveau) :** un `dashboard.php` autonome qui enregistre et visualise chaque lecture — un globe interactif, des graphiques et un tableau des sessions affichant le titre/l'affiche, les films vs séries TV, le compte, la langue demandée et audio, la release exacte et le service debrideur utilisé, la progression de la lecture (% et h:mm:ss), les sous-titres proposés, le pays/ville/FAI, l'appareil et le user-agent, l'IP, et le temps de résolution. Protégé par connexion avec un stockage SQLite privé, plus un éditeur `config.php` intégré et une gestion des identifiants. Voir [Tableau de bord M3uListerr](#tableau-de-bord-analytique-m3ulisterr).
- **Audio de la langue correcte :** les releases multi-audio ne lisent plus la mauvaise langue. La piste audio sélectionnée est désormais choisie à partir de l'en-tête du fichier lui-même (par ex. une release "ITA ENG" lit l'anglais, pas l'italien) et la balise HLS `LANGUAGE` rapporte ce qui est réellement lu au lieu de toujours prétendre que c'est de l'anglais.
- **Compte français / `?lang=fr` :** un chemin français dédié qui n'accepte que les releases dont l'audio par défaut est réellement le français (vérifié à partir de l'en-tête du conteneur, pas seulement de la balise), privilégiant les torrents en cache et une échelle de qualité x264 1080p→720p→SD (`?codec=x265` bascule vers une échelle privilégiant le HEVC). Les flux français sont servis via un proxy léger (sans ffmpeg) qui résout la source une fois et diffuse par plages de données, évitant la limitation de débit IP du debrideur.
- **Sous-titres :** les pistes de sous-titres en portugais européen (pt-PT) et brésilien (pt-BR) sont proposées, avec une solution de repli directe via l'API OpenSubtitles lorsque le fournisseur inclus est en panne, et une prise en charge complète des sous-titres pour les épisodes de séries TV.
- **Stabilité de la lecture :** correction des images dupliquées / discontinuités d'horodatage aux limites des segments, et correction d'un bug d'épuisement de mémoire qui produisait silencieusement des segments vides (mise en mémoire tampon sans fin) sur des sources 4K/remux à haut débit.
- **Renforcement de la sécurité :** blocage de l'accès public aux fichiers sensibles (`.git`, `cache.json`, journaux, `config.php`, les données privées du tableau de bord), ajout d'une protection SSRF au proxy vidéo, ainsi qu'une Content-Security-Policy stricte, une protection CSRF, un renforcement des sessions et un verrouillage de connexion sur le tableau de bord.

### Mise à jour 09/28/2025
- **TV en direct :** Correction de la section TV en direct et ajout de DrewLive, une source massive tout-en-un de plus de 7 000 chaînes.
- **Read Debrid :** Correction des vérifications du cache de Read Debrid et ajout de Streamio Sites comme source debrideur (la prise en charge de plus de services de debrideurs arrive bientôt).
- **Sources de flux :** Nettoyage et suppression de plusieurs sources de flux directes dans le script principal et dans HeadlessVidX pour améliorer la fiabilité.
- **VOD Adulte :** Correction de la source VOD adulte, la bibliothèque de 10 000 films pour adultes s'actualise désormais automatiquement chaque dimanche.
- **HeadlessVidX :** Refonte majeure et corrections de bugs. Le logiciel présentait de nombreux problèmes et j'ai passé plusieurs semaines à le stabiliser et à l'amener au niveau souhaité.
- **Général :** Une grande partie du projet était cassée après plus d'un an sans mises à jour. Les choses fonctionnent beaucoup mieux maintenant, et j'ai prévu d'ajouter plus de fonctionnalités dans les prochaines versions.

### Changements et ajouts (plus anciens)
- Ajout du service Premiumize comme alternative à Real-Debrid. (utilisé uniquement avec les sites de torrents)
- Ajout de threads lors de la recherche de liens magnet sur les sites de torrents. (accélère le temps nécessaire pour trouver un lien)
- Ajout et correction de sources directes de films et séries TV ainsi que plus d'extracteurs de liens.
- Ajout de la section sport TheTvApp dans la liste de lecture TV en direct (configurez votre application pour charger l'EPG et la liste de lecture toutes les 12 heures ou moins.)
- Ajout de PlutoTV à la liste de lecture TV en direct (Multilingue ici : https://github.com/matthuisman/i.mjh.nz)
- Refonte des fonctions et de la liste de lecture TV en direct et DaddyLive. (toutes les images de la liste de lecture fonctionnent)
- Correction de nombreux bugs dans les fonctions de recherche et de filtrage des torrents. (les liens sont trouvés beaucoup plus souvent maintenant)
- Correction du tri par résolution et plus de chances d'obtenir des liens de meilleure qualité (sites de torrents)
- Ajout de films pour adultes à la vod (désactivé par défaut)

---


</details>

<details><summary><b>🌐 HE Changelog</b></summary>


### 📅 עדכון 14/09/2026
עדכון ענק הכולל שיפורים דרמטיים באמינות, תמיכה בשפות, מערכת כתוביות משופרת, כלי ניתוח נתונים (Analytics) ואבטחה. נקודות עיקריות:
- **לוח בקרה וניתוח נתונים M3uListerr (חדש):** קובץ `dashboard.php` עצמאי שמתעד ומציג חזותית כל ניגון (פירוט נרחב מופיע מעלה תחת סעיף לוח הבקרה).
- **שמע בשפה נכונה:** שחרורים מרובי-שמע (multi-audio) כבר אינם מנגנים שפה שגויה. רצועת השמע מוגדרת כעת מכותרת הקובץ עצמו ותגית ה-HLS מדווחת באמינות מה באמת מתנגן.
- **חשבון צרפתי / `?lang=fr`:** נתיב ייעודי לצרפתית המקבל רק שחרורים ששמע הברירת מחדל שלהם הוא באמת צרפתית, מעדיף טורנטים שמורים במטמון (cached) וסולם איכויות. הזרמות בצרפתית מועברות כעת דרך פרוקסי קל-משקל.
- **כתוביות:** רצועות כתוביות בפורטוגזית אירופאית וברזילאית מוצעות, עם מנגנון גיבוי ישיר של API של OpenSubtitles כאשר הספק המובנה אינו זמין. נוספה תמיכה מלאה בכתוביות לפרקי סדרות.
- **יציבות ניגון:** תוקנו בעיות נפוצות של פריימים כפולים או קטיעות חותם-זמן (Timestamp), ותוקן באג קריטי של מיצוי זיכרון.
- **הקשחת אבטחה:** חסימת גישה ציבורית לקבצים רגישים, הוספת הגנת SSRF לפרוקסי הוידאו ויישום CSP קפדני ללוח הבקרה.

### 📅 עדכון 28/09/2025
- **טלוויזיה חיה:** תוקן מדור הטלוויזיה החיה והתווסף *DrewLive* כמקור עצום של מעל ל-7,000 ערוצים.
- **Read Debrid:** תוקנו בדיקות המטמון של Read Debrid והתווספו אתרי Streamio כמקור debrid.
- **מקורות הזרמה (Stream):** המערכת נוקתה והוסרו ממנה מספר מקורות הזרמה ישירים ובעייתיים.
- **VOD למבוגרים:** תוקן מקור ה-VOD למבוגרים, הספריה הכוללת מעל 10,000 סרטים למבוגרים מתרעננת כעת אוטומטית בכל יום ראשון.
- **HeadlessVidX:** שיפוץ מקיף ותיקוני באגים נרחבים.
- **באופן כללי:** רוב הפרויקט שוקם לאחר יותר משנה ללא עדכונים. הדברים עובדים בצורה חלקה וטובה הרבה יותר.

### 🛠️ שינויים ותוספות ישנים יותר
- התווסף שירות Premiumize כחלופה ל-Real-Debrid.
- נוספו תהליכונים (Threads) מהירים בעת חיפוש באתרי טורנט אחר קישורי מגנט.
- התווספו ותוקנו מקורות סרטים וסדרות טלוויזיה ישירים, וכן מחלצי קישורים.
- הוסף מדור ספורט של *TheTvApp*.
- התווסף ערוץ *PlutoTV* לרשימת ההשמעה של הטלוויזיה החיה.
- עוצבו מחדש פונקציות הטלוויזיה החיה ו-*DaddyLive*.
- תוקנו הרבה באגים קטנים בפונקציות החיפוש וסינון הטורנטים.
- הוספו סרטים למבוגרים (מושבת כברירת מחדל).

---


</details>

<details><summary><b>🌐 IT Changelog</b></summary>


### Aggiornamento 17/09/2026

**Politica di corrispondenza linguistica AIOStreams estesa — i candidati Multi/French ora accettati anche se la lingua non è la traccia predefinita:**
- **Cambiamento di politica (decisione esplicita dell'operatore):** i candidati in francese e in modalità proxy sono ora accettati se la lingua richiesta è presente *ovunque* nel file (qualsiasi traccia audio), non solo quando è la traccia predefinita/prima. Il player può cambiare traccia invece che il server rifiutare una fonte valida.
- **L'inglese ottiene un vero livello di ultimo ricorso:** per le richieste `Unlimited`/`?lang=eng`, se non è disponibile nessun candidato in inglese o Multi, il miglior candidato rimanente viene servito nella sua lingua originale invece di fallire completamente.

**Correzione mismatch saga/pack — AIOStreams ora rifiuta i candidati del film sbagliato dai pack multi-film:**
- **Causa confermata in produzione:** AIOStreams a volte restituiva un candidato il cui `behaviorHints.filename` era un film *diverso* dello stesso pack saga (es: una query per Harry Potter: La Camera dei Segreti restituiva file de I Doni della Morte dello stesso pack da 8 film).
- **Correzione:** leggero controllo dell'anno su `behaviorHints.filename` — se il nome del file contiene un anno esplicito diverso dall'anno del titolo richiesto, il candidato viene rifiutato. Un file senza anno viene accettato.
- **Regressione trovata e corretta lo stesso giorno** dopo la distribuzione in produzione. Ri-verificata su tutti e 8 i film reali di Harry Potter su entrambi gli account.

**Dashboard: le risoluzioni `&dev=true` non venivano mai registrate (corretto):**
- **Bug:** i rami debug in `play.php` chiamavano `writeToCache()` ma saltavano `m3uLogResolution()`. Qualsiasi URL risolta per la prima volta tramite `&dev=true` mostrava colonne Release e Debrid vuote per tutti i successivi spettatori reali, e AllDebrid non appariva mai nelle statistiche globali.
- **Correzione:** `m3uLogResolution()` aggiunto in tutti e quattro i rami DEBUG prima dei loro `exit()`.

**Preferenza ponderata AllDebrid/Premiumize — ora vincolante, sostituisce i livelli, verificata in produzione:**
- **`$aioDebridWeights` è ora una vera preferenza per-risoluzione:** un peso come `alldebrid => 70, premiumize => 30` fa vincere AllDebrid su ~70% delle risoluzioni, sovrascrivendo sia la classificazione di qualità che il livello di corrispondenza linguistica (livelli 0–2 raggruppati). Un candidato in cache batte sempre uno non ancora in cache indipendentemente dal peso. Il livello 3 rimane assoluto ultimo ricorso.
- **Un peso esattamente 0 è un'esclusione rigida:** rimuove i candidati di quel servizio prima del calcolo di livelli e qualità.
- **Corretta la verifica di vitalità dei link in cache che era inefficace:** il controllo si fidava incondizionatamente di qualsiasi URL `video_proxy.php` senza verificarla davvero. Corretto tramite `checkVideoProxyUpstreamAlive()`.

**Schermata di blocco/limite ora riprodotta come vero video su tutti i player IPTV:**
- **Corretto "Source Error" su IMPlayer, MyTVOnline3, STBEMU, ecc.:** la schermata di notifica veniva consegnata come `multipart/x-mixed-replace` (MJPEG — tecnica solo per browser). Ora è uno stream MPEG-TS H.264/AAC generato in tempo reale via ffmpeg, nessun file scritto su disco.
- **Nuova notifica "non ancora disponibile":** un titolo senza stream disponibile mostra ora una notifica video invece di un errore grezzo nel player, per film, serie e contenuto adulto.

### Aggiornamento 16/09/2026

**Correzioni AllDebrid su entrambi i percorsi di risoluzione, più miglioramenti di affidabilità AIOStreams:**
- **AllDebrid/TorBox non raggiungibili tramite `torrentSites` (corretto):** tre funzioni in `play.php` mancavano `$useAllDebrid`/`$useTorBox` nelle loro dichiarazioni `global` — tutti i controlli valutavano silenziosamente `false`. AllDebrid e TorBox tramite il percorso diretto torrent non venivano mai effettivamente eseguiti.
- **Migrazione API AllDebrid v4.1 (corretto):** AllDebrid ha interrotto `/v4/magnet/status`. Migrato a `/v4.1/magnet/status` con la nuova forma di risposta `files[].n`/`.l`. Aggiunta lista di estensioni video per evitare di selezionare file di sottotitoli o copertine.
- **Rifiuti falsi del controllo di riproducibilità AIOStreams (corretto):** errori 5xx transienti trattati come permanenti, e `curl_getinfo()` che restituisce `false` per Content-Type assente trattato come tipo sbagliato. Corretto: un singolo tentativo di ripetizione per soli errori genuinamente transienti.
- **Modalità `--expiring` del preriscaldamento (nuova):** `prewarm.php --expiring[=N]` ri-scalda le voci della cache durevole prima della scadenza, usando l'account e la lingua originali di ciascuna voce. Argomenti cron predefiniti ora con `--expiring=150`.
- **Correzione dell'auto-blocco del loopback:** `127.0.0.1`/`::1` ora sono incondizionatamente attendibili prima di qualsiasi controllo di blocco/limite — il server che chiama se stesso non può essere falsificato tramite `REMOTE_ADDR`.

### Aggiornamento 14/09/2026
Un grande aggiornamento riguardante affidabilità, lingua, sottotitoli, analisi e sicurezza. Punti salienti:
- **Dashboard analitica M3uListerr (nuovo):** un `dashboard.php` indipendente che registra e visualizza ogni riproduzione — un globo interattivo, grafici e una tabella delle sessioni che mostra titolo/locandina, film vs serie TV, account, lingua richiesta e audio, l'esatta release e il servizio debrid utilizzato, il progresso della riproduzione (% e h:mm:ss), i sottotitoli offerti, paese/città/ISP, dispositivo e user-agent, IP e tempo di risoluzione. Protetto da login con un archivio privato SQLite, oltre a un editor integrato per `config.php` e gestione delle credenziali. Vedi [Dashboard analitica M3uListerr](#dashboard-analitica-m3ulisterr).
- **Audio nella lingua corretta:** le release multi-audio non riproducono più la lingua sbagliata. La traccia audio selezionata viene ora scelta dall'intestazione del file stesso (es. una release "ITA ENG" riproduce l'inglese, non l'italiano) e il tag `LANGUAGE` dell'HLS riporta ciò che viene effettivamente riprodotto invece di dichiarare sempre l'inglese.
- **Account francese / `?lang=fr`:** un percorso francese dedicato che accetta solo release il cui audio predefinito è realmente il francese (verificato dall'intestazione del contenitore, non solo dal tag), preferendo i torrent in cache e una scala di qualità x264 1080p→720p→SD (`?codec=x265` passa a una scala che privilegia l'HEVC). Gli stream francesi vengono serviti tramite un proxy di byte leggero (senza ffmpeg) che risolve la fonte una volta e trasmette a blocchi, evitando le limitazioni di frequenza IP del debrid.
- **Sottotitoli:** sono offerti sia i sottotitoli in portoghese europeo (pt-PT) che brasiliano (pt-BR), con un fallback diretto alle API di OpenSubtitles per quando il provider integrato è offline, e pieno supporto ai sottotitoli per gli episodi delle serie TV.
- **Stabilità di riproduzione:** risolti i problemi di frame duplicati/discontinuità dei timestamp ai confini dei segmenti, e risolto un bug di esaurimento della memoria che produceva silenziosamente segmenti vuoti (buffering infinito) su sorgenti 4K/remux ad alto bitrate.
- **Rafforzamento della sicurezza:** bloccato l'accesso pubblico ai file sensibili (`.git`, `cache.json`, log, `config.php`, dati privati della dashboard), aggiunta una protezione SSRF al proxy video, e una rigorosa Content-Security-Policy, protezione CSRF, rafforzamento delle sessioni e blocco dei login sulla dashboard.

### Aggiornamento 28/09/2025
- **TV in Diretta:** Risolta la sezione della TV in Diretta e aggiunto DrewLive, un'enorme fonte all-in-one di oltre 7.000 canali.
- **Read Debrid:** Risolti i controlli della cache di Read Debrid e aggiunto Streamio Sites come fonte debrid (il supporto per altri servizi debrid è in arrivo).
- **Fonti di streaming:** Pulite e rimosse diverse fonti di streaming diretto sia nello script principale che in HeadlessVidX per migliorare l'affidabilità.
- **VOD per Adulti:** Risolta la fonte del VOD per adulti, la libreria di 10.000 film per adulti si aggiorna ora automaticamente ogni domenica.
- **HeadlessVidX:** Importante revisione e correzione di bug. Il software aveva numerosi problemi e ho trascorso diverse settimane per stabilizzarlo e portarlo allo standard desiderato.
- **In generale:** Gran parte del progetto si era interrotta dopo più di un anno senza aggiornamenti. Le cose funzionano molto meglio ora e ho intenzione di aggiungere ulteriori funzionalità nelle prossime versioni.

### Modifiche e Aggiunte Precedenti
- Aggiunto il servizio Premiumize come alternativa a Real-Debrid. (utilizzato solo con siti torrent)
- Aggiunti i thread durante la ricerca di link magnetici sui siti torrent. (velocizza il tempo impiegato per trovare un link)
- Aggiunte e corrette le fonti dirette per film e programmi TV, nonché più estrattori di link.
- Aggiunta la sezione sportiva di TheTvApp nella Playlist TV in Diretta (imposta la tua app per ricaricare EPG e playlist ogni 12 ore o meno).
- Aggiunto PlutoTV alla playlist TV in diretta (Multi-Lingue Qui: https://github.com/matthuisman/i.mjh.nz)
- Riprogettate le funzioni e la playlist per TV in Diretta e DaddyLive. (tutte le immagini nella playlist sono funzionanti)
- Risolti molti bug nelle funzioni di ricerca e filtraggio dei torrent. (ora trova i link molto più spesso)
- Risolto l'ordinamento in base alla risoluzione e aumentata la probabilità di ottenere link di qualità superiore (siti torrent)
- Aggiunti film per adulti al vod (disabilitati di default)

---


</details>

<details><summary><b>🌐 PT Changelog</b></summary>


### Atualização 17/09/2026

**Política de correspondência de idioma AIOStreams expandida — candidatos Multi/French agora aceitos mesmo quando o idioma não é a faixa padrão:**
- **Mudança de política (decisão explícita do operador):** candidatos em francês e no modo proxy são agora aceitos se o idioma solicitado estiver presente *em qualquer lugar* no arquivo (qualquer faixa de áudio), e não apenas quando é a faixa padrão/primeira. O player pode trocar as faixas em vez de o servidor rejeitar uma fonte válida.
- **Inglês recebe um nível de último recurso:** para pedidos `Unlimited`/`?lang=eng`, se nenhum candidato em inglês ou Multi estiver disponível, o melhor candidato restante é servido no seu idioma original em vez de falhar completamente.

**Correção de incompatibilidade saga/pack — AIOStreams agora rejeita candidatos do filme errado em packs multi-filmes:**
- **Causa raiz confirmada em produção:** AIOStreams às vezes retornava um candidato cujo `behaviorHints.filename` era um filme *diferente* do mesmo pack saga (ex: consulta para Harry Potter: Câmara Secreta retornou arquivos de As Relíquias da Morte do mesmo pack de 8 filmes).
- **Correção:** verificação leve do ano em `behaviorHints.filename` — se o nome do arquivo contiver um ano explícito diferente do ano do título solicitado, o candidato é rejeitado. Um arquivo sem ano é aceito.
- **Regressão encontrada e corrigida no mesmo dia** após implantação em produção. Re-verificada em todos os 8 filmes reais de Harry Potter em ambas as contas.

**Dashboard: resoluções via `&dev=true` nunca eram registadas (corrigido):**
- **Bug:** ramos de debug em `play.php` chamavam `writeToCache()` mas ignoravam `m3uLogResolution()`. Qualquer URL resolvida via `&dev=true` mostrava colunas Release e Debrid em branco no dashboard para visualizadores reais subsequentes, e AllDebrid nunca aparecia nas estatísticas globais.
- **Correção:** `m3uLogResolution()` adicionado nos quatro ramos DEBUG antes dos seus `exit()`.

**Preferência ponderada AllDebrid/Premiumize — agora forçosa, substitui camadas, verificada em produção:**
- **`$aioDebridWeights` é agora uma preferência real por resolução:** um peso como `alldebrid => 70, premiumize => 30` faz o AllDebrid ganhar ~70% das resoluções, substituindo tanto a classificação de qualidade como a camada de correspondência de idioma (camadas 0–2 agrupadas). Um candidato em cache sempre vence um não armazenado em cache independentemente do peso. A camada 3 (sem correspondência de idioma) permanece último recurso absoluto.
- **Um peso de exatamente 0 é uma exclusão rígida:** remove os candidatos desse serviço antes do cálculo de camadas e qualidade.
- **Correção da verificação de validade do link em cache que era inoperante:** o controle confiava incondicionalmente em qualquer URL `video_proxy.php` sem realmente verificá-la. Corrigido via `checkVideoProxyUpstreamAlive()`.

**Tela de bloqueio/limite agora reproduzida como vídeo real em todos os players IPTV:**
- **Corrigido "Source Error" no IMPlayer, MyTVOnline3, STBEMU, etc.:** a tela de aviso era entregue como `multipart/x-mixed-replace` (MJPEG — técnica apenas para browsers). Agora é um fluxo MPEG-TS H.264/AAC gerado ao vivo via ffmpeg, nada é escrito no disco.
- **Novo aviso "não disponível ainda":** um título sem fluxo disponível agora exibe um vídeo de aviso em vez de um erro bruto no player, para filmes, séries e conteúdo adulto.

### Atualização 16/09/2026

**Correções do AllDebrid em ambos os caminhos de resolução, mais melhorias de fiabilidade do AIOStreams:**
- **AllDebrid/TorBox inacessíveis via `torrentSites` (corrigido):** três funções em `play.php` não tinham `$useAllDebrid`/`$useTorBox` nas suas declarações `global` — todas as verificações avaliavam silenciosamente `false`. AllDebrid e TorBox via o caminho direto de torrent nunca executavam de facto.
- **Migração da API AllDebrid v4.1 (corrigido):** AllDebrid descontinuou `/v4/magnet/status`. Migrado para `/v4.1/magnet/status` com a nova forma de resposta `files[].n`/`.l`. Adicionada lista de extensões de vídeo para evitar selecionar ficheiros de legendas ou capas.
- **Falsas rejeições da verificação de reproduzibilidade do AIOStreams (corrigido):** erros 5xx transitórios tratados como permanentes, e `curl_getinfo()` retornando `false` para Content-Type ausente tratado como tipo errado. Corrigido: uma única nova tentativa para falhas genuinamente transitórias.
- **Modo `--expiring` do pré-aquecimento (novo):** `prewarm.php --expiring[=N]` re-aquece entradas do cache durável antes de expirarem, usando a conta e idioma originais de cada entrada. Cron padrão agora com `--expiring=150`.
- **Correção de auto-bloqueio de loopback:** `127.0.0.1`/`::1` são agora incondicionalmente confiados antes de qualquer verificação de bloqueio/limite — o servidor chamando a si mesmo não pode ser falsificado via `REMOTE_ADDR`.

### Atualização 14/09/2026
Uma grande revisão de estabilidade, idioma, legendas, análise de dados e segurança. Destaques:
- **Dashboard analítico M3uListerr (novo):** Integração total do `dashboard.php` independente. Protegido por login privado, estatísticas completas, monitoramento em tempo real, visualização de cache e editor do arquivo `config.php` diretamente no navegador.
- **Áudio no Idioma Correto:** Faixas de áudio escolhidas a dedo utilizando a leitura de cabeçalhos binários para reproduzir fielmente o que o nome do arquivo sugere (ex: evita erro de tocar Inglês em releases que são Dublados e Legendados).
- **Conta Francesa / `?lang=fr`:** Um caminho de rede projetado especificamente para priorizar torrents em Francês real com um proxy ultraleve próprio que não precisa do ffmpeg, otimizando o uso do debrid e qualidade do stream.
- **Legendas PT-BR e PT-PT:** Integração reforçada para usuários falantes da língua portuguesa (com fallback ao OpenSubtitles caso a fonte principal caia).
- **Estabilidade Aprimorada:** Resoluções para saltos, quadros duplicados no buffer e correções de estouro de memória durante a reprodução de mídias de altíssima taxa de bits (ex: 4K remuxes).
- **Segurança Máxima:** Controle severo de acesso público em diretórios como `.git`, arquivos `.json`, `config.php` e implementações SSRF robustas no proxy.

### Atualização 28/09/2025
- **TV ao Vivo:** Adição do DrewLive, aumentando a lista para mais de 7.000 canais ao vivo.
- **Streamio Sites & Debrid:** Adicionada nova integração de Debrid para aumentar as taxas de sucesso e caches corrigidos.
- **HeadlessVidX Aprimorado:** Grande reformulação do componente após meses inativo. Estabilização massiva do código.
- **Limpeza e Organização:** Remoção de fontes mortas para manter o software enxuto e produtivo.

---


</details>
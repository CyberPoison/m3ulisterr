# CLAUDE.md — Working Guide for This Project

This file is for Claude (or any AI coding agent) picking up this repository —
whether freshly `git clone`d, or continuing from an earlier session. Read this
before making any change. It covers: the two-directory workflow, the
mandatory local dev/test cycle, the commit/changelog/port process, the
project's architecture and the non-obvious lessons learned building it, and a
full file-tree reference.

If you are a human "vibe coder" reading this to understand the project: the
same rules apply to you. Section 6 (file tree) and Section 5 (architecture
notes) are the fastest way to get oriented.

---

## 1. The two-directory workflow (read this first)

This project is normally developed across **two separate directories on the
same machine**, not one:

| Directory | What it is | Git? | Secrets? |
|---|---|---|---|
| **Local working copy** (e.g. `~/Downloads/tmdb-vod/tmdb-to-vod-playlist`) | Where you edit and test. Has the developer's REAL API keys/passwords filled into `config.php` so local testing hits real services. | **Not a git repo.** | **YES — real secrets.** Never publish, never `git init` here, never paste its `config.php` anywhere public. |
| **Public repo clone** (e.g. `~/Downloads/tmdb-vod/new-github-repo` or wherever this repo was cloned to) | The deploy source. Pushed to GitHub → built by GitHub Actions → pushed to `ghcr.io` → pulled by Portainer on the production server. | **Yes, real git repo.** | **NO — `config.php` here must keep every secret value blank** (`''`), e.g. `$apiKey = '';`, `$alldebridApiKey = '';`, `$frenchAioStreamsUrl = '';`, account passwords as `'YOUR_PASSWORD'`. |

If you only have ONE of these two directories available (e.g. you only have
the git clone), all of Section 2's local-testing instructions still apply —
just be aware that `config.php` in a fresh clone has blank secrets, so
AIOStreams/debrid-dependent paths won't return real results until a real
`config.php` is filled in (by the human operator, never by you from
memory — you do not know the real values unless the human pastes them into
the local working copy directly).

**Never commit or paste a real secret into the public repo's `config.php`.**
When a change touches `config.php`, port it with a targeted `Edit`, not a
blind file copy — copy comments/structure, never the live key values. Diff
before staging, every time, and actually read the diff for anything that
looks like a key, token, or password.

---

## 2. Local development & test environment

### 2.1 Starting a local PHP dev server

This project is plain PHP (no build step, no framework). Use PHP's built-in
dev server to test changes before committing:

```bash
cd <local-working-copy>
php -d opcache.enable=0 -d opcache.enable_cli=0 -S 127.0.0.1:8099
```

Notes learned the hard way:
- **`opcache.enable=0` is required** in a sandboxed shell — PHP's opcache
  tries to create a lock file under `/tmp` and a sandbox that only allows
  writes to `$TMPDIR` will fail with `Unable to create opcache lock file in
  /tmp: Operation not permitted`. Disabling opcache for the CLI dev server
  sidesteps this entirely (irrelevant for a throwaway test server anyway).
- **Binding a listening socket needs the sandbox disabled** for that one
  command (`dangerouslyDisableSandbox: true` in Claude Code, or run outside
  any sandbox). A plain sandboxed `php -S` will fail with `Failed to listen
  ... Operation not permitted`.
- Run it in the background (`nohup ... & disown`, or your shell's job
  control) and `pkill -f "php -S 127.0.0.1:8099"` when done or before
  restarting it with new code (PHP's built-in server doesn't hot-reload
  opcode changes reliably across processes — always kill and restart after
  editing a file you're about to test).
- Pick a port unlikely to collide (`8099` was used throughout this project's
  history) and stick to `127.0.0.1`, never `0.0.0.0`, for a local test
  server.

### 2.2 Exercising the resolve path

The core of this app is `play.php`'s stream resolver. Useful query params for
testing, all of which work against both the local dev server and production:

- `&dev=true` — turns on verbose debug tracing (candidate lists, rejection
  reasons, cache reads/writes, everything an `if ($GLOBALS['DEBUG'])` branch
  guards). **Always test with this on first** — it is the single most useful
  tool for understanding what the resolver actually did.
- `?clearMovieCache=true&movieId=<id>&username=<account>` — deletes just that
  one cache key from `cache.json` (safe, surgical — does NOT rebuild the
  whole cache from SQLite unless the file is missing entirely). Use this
  before every fresh-resolve test so you're not just reading a stale cache
  hit.
- `?movieId=<TMDB id>&username=<account>` — the actual resolve. Known test
  accounts (see `$xcAccounts` in `config.php`): `Unlimited` (English),
  `UnlimitedFR` (French, `lang=fr`).
- Known-good test titles used throughout this project's history (both have
  real French AND English AIOStreams competition across AllDebrid/Premiumize
  — good for testing the weight/tier logic): **Oppenheimer** (TMDB id
  `872585`), **Avengers: Age of Ultron** (TMDB id `99861`), **Harry Potter
  and the Deathly Hallows: Part 2** (TMDB id `12445`).

### 2.3 Verifying an actual resolved stream

Don't just trust a 301 redirect — verify the underlying file is real and
plays:

```bash
ffprobe -v error -rw_timeout 20000000 -show_format -show_streams -print_format json \
  "http://127.0.0.1:8099/play.php?movieId=<id>&username=<account>"
```

`ffprobe` follows the redirect chain itself (through `video_proxy.php`) and
reports the real container/codec/duration/size — this is how a dead upstream
link (`502 Bad Gateway: Upstream link could not be streamed`) was caught in
practice, when the cache-hit path was blindly trusting a stale link.

If `ffprobe`/`ffmpeg` reports a `dyld` / `libx265` load error on macOS, run
`arch -arm64 brew reinstall ffmpeg` — a known-broken Homebrew ffmpeg install
issue on this kind of setup, unrelated to this project's code.

### 2.4 Testing against production directly (read-only, careful)

When a local dev server can't exercise something realistically (e.g. it
needs a real AIOStreams instance, or you're diagnosing a live user report),
the same `&dev=true` / `?clearMovieCache=true` query params work directly
against the production URL. This is fine for **read-only diagnosis**. Before
writing/deploying anything based on it:
- Remember `AIO_LIST_CACHE_TTL` (600 seconds) caches AIOStreams' raw catalog
  response separately from `cache.json` — rapid repeated test trials within
  that window are re-sorting the *same* snapshot, not independent samples.
  Space out trials by >10 minutes for genuinely independent tests, or accept
  that repeats will look identical and don't prove anything by themselves.
- The dashboard (`dashboard.php`) requires login — log in via a `curl` POST
  to `?action=login` with the CSRF token scraped from the login page first,
  then reuse the cookie jar for `?action=api&q=...` calls. `q=overview` (or
  `q=refresh`) must be called before `q=sessions` in the same session to
  import the latest JSONL events into SQLite — `q=sessions` alone does not
  trigger that import.
- Never treat a single external-catalog bitrate match as proof of anything —
  AIOStreams' live catalog changes within seconds to minutes; cross-checking
  candidates externally produced multiple false-trail matches in this
  project's history. Prefer a temporary in-code diagnostic (an `if
  ($DEBUG) { echo ... }` block) for first-party proof, and remove it once
  verification is done.

---

## 3. The mandatory test → fix cycle (before any commit)

**Never commit a change to the public repo without having actually run it.**
The required sequence for any PHP change:

1. **Syntax check**: `php -l <file>` — catches nothing functional, but catches
   typos before you waste a test cycle.
2. **Start (or reuse) the local dev server** (Section 2.1).
3. **Exercise the actual changed behavior**, not just "does it load" — if you
   changed a sort/selection function, force both the success path and the
   failure path you were trying to fix (see Section 3.1 for how). Read the
   `&dev=true` debug output; don't assume from the diff alone that the logic
   does what you think.
4. **If it's wrong, fix and re-test** — repeat steps 1–3 until the actual
   observed behavior matches intent. This project's real history includes
   fixes that needed 2–3 iterations before the debug trace showed the
   correct outcome (e.g. the weighted-preference logic needed a v1 fix, then
   a v2 fix after live testing showed tier was still blocking it).
5. **Only once a test has actually passed**, port and commit (Section 4).

### 3.1 Forcing the failure path

A lot of this codebase's real bugs only show up on a **failure/fallback**
path, not the happy path. Don't skip testing it. Techniques used
successfully in this project:
- **Inject a broken cache entry directly** to test cache-hit fallback logic:
  read `cache.json`'s existing entry for a key with `php -r
  'var_export(json_decode(file_get_contents("cache.json"), true)["<key>"]);'`
  to see the exact structure, then write back a version pointing at a
  deliberately dead URL (e.g. a `video_proxy.php?url=...` wrapping a
  nonsense upstream path) with a small PHP one-liner, and hit `play.php`
  again to confirm it's rejected and falls through, rather than silently
  breaking.
- **Point `curl -I` / `ffprobe` directly at the exact URL a candidate would
  produce** to independently confirm whether it's really alive or really
  dead, rather than trusting the app's own verdict about itself.
- **Temporarily flip a config value to its extreme** (e.g.
  `$aioDebridWeights` to `100`/`0`) to make a code path's effect obvious and
  unambiguous in the debug trace, then restore the original value afterward
  — never leave a test-only config value committed.
- Clean up any test artifact you inject into `cache.json` afterward (or
  confirm it self-healed via a subsequent real resolve) — don't leave the
  local working copy's cache in a broken state for the next session.

---

## 4. Commit, changelog, and port policy

**Standing rule, confirmed explicitly by the project owner: always commit a
tested change to the public repo without being asked. Never `git push` —
the human pushes themselves, on their own schedule.**

The full sequence, every time, after a test has passed (Section 3):

1. **Port the file(s)** from the local working copy to the public repo clone.
   - For any file that is NOT `config.php`: a plain copy is fine (`cp
     <local>/<file> <public-repo>/<file>`), since these files don't carry
     secrets. Still `diff` the two afterward and `php -l` the result before
     staging, to catch a botched copy.
   - For `config.php` specifically: **never copy the whole file.** Use a
     targeted `Edit` that applies only the structural/comment/default-value
     change, leaving every real secret in the public repo's copy exactly as
     blank as it already was. Diff first, read the whole diff, confirm
     nothing that looks like a key/token/password is in it, before staging.
2. **Update the changelog.** This project's changelog lives in `README.md`
   under the `# 🔄 Updates & Changelog` heading, as dated entries (most
   recent first, format `### 📅 Update MM/DD/YYYY`, bold-labeled bullet
   points describing the fix/feature and, where useful, how it was verified
   — see existing entries for tone/style). Add today's change to the top of
   that section (create a new `### 📅 Update` block if none exists yet for
   today's date, otherwise add a bullet to today's existing block). Then
   regenerate the derived multi-language rollup:
   ```bash
   cd <public-repo>
   python3 generate_release_notes.py
   ```
   This rewrites `RELEASE_NOTES.md` from `README.md` + every `README_*.md`
   translation's own changelog section — **never hand-edit
   `RELEASE_NOTES.md` directly**, it will be silently overwritten the next
   time this script runs. (The translated `README_*.md` files' changelog
   sections are maintained separately/manually per-language; adding an
   English-only entry to `README.md` and regenerating is enough for a normal
   change — full translation of every language file is a separate,
   larger task, not expected for every commit.)
3. **Stage and commit** in the public repo:
   ```bash
   cd <public-repo>
   git add <changed files>
   git status --short   # review scope - nothing unexpected staged
   git commit -m "$(cat <<'EOF'
   <concise summary line>

   <why this change, what it fixes, how it was tested>
   EOF
   )"
   ```
   - `git add`/`git commit` in the public repo clone typically need the
     sandbox disabled for that command (writing `.git/index.lock` is
     otherwise blocked) — this is expected, not a sign of a problem.
   - **Avoid apostrophes and other shell-special characters in the commit
     message text** when passing it via a `'EOF'`-quoted heredoc through a
     wrapped shell invocation — this project hit a real, reproducible
     `unexpected EOF` / `bad substitution` failure from a single apostrophe
     inside the message body more than once. If a commit fails with a
     quoting error, simplify the wording (rephrase to avoid the apostrophe)
     and retry, rather than fighting the quoting.
   - End every commit message with the attribution line currently specified
     for this session (check the active system reminder for the exact
     wording — it has changed once already in this project's history, so
     don't hardcode it from memory here).
4. **Never `git push`.** Leave the commit staged locally in the public repo
   clone; tell the human it's ready, and let them push on their own
   schedule.

---

## 5. Architecture notes — non-obvious things a new session needs to know

These are lessons that took real debugging effort to establish. Reading them
first will save you from re-discovering them the hard way.

- **Two separate resolve paths, two separate caches.** The AIOStreams path
  (`aioStreamsFindAudioLanguage()` in `play.php`, used for `Unlimited` /
  `UnlimitedFR` / TV accounts) and the direct-torrent path (`debrid.php`,
  `torrentSites` scrapers) are different code with different debrid
  handling. `config.php`'s single-key debrid settings feed the torrent path
  directly; the AIOStreams path gets its own debrid selection from
  AIOStreams' own catalog tags (`[AD⚡]`/`[PM⚡]`/etc in the stream name) —
  changing one path's debrid config does not affect the other.
- **`AIO_LIST_CACHE_TTL` (600s) vs `cache.json`/durable SQLite cache are
  completely separate layers.** The first caches AIOStreams' raw catalog
  JSON per query URL; the second caches this app's own final resolved
  stream URL per movie/account. Clearing one does not clear the other. A
  burst of rapid test requests within 10 minutes will look identical to each
  other regardless of code changes, because they're all re-sorting the same
  cached catalog snapshot — this cost significant debugging time once before
  being understood.
- **`checkLinkStatusCode()` used to unconditionally trust any
  `video_proxy.php` URL** (an early-return `true` with no real check) —
  since virtually every AIOStreams candidate is wrapped through
  `video_proxy.php`, this meant the cache-hit liveness gate was a no-op for
  the URLs that mattered most. Fixed by actually issuing a real HEAD request
  against the server's own `video_proxy.php` (its `auto` mode already
  understands HEAD and probes the real upstream link without transferring
  video bytes) — see `checkVideoProxyUpstreamAlive()`. If you're touching
  cache-hit logic again, remember a cached URL CAN legitimately go dead
  between being cached and being served (the upstream CDN/debrid link
  expires), and the liveness check has to be real, not a shortcut.
- **AIOStreams candidate tiers** (`aioStreamsFindAudioLanguage()`): tier 0 =
  confirmed language tag / listed first; tier 1 = "Multi" tag or a
  French-scene-release filename pattern (TRUEFRENCH/VFF/VFQ/VF2/VFI/VOF/
  MULTI); tier 2 = proxy-mode-only, unresolved tag but verified via the
  file's own container header; tier 3 = true last-resort (no language match
  at all — only reachable on the non-proxy/English path, so a foreign film
  with no dub still plays in its original language instead of failing
  outright). Tiers 0–2 are all "some real language match" and are grouped
  together for weight/preference purposes; tier 3 is the only one that stays
  strictly last no matter what.
- **`$aioDebridWeights` (config.php) is a real per-resolve service
  preference, not a tie-break** — it can override both quality rank AND
  language tier (tiers 0–2 only; tier 3 stays absolute), specifically so a
  heavily-weighted service (e.g. AllDebrid at 70%) actually reduces call
  volume on the other one (e.g. Premiumize, to avoid its quota), not just
  win coin-flips on exact ties. Cached candidates still always beat
  not-yet-cached ones regardless of weight. **Setting a service's weight to
  exactly `0` is a hard exclusion**: that service's candidates are removed
  from the tier list entirely (not just deprioritized), before tiers/quality
  are even computed — if the *other* service then has nothing, the resolve
  falls through to the next provider entirely rather than ever using the
  zeroed-out service. A service simply left out of the config array (vs.
  explicitly set to `0`) is unaffected and still defaults to weight 50.
- **The block-notice / rate-limit mechanism**: an IP that's manually blocked
  (dashboard *Sessions* table) or hits a configured per-IP daily limit
  (`dailyMovieLimit`/`dailyEpisodeLimit`/`dailyRequestLimit` in
  `config.php`) gets served a live MJPEG "blocked" placeholder stream
  (`--m3ublockframe` multipart marker, `image/jpeg` content-type, capped at
  10 minutes) in place of real video — no ffmpeg, no storage. If a player
  reports an immediate "source error," check `dashboard.php?action=api&q=blocked`
  for currently-blocked IPs before assuming the resolve itself is broken.
- **`&dev=true` used to silently skip `m3uLogResolution()`** in several
  branches (cache-hit and fresh-resolve, movies and series) — meaning any
  title first resolved via a `&dev=true` test would permanently show blank
  Release/Debrid on the dashboard for every real viewer's later cache hit,
  and AllDebrid could look like it was never actually being picked even when
  it was. This is fixed; if you add a new `exit()`-ing debug branch,
  remember to log it too.
- **Dashboard event import isn't automatic on every query** —
  `dashboard.php?action=api&q=sessions` alone does NOT import the latest
  JSONL event log into SQLite; only `q=overview` or `q=refresh` do. Call one
  of those first if you need to see very recent activity via `q=sessions`.
- **`config.php` is a bind-mounted HOST file in production - the image never overwrites it, so functions added to it do not exist there.** `docker-compose.yml` mounts `./config.php:/var/www/html/config.php`. A function added to the repo's `config.php` (and called by shipped code) fatals in production with an uncaught `Error` until someone pastes it into the host copy by hand - and because `player_api.php`/`play.php` run `error_reporting(0)`, the symptom is just HTTP 200 with an empty body. This caused a long production incident (empty `get_vod_info`/`get_series_info`; missing `makeGetRequest` and `tmdb*` helpers). Put new logic in its own shipped file (like `aio_availability.php`), keep `config.php` to settings, and when reproducing a "200 with empty body", pull the real image and mount the real host `config.php` (`docker run -v .../config.php:/var/www/html/config.php`), then re-enable errors *after* the `error_reporting(0)` line in a debug copy of the script (`display_errors=1` alone does nothing once `error_reporting(0)` has run). Also: `?clearMovieCache=true` deliberately `exit()`s right after clearing - it never resolves in the same request, so test clear and resolve as two calls.
- **Production deployment is GitHub Actions → `ghcr.io` → Portainer**
  (container `tmdb-vod-php`), not a direct push-to-server. You can check a
  deploy without SSH/docker access: the GitHub Actions API
  (`api.github.com/repos/<owner>/<repo>/actions/runs`) for CI status by
  commit SHA, and the `ghcr.io` registry API (token exchange → manifest →
  blob) for image build timestamps. The CI workflow currently only pushes
  the `:latest` tag (not `:main`) — worth checking if a Portainer stack ever
  seems to be running stale code, in case its compose file pins `:main`
  explicitly.
- **`locateBaseURL()` (`config.php`) is the correct way to turn a relative
  in-app URL (like a cached `video_proxy.php?...` path) into an absolute
  one** for the server to reference or call itself — it already handles the
  `$userSetHost` override and http/https detection correctly. Don't
  hand-roll host detection elsewhere.

---

## 6. File tree reference

Top-level files and directories in the public repo, with what each is for.
Paths are relative to the repo root.

### Core request handlers (Xtream-Codes-compatible endpoints)

| Path | Purpose |
|---|---|
| `play.php` | **The core VOD resolver.** Given a TMDB movie/episode id and account, finds and returns a working stream URL — via AIOStreams (`aioStreamsFindAudioLanguage()`, the Unlimited/UnlimitedFR/TV accounts' path) or the direct-torrent `torrentSites` scrapers. Also handles response caching (`cache.json` + durable SQLite), the weighted debrid preference/tier system, and playability verification. By far the largest and most frequently touched file. |
| `debrid.php` | Unified multi-key debrid resolution layer for the direct-torrent path: Real-Debrid, Premiumize, AllDebrid, TorBox. Each service can hold multiple API keys with automatic fallback when one hits a quota. |
| `live_play.php` | Live TV stream resolution (separate from VOD `play.php`). |
| `player_api.php` | The actual Xtream Codes `player_api.php` endpoint (account auth, category/stream listings) that IPTV client apps (IMPlayer, MyTVOnline3, STBEMU, etc.) talk to. |
| `aio_availability.php` | Backs `player_api.php?action=get_vod_availability` (one movie) and `get_availability_batch` (up to 100 movies or shows, curl_multi): does a title have any already-cached AIOStreams candidate (1080p/720p x264, 4K x264/x265, 1080p/720p x265) for the account language? Read-only; one upstream lookup is cached language-independently for 6h. **Throttled/degraded upstream answers are `available: null`, never "none"** (the public ElfHosted AIOStreams allows 100 searches then 1/minute per client IP and answers HTTP 200 with a fake "rate-limit exceeded" stream), a token bucket + cooldown protect real playback's shared rate limit, and a dedicated AIOStreams (`$availabilityAioStreamsUrl`) is what unlocks fast sweeps. Consumed by Decypharr to hide unplayable titles. Self-contained on purpose (see the config.php lesson in Section 5). |
| `router.php` | Tiny URL rewrite shim: maps a `/series/<user>/<pw>/<id>.<ext>` style path to `play.php` with the right `$_GET` params. |
| `xmltv.php` | Generates the XMLTV EPG (Electronic Program Guide) feed for Live TV. |
| `create_playlist.php` / `create_tv_playlist.php` / `create_adult_playlist.php` | Build the M3U/VOD playlists (movies, TV series, adult content) from TMDB, when `$userCreatePlaylist = true`. |

### Video/subtitle delivery & proxying

| Path | Purpose |
|---|---|
| `video_proxy.php` | Byte-proxy for AIOStreams `/playback/` links (IP-locked to whoever first resolved them) — the server fetches and streams bytes so any viewer's IP can play it. `auto=1` mode resolves once server-side then serves ranged requests efficiently, understands `HEAD` for liveness checks, and returns `502` on a dead upstream link. |
| `hls_proxy.php` / `hls_shared.php` | HLS-specific proxying and shared helpers for the subtitle-track HLS scripts below. |
| `subtitle_track_playlist.php` | Master HLS playlist exposing every available subtitle language (via OpenSubtitles/AIOStreams) as a selectable rendition, alongside the video. |
| `subtitle_video_init.php` / `subtitle_video_playlist.php` / `subtitle_video_segment.php` | Video-only CMAF/fMP4 HLS rendition (stream-copy, no re-encode — preserves HDR10/DV metadata) for the subtitle-track playback path. |
| `subtitle_audio_init.php` / `subtitle_audio_playlist.php` / `subtitle_audio_segment.php` | Audio HLS renditions (original + AAC stereo fallback) for the same path. |
| `subtitle_vtt_playlist.php` / `subtitle_vtt_segment.php` | Wraps a plain external `.vtt` subtitle file as its own tiny HLS media playlist (HLS requires a playlist URI, not a raw file, for `#EXT-X-MEDIA:TYPE=SUBTITLES`). |

### Config, shared libraries, analytics dashboard

| Path | Purpose |
|---|---|
| `config.php` | **All user-facing configuration**: API keys, debrid keys (single + multi-key), account credentials (`$xcAccounts`), `$aioDebridWeights`, resolution/quality preferences, rate limits, feature toggles. Contains real secrets in the local working copy; **must stay blank in this public repo**. Also defines `locateBaseURL()`. |
| `m3ulisterr_lib.php` | Shared runtime loaded on every hot request path (`play.php`, every HLS segment endpoint): playback analytics event logging, the private data directory, outbound-URL SSRF safety checks. |
| `m3ulisterr_api.php` | JSON API layer for the dashboard (`dashboard.php?action=api&q=...`). Reads only from the SQLite analytics store; every query is a whitelisted name (`overview`, `refresh`, `sessions`, `blocked`, `whitelisted`, `config`, `cache`, `prewarmed`). |
| `m3ulisterr_view.php` | Dashboard front-end rendering (setup form, login form, the full dashboard UI). CSP-nonce-protected inline script only. |
| `dashboard.php` | Entry point for the self-contained analytics & control dashboard: login, SQLite store, JSONL event import, IP block/whitelist controls, config editor. Requires login (`?action=login` with a CSRF token) before any `?action=api` query works. |
| `prewarm.php` | Cron-driven prewarmer: resolves titles ahead of real viewer demand so the first real play is a cache hit instead of a ~13s cold AIOStreams round-trip. Supports an `--expiring` mode that re-warms entries as they approach cache expiry. |
| `libs/` | Vendored/third-party extraction helpers for specific direct-stream sources (`autoembed.php`, `vidsrc_rip.php`, `bypass_cloudflare.php`, `JavaScriptUnpacker.php`, `vidscr.php`). |

### Deployment & infra

| Path | Purpose |
|---|---|
| `Dockerfile` | Production image build (Apache + PHP). |
| `docker-compose.yml` | Local/production compose definition. |
| `docker-entrypoint.sh` | Container entrypoint: applies `PHP_MEMORY_LIMIT`, fixes ownership/permissions on the mounted durable-data volume, optionally starts the prewarm cron, hands off to Apache. |
| `.dockerignore` | Build-context excludes for the Docker image. |
| `.htaccess` | Apache rewrite rules — notably blocks public access to the private analytics data dir, `.git`, `cache.json`, logs, and other sensitive paths even where directive-level `Require`/`Files` blocks aren't available. |
| `.github/workflows/ci.yml` | GitHub Actions CI: builds and pushes the image to `ghcr.io` (currently `:latest` tag only) on push. |

### Docs & content

| Path | Purpose |
|---|---|
| `README.md` + `README_*.md` (AR/DE/EL/FR/HE/IT/PT) | Main project documentation, including the **`# 🔄 Updates & Changelog`** section — the actual source of truth for the changelog (see Section 4). Translated variants maintain their own changelog section separately. |
| `RELEASE_NOTES.md` | **Generated file — do not hand-edit.** Multi-language changelog rollup, produced by `generate_release_notes.py` from `README.md` + every `README_*.md`'s own changelog section. |
| `generate_release_notes.py` | The generator script for `RELEASE_NOTES.md` (see above). Run after updating `README.md`'s changelog. |
| `LICENSE.md` | Project license. |
| `wiki/` | Additional wiki-style docs, per-language (`Home_*.md`). |
| `images/` | Screenshots and static images used in docs/UI. |
| `assets/` | Runtime-used assets served by the app itself (e.g. `block_notice_bg.png`, the branded background for the block-notice stream). |
| `m3ulisterr_assets/` | Vendored front-end libraries for the dashboard's world-map visualization (`d3.min.js`, `topojson-client.min.js`, `countries-110m.json`). |

### Bundled sub-project

| Path | Purpose |
|---|---|
| `HeadlessVidX/` | A separate, bundled headless browser automation tool (Playwright-based, has its own `Dockerfile`/`docker-compose.yml`/`package.json`) used for extracting streams from sites that need real JS execution to reveal their video source. Required for the `TheTvApp` Live TV sports section and certain direct-stream sources. Has its own install instructions inside. |
| `HeadlessVidX_sitelist/` | `movies.txt` / `series.txt` — site lists HeadlessVidX scrapes against. |

### Data / runtime (not meant to be hand-edited)

| Path | Purpose |
|---|---|
| `cache.json` | Hot, short-lived resolved-URL cache (per `movieId[_lang]_tmdb_url` key). Read/written by `readFromCache()`/`writeToCache()` in `play.php`. Safe to surgically clear one key via `?clearMovieCache=true&movieId=<id>&username=<account>`; do not hand-edit in production. |
| `adult-movies.json` | Pre-built adult VOD catalog, refreshed on a schedule. |
| `access.log` | Local access log artifact — not meaningful outside the machine it was generated on. |
| `scratch/` | Scratch/working directory used at runtime — not source. |

---

## 7. Quick-reference: this project's standing behavioral rules

Condensed from explicit, direct instructions given during this project's
development — treat these as durable unless a human explicitly overrides
one in a later session:

0. Tests for the availability endpoint: `php tests/aio_availability_test.php` (unit) and `bash tests/aio_availability_e2e.sh` (real `player_api.php` against `tests/mock_aiostreams.php`, incl. throttle/degraded/series/budget cases) - run both after touching `aio_availability.php` or its `player_api.php` action.
1. Local working copy is never a git repo and never gets secrets stripped —
   it's the developer's real, working install.
2. Public repo `config.php` secrets must always stay blank; port
   `config.php` changes with a targeted `Edit`, never a full-file copy.
3. Always commit a tested, ported change to the public repo without being
   asked. **Never `git push`** — the human does that themselves.
4. Before committing: syntax-check, run it against a real local dev server,
   and actually exercise the changed behavior (success AND failure paths
   where relevant) — don't commit on the strength of a diff read-through
   alone.
5. After a test passes: update `README.md`'s `# 🔄 Updates & Changelog`
   section, then regenerate `RELEASE_NOTES.md` via
   `python3 generate_release_notes.py`. Never hand-edit `RELEASE_NOTES.md`.
6. Prefer a temporary, clearly-labeled in-code debug block for first-party
   verification over trusting an external/third-party catalog's own data —
   remove the temporary block once verification is done, and commit its
   removal.
7. Respect `AIO_LIST_CACHE_TTL` (600s) when running repeated live tests
   against AIOStreams-backed paths — space independent trials out, or
   understand that rapid repeats share one cached catalog snapshot.
8. Never fabricate or guess a real secret value — only the human operator
   supplies those, directly into the local working copy.

# TMDB to VOD: Free Live TV, Movies & Series Playlist \[Xtream Codes & M3U8\]

[Français](README_FR.md) | [Português](README_PT.md) | [Italiano](README_IT.md) | [Ελληνικά](README_EL.md) | [العربية](README_AR.md) | [עברית](README_HE.md) | [Deutsch](README_DE.md)

[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)


## Update 09/16/2026

AllDebrid fixes, both on the AIOStreams path and the direct-torrent path:

- <strong>AIOStreams no longer favors Premiumize by default:</strong> when AIOStreams offers multiple equally-good cached streams (same tier, same quality) from different debrid services, candidates are now shuffled before the final sort so the tie is broken at random instead of always landing on whichever service the AIOStreams API happened to list first. Genuine quality/cache-status differences still decide the winner every time — this only spreads selection across debrid services (AllDebrid included) when they're truly tied, giving AllDebrid a fair shot instead of Premiumize winning by array-order accident.
- <strong>Direct-AllDebrid resolving fixed (`torrentSites` path, `debrid.php`):</strong> AllDebrid discontinued their `/v4/magnet/status` endpoint, which silently broke every direct AllDebrid resolve (magnets uploaded fine, but status checks always failed). Migrated to the new `/v4.1/magnet/status` endpoint and its different response shape. Also fixed a related bug this migration exposed: the file list returned by that endpoint includes every file in a torrent (subtitles, posters, samples...), not just the video, so a missing extension filter could pick a non-video file — now only actual video files are considered.
- <strong>Verified against the live AllDebrid API</strong> with a real magnet end-to-end (upload → status → unlock → final streamable link, including HTTP range-request support needed for seeking) and against real AIOStreams data confirming Premiumize/AllDebrid ties genuinely occur on real titles.

---

## Update 09/14/2026

A large reliability, language, subtitle, analytics and security pass. Highlights:

- <strong>M3uListerr analytics dashboard (new):</strong> a self-contained `dashboard.php` that logs and visualises every playback — an interactive globe, charts and a sessions table showing title/poster, movie vs TV show, account, requested & audio language, the exact release and debrid service used, playback progress (% and h:mm:ss), subtitles offered, country/city/ISP, device and user-agent, IP, and resolve time. Login-protected with a private SQLite store, plus a built-in `config.php` editor and credential management. See [M3uListerr Dashboard](#m3ulisterr-analytics-dashboard).
- <strong>Correct-language audio:</strong> multi-audio releases no longer play the wrong language. The picked audio track is now chosen from the file's own header (e.g. an "ITA ENG" release plays English, not Italian) and the HLS `LANGUAGE` tag reports what actually plays instead of always claiming English.
- <strong>French account / `?lang=fr`:</strong> a dedicated French path that only accepts releases whose default audio is really French (verified from the container header, not just the tag), preferring cached torrents and an x264 1080p→720p→SD quality ladder (`?codec=x265` flips to an HEVC-first ladder). French streams are served through a lightweight byte-proxy (no ffmpeg) that resolves the source once and streams ranges, avoiding debrid IP rate-limiting.
- <strong>Subtitles:</strong> both European (pt-PT) and Brazilian (pt-BR) Portuguese subtitle tracks are offered, with a direct OpenSubtitles API fallback for when the bundled provider is down, and full subtitle support for TV series episodes.
- <strong>Playback stability:</strong> fixed duplicated frames / timestamp discontinuities at segment boundaries, and fixed a memory-exhaustion bug that silently produced empty segments (endless buffering) on high-bitrate 4K/remux sources.
- <strong>Security hardening:</strong> blocked public access to sensitive files (`.git`, `cache.json`, logs, `config.php`, the dashboard's private data), added an SSRF guard to the video proxy, and a strict Content-Security-Policy, CSRF protection, session hardening and login lockout on the dashboard.
- <strong>Multi-service debrid with key fallback (new):</strong> the torrent resolve path now supports <strong>Real-Debrid, Premiumize, AllDebrid and TorBox</strong> (`debrid.php`), tried in order until one returns a working link. Each service can hold <strong>as many API keys as you like</strong> via `$debridApiKeys` in `config.php`; when one key hits its quota / fair-use / hoster limit the resolver automatically rotates to the next key for that service. The legacy single-key settings still work and are merged in first. Note: the Unlimited / UnlimitedFR / TV-show accounts resolve through AIOStreams, which debrids on its own side with the keys set in the AIOStreams dashboard — these `config.php` debrid keys drive the direct torrent fallback path.
- <strong>Faster first play + durable cache (new):</strong> resolved stream URLs are now persisted to a durable SQLite store (source of truth) that survives deploys, in addition to the temporary hot `cache.json`. `cache.json` only ever holds in-progress and resolved entries (failed lookups are logged to the dashboard, never cached), and is rebuilt one-directionally from SQLite (`resolved` only) — with a dashboard <em>Recreate cache.json</em> button. An optional prewarmer (`prewarm.php`, cron-driven in Docker) resolves titles ahead of time so the first viewer play is an instant cache hit instead of a cold round-trip.
- <strong>Config editor upgrades:</strong> the dashboard `config.php` editor can now edit credentials and URLs (TMDB key, AIOStreams URL, Premiumize / Real-Debrid / AllDebrid / TorBox keys, OpenSubtitles key/token) with show/hide masking, plus the new `Use AllDebrid` / `Use TorBox` toggles — all written back safely behind a PHP syntax check and timestamped backup.
- <strong>IP blocking &amp; per-IP daily rate limit (new):</strong> block any abusive client IP with one click from the dashboard <em>Sessions</em> table (with an optional custom reason) using a clear red-lock / green-unlock button — blocked IPs are listed in a manageable panel and can be unblocked anytime. Separately, configurable per-IP daily request limits in `config.php` (all editable from the dashboard) cap how many titles a single IP may request per day, with <strong>separate caps for movies and TV episodes</strong> (`$dailyMovieLimit`, `$dailyEpisodeLimit`) plus an optional combined total (`$dailyRequestLimit`) — e.g. 100 movies and 200 episodes per IP per day. Movie and episode counters are tracked independently, so hitting the movie cap doesn't block TV and vice-versa; a request is blocked if it would exceed either its own type limit or the combined total. When an IP goes over, `play.php` stops resolving for it and serves a live notice <strong>stream</strong> (not a still image — a bare image flashes by instantly or fails to render at all in most IPTV/VOD players, since they expect an actual video stream at a play.php-style URL) explaining why (&ldquo;you reached the maximum limit of requesting movies / TV shows per day&rdquo;), held on screen for up to 10 minutes so the viewer has time to read it. The notice is rendered once (GD, no ffmpeg needed) and delivered as a live MJPEG stream — the same frame resent every few seconds for as long as the viewer stays connected — so nothing is written to disk and storage never grows no matter how many IPs are blocked or how often a reason/limit changes; it's drawn over a branded neon-framed background (`assets/block_notice_bg.png`, falling back to a plain dark screen if that asset or GD is missing). Counters reset automatically each UTC day; set a limit to `0` to disable it. All checks run before any debrid/AIOStreams work and are fail-open, so a storage hiccup never locks out every viewer.
- <strong>IP whitelist (new):</strong> testing/development IPs never get blocked or rate-limited, full stop — whitelist your own office/VPN/dev IP with one click from the dashboard <em>Sessions</em> table (blue shield button) and it bypasses both manual blocks and every daily limit above. IPs can also be hardcoded in `config.php`'s `$ipWhitelist` array for a fixed dev IP that survives even if the dashboard's database is ever wiped; either source is enough.
- <strong>Adult content in the dashboard (new):</strong> Sessions and Titles now show the correct poster, name and id for adult titles too (previously blank, since their ids aren't real TMDB ids) — sourced from `adult-movies.json` into its own `adult_meta` table, imported automatically alongside the usual TMDB lookups. Every title's id is now labelled by source: `tmdb:<id>` for movies and TV shows, `adult:<id>` for adult content (from the adult playlist's own id space).

---

## Update 09/28/2025

- <strong>Live TV:</strong> Fixed the Live TV section and added DrewLive, a massive all in one source of 7,000+ channels.
- <strong>Read Debrid:</strong> Fixed Read Debrid cache checks and added Streamio Sites as a debrid source (support for more debrid services coming soon).
- <strong>Stream sources:</strong> Cleaned up and removed several direct stream sources in both the main script and HeadlessVidX to improve reliability.
- <strong>Adult VOD:</strong> Fixed the Adult VOD source, the 10,000 title adult movie library now refreshes automatically every Sunday.
- <strong>HeadlessVidX:</strong> Major overhaul and bug fixes. The software had numerous issues and I spent several weeks stabilizing it and bringing it up to the standard I wanted.
- <strong>Overall:</strong> Much of the project had broken after more than a year without updates. Things are working much better now, and I’ve got plans to add more features in upcoming releases.

---

# Summary

<p>Create Live TV, Movies and TV Series Video on Demand (VOD) Playlist's using Xtream Codes or M3U8 Format.

Generate dynamic playlists for Live TV, Movies and TV Series using a mock version of Xtream Codes. Create IPTV, Movies and Series playlists with comprehensive metadata. Streaming links located using TMDB, Real-Debrid, Premiumize and Direct Sources. Ideal for use with apps like iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player and more.</p>

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;"> <!-- Adjust padding as needed -->
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://img.shields.io/badge/Ko--fi-Support-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

# Demo Video

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">
<br><br>

# Screenshots

<table>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110311.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110433.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110501.png" width="400">
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110535.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110653.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110819.png" width="400">
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110832.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110847.png" width="400">
    </td>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111001.png" width="400">
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111026.png" width="400">
    </td>
    <!-- Add more images and rows as needed -->
  </tr>
</table>

# Features

- Dynamic playlist generation for live tv, movies and TV series
- Integration with TMDB, Real Debrid, Premiumize and direct sources for enhanced content retrieval
- Emulation of Xtream Codes software for full metadata details
- Inclusion of  Live TV sources such as [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) and more.
- Most of the live TV channels include detailed TV Guide (EPG) information.
- Automatic caching of found streaming links for efficient playback
- 10K Full length adult movies added to the VOD (disabled by default)
- Correct-language audio selection for multi-audio releases (reads the file header, not just the tag)
- Dedicated French (`UnlimitedFR` / `?lang=fr`) path with header-verified French audio and a cached-first quality ladder
- European (pt-PT) and Brazilian (pt-BR) Portuguese subtitle tracks, with a direct OpenSubtitles API fallback
- **M3uListerr** analytics dashboard: playback stats, an interactive globe, charts and a per-session table (see below)

# Getting Started

[![Video Thumbnail](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Configuration**: Start by setting up the script with the required free [TMDB API Key](https://developer.themoviedb.org/docs/getting-started) and an optional private key for [Real Debrid](https://real-debrid.com/apitoken) or [Premiumize](https://www.premiumize.me/account), which are not mandatory.

2. **Xtream Codes Integration**: Enter the IP address or domain as an Xtream Codes server. Any username and password will work since the script doesn't require authentication. This will automatically load the Live TV, Movies and TV Series playlists into the app.

3. **Non-Xtream Codes Apps**: If your app does not support Xtream Codes, load http://IP_ADDRESS/player_api.php?action=get_vod_streams (replace IP_ADDRESS with your computers ip address) in your browser, then locate the `playlist.m3u8` in the same folder as the script and load it as an M3U playlist. Note that the M3U8 playlists are available for movies and live TV only; TV series cannot be loaded as an M3U playlist.

5. **Playback**: Once everything is set up and the playlists are loaded, you should be able to play a video. Clicking the play button will trigger the script to search multiple websites in the background for a playable link. Please be patient and allow some time for a link to be found and streaming to commence. The script caches and stores the found link for approximately 3 hours, aligning with the typical access token expiration of most direct sources, which occurs at around 4 hours.

5. **Local Hosting**: If you lack a hosting company to run this extremely lightweight script, you can install and run software on your desktop computer like Xampp.

# Changes and Additions

- Added the Premiumize service as an alternative to Real-Debrid. (used only with torrent sites)
- Added threads when searching torrent sites for magnet links. (speeds up the time it takes to find a link)
- Added and fixed direct movie and TV show sources as well as more link extractors.
- Added TheTvApp sports section in the Live TV Playlist (set your app to load EPG and playlist every 12 hours or less.)
- Added PlutoTV to the live TV playlist (Multi Languages Here: https://github.com/matthuisman/i.mjh.nz)
- Redesigned the Live TV and DaddyLive functions and playlist. (all of the images in the playlist are working)
- Fixed a lot of bugs in the torrent search and filtering functions. (it finds links much more often now)
- Fixed the sorting by resolution and more likely to get higher quality links (torrent sites)
- Added adult movies to vod (disabled by default)<br>

# What is HeadlessVidX?​

HeadlessVidX is a tool designed to simplify the development of video extractors for streaming websites. It provides an easy-to-use solution for users, regardless of their programming skills, to quickly add video streaming sites to tools such as 'TMDB TO VOD'.
<table>
  <tr>
<td align="center">
        <img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-41-13%20HeadlessVidX%20-%20Home.png" width="400">
    </td>
    <td align="center">
     <img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-40-15%20HeadlessVidX%20-%20Trainer.png" width="400">   
    </td>
  </tr>
</table>

# Creating Playlist

You no longer need to manually run create_playlist.php and create_tv_playlist.php. With the workflow set up on GitHub, these playlists are automatically generated twice a day. To create your own movies and series playlist, simply set $userCreatePlaylist to true in the config.php file.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b






## Docker Deployment

You can now run the entire stack using Docker and Docker Compose.

### Prerequisites
- Docker and Docker Compose installed.

### Quick Start
1. Clone the repository.
2. Configure your `config.php` (or use environment variables for some settings).
3. Run the following command in the root directory:
   ```bash
   docker-compose up -d
   ```
4. Access the website at `http://localhost:8080`.

### GitHub Actions
The project includes a GitHub Actions workflow `.github/workflows/deploy.yml` that automatically builds and pushes the Docker image to GitHub Container Registry (GHCR) on every push to `main`.

### Environment Variables
The following environment variables can be used to configure the container:
- `HEADLESSVIDX_ADDRESS`: The address of the HeadlessVidX service (default: `localhost:3202`). In docker-compose, this is set to `headlessvidx:3202`.

# M3uListerr Analytics Dashboard

### Dashboard Screenshots

![Overview](wiki/Overview.png)
![Globe](wiki/Globe.png)
![Sessions](wiki/Sessions.png)
![Titles](wiki/Titles.png)
![Cache Logs](wiki/cache%20logs.png)


`dashboard.php` is a self-contained, login-protected analytics and control panel for the server. It records one lightweight event per playback (resolve, playlist, segment, subtitle and proxy events) to a private log, imports them into a local SQLite database, and renders them as a modern dashboard.

### What it shows
- **Overview** — total sessions, resolves, unique viewers and countries; average resolve and segment-delivery times; cache hit/fail counts; and bar charts for debrid service used, country, device/player, account, language, **client ISP** and **movies vs TV shows**.
- **Globe** — an interactive 3D globe plotting where each movie/TV show was requested from.
- **Sessions** — a per-playback table with poster & title, **Movie/TV type and episode code**, account (and its password), requested & audio language, the exact **release name and its languages**, the **debrid service (AD/PM) and provider**, **playback progress** (% and `h:mm:ss` reached), the subtitle tracks offered, country + flag, city/zip, **client ISP**, device, **user-agent** and IP, and the resolve time (or a cache marker).
- **Titles** — most-requested movies and TV shows as a poster grid.
- **Cache** — a viewer for the resolver's `cache.json` entries.
- **Config** — edit an allow-listed set of `config.php` settings from the browser (a timestamped backup is written and the file is syntax-checked before it is replaced).
- **Account** — change the dashboard username and password.

### Security
- First run prompts you to create an admin account; the password is stored hashed (Argon2id) in a private SQLite database, never in code.
- All state is kept in a private `m3ulisterr_data/` directory that the web server refuses to serve (SQLite DB, event log, sessions and a per-install secret); it is git-ignored and must never be committed.
- A strict Content-Security-Policy (with a per-response nonce), CSRF tokens on every write, session binding/timeouts and login rate-limiting protect the dashboard. The bundled chart libraries are served from disk after login rather than from any CDN.
- IP geolocation (country/city/ISP) uses the free ip-api.com service; each viewer IP is looked up once and cached.

### Getting started
1. Deploy the files as usual (the dashboard needs no extra setup).
2. Ensure the app can create a writable `m3ulisterr_data/` directory next to the site (it is created automatically when writable).
3. Open `http://YOUR_SERVER/dashboard.php`, create the admin account, and sign in. Data is imported on first load and whenever you press **Refresh data**.


# 🙏 Special Thanks

This project's source code was originally created by **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**. 
If you appreciate the original foundation of this project, please consider supporting them:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

The project has since been significantly refactored, modernized, and maintained by **[CyberPoison](https://github.com/CyberPoison)**.

---

# Legal Disclaimer

This script retrieves movie information from TMDB and searches for related content on third-party websites. The legality of streaming or downloading content through these websites is uncertain. Please exercise caution and consider the legal and ethical implications of using this script to access and consume copyrighted content. Always respect copyright laws and the terms of service of the websites you visit.


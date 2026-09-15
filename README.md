# TMDB to VOD: Free Live TV, Movies & Series Playlist \[Xtream Codes & M3U8\]

## Update 09/14/2026

A large reliability, language, subtitle, analytics and security pass. Highlights:

- <strong>M3uListerr analytics dashboard (new):</strong> a self-contained `dashboard.php` that logs and visualises every playback — an interactive globe, charts and a sessions table showing title/poster, movie vs TV show, account, requested & audio language, the exact release and debrid service used, playback progress (% and h:mm:ss), subtitles offered, country/city/ISP, device and user-agent, IP, and resolve time. Login-protected with a private SQLite store, plus a built-in `config.php` editor and credential management. See [M3uListerr Dashboard](#m3ulisterr-analytics-dashboard).
- <strong>Correct-language audio:</strong> multi-audio releases no longer play the wrong language. The picked audio track is now chosen from the file's own header (e.g. an "ITA ENG" release plays English, not Italian) and the HLS `LANGUAGE` tag reports what actually plays instead of always claiming English.
- <strong>French account / `?lang=fr`:</strong> a dedicated French path that only accepts releases whose default audio is really French (verified from the container header, not just the tag), preferring cached torrents and an x264 1080p→720p→SD quality ladder (`?codec=x265` flips to an HEVC-first ladder). French streams are served through a lightweight byte-proxy (no ffmpeg) that resolves the source once and streams ranges, avoiding debrid IP rate-limiting.
- <strong>Subtitles:</strong> both European (pt-PT) and Brazilian (pt-BR) Portuguese subtitle tracks are offered, with a direct OpenSubtitles API fallback for when the bundled provider is down, and full subtitle support for TV series episodes.
- <strong>Playback stability:</strong> fixed duplicated frames / timestamp discontinuities at segment boundaries, and fixed a memory-exhaustion bug that silently produced empty segments (endless buffering) on high-bitrate 4K/remux sources.
- <strong>Security hardening:</strong> blocked public access to sensitive files (`.git`, `cache.json`, logs, `config.php`, the dashboard's private data), added an SSRF guard to the video proxy, and a strict Content-Security-Policy, CSRF protection, session hardening and login lockout on the dashboard.

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
      <a href="https://github.com/gogetta69/TMDB-To-VOD-Playlist/archive/refs/heads/main.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;"> <!-- Adjust padding as needed -->
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://www.ko-fi.com/img/githubbutton_sm.svg" alt="Ko-fi">
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

# Legal Disclaimer

This script retrieves movie information from TMDB and searches for related content on third-party websites. The legality of streaming or downloading content through these websites is uncertain. Please exercise caution and consider the legal and ethical implications of using this script to access and consume copyrighted content. Always respect copyright laws and the terms of service of the websites you visit.


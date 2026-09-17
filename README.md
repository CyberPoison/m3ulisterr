# 🍿 TMDB to VOD: Free Live TV, Movies & Series Playlist [Xtream Codes & M3U8]

[Français](README_FR.md) | [Português](README_PT.md) | [Italiano](README_IT.md) | [Ελληνικά](README_EL.md) | [العربية](README_AR.md) | [עברית](README_HE.md) | [Deutsch](README_DE.md)

[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)

---

# 📝 Summary

<p>Create Live TV, Movies and TV Series Video on Demand (VOD) Playlists using Xtream Codes or M3U8 Format.</p>

<p>Generate dynamic playlists for Live TV, Movies, and TV Series using a mock version of Xtream Codes. Create IPTV, Movies, and Series playlists with comprehensive metadata. Streaming links are located using TMDB, Real-Debrid, Premiumize, and Direct Sources. Ideal for use with apps like iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player, and more.</p>

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;">
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://img.shields.io/badge/Ko--fi-Support-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

---

# 🎬 Demo Video

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">
<br><br>

---

# 📸 Screenshots

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
  </tr>
</table>

---

# ✨ Features

- **Dynamic playlist generation** for Live TV, Movies, and TV Series.
- **Seamless Integration** with TMDB, Real Debrid, Premiumize, and direct sources for enhanced content retrieval.
- **Xtream Codes Emulation** providing full metadata details.
- **Included Live TV Sources**: [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf), and more.
- **EPG Integration**: Most live TV channels include detailed TV Guide (EPG) information.
- **Automatic Caching** of found streaming links for efficient, buffer-free playback.
- **Adult VOD Integration**: 10K full-length adult movies added to the VOD (disabled by default).
- **Correct-Language Audio Selection** for multi-audio releases (reads the file header, not just the tag).
- **Dedicated French Path** (`UnlimitedFR` / `?lang=fr`) with header-verified French audio and a cached-first quality ladder.
- **Robust Subtitles**: European (pt-PT) and Brazilian (pt-BR) Portuguese subtitle tracks, with a direct OpenSubtitles API fallback.
- **M3uListerr Analytics Dashboard**: Rich playback stats, an interactive globe, charts, and a per-session table (see details below).

---

# 🚀 Getting Started

[![Video Thumbnail](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Configuration**: Start by setting up the script with the required free [TMDB API Key](https://developer.themoviedb.org/docs/getting-started) and an optional private key for [Real Debrid](https://real-debrid.com/apitoken) or [Premiumize](https://www.premiumize.me/account), which are not mandatory.

2. **Xtream Codes Integration**: Enter the IP address or domain as an Xtream Codes server. Any username and password will work since the script doesn't require authentication. This will automatically load the Live TV, Movies, and TV Series playlists into your app.

3. **Non-Xtream Codes Apps**: If your app does not support Xtream Codes, load `http://IP_ADDRESS/player_api.php?action=get_vod_streams` (replace `IP_ADDRESS` with your server's IP address) in your browser. Then locate the `playlist.m3u8` in the same folder as the script and load it as an M3U playlist. Note that M3U8 playlists are available for movies and live TV only; TV series cannot be loaded as an M3U playlist.

4. **Playback**: Once everything is set up and the playlists are loaded, you should be able to play a video. Clicking the play button will trigger the script to search multiple websites in the background for a playable link. Please be patient and allow some time for a link to be found and streaming to commence. The script caches and stores the found link for approximately 3 hours, aligning with the typical access token expiration of most direct sources (around 4 hours).

5. **Local Hosting**: If you lack a remote server to run this extremely lightweight script, you can easily install and run it on your desktop computer using software like XAMPP or Docker.

---

# 🤖 What is HeadlessVidX?

HeadlessVidX is a powerful tool designed to simplify the development of video extractors for streaming websites. It provides an easy-to-use solution for users, regardless of their programming skills, to quickly add video streaming sites to tools such as 'TMDB TO VOD'.

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

---

# 📋 Creating Playlist

You no longer need to manually run `create_playlist.php` and `create_tv_playlist.php`. With the workflow set up on GitHub, these playlists are automatically generated twice a day. To create your own customized movies and series playlist locally, simply set `$userCreatePlaylist = true;` in the `config.php` file.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

---

# 🐳 Docker Deployment

You can seamlessly run the entire stack using Docker and Docker Compose.

### ⚡ Quick Start (No git clone needed)

If you just want to run the application immediately using the public image, run the following commands:

```bash
mkdir -p m3ulisterr_data
touch config.php
# 1. Create a custom Docker network for DNS resolution
docker network create m3ulisterr_net

# 2. Start the Gluetun VPN container to bypass Debrid IP blocks
# (See [Gluetun VPN](https://github.com/qdm12/gluetun) for provider configurations)
docker run -d \
  --name gluetun \
  --network=m3ulisterr_net \
  --cap-add=NET_ADMIN \
  --device=/dev/net/tun:/dev/net/tun \
  -e VPN_SERVICE_PROVIDER=custom \
  qmcgaw/gluetun

# 3. Start m3ulisterr routed through the Gluetun VPN network
docker run -d \
  --name m3ulisterr \
  --network=container:gluetun \
  -v $(pwd)/m3ulisterr_data:/var/www/html/m3ulisterr_data \
  -v $(pwd)/config.php:/var/www/html/config.php \
  -e HEADLESSVIDX_ADDRESS=localhost:3202 \
  ghcr.io/cyberpoison/m3ulisterr:latest

# 4. Start a lightweight proxy to fix VPN asymmetric routing for incoming access
# (We fetch the VPN's internal IP directly to avoid any Docker DNS resolution bugs on your host)
GLUETUN_IP=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' gluetun)
docker run -d \
  --name m3ulisterr-proxy \
  --network=m3ulisterr_net \
  -p 8080:80 \
  caddy:alpine caddy reverse-proxy --from :80 --to $GLUETUN_IP:80
```
*Note: Make sure to edit the `config.php` file later with your API keys and restart the container!*

### 🛠️ Using Docker Compose
**Prerequisites**: Docker and Docker Compose installed.

1. Clone the repository.
2. Configure your `config.php` (or use environment variables for some settings).
3. Run the following command in the root directory:
   ```bash
   docker-compose up -d
   ```
4. Access the website at `http://localhost:8080`.

### 🐙 GitHub Actions
The project includes a GitHub Actions workflow (`.github/workflows/deploy.yml`) that automatically builds and pushes the Docker image to GitHub Container Registry (GHCR) on every push to the `main` branch.

### ⚙️ Environment Variables
The following environment variables can be used to configure the container:
- `HEADLESSVIDX_ADDRESS`: The address of the HeadlessVidX service (default: `localhost:3202`). In docker-compose, this is typically set to `headlessvidx:3202`.

---

# 📊 M3uListerr Analytics Dashboard

`dashboard.php` is a self-contained, login-protected analytics and control panel for the server. It records one lightweight event per playback (resolve, playlist, segment, subtitle, and proxy events) to a private log, imports them into a local SQLite database, and renders them as a modern, insightful dashboard.

### 📈 Dashboard Screenshots

**1. Overview Dashboard**  
![Overview](wiki/Overview.png)  
> *Monitor the high-level health of your VOD server. Track total sessions, resolve performance, unique viewers, and the breakdown of movies versus TV shows to understand overall user engagement.*

**2. Interactive Globe**  
![Globe](wiki/Globe.png)  
> *Visualize your audience globally! This 3D interactive globe plots exactly where each movie or TV show request originates, giving you a clear geographical view of your user base.*

**3. Sessions Tracking**  
![Sessions](wiki/Sessions.png)  
> *A granular, per-playback table offering a deep dive into individual streams. See exact releases, requested audio languages, debrid services used, playback progress (% and time), and detailed viewer metrics like ISP, device, and geolocation.*

**4. Top Titles Grid**  
![Titles](wiki/Titles.png)  
> *A visually appealing poster grid showcasing the most requested movies and TV shows. Quickly identify what content is trending among your viewers.*

**5. Cache Logs Viewer**  
![Cache Logs](wiki/cache%20logs.png)  
> *Inspect internal system behaviors with the cache viewer. It displays resolver `cache.json` entries, allowing you to debug and ensure your content delivery is operating flawlessly.*

### 🛡️ Security Features
- **Secure Credentials**: First run prompts you to create an admin account. Passwords are safely hashed (Argon2id) in a private SQLite database.
- **Isolated Data**: All state is kept in a private `m3ulisterr_data/` directory that the web server strictly refuses to serve to the public.
- **Hardened UI**: Features a strict Content-Security-Policy, CSRF tokens, session binding, timeouts, and login rate-limiting to protect the dashboard from abuse.
- **Geolocation Caching**: IP geolocation (country/city/ISP) uses ip-api.com, caching viewer IPs locally to prevent API limits.

### 🚦 Getting Started with the Dashboard
1. Deploy the files as usual (the dashboard needs no extra setup).
2. Ensure the app can create a writable `m3ulisterr_data/` directory next to the site (it is created automatically when writable).
3. Open `http://YOUR_SERVER/dashboard.php`, create the admin account, and sign in. Data is imported on the first load and whenever you press **Refresh data**.

---

# 🔄 Updates & Changelog

### 📅 Update 09/17/2026

**Weighted AllDebrid/Premiumize preference now forceful, plus a real dead-link check:**
- **`$aioDebridWeights` is now a genuine per-resolve preference, not a tie-break:** a weight like `alldebrid => 70, premiumize => 30` makes AllDebrid win roughly 70% of resolves - overriding both quality rank AND language-match tier (a French "Multi" release can now beat a French confirmed-default one from the other service) - specifically to keep call volume off a quota-limited service. A cached candidate still always beats a not-yet-cached one regardless of weight.
- **A weight of exactly 0 is now a hard exclusion:** setting a service to `0` (e.g. `alldebrid => 100, premiumize => 0`) removes that service's candidates from consideration entirely, not just deprioritizes them - if the other service then has nothing cached for a title, the resolve falls through to the next provider rather than ever using the zeroed-out service.
- **Fixed a cache-hit link-liveness check that was a no-op:** the check guarding whether a cached stream URL is still safe to redirect a viewer to used to unconditionally trust any `video_proxy.php` link without actually checking it - which is virtually every AIOStreams candidate. A cached link that went dead upstream (a genuine `502` from the CDN) was still being blindly served to real players. Now issues a real check against the link before trusting it, so a dead cached link correctly triggers a fresh resolve instead.
- **Verified against live production data**, including forcing and confirming both failure and recovery of a real dead cached link, and confirming the weight/exclusion logic on real titles with genuine cross-service competition.

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

# 🙏 Special Thanks

This project's source code was originally created by **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**. 
If you appreciate the original foundation of this project, please consider supporting them:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

The project has since been significantly refactored, modernized, and maintained by **[CyberPoison](https://github.com/CyberPoison)**.

---

# ⚖️ Legal Disclaimer

This script retrieves movie information from TMDB and searches for related content on third-party websites. The legality of streaming or downloading content through these websites is uncertain. Please exercise caution and consider the legal and ethical implications of using this script to access and consume copyrighted content. Always respect copyright laws and the terms of service of the websites you visit.

# 🎬 TMDB to VOD: Kostenloses Live-TV, Filme & Serien Playlist [Xtream Codes & M3U8]

🌍 **Sprachen:** [English](README.md) | [Deutsch](README_DE.md)

[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)

---

## 📝 Zusammenfassung

<p>Erstellen Sie Video-on-Demand (VOD) Playlists für Live-TV, Filme und TV-Serien im Xtream Codes- oder M3U8-Format.</p>

<p>Generieren Sie dynamische Playlists für Live-TV, Filme und TV-Serien mit einer nachgebildeten Version von Xtream Codes. Erstellen Sie IPTV-, Filme- und Serien-Playlists mit umfassenden Metadaten. Streaming-Links werden über TMDB, Real-Debrid, Premiumize und direkte Quellen gefunden. Ideal für die Verwendung mit Apps wie iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player und mehr.</p>

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

## 🎥 Demo-Video

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">
<br><br>

## 🖼️ Screenshots

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

## ✨ Funktionen

- Dynamische Playlist-Generierung für Live-TV, Filme und TV-Serien
- Integration mit TMDB, Real Debrid, Premiumize und direkten Quellen für verbesserte Inhaltsabrufe
- Emulation der Xtream Codes Software für vollständige Metadaten-Details
- Aufnahme von Live-TV-Quellen wie [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) und mehr.
- Die meisten Live-TV-Kanäle enthalten detaillierte TV-Guide (EPG) Informationen.
- Automatisches Caching von gefundenen Streaming-Links für effiziente Wiedergabe
- 10.000 vollständige Erwachsenenfilme zum VOD hinzugefügt (standardmäßig deaktiviert)
- Audioauswahl in der richtigen Sprache für Multi-Audio-Releases (liest den Datei-Header, nicht nur den Tag)
- Dedizierter französischer (`UnlimitedFR` / `?lang=fr`) Pfad mit header-verifiziertem französischem Audio und einer cached-zuerst Qualitätsleiter
- Europäische (pt-PT) und brasilianische (pt-BR) portugiesische Untertitelspuren, mit direktem OpenSubtitles-API-Fallback
- **M3uListerr** Analyse-Dashboard: Wiedergabestatistiken, ein interaktiver Globus, Diagramme und eine pro-Sitzung-Tabelle (siehe unten)

## 🚀 Erste Schritte

[![Video Thumbnail](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Konfiguration**: Beginnen Sie mit der Einrichtung des Skripts mit dem erforderlichen kostenlosen [TMDB API-Schlüssel](https://developer.themoviedb.org/docs/getting-started) und einem optionalen privaten Schlüssel für [Real Debrid](https://real-debrid.com/apitoken) oder [Premiumize](https://www.premiumize.me/account), die nicht zwingend erforderlich sind.
2. **Xtream Codes Integration**: Geben Sie die IP-Adresse oder Domäne als Xtream Codes-Server ein. Jeder Benutzername und jedes Passwort funktioniert, da das Skript keine Authentifizierung erfordert. Dadurch werden die Live-TV-, Filme- und TV-Serien-Playlists automatisch in die App geladen.
3. **Nicht-Xtream Codes Apps**: Wenn Ihre App keine Xtream Codes unterstützt, laden Sie `http://IP_ADDRESS/player_api.php?action=get_vod_streams` (ersetzen Sie IP_ADDRESS durch die IP-Adresse Ihres Computers) in Ihrem Browser, suchen Sie dann die `playlist.m3u8` im selben Ordner wie das Skript und laden Sie sie als M3U-Playlist. Beachten Sie, dass die M3U8-Playlists nur für Filme und Live-TV verfügbar sind; TV-Serien können nicht als M3U-Playlist geladen werden.
4. **Wiedergabe**: Sobald alles eingerichtet ist und die Playlists geladen sind, sollten Sie in der Lage sein, ein Video abzuspielen. Ein Klick auf die Play-Schaltfläche veranlasst das Skript, im Hintergrund mehrere Websites nach einem abspielbaren Link zu durchsuchen. Bitte haben Sie etwas Geduld und lassen Sie etwas Zeit, damit ein Link gefunden wird und das Streaming beginnen kann. Das Skript speichert und sichert den gefundenen Link für ca. 3 Stunden im Cache, was in etwa mit der typischen Ablaufzeit des Zugriffstokens der meisten direkten Quellen übereinstimmt, die bei etwa 4 Stunden liegt.
5. **Lokales Hosting**: Wenn Ihnen ein Hosting-Unternehmen fehlt, um dieses extrem leichte Skript auszuführen, können Sie Software auf Ihrem Desktop-Computer wie XAMPP installieren und ausführen.

## 🤖 Was ist HeadlessVidX?

HeadlessVidX ist ein Tool, das entwickelt wurde, um die Entwicklung von Video-Extraktoren für Streaming-Websites zu vereinfachen. Es bietet eine einfach zu bedienende Lösung für Benutzer, unabhängig von ihren Programmierkenntnissen, um Videostreaming-Websites schnell zu Tools wie 'TMDB TO VOD' hinzuzufügen.

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

## 📂 Playlists erstellen

Sie müssen nicht mehr manuell `create_playlist.php` und `create_tv_playlist.php` ausführen. Mit dem auf GitHub eingerichteten Workflow werden diese Playlists zweimal täglich automatisch generiert. Um Ihre eigenen Film- und Serien-Playlists zu erstellen, setzen Sie einfach `$userCreatePlaylist` in der Datei `config.php` auf `true`.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

## 🐳 Docker-Bereitstellung

Sie können nun den gesamten Stack mit Docker und Docker Compose ausführen.

### 🔧 Voraussetzungen
- Docker und Docker Compose installiert.

### ⚡ Schnellstart (Ohne git clone)

Führen Sie das öffentliche Image ganz einfach aus, ohne das Repository klonen zu müssen:

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

### 🛠️ Schnellstart (Mit Repository)
1. Klonen Sie das Repository.
2. Konfigurieren Sie Ihre `config.php` (oder verwenden Sie Umgebungsvariablen für einige Einstellungen).
3. Führen Sie den folgenden Befehl im Stammverzeichnis aus:
   ```bash
   docker-compose up -d
   ```
4. Greifen Sie auf die Website unter `http://localhost:8080` zu.

### ⚙️ GitHub Actions
Das Projekt enthält einen GitHub Actions-Workflow `.github/workflows/deploy.yml`, der das Docker-Image bei jedem Push auf `main` automatisch erstellt und in die GitHub Container Registry (GHCR) hochlädt.

### 🌍 Umgebungsvariablen
Die folgenden Umgebungsvariablen können zur Konfiguration des Containers verwendet werden:
- `HEADLESSVIDX_ADDRESS`: Die Adresse des HeadlessVidX-Dienstes (Standard: `localhost:3202`). In docker-compose ist dies auf `headlessvidx:3202` gesetzt.

## 📊 M3uListerr Analyse-Dashboard

`dashboard.php` ist ein eigenständiges, anmeldegeschütztes Analyse- und Kontrollzentrum für den Server. Es zeichnet ein leichtgewichtiges Ereignis pro Wiedergabe (Resolve-, Playlist-, Segment-, Untertitel- und Proxy-Ereignisse) in einem privaten Protokoll auf, importiert sie in eine lokale SQLite-Datenbank und rendert sie als modernes Dashboard.

### 📸 Dashboard Screenshots

**1. Übersicht (Overview)**<br>
Bietet einen umfassenden Blick auf die Systemleistung mit Statistiken zu Sitzungen, einzigartigen Zuschauern und Provider-Nutzung. Die Diagramme helfen dabei, Trends und Nutzerverhalten auf einen Blick zu erfassen.<br>
![Overview](wiki/Overview.png)

**2. Globus (Globe)**<br>
Visualisiert die geografische Verteilung der Zuschauer in Echtzeit auf einem interaktiven 3D-Globus. Ideal, um zu sehen, wo Ihre Inhalte weltweit am beliebtesten sind.<br>
![Globe](wiki/Globe.png)

**3. Sitzungen (Sessions)**<br>
Zeigt detaillierte, pro-Wiedergabe-Informationen an, einschließlich Nutzer, IP-Adresse, Wiedergabefortschritt und verwendetem Debrid-Dienst. So behalten Sie die volle Kontrolle und Übersicht über alle laufenden und vergangenen Streams.<br>
![Sessions](wiki/Sessions.png)

**4. Titel (Titles)**<br>
Präsentiert die beliebtesten und meistgesehenen Filme und Serien in einer übersichtlichen Poster-Ansicht. Dies erleichtert es, Trends im Nutzerverhalten zu erkennen.<br>
![Titles](wiki/Titles.png)

**5. Cache-Protokolle (Cache Logs)**<br>
Ermöglicht tiefe Einblicke in den internen Cache des Systems und die Resolving-Zeiten, um die Ladezeiten zu optimieren und die Effizienz des Streamings sicherzustellen.<br>
![Cache Logs](wiki/cache%20logs.png)

### 🔍 Was es zeigt
- **Übersicht** — Gesamtsitzungen, Resolves, eindeutige Zuschauer und Länder; durchschnittliche Resolve- und Segment-Bereitstellungszeiten; Cache-Treffer/Fehler-Zähler; und Balkendiagramme für den verwendeten Debrid-Dienst, Land, Gerät/Player, Konto, Sprache, **Kunden-ISP** und **Filme vs. TV-Serien**.
- **Globus** — ein interaktiver 3D-Globus, der darstellt, von wo aus jeder Film/jede TV-Serie angefordert wurde.
- **Sitzungen** — eine pro-Wiedergabe-Tabelle mit Poster & Titel, **Film/TV-Typ und Episodencode**, Konto (und dessen Passwort), angeforderter & Audio-Sprache, dem genauen **Release-Namen und seinen Sprachen**, dem **Debrid-Dienst (AD/PM) und Anbieter**, **Wiedergabefortschritt** (% und erreichte `h:mm:ss`), die angebotenen Untertitelspuren, Land + Flagge, Stadt/Postleitzahl, **Kunden-ISP**, Gerät, **User-Agent** und IP sowie die Auflösungszeit (oder einen Cache-Marker).
- **Titel** — die am häufigsten angeforderten Filme und TV-Serien als Poster-Raster.
- **Cache** — ein Viewer für die `cache.json`-Einträge des Resolvers.
- **Konfiguration** — Bearbeiten einer zugelassenen Gruppe von `config.php`-Einstellungen aus dem Browser (ein mit Zeitstempel versehenes Backup wird geschrieben und die Datei auf Syntax geprüft, bevor sie ersetzt wird).
- **Konto** — Ändern Sie den Dashboard-Benutzernamen und das Passwort.

### 🛡️ Sicherheit
- Beim ersten Start werden Sie aufgefordert, ein Administratorkonto zu erstellen; das Passwort wird gehasht (Argon2id) in einer privaten SQLite-Datenbank gespeichert, niemals im Code.
- Der gesamte Zustand wird in einem privaten `m3ulisterr_data/`-Verzeichnis gehalten, dessen Bereitstellung der Webserver verweigert (SQLite DB, Ereignisprotokoll, Sitzungen und ein pro-Installations-Geheimnis); es ist durch git ignoriert und darf niemals committet werden.
- Eine strikte Content-Security-Policy (mit einer pro-Antwort-Nonce), CSRF-Token bei jedem Schreibvorgang, Session-Binding/Timeouts und Login-Ratenbegrenzung schützen das Dashboard. Die gebündelten Diagramm-Bibliotheken werden nach dem Login von der Festplatte bereitgestellt und nicht von einem CDN.
- IP-Geolokalisierung (Land/Stadt/ISP) nutzt den kostenlosen ip-api.com-Dienst; jede Zuschauer-IP wird einmal nachgeschlagen und zwischengespeichert.

### 🚀 Dashboard Erste Schritte
1. Stellen Sie die Dateien wie gewohnt bereit (das Dashboard benötigt keine zusätzliche Einrichtung).
2. Stellen Sie sicher, dass die App ein beschreibbares `m3ulisterr_data/`-Verzeichnis neben der Site erstellen kann (es wird automatisch erstellt, wenn es beschreibbar ist).
3. Öffnen Sie `http://YOUR_SERVER/dashboard.php`, erstellen Sie das Administratorkonto und melden Sie sich an. Daten werden beim ersten Laden und immer dann importiert, wenn Sie **Daten aktualisieren** drücken.

## 🔄 Updates & Changelog

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

## 🙏 Besonderer Dank (Special Thanks)

Der Quellcode dieses Projekts wurde ursprünglich von **Michell Smith alias [gogetta69](https://github.com/gogetta69)** erstellt.
Wenn Sie die ursprüngliche Grundlage dieses Projekts schätzen, erwägen Sie bitte, ihn zu unterstützen:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

Das Projekt wurde seitdem erheblich überarbeitet, modernisiert und wird von **[CyberPoison](https://github.com/CyberPoison)** gepflegt.

---

## ⚖️ Rechtlicher Hinweis

Dieses Skript ruft Filminformationen von TMDB ab und sucht auf Websites Dritter nach zugehörigen Inhalten. Die Legalität des Streamings oder Downloads von Inhalten über diese Websites ist ungewiss. Bitte seien Sie vorsichtig und bedenken Sie die rechtlichen und ethischen Auswirkungen der Verwendung dieses Skripts für den Zugriff auf und Konsum von urheberrechtlich geschützten Inhalten. Respektieren Sie stets die Urheberrechtsgesetze und die Nutzungsbedingungen der Websites, die Sie besuchen.

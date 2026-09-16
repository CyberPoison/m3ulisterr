# TMDB to VOD: Kostenloses Live-TV, Filme & Serien Playlist \[Xtream Codes & M3U8\]


[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)


## Update 14.09.2026

Ein großes Update für Zuverlässigkeit, Sprachen, Untertitel, Analysen und Sicherheit. Highlights:

- **M3uListerr Analyse-Dashboard (neu):** ein eigenständiges `dashboard.php`, das jede Wiedergabe protokolliert und visualisiert — ein interaktiver Globus, Diagramme und eine Sitzungstabelle mit Titel/Poster, Film vs. TV-Serie, Konto, angeforderter & Audio-Sprache, dem genauen Release und verwendetem Debrid-Dienst, Wiedergabefortschritt (% und h:mm:ss), angebotenen Untertiteln, Land/Stadt/ISP, Gerät und User-Agent, IP und Auflösungszeit. Login-geschützt mit einem privaten SQLite-Speicher, plus integriertem `config.php`-Editor und Anmeldeinformationsverwaltung. Siehe [M3uListerr Dashboard](#m3ulisterr-analytics-dashboard).
- **Audio in der richtigen Sprache:** Multi-Audio-Releases spielen nicht mehr die falsche Sprache ab. Die ausgewählte Audiospur wird nun aus dem Header der Datei selbst gewählt (z.B. spielt ein "ITA ENG"-Release Englisch, nicht Italienisch) und der HLS `LANGUAGE`-Tag meldet, was tatsächlich gespielt wird, anstatt immer Englisch zu behaupten.
- **Französisches Konto / `?lang=fr`:** ein dedizierter französischer Pfad, der nur Releases akzeptiert, deren Standard-Audio wirklich Französisch ist (verifiziert über den Container-Header, nicht nur den Tag), bevorzugt gecachte Torrents und eine x264 1080p→720p→SD Qualitätsleiter (`?codec=x265` wechselt zu einer HEVC-zuerst Leiter). Französische Streams werden über einen leichtgewichtigen Byte-Proxy (kein ffmpeg) bereitgestellt, der die Quelle einmal auflöst und Bereiche streamt, wodurch die IP-Ratenbegrenzung von Debrid vermieden wird.
- **Untertitel:** Es werden sowohl europäische (pt-PT) als auch brasilianische (pt-BR) portugiesische Untertitelspuren angeboten, mit einem direkten OpenSubtitles-API-Fallback für den Fall, dass der gebündelte Anbieter ausgefallen ist, und voller Untertitelunterstützung für TV-Serien-Episoden.
- **Wiedergabestabilität:** Duplizierte Frames / Zeitstempel-Diskontinuitäten an Segmentgrenzen behoben und ein Fehler bei der Speicherauslastung behoben, der bei hochratigen 4K/Remux-Quellen unbemerkt leere Segmente (endloses Puffern) erzeugte.
- **Sicherheitsverbesserungen:** Öffentlicher Zugriff auf sensible Dateien blockiert (`.git`, `cache.json`, Protokolle, `config.php`, die privaten Daten des Dashboards), ein SSRF-Schutz für den Videoproxy hinzugefügt und eine strikte Content-Security-Policy, CSRF-Schutz, Sitzungshärtung und Login-Sperre auf dem Dashboard.

---

## Update 28.09.2025

- **Live-TV:** Die Live-TV-Sektion wurde repariert und DrewLive hinzugefügt, eine riesige All-in-One-Quelle mit über 7.000 Kanälen.
- **Read Debrid:** Read Debrid Cache-Prüfungen repariert und Streamio Sites als Debrid-Quelle hinzugefügt (Unterstützung für weitere Debrid-Dienste folgt in Kürze).
- **Stream-Quellen:** Mehrere direkte Stream-Quellen im Hauptskript und HeadlessVidX aufgeräumt und entfernt, um die Zuverlässigkeit zu verbessern.
- **Erwachsenen-VOD:** Die Quelle für Erwachsenen-VOD repariert, die Bibliothek mit 10.000 Filmen für Erwachsene wird jetzt jeden Sonntag automatisch aktualisiert.
- **HeadlessVidX:** Größere Überarbeitung und Fehlerbehebungen. Die Software hatte zahlreiche Probleme, und ich verbrachte mehrere Wochen damit, sie zu stabilisieren und auf den von mir gewünschten Standard zu bringen.
- **Insgesamt:** Ein großer Teil des Projekts war nach mehr als einem Jahr ohne Updates kaputtgegangen. Die Dinge funktionieren jetzt viel besser, und ich habe Pläne, in kommenden Releases weitere Funktionen hinzuzufügen.

---

# Zusammenfassung

<p>Erstellen Sie Video-on-Demand (VOD) Playlists für Live-TV, Filme und TV-Serien im Xtream Codes- oder M3U8-Format.

Generieren Sie dynamische Playlists für Live-TV, Filme und TV-Serien mit einer nachgebildeten Version von Xtream Codes. Erstellen Sie IPTV-, Filme- und Serien-Playlists mit umfassenden Metadaten. Streaming-Links werden über TMDB, Real-Debrid, Premiumize und direkte Quellen gefunden. Ideal für die Verwendung mit Apps wie iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player und mehr.</p>

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;">
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://www.ko-fi.com/img/githubbutton_sm.svg" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

# Demo-Video

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
  </tr>
</table>

# Funktionen

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

# Erste Schritte

[![Video Thumbnail](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Konfiguration**: Beginnen Sie mit der Einrichtung des Skripts mit dem erforderlichen kostenlosen [TMDB API-Schlüssel](https://developer.themoviedb.org/docs/getting-started) und einem optionalen privaten Schlüssel für [Real Debrid](https://real-debrid.com/apitoken) oder [Premiumize](https://www.premiumize.me/account), die nicht zwingend erforderlich sind.

2. **Xtream Codes Integration**: Geben Sie die IP-Adresse oder Domäne als Xtream Codes-Server ein. Jeder Benutzername und jedes Passwort funktioniert, da das Skript keine Authentifizierung erfordert. Dadurch werden die Live-TV-, Filme- und TV-Serien-Playlists automatisch in die App geladen.

3. **Nicht-Xtream Codes Apps**: Wenn Ihre App keine Xtream Codes unterstützt, laden Sie http://IP_ADDRESS/player_api.php?action=get_vod_streams (ersetzen Sie IP_ADDRESS durch die IP-Adresse Ihres Computers) in Ihrem Browser, suchen Sie dann die `playlist.m3u8` im selben Ordner wie das Skript und laden Sie sie als M3U-Playlist. Beachten Sie, dass die M3U8-Playlists nur für Filme und Live-TV verfügbar sind; TV-Serien können nicht als M3U-Playlist geladen werden.

4. **Wiedergabe**: Sobald alles eingerichtet ist und die Playlists geladen sind, sollten Sie in der Lage sein, ein Video abzuspielen. Ein Klick auf die Play-Schaltfläche veranlasst das Skript, im Hintergrund mehrere Websites nach einem abspielbaren Link zu durchsuchen. Bitte haben Sie etwas Geduld und lassen Sie etwas Zeit, damit ein Link gefunden wird und das Streaming beginnen kann. Das Skript speichert und sichert den gefundenen Link für ca. 3 Stunden im Cache, was in etwa mit der typischen Ablaufzeit des Zugriffstokens der meisten direkten Quellen übereinstimmt, die bei etwa 4 Stunden liegt.

5. **Lokales Hosting**: Wenn Ihnen ein Hosting-Unternehmen fehlt, um dieses extrem leichte Skript auszuführen, können Sie Software auf Ihrem Desktop-Computer wie Xampp installieren und ausführen.

# Änderungen und Ergänzungen

- Der Premiumize-Dienst wurde als Alternative zu Real-Debrid hinzugefügt. (nur bei Torrent-Seiten verwendet)
- Threads beim Durchsuchen von Torrent-Seiten nach Magnet-Links hinzugefügt. (beschleunigt die Zeit, um einen Link zu finden)
- Direkte Film- und TV-Show-Quellen sowie weitere Link-Extraktoren hinzugefügt und repariert.
- TheTvApp Sport-Bereich in der Live-TV Playlist hinzugefügt (stellen Sie Ihre App so ein, dass sie EPG und Playlist alle 12 Stunden oder weniger lädt.)
- PlutoTV zur Live-TV-Playlist hinzugefügt (Mehrere Sprachen hier: https://github.com/matthuisman/i.mjh.nz)
- Die Live-TV- und DaddyLive-Funktionen und -Playlists neu gestaltet. (alle Bilder in der Playlist funktionieren)
- Viele Fehler in den Torrent-Such- und Filterfunktionen behoben. (es findet jetzt viel häufiger Links)
- Sortierung nach Auflösung repariert und höhere Wahrscheinlichkeit, qualitativ hochwertigere Links zu erhalten (Torrent-Seiten)
- Erwachsenenfilme zum VOD hinzugefügt (standardmäßig deaktiviert)<br>

# Was ist HeadlessVidX?​

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

# Playlists erstellen

Sie müssen nicht mehr manuell create_playlist.php und create_tv_playlist.php ausführen. Mit dem auf GitHub eingerichteten Workflow werden diese Playlists zweimal täglich automatisch generiert. Um Ihre eigenen Film- und Serien-Playlists zu erstellen, setzen Sie einfach `$userCreatePlaylist` in der Datei `config.php` auf true.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

## Docker-Bereitstellung

Sie können nun den gesamten Stack mit Docker und Docker Compose ausführen.

### Voraussetzungen
- Docker und Docker Compose installiert.

### Schnellstart
1. Klonen Sie das Repository.
2. Konfigurieren Sie Ihre `config.php` (oder verwenden Sie Umgebungsvariablen für einige Einstellungen).
3. Führen Sie den folgenden Befehl im Stammverzeichnis aus:
   ```bash
   docker-compose up -d
   ```
4. Greifen Sie auf die Website unter `http://localhost:8080` zu.

### GitHub Actions
Das Projekt enthält einen GitHub Actions-Workflow `.github/workflows/deploy.yml`, der das Docker-Image bei jedem Push auf `main` automatisch erstellt und in die GitHub Container Registry (GHCR) hochlädt.

### Umgebungsvariablen
Die folgenden Umgebungsvariablen können zur Konfiguration des Containers verwendet werden:
- `HEADLESSVIDX_ADDRESS`: Die Adresse des HeadlessVidX-Dienstes (Standard: `localhost:3202`). In docker-compose ist dies auf `headlessvidx:3202` gesetzt.

# M3uListerr Analyse-Dashboard

### Dashboard Screenshots

![Overview](wiki/Overview.png)
![Globe](wiki/Globe.png)
![Sessions](wiki/Sessions.png)
![Titles](wiki/Titles.png)
![Cache Logs](wiki/cache%20logs.png)


`dashboard.php` ist ein eigenständiges, anmeldegeschütztes Analyse- und Kontrollzentrum für den Server. Es zeichnet ein leichtgewichtiges Ereignis pro Wiedergabe (Resolve-, Playlist-, Segment-, Untertitel- und Proxy-Ereignisse) in einem privaten Protokoll auf, importiert sie in eine lokale SQLite-Datenbank und rendert sie als modernes Dashboard.

### Was es zeigt
- **Übersicht** — Gesamtsitzungen, Resolves, eindeutige Zuschauer und Länder; durchschnittliche Resolve- und Segment-Bereitstellungszeiten; Cache-Treffer/Fehler-Zähler; und Balkendiagramme für den verwendeten Debrid-Dienst, Land, Gerät/Player, Konto, Sprache, **Kunden-ISP** und **Filme vs. TV-Serien**.
- **Globus** — ein interaktiver 3D-Globus, der darstellt, von wo aus jeder Film/jede TV-Serie angefordert wurde.
- **Sitzungen** — eine pro-Wiedergabe-Tabelle mit Poster & Titel, **Film/TV-Typ und Episodencode**, Konto (und dessen Passwort), angeforderter & Audio-Sprache, dem genauen **Release-Namen und seinen Sprachen**, dem **Debrid-Dienst (AD/PM) und Anbieter**, **Wiedergabefortschritt** (% und erreichte `h:mm:ss`), die angebotenen Untertitelspuren, Land + Flagge, Stadt/Postleitzahl, **Kunden-ISP**, Gerät, **User-Agent** und IP sowie die Auflösungszeit (oder einen Cache-Marker).
- **Titel** — die am häufigsten angeforderten Filme und TV-Serien als Poster-Raster.
- **Cache** — ein Viewer für die `cache.json`-Einträge des Resolvers.
- **Konfiguration** — Bearbeiten einer zugelassenen Gruppe von `config.php`-Einstellungen aus dem Browser (ein mit Zeitstempel versehenes Backup wird geschrieben und die Datei auf Syntax geprüft, bevor sie ersetzt wird).
- **Konto** — Ändern Sie den Dashboard-Benutzernamen und das Passwort.

### Sicherheit
- Beim ersten Start werden Sie aufgefordert, ein Administratorkonto zu erstellen; das Passwort wird gehasht (Argon2id) in einer privaten SQLite-Datenbank gespeichert, niemals im Code.
- Der gesamte Zustand wird in einem privaten `m3ulisterr_data/`-Verzeichnis gehalten, dessen Bereitstellung der Webserver verweigert (SQLite DB, Ereignisprotokoll, Sitzungen und ein pro-Installations-Geheimnis); es ist durch git ignoriert und darf niemals committet werden.
- Eine strikte Content-Security-Policy (mit einer pro-Antwort-Nonce), CSRF-Token bei jedem Schreibvorgang, Session-Binding/Timeouts und Login-Ratenbegrenzung schützen das Dashboard. Die gebündelten Diagramm-Bibliotheken werden nach dem Login von der Festplatte bereitgestellt und nicht von einem CDN.
- IP-Geolokalisierung (Land/Stadt/ISP) nutzt den kostenlosen ip-api.com-Dienst; jede Zuschauer-IP wird einmal nachgeschlagen und zwischengespeichert.

### Erste Schritte
1. Stellen Sie die Dateien wie gewohnt bereit (das Dashboard benötigt keine zusätzliche Einrichtung).
2. Stellen Sie sicher, dass die App ein beschreibbares `m3ulisterr_data/`-Verzeichnis neben der Site erstellen kann (es wird automatisch erstellt, wenn es beschreibbar ist).
3. Öffnen Sie `http://YOUR_SERVER/dashboard.php`, erstellen Sie das Administratorkonto und melden Sie sich an. Daten werden beim ersten Laden und immer dann importiert, wenn Sie **Daten aktualisieren** drücken.

# Rechtlicher Hinweis

Dieses Skript ruft Filminformationen von TMDB ab und sucht auf Websites Dritter nach zugehörigen Inhalten. Die Legalität des Streamings oder Downloads von Inhalten über diese Websites ist ungewiss. Bitte seien Sie vorsichtig und bedenken Sie die rechtlichen und ethischen Auswirkungen der Verwendung dieses Skripts für den Zugriff auf und Konsum von urheberrechtlich geschützten Inhalten. Respektieren Sie stets die Urheberrechtsgesetze und die Nutzungsbedingungen der Websites, die Sie besuchen.

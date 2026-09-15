# Willkommen bei TMDB to VOD

Willkommen im offiziellen Wiki für **TMDB to VOD** – ein Tool zum Erstellen von Live-TV, Filme & Serien Video on Demand (VOD) Playlists im Xtream Codes oder M3U8 Format!

## 📌 Zusammenfassung & Wie es funktioniert

Generieren Sie dynamische Playlists für Live-TV, Filme und TV-Serien mit einer nachgebildeten Version von Xtream Codes. Erstellen Sie IPTV-, Filme- und Serien-Playlists mit umfassenden Metadaten. Streaming-Links werden über TMDB, Real-Debrid, Premiumize und direkte Quellen gefunden. Ideal für die Verwendung mit Apps wie iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player und mehr.

### Wichtige Funktionen
- Dynamische Playlist-Generierung für Live-TV, Filme und TV-Serien
- Integration mit TMDB, Real Debrid, Premiumize und direkten Quellen für verbesserte Inhaltsabrufe
- Emulation der Xtream Codes Software für vollständige Metadaten-Details
- Aufnahme von Live-TV-Quellen wie Daddylive, TheTVApp, MoveOnJoy, Streamed Su Sports, Pluto TV und mehr.
- Die meisten Live-TV-Kanäle enthalten detaillierte TV-Guide (EPG) Informationen.
- Automatisches Caching von gefundenen Streaming-Links für effiziente Wiedergabe (für ca. 3 Stunden)
- **Audio in der richtigen Sprache**: Multi-Audio-Releases spielen nicht mehr die falsche Sprache ab. Die Auswahl erfolgt durch das Lesen des Datei-Headers.
- **Untertitel**: Europäische (pt-PT) und brasilianische (pt-BR) portugiesische Untertitelspuren, mit direktem OpenSubtitles-API-Fallback.

---

## 🚀 Installation (Erste Schritte)

1. **Konfiguration**: Beginnen Sie mit der Einrichtung des Skripts mit dem erforderlichen kostenlosen TMDB API-Schlüssel und einem optionalen privaten Schlüssel für Real Debrid oder Premiumize, die nicht zwingend erforderlich sind.
2. **Xtream Codes Integration**: Geben Sie die IP-Adresse oder Domäne als Xtream Codes-Server in Ihrer Player-App ein. Jeder Benutzername und jedes Passwort funktioniert, da das Skript keine Authentifizierung erfordert. Dadurch werden die Playlists automatisch geladen.
3. **Nicht-Xtream Codes Apps**: Laden Sie `http://IP_ADDRESS/player_api.php?action=get_vod_streams` in Ihrem Browser, suchen Sie dann die `playlist.m3u8` im selben Ordner wie das Skript und laden Sie sie als M3U-Playlist. (Beachten Sie: M3U8 ist nur für Filme und Live-TV verfügbar).
4. **Wiedergabe**: Ein Klick auf "Abspielen" veranlasst das Skript, im Hintergrund mehrere Websites nach einem abspielbaren Link zu durchsuchen. Bitte haben Sie etwas Geduld.
5. **Lokales Hosting**: Sie können Xampp auf Ihrem Desktop-Computer verwenden, wenn Sie keinen Server haben.

---

## 🐳 Docker-Bereitstellung

Sie können den gesamten Stack ganz einfach mit Docker und Docker Compose ausführen.

### Voraussetzungen
- Docker und Docker Compose müssen installiert sein.

### Schnellstart
1. Klonen Sie das Repository.
2. Konfigurieren Sie Ihre `config.php` (oder verwenden Sie Umgebungsvariablen für einige Einstellungen).
3. Führen Sie den folgenden Befehl im Stammverzeichnis aus:
   ```bash
   docker-compose up -d
   ```
4. Greifen Sie auf die Website unter `http://localhost:8080` zu.

Das Projekt enthält auch einen GitHub Actions-Workflow, der das Docker-Image bei jedem Push auf `main` automatisch erstellt und in die GitHub Container Registry (GHCR) hochlädt.

---

## 🛠️ Playlists erstellen & Befehle

Sie müssen nicht mehr manuell `create_playlist.php` und `create_tv_playlist.php` ausführen. Mit dem auf GitHub eingerichteten Workflow werden diese Playlists zweimal täglich automatisch generiert. 

Um Ihre **eigenen** Film- und Serien-Playlists zu erstellen, setzen Sie einfach `$userCreatePlaylist` in der Datei `config.php` auf `true`.

---

## 📊 M3uListerr Analyse-Dashboard

`dashboard.php` ist ein eigenständiges, anmeldegeschütztes Analyse- und Kontrollzentrum für den Server.

### Was es zeigt:
- **Übersicht**: Gesamtsitzungen, eindeutige Zuschauer, Länder, Balkendiagramme für Debrid-Dienst, Gerät, Sprache und ISP.
- **Globus**: Ein interaktiver 3D-Globus, der die Anfragen darstellt.
- **Sitzungen**: Detaillierte Tabelle mit Poster, Titel, verwendeter Auflösung, Wiedergabefortschritt, IP, Untertiteln und Auflösungszeit.
- **Cache**: Ein Viewer für die `cache.json`-Einträge des Resolvers.
- **Konfiguration**: Bearbeiten von erlaubten `config.php`-Einstellungen direkt aus dem Browser.

### Sicherheit:
- Sichere SQLite-Datenbank (`m3ulisterr_data/`), niemals im Code oder öffentlich zugänglich.
- Strikte Content-Security-Policy (CSP), CSRF-Token und IP-Ratenbegrenzung beim Login.

![M3uListerr Screenshots](https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110433.png)

---

## 🎬 Was ist HeadlessVidX?

HeadlessVidX ist ein Tool, das entwickelt wurde, um die Entwicklung von Video-Extraktoren für Streaming-Websites zu vereinfachen. Es bietet eine einfach zu bedienende Lösung für Benutzer, unabhängig von ihren Programmierkenntnissen, um Videostreaming-Websites schnell zu Tools wie 'TMDB TO VOD' hinzuzufügen.

![HeadlessVidX Home](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-41-13%20HeadlessVidX%20-%20Home.png)
![HeadlessVidX Trainer](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-40-15%20HeadlessVidX%20-%20Trainer.png)

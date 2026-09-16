# TMDB in VOD: TV in Diretta, Film & Serie Gratis \[Xtream Codes & M3U8\]


[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)


## Aggiornamento 14/09/2026

Un grande aggiornamento riguardante affidabilità, lingua, sottotitoli, analisi e sicurezza. Punti salienti:

- <strong>Dashboard analitica M3uListerr (nuovo):</strong> un `dashboard.php` indipendente che registra e visualizza ogni riproduzione — un globo interattivo, grafici e una tabella delle sessioni che mostra titolo/locandina, film vs serie TV, account, lingua richiesta e audio, l'esatta release e il servizio debrid utilizzato, il progresso della riproduzione (% e h:mm:ss), i sottotitoli offerti, paese/città/ISP, dispositivo e user-agent, IP e tempo di risoluzione. Protetto da login con un archivio privato SQLite, oltre a un editor integrato per `config.php` e gestione delle credenziali. Vedi [Dashboard analitica M3uListerr](#dashboard-analitica-m3ulisterr).
- <strong>Audio nella lingua corretta:</strong> le release multi-audio non riproducono più la lingua sbagliata. La traccia audio selezionata viene ora scelta dall'intestazione del file stesso (es. una release "ITA ENG" riproduce l'inglese, non l'italiano) e il tag `LANGUAGE` dell'HLS riporta ciò che viene effettivamente riprodotto invece di dichiarare sempre l'inglese.
- <strong>Account francese / `?lang=fr`:</strong> un percorso francese dedicato che accetta solo release il cui audio predefinito è realmente il francese (verificato dall'intestazione del contenitore, non solo dal tag), preferendo i torrent in cache e una scala di qualità x264 1080p→720p→SD (`?codec=x265` passa a una scala che privilegia l'HEVC). Gli stream francesi vengono serviti tramite un proxy di byte leggero (senza ffmpeg) che risolve la fonte una volta e trasmette a blocchi, evitando le limitazioni di frequenza IP del debrid.
- <strong>Sottotitoli:</strong> sono offerti sia i sottotitoli in portoghese europeo (pt-PT) che brasiliano (pt-BR), con un fallback diretto alle API di OpenSubtitles per quando il provider integrato è offline, e pieno supporto ai sottotitoli per gli episodi delle serie TV.
- <strong>Stabilità di riproduzione:</strong> risolti i problemi di frame duplicati/discontinuità dei timestamp ai confini dei segmenti, e risolto un bug di esaurimento della memoria che produceva silenziosamente segmenti vuoti (buffering infinito) su sorgenti 4K/remux ad alto bitrate.
- <strong>Rafforzamento della sicurezza:</strong> bloccato l'accesso pubblico ai file sensibili (`.git`, `cache.json`, log, `config.php`, dati privati della dashboard), aggiunta una protezione SSRF al proxy video, e una rigorosa Content-Security-Policy, protezione CSRF, rafforzamento delle sessioni e blocco dei login sulla dashboard.

---

## Aggiornamento 28/09/2025

- <strong>TV in Diretta:</strong> Risolta la sezione della TV in Diretta e aggiunto DrewLive, un'enorme fonte all-in-one di oltre 7.000 canali.
- <strong>Read Debrid:</strong> Risolti i controlli della cache di Read Debrid e aggiunto Streamio Sites come fonte debrid (il supporto per altri servizi debrid è in arrivo).
- <strong>Fonti di streaming:</strong> Pulite e rimosse diverse fonti di streaming diretto sia nello script principale che in HeadlessVidX per migliorare l'affidabilità.
- <strong>VOD per Adulti:</strong> Risolta la fonte del VOD per adulti, la libreria di 10.000 film per adulti si aggiorna ora automaticamente ogni domenica.
- <strong>HeadlessVidX:</strong> Importante revisione e correzione di bug. Il software aveva numerosi problemi e ho trascorso diverse settimane per stabilizzarlo e portarlo allo standard desiderato.
- <strong>In generale:</strong> Gran parte del progetto si era interrotta dopo più di un anno senza aggiornamenti. Le cose funzionano molto meglio ora e ho intenzione di aggiungere ulteriori funzionalità nelle prossime versioni.

---

# Riepilogo

<p>Crea Playlist Video on Demand (VOD) di TV in Diretta, Film e Serie TV utilizzando il formato Xtream Codes o M3U8.

Genera playlist dinamiche per TV in Diretta, Film e Serie TV utilizzando una versione simulata di Xtream Codes. Crea playlist IPTV, di film e serie con metadati completi. Link di streaming individuati utilizzando TMDB, Real-Debrid, Premiumize e Fonti Dirette. Ideale per l'uso con app come iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player e altre.</p>

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Scarica ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;"> <!-- Regola il padding secondo necessità -->
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://img.shields.io/badge/Ko--fi-Support-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

# Video Dimostrativo

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="GIF Dimostrativa" width="70%">
<br><br>

# Screenshot

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
    <!-- Aggiungi altre immagini e righe come necessario -->
  </tr>
</table>

# Funzionalità

- Generazione dinamica di playlist per TV in diretta, film e serie TV
- Integrazione con TMDB, Real Debrid, Premiumize e fonti dirette per un recupero avanzato dei contenuti
- Emulazione del software Xtream Codes per dettagli completi sui metadati
- Inclusione di fonti di TV in Diretta come [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) e altre.
- La maggior parte dei canali TV in diretta include informazioni dettagliate sulla Guida TV (EPG).
- Memorizzazione nella cache automatica dei link di streaming trovati per una riproduzione efficiente
- 10.000 film interi per adulti aggiunti al VOD (disabilitato per impostazione predefinita)
- Selezione dell'audio nella lingua corretta per le release multi-audio (legge l'intestazione del file, non solo il tag)
- Percorso francese dedicato (`UnlimitedFR` / `?lang=fr`) con audio francese verificato nell'intestazione e una scala di qualità che privilegia la cache
- Tracce di sottotitoli in portoghese europeo (pt-PT) e brasiliano (pt-BR), con un fallback diretto all'API di OpenSubtitles
- Dashboard analitica **M3uListerr**: statistiche di riproduzione, un globo interattivo, grafici e una tabella per singola sessione (vedi sotto)

# Per Iniziare

[![Miniatura del Video](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Configurazione**: Inizia configurando lo script con la [TMDB API Key](https://developer.themoviedb.org/docs/getting-started) gratuita richiesta e una chiave privata opzionale per [Real Debrid](https://real-debrid.com/apitoken) o [Premiumize](https://www.premiumize.me/account), che non sono obbligatorie.

2. **Integrazione Xtream Codes**: Inserisci l'indirizzo IP o il dominio come server Xtream Codes. Qualsiasi nome utente e password funzionerà poiché lo script non richiede l'autenticazione. Questo caricherà automaticamente le playlist di TV in Diretta, Film e Serie TV nell'app.

3. **App non compatibili con Xtream Codes**: Se la tua app non supporta Xtream Codes, carica http://INDIRIZZO_IP/player_api.php?action=get_vod_streams (sostituisci INDIRIZZO_IP con l'indirizzo ip del tuo computer) nel tuo browser, quindi individua la `playlist.m3u8` nella stessa cartella dello script e caricala come playlist M3U. Nota che le playlist M3U8 sono disponibili solo per i film e la TV in diretta; le serie TV non possono essere caricate come playlist M3U.

4. **Riproduzione**: Una volta configurato tutto e caricate le playlist, dovresti essere in grado di riprodurre un video. Facendo clic sul pulsante play, lo script inizierà a cercare un link riproducibile su più siti Web in background. Ti preghiamo di avere pazienza e di concedere un po' di tempo affinché venga trovato un link e inizi lo streaming. Lo script memorizza nella cache e salva il link trovato per circa 3 ore, allineandosi con la scadenza tipica del token di accesso della maggior parte delle fonti dirette, che avviene dopo circa 4 ore.

5. **Hosting locale**: Se non disponi di una società di hosting per eseguire questo script estremamente leggero, puoi installare ed eseguire sul tuo computer desktop un software come Xampp.

# Modifiche e Aggiunte

- Aggiunto il servizio Premiumize come alternativa a Real-Debrid. (utilizzato solo con siti torrent)
- Aggiunti i thread durante la ricerca di link magnetici sui siti torrent. (velocizza il tempo impiegato per trovare un link)
- Aggiunte e corrette le fonti dirette per film e programmi TV, nonché più estrattori di link.
- Aggiunta la sezione sportiva di TheTvApp nella Playlist TV in Diretta (imposta la tua app per ricaricare EPG e playlist ogni 12 ore o meno).
- Aggiunto PlutoTV alla playlist TV in diretta (Multi-Lingue Qui: https://github.com/matthuisman/i.mjh.nz)
- Riprogettate le funzioni e la playlist per TV in Diretta e DaddyLive. (tutte le immagini nella playlist sono funzionanti)
- Risolti molti bug nelle funzioni di ricerca e filtraggio dei torrent. (ora trova i link molto più spesso)
- Risolto l'ordinamento in base alla risoluzione e aumentata la probabilità di ottenere link di qualità superiore (siti torrent)
- Aggiunti film per adulti al vod (disabilitati di default)<br>

# Cos'è HeadlessVidX?

HeadlessVidX è uno strumento progettato per semplificare lo sviluppo di estrattori video per siti Web di streaming. Offre una soluzione facile da usare per gli utenti, indipendentemente dalle loro competenze di programmazione, per aggiungere rapidamente siti di streaming video a strumenti come 'TMDB TO VOD'.
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

# Creazione della Playlist

Non è più necessario eseguire manualmente create_playlist.php e create_tv_playlist.php. Con il flusso di lavoro configurato su GitHub, queste playlist vengono generate automaticamente due volte al giorno. Per creare le tue playlist di film e serie, imposta semplicemente $userCreatePlaylist su true nel file config.php.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

## Distribuzione Docker

Ora puoi eseguire l'intero stack utilizzando Docker e Docker Compose.

### Prerequisiti
- Docker e Docker Compose installati.

### Avvio Rapido
1. Clona la repository.
2. Configura il tuo `config.php` (o utilizza le variabili d'ambiente per alcune impostazioni).
3. Esegui il seguente comando nella directory principale:
   ```bash
   docker-compose up -d
   ```
4. Accedi al sito web su `http://localhost:8080`.

### GitHub Actions
Il progetto include un flusso di lavoro di GitHub Actions `.github/workflows/deploy.yml` che compila ed effettua il push automatico dell'immagine Docker sul GitHub Container Registry (GHCR) ad ogni push sul branch `main`.

### Variabili d'ambiente
Le seguenti variabili d'ambiente possono essere utilizzate per configurare il container:
- `HEADLESSVIDX_ADDRESS`: L'indirizzo del servizio HeadlessVidX (predefinito: `localhost:3202`). In docker-compose, questo è impostato su `headlessvidx:3202`.

# Dashboard Analitica M3uListerr

### Dashboard Screenshots

![Overview](wiki/Overview.png)
![Globe](wiki/Globe.png)
![Sessions](wiki/Sessions.png)
![Titles](wiki/Titles.png)
![Cache Logs](wiki/cache%20logs.png)


`dashboard.php` è un pannello di controllo e di analisi indipendente e protetto da login per il server. Registra un evento leggero per ogni riproduzione (eventi resolver, playlist, segmento, sottotitolo e proxy) in un registro privato, li importa in un database SQLite locale e li renderizza come una dashboard moderna.

### Cosa mostra
- **Panoramica** — totale sessioni, risoluzioni, spettatori unici e paesi; tempi medi di risoluzione e di erogazione dei segmenti; conteggio successi/fallimenti della cache; e grafici a barre per il servizio debrid utilizzato, il paese, il dispositivo/player, l'account, la lingua, **ISP del client** e **film vs serie TV**.
- **Globo** — un globo 3D interattivo che traccia da dove è stato richiesto ciascun film/programma TV.
- **Sessioni** — una tabella con le informazioni per ogni riproduzione con locandina e titolo, **tipo (Film/Serie TV) e codice dell'episodio**, account (e relativa password), lingua richiesta e audio, l'esatto **nome della release e le sue lingue**, il **servizio debrid (AD/PM) e il provider**, **il progresso della riproduzione** (% e `h:mm:ss` raggiunti), le tracce dei sottotitoli offerte, il paese + bandiera, città/cap, **ISP del client**, dispositivo, **user-agent** e IP, e il tempo di risoluzione (o un indicatore della cache).
- **Titoli** — i film e le serie TV più richiesti, mostrati come una griglia di locandine.
- **Cache** — un visualizzatore per le voci di `cache.json` del resolver.
- **Configurazione** — modifica un set consentito di impostazioni di `config.php` dal browser (viene scritto un backup con timestamp e la sintassi del file viene verificata prima di essere sostituito).
- **Account** — cambia l'username e la password della dashboard.

### Sicurezza
- Al primo avvio viene richiesto di creare un account amministratore; la password viene memorizzata sottoposta ad hashing (Argon2id) in un database SQLite privato, mai nel codice.
- Tutto lo stato viene mantenuto in una directory privata `m3ulisterr_data/` che il web server si rifiuta di servire (database SQLite, registro eventi, sessioni e un segreto per-installazione); è ignorata da git e non deve mai essere committata.
- Una rigorosa Content-Security-Policy (con un nonce per-risposta), i token CSRF su ogni operazione di scrittura, l'associazione/i timeout di sessione e il rate-limiting dei login proteggono la dashboard. Le librerie dei grafici in bundle sono servite dal disco dopo l'accesso anziché da qualsiasi CDN.
- La geolocalizzazione dell'IP (paese/città/ISP) usa il servizio gratuito ip-api.com; ogni IP degli spettatori viene cercato una sola volta e messo in cache.

### Per iniziare
1. Distribuisci i file come al solito (la dashboard non richiede altre configurazioni).
2. Assicurati che l'app possa creare una directory scrivibile `m3ulisterr_data/` accanto al sito (viene creata automaticamente se possibile).
3. Apri `http://TUO_SERVER/dashboard.php`, crea l'account amministratore e accedi. I dati vengono importati al primo caricamento e ogni volta che premi **Aggiorna dati**.


# 🙏 Special Thanks

This project's source code was originally created by **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**. 
If you appreciate the original foundation of this project, please consider supporting them:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

The project has since been significantly refactored, modernized, and maintained by **[CyberPoison](https://github.com/CyberPoison)**.

---

# Dichiarazione di Non Responsabilità Legale

Questo script recupera informazioni sui film da TMDB e cerca contenuti correlati su siti Web di terze parti. La legalità dello streaming o del download di contenuti tramite questi siti Web è incerta. Si prega di prestare attenzione e considerare le implicazioni legali ed etiche derivanti dall'utilizzo di questo script per accedere a e consumare contenuti protetti da copyright. Rispetta sempre le leggi sul copyright e i termini di servizio dei siti Web che visiti.

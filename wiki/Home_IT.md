# Wiki: TMDB in VOD

Benvenuto nella Wiki ufficiale di TMDB in VOD. Questa guida copre tutto ciò che c'è da sapere per far funzionare l'applicazione, incluse l'installazione, la configurazione di Docker, i comandi principali e il funzionamento generale dello script.

## 1. Come Funziona (How it Works)

Questo progetto ti permette di generare playlist dinamiche per TV in Diretta, Film e Serie TV utilizzando una versione simulata di Xtream Codes oppure il formato M3U8. Gli stream video vengono rintracciati utilizzando TMDB, Real-Debrid, Premiumize e Fonti Dirette. È lo strumento ideale per applicazioni IPTV come iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player e molte altre.

Quando un utente preme "Play" sulla sua app IPTV, lo script esegue una ricerca in tempo reale su molteplici siti web (o tramite servizi Debrid) per trovare un link funzionante, mettendolo successivamente in cache per circa 3 ore (in base alla scadenza tipica dei token dei link diretti).

### Cos'è HeadlessVidX?
HeadlessVidX è uno strumento progettato per semplificare lo sviluppo di estrattori video per i siti Web di streaming, offrendo a chiunque una soluzione semplice per aggiungere nuovi siti di streaming a questo progetto, a prescindere dalle proprie conoscenze di programmazione.
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

## 2. Installazione (Installation)

### Prerequisiti:
- Un server web o un host locale. Se utilizzi un computer desktop puoi optare per un software leggero come **Xampp**.
- Una **TMDB API Key** gratuita, che puoi richiedere su [The Movie Database API](https://developer.themoviedb.org/docs/getting-started).
- (Opzionale ma raccomandato) Una chiave privata (API token) per [Real Debrid](https://real-debrid.com/apitoken) o [Premiumize](https://www.premiumize.me/account).

### Per Iniziare:
1. **Configurazione:** Inizia configurando lo script con la TMDB API Key gratuita richiesta e una chiave privata opzionale per Real Debrid o Premiumize, che non sono obbligatorie.
2. **Integrazione Xtream Codes:** Inserisci l'indirizzo IP o il dominio come server Xtream Codes. Qualsiasi nome utente e password funzionerà poiché lo script non richiede l'autenticazione. Questo caricherà automaticamente le playlist di TV in Diretta, Film e Serie TV nell'app.
3. **App non compatibili con Xtream Codes:** Se la tua app non supporta Xtream Codes, carica `http://INDIRIZZO_IP/player_api.php?action=get_vod_streams` (sostituisci INDIRIZZO_IP con l'indirizzo ip del tuo computer) nel tuo browser, quindi individua la `playlist.m3u8` nella stessa cartella dello script e caricala come playlist M3U. Nota che le playlist M3U8 sono disponibili solo per i film e la TV in diretta; le serie TV non possono essere caricate come playlist M3U.
4. **Riproduzione:** Una volta configurato tutto e caricate le playlist, dovresti essere in grado di riprodurre un video. Facendo clic sul pulsante play, lo script inizierà a cercare un link riproducibile su più siti Web in background. Ti preghiamo di avere pazienza e di concedere un po' di tempo affinché venga trovato un link e inizi lo streaming. Lo script memorizza nella cache e salva il link trovato per circa 3 ore.
5. **Hosting locale:** Se non disponi di una società di hosting per eseguire questo script estremamente leggero, puoi installare ed eseguire sul tuo computer desktop un software come Xampp.

### Creazione della Playlist
Non è più necessario eseguire manualmente create_playlist.php e create_tv_playlist.php. Con il flusso di lavoro configurato su GitHub, queste playlist vengono generate automaticamente due volte al giorno. Per creare le tue playlist di film e serie, imposta semplicemente `$userCreatePlaylist = true` nel file config.php.

## 3. Distribuzione Docker (Docker Deployment)

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

### Variabili d'ambiente
Le seguenti variabili d'ambiente possono essere utilizzate per configurare il container:
- `HEADLESSVIDX_ADDRESS`: L'indirizzo del servizio HeadlessVidX (predefinito: `localhost:3202`). In docker-compose, questo è impostato su `headlessvidx:3202`.

*(Il progetto include anche un flusso di lavoro di GitHub Actions `.github/workflows/deploy.yml` che compila ed effettua il push automatico dell'immagine Docker sul GitHub Container Registry (GHCR) ad ogni push sul branch `main`)*.

## 4. Dashboard Analitica (M3uListerr)

`dashboard.php` è un pannello di controllo e di analisi indipendente e protetto da login per il server. Registra un evento leggero per ogni riproduzione in un registro privato, li importa in un database SQLite locale e li renderizza come una dashboard moderna.

Per avviarla:
1. Distribuisci i file come al solito (la dashboard non richiede altre configurazioni).
2. Assicurati che l'app possa creare una directory scrivibile `m3ulisterr_data/` accanto al sito (viene creata automaticamente se possibile).
3. Apri `http://TUO_SERVER/dashboard.php`, crea l'account amministratore e accedi. I dati vengono importati al primo caricamento e ogni volta che premi **Aggiorna dati**.



### Dashboard Screenshots

![Overview](Overview.png)
![Globe](Globe.png)
![Sessions](Sessions.png)
![Titles](Titles.png)
![Cache Logs](cache%20logs.png)


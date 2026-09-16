# 🎬 TMDB σε VOD: Δωρεάν Ζωντανή Τηλεόραση, Ταινίες & Σειρές (Λίστα Αναπαραγωγής) \[Xtream Codes & M3U8\]

🌎 **[English](README.md)** | **[Ελληνικά](README_EL.md)**

[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)

## 📋 Περίληψη

Δημιουργήστε δυναμικές Λίστες Αναπαραγωγής Video on Demand (VOD) για Ζωντανή Τηλεόραση, Ταινίες και Τηλεοπτικές Σειρές χρησιμοποιώντας μορφή Xtream Codes ή M3U8.

Απολαύστε μια εικονική έκδοση του Xtream Codes για την παραγωγή λιστών IPTV, Ταινιών και Σειρών με πλήρη μεταδεδομένα (metadata). Οι σύνδεσμοι ροής εντοπίζονται αυτόματα μέσω TMDB, Real-Debrid, Premiumize και άμεσων πηγών. Ιδανικό για χρήση με εφαρμογές όπως iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player και άλλες.

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Λήψη ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;">
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://img.shields.io/badge/Ko--fi-Support-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

## 🌟 Χαρακτηριστικά

- **Δυναμική παραγωγή λιστών αναπαραγωγής** για ζωντανή τηλεόραση, ταινίες και τηλεοπτικές σειρές.
- **Προηγμένη ενσωμάτωση** με TMDB, Real Debrid, Premiumize και άμεσες πηγές για βελτιωμένη αναζήτηση περιεχομένου.
- **Εξομοίωση API Xtream Codes** που παρέχει πλήρεις λεπτομέρειες και μεταδεδομένα.
- **Περίληψη πηγών Ζωντανής Τηλεόρασης** συμπεριλαμβανομένων των [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) και άλλων.
- **Λεπτομερείς πληροφορίες EPG (Οδηγός TV)** για τα περισσότερα κανάλια.
- **Έξυπνη προσωρινή αποθήκευση (caching)** συνδέσμων ροής για ταχύτερη και πιο αξιόπιστη αναπαραγωγή.
- **Επιλογή σωστής γλώσσας ήχου** για κυκλοφορίες με πολλαπλούς ήχους (διαβάζοντας την κεφαλίδα του αρχείου, όχι μόνο την ετικέτα).
- **Ειδική Γαλλική διαδρομή** (`UnlimitedFR` / `?lang=fr`) με επιβεβαιωμένο γαλλικό ήχο και κλιμάκωση ποιότητας που προτιμά τη μνήμη cache.
- **Υποστήριξη Υποτίτλων:** Παρέχονται κομμάτια για Ευρωπαϊκά (pt-PT) και Βραζιλιάνικα (pt-BR) Πορτογαλικά, με άμεση εναλλακτική λύση μέσω OpenSubtitles API.
- **Πίνακας ελέγχου analytics M3uListerr:** Παρακολούθηση στατιστικών αναπαραγωγής, διαδραστική υδρόγειος 3D, γραφήματα και λεπτομερής πίνακας συνεδριών.
- **Ενηλίκων VOD:** Πάνω από 10.000 ταινίες ενηλίκων (απενεργοποιημένο από προεπιλογή).

## 🚀 Ξεκινώντας

[![Video Thumbnail](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **⚙️ Διαμόρφωση (Configuration)**: Ξεκινήστε ρυθμίζοντας το script με το απαιτούμενο δωρεάν [TMDB API Key](https://developer.themoviedb.org/docs/getting-started) και ένα προαιρετικό ιδιωτικό κλειδί για [Real Debrid](https://real-debrid.com/apitoken) ή [Premiumize](https://www.premiumize.me/account), τα οποία δεν είναι υποχρεωτικά.
2. **🔌 Ενσωμάτωση Xtream Codes**: Εισαγάγετε τη διεύθυνση IP ή το domain ως διακομιστή Xtream Codes. Οποιοδήποτε όνομα χρήστη και κωδικός πρόσβασης θα λειτουργήσουν, καθώς το script δεν απαιτεί έλεγχο ταυτότητας. Αυτό θα φορτώσει αυτόματα τις λίστες αναπαραγωγής Ζωντανής Τηλεόρασης, Ταινιών και Τηλεοπτικών Σειρών στην εφαρμογή.
3. **📺 Εφαρμογές που δεν υποστηρίζουν Xtream Codes**: Αν η εφαρμογή σας δεν υποστηρίζει Xtream Codes, φορτώστε το `http://IP_ADDRESS/player_api.php?action=get_vod_streams` (αντικαταστήστε το IP_ADDRESS με τη διεύθυνση IP του υπολογιστή σας) στον browser σας, εντοπίστε το `playlist.m3u8` στον ίδιο φάκελο με το script και φορτώστε το ως λίστα M3U. Σημειώστε ότι οι λίστες M3U8 είναι διαθέσιμες μόνο για ταινίες και ζωντανή τηλεόραση. Οι τηλεοπτικές σειρές δεν μπορούν να φορτωθούν ως λίστα M3U.
4. **▶️ Αναπαραγωγή**: Αφού ρυθμιστούν όλα και φορτωθούν οι λίστες αναπαραγωγής, θα πρέπει να μπορείτε να παίξετε ένα βίντεο. Κάνοντας κλικ στο κουμπί αναπαραγωγής, το script θα αρχίσει να ψάχνει σε πολλούς ιστότοπους στο παρασκήνιο για έναν λειτουργικό σύνδεσμο. Παρακαλώ κάντε υπομονή και δώστε λίγο χρόνο για να βρεθεί ένας σύνδεσμος και να ξεκινήσει η ροή. Το script αποθηκεύει προσωρινά τον σύνδεσμο για περίπου 3 ώρες.
5. **💻 Τοπική Φιλοξενία (Local Hosting)**: Εάν δεν έχετε μια εταιρεία φιλοξενίας για να τρέξετε αυτό το εξαιρετικά ελαφρύ script, μπορείτε να εγκαταστήσετε και να τρέξετε λογισμικό στον υπολογιστή σας, όπως το XAMPP.

## 🎥 Βίντεο Επίδειξης

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">

<br><br>

## 📸 Στιγμιότυπα Οθόνης

<table>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110311.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110433.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110501.png" width="400"></td>
  </tr>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110535.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110653.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110819.png" width="400"></td>
  </tr>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110832.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623110847.png" width="400"></td>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111001.png" width="400"></td>
  </tr>
  <tr>
    <td align="center"><img src="https://github.com/gogetta69/TMDB-To-VOD-Playlist/raw/main/images/101623111026.png" width="400"></td>
  </tr>
</table>

## 🤖 Τι είναι το HeadlessVidX?

Το **HeadlessVidX** είναι ένα εργαλείο σχεδιασμένο να απλοποιεί την ανάπτυξη εξαγωγέων (extractors) βίντεο για ιστότοπους ροής. Παρέχει μια εύκολη στη χρήση λύση για τους χρήστες, ανεξάρτητα από τις γνώσεις προγραμματισμού τους, ώστε να προσθέτουν γρήγορα ιστότοπους ροής βίντεο σε εργαλεία όπως το 'TMDB TO VOD'.

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

## 📝 Δημιουργία Λίστας Αναπαραγωγής (Playlist)

Δεν χρειάζεται πλέον να εκτελείτε χειροκίνητα τα `create_playlist.php` και `create_tv_playlist.php`. Με τη ροή εργασιών (workflow) ρυθμισμένη στο GitHub, αυτές οι λίστες δημιουργούνται αυτόματα δύο φορές την ημέρα. Για να δημιουργήσετε τη δική σας λίστα αναπαραγωγής ταινιών και σειρών, απλώς ρυθμίστε το `$userCreatePlaylist = true;` στο αρχείο `config.php`.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

## 🐳 Ανάπτυξη με Docker (Docker Deployment)

Μπορείτε πλέον να εκτελέσετε ολόκληρη τη στοίβα χρησιμοποιώντας Docker και Docker Compose.

### ⚡ Γρήγορη Εκκίνηση (Χωρίς ανάγκη για git clone)
Μπορείτε να εκτελέσετε απευθείας την έτοιμη εικόνα Docker (public image) με την παρακάτω εντολή:

```bash
mkdir -p m3ulisterr_data
touch config.php
docker run -d \
  --name m3ulisterr \
  -p 8080:80 \
  -v $(pwd)/m3ulisterr_data:/var/www/html/m3ulisterr_data \
  -v $(pwd)/config.php:/var/www/html/config.php \
  -e HEADLESSVIDX_ADDRESS=localhost:3202 \
  ghcr.io/cyberpoison/m3ulisterr:latest
```

### 🛠️ Προαπαιτούμενα
- Εγκατεστημένο Docker και Docker Compose.

### 🏗️ Χρήση με Docker Compose (Από τον κώδικα)
1. Κλωνοποιήστε το αποθετήριο.
2. Διαμορφώστε το `config.php` (ή χρησιμοποιήστε μεταβλητές περιβάλλοντος για ορισμένες ρυθμίσεις).
3. Εκτελέστε την ακόλουθη εντολή στον ριζικό κατάλογο:
   ```bash
   docker-compose up -d
   ```
4. Αποκτήστε πρόσβαση στον ιστότοπο στο `http://localhost:8080`.

### 🔄 GitHub Actions & Μεταβλητές Περιβάλλοντος
Το έργο περιλαμβάνει μια ροή εργασιών του GitHub Actions `.github/workflows/deploy.yml` που δημιουργεί (builds) αυτόματα και ωθεί την εικόνα Docker στο GitHub Container Registry (GHCR) σε κάθε push στο `main`.
Μπορείτε να ορίσετε τη μεταβλητή `HEADLESSVIDX_ADDRESS` (προεπιλογή: `localhost:3202`).

## 📊 Πίνακας ελέγχου analytics M3uListerr

Το `dashboard.php` είναι ένας αυτόνομος πίνακας ελέγχου analytics και διαχείρισης, προστατευμένος με σύνδεση για τον διακομιστή. Καταγράφει ένα ελαφρύ γεγονός ανά αναπαραγωγή σε ένα ιδιωτικό αρχείο καταγραφής, τα εισάγει σε μια τοπική βάση δεδομένων SQLite, και τα αποδίδει ως έναν σύγχρονο πίνακα ελέγχου.

### 🖼️ Επεξήγηση Στιγμιότυπων Οθόνης (Dashboard Screenshots)

![Overview](wiki/Overview.png)
**Επισκόπηση (Overview):** *Αυτή η οθόνη σας προσφέρει μια άμεση, συνοπτική ματιά στη συνολική δραστηριότητα του διακομιστή σας. Μπορείτε να δείτε το σύνολο των συνεδριών, μοναδικούς θεατές, ποσοστά επιτυχίας της μνήμης cache και διαδραστικά ραβδογράμματα που αναλύουν τη χρήση ανά πάροχο debrid, τοποθεσία, συσκευή, και τύπο περιεχομένου (ταινίες vs σειρές).*

![Globe](wiki/Globe.png)
**Υδρόγειος (Globe):** *Μια εντυπωσιακή, διαδραστική 3D απεικόνιση της υδρογείου που οπτικοποιεί γεωγραφικά την προέλευση των αιτημάτων. Σας επιτρέπει να δείτε με μια ματιά σε ποιες χώρες και περιοχές ο διακομιστής σας είναι πιο δημοφιλής.*

![Sessions](wiki/Sessions.png)
**Συνεδρίες (Sessions):** *Ένας αναλυτικός πίνακας που παρουσιάζει σε πραγματικό χρόνο την κάθε αναπαραγωγή. Εδώ ελέγχετε ποιος χρήστης παρακολουθεί τι, τον τύπο της συσκευής του, την IP του, την πρόοδο της αναπαραγωγής (με ακρίβεια δευτερολέπτου) και τον ακριβή τίτλο/έκδοση της ταινίας ή σειράς που μεταδίδεται.*

![Titles](wiki/Titles.png)
**Τίτλοι (Titles):** *Μια όμορφη διάταξη με τις αφίσες των ταινιών και των τηλεοπτικών σειρών που έχουν ζητηθεί περισσότερο, βοηθώντας σας να αναγνωρίσετε αμέσως τις τάσεις και το δημοφιλέστερο περιεχόμενο.*

![Cache Logs](wiki/cache%20logs.png)
**Καταγραφές Προσωρινής Μνήμης (Cache Logs):** *Μια τεχνική, αλλά πολύτιμη προβολή των εσωτερικών διεργασιών του resolver. Δείχνει ακριβώς τι έχει αποθηκευτεί στην προσωρινή μνήμη (cache.json), επιτρέποντάς σας να ελέγχετε την αποδοτικότητα της αναζήτησης συνδέσμων και των πηγών.*

### 🔒 Ασφάλεια
- Η πρώτη εκτέλεση σας προτρέπει να δημιουργήσετε έναν λογαριασμό διαχειριστή (admin), ο κωδικός αποθηκεύεται κρυπτογραφημένος (Argon2id) στην SQLite, ποτέ στον κώδικα.
- Όλη η κατάσταση διατηρείται σε έναν ιδιωτικό κατάλογο `m3ulisterr_data/` που ο web server αρνείται να εξυπηρετήσει.
- Μια αυστηρή Πολιτική Ασφάλειας Περιεχομένου, tokens CSRF, και περιορισμός προσπαθειών σύνδεσης (login rate-limiting) προστατεύουν τον πίνακα ελέγχου.

### 🏁 Ξεκινώντας με το Dashboard
1. Αναπτύξτε τα αρχεία ως συνήθως (ο πίνακας ελέγχου δεν χρειάζεται επιπλέον ρύθμιση).
2. Βεβαιωθείτε ότι η εφαρμογή μπορεί να δημιουργήσει έναν εγγράψιμο κατάλογο `m3ulisterr_data/` δίπλα στον ιστότοπο.
3. Ανοίξτε το `http://YOUR_SERVER/dashboard.php`, δημιουργήστε τον λογαριασμό διαχειριστή, και συνδεθείτε. Τα δεδομένα εισάγονται στην πρώτη φόρτωση και κάθε φορά που πατάτε **Refresh data**.

---

## 🔄 Ενημερώσεις & Αλλαγές (Changelog)

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

## 🙏 Ιδιαίτερες Ευχαριστίες (Special Thanks)

Αυτό το έργο βασίστηκε αρχικά στον κώδικα που δημιουργήθηκε από τον **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**.
Αν εκτιμάτε το αρχικό θεμέλιο αυτού του έργου, παρακαλούμε εξετάστε το ενδεχόμενο να τον υποστηρίξετε:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

Από τότε το έργο έχει αναδιαμορφωθεί σημαντικά, έχει εκσυγχρονιστεί και συντηρείται από τον **[CyberPoison](https://github.com/CyberPoison)**.

---

## ⚖️ Αποποίηση Ευθυνών (Legal Disclaimer)

Αυτό το script ανακτά πληροφορίες ταινιών από το TMDB και αναζητά σχετικό περιεχόμενο σε ιστότοπους τρίτων. Η νομιμότητα της ροής (streaming) ή λήψης περιεχομένου μέσω αυτών των ιστότοπων είναι αβέβαιη. Παρακαλούμε να είστε προσεκτικοί και να εξετάσετε τις νομικές και ηθικές συνέπειες της χρήσης αυτού του script για πρόσβαση και κατανάλωση περιεχομένου που προστατεύεται από πνευματικά δικαιώματα. Να σέβεστε πάντα τους νόμους περί πνευματικών δικαιωμάτων και τους όρους χρήσης των ιστότοπων που επισκέπτεστε.

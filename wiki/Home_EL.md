# Wiki: TMDB σε VOD

Καλώς ήρθατε στο Wiki του TMDB-To-VOD. Αυτός ο οδηγός περιέχει όλες τις απαραίτητες πληροφορίες για να εγκαταστήσετε, να κατανοήσετε και να λειτουργήσετε το σύστημα.

## Εγκατάσταση (Installation)

1. **Διαμόρφωση (Configuration)**: Ξεκινήστε ρυθμίζοντας το script με το απαιτούμενο δωρεάν [TMDB API Key](https://developer.themoviedb.org/docs/getting-started) και ένα προαιρετικό ιδιωτικό κλειδί για [Real Debrid](https://real-debrid.com/apitoken) ή [Premiumize](https://www.premiumize.me/account), τα οποία δεν είναι υποχρεωτικά.
2. **Τοπική Φιλοξενία (Local Hosting)**: Εάν δεν έχετε μια εταιρεία φιλοξενίας για να τρέξετε αυτό το εξαιρετικά ελαφρύ script, μπορείτε να εγκαταστήσετε και να τρέξετε λογισμικό στον υπολογιστή σας, όπως το Xampp.

## Ανάπτυξη με Docker (Docker)

Μπορείτε να εκτελέσετε ολόκληρη τη στοίβα χρησιμοποιώντας Docker και Docker Compose.

### Προαπαιτούμενα
- Εγκατεστημένο Docker και Docker Compose.

### Γρήγορη Εκκίνηση
1. Κλωνοποιήστε το αποθετήριο.
2. Διαμορφώστε το `config.php` (ή χρησιμοποιήστε μεταβλητές περιβάλλοντος).
3. Εκτελέστε την εντολή docker-compose (βλ. "Εντολές").
4. Αποκτήστε πρόσβαση στον ιστότοπο στο `http://localhost:8080`.

## Εντολές (Commands)

### Εκκίνηση μέσω Docker
Για να σηκώσετε τους containers, τρέξτε τον παρακάτω κώδικα στον ριζικό κατάλογο:
```bash
docker-compose up -d
```

### Χρήσιμα URLs και Endpoints
- **Για M3U λίστες:** `http://IP_ADDRESS/player_api.php?action=get_vod_streams`
- **Για τον πίνακα ελέγχου (Dashboard):** `http://IP_ADDRESS/dashboard.php`
- **Γαλλικός λογαριασμός:** Χρησιμοποιήστε την παράμετρο `?lang=fr` για τη γαλλική διαδρομή. Ή `?codec=x265` για κλίμακα ποιότητας πρώτα HEVC.

## Πώς Λειτουργεί (How it Works)

Όταν προσθέτετε τις λίστες στο IPTV πρόγραμμα σας, δημιουργούνται δυναμικά από τα δεδομένα του TMDB. 

- **Αναπαραγωγή**: Κάνοντας κλικ στο κουμπί αναπαραγωγής μιας ταινίας, το script θα αρχίσει να ψάχνει σε πολλούς ιστότοπους στο παρασκήνιο για έναν λειτουργικό σύνδεσμο. Το script αποθηκεύει προσωρινά (caches) τον σύνδεσμο που βρέθηκε για περίπου 3 ώρες, εναρμονισμένο με την τυπική λήξη του access token των περισσότερων πηγών.
- **HeadlessVidX**: Το HeadlessVidX είναι ένα εργαλείο σχεδιασμένο να απλοποιεί την ανάπτυξη εξαγωγέων (extractors) βίντεο για ιστότοπους ροής (streaming). Παρέχει μια εύκολη στη χρήση λύση για την προσθήκη νέων ιστοτόπων ροής.

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

- **Δημιουργία Λίστας Αναπαραγωγής (Playlist)**: Οι λίστες δημιουργούνται αυτόματα δύο φορές την ημέρα μέσω ενός GitHub Workflow (δεν χρειάζεται πλέον να εκτελείτε χειροκίνητα τα script). Για να δημιουργήσετε τη δική σας λίστα, ρυθμίστε το `$userCreatePlaylist` σε `true` στο αρχείο `config.php`.

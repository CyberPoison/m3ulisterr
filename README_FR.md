# 🎬 TMDB to VOD : Liste de lecture gratuite de TV en direct, films et séries [Xtream Codes & M3U8]

🌍 **Langues :** [English](README.md) | [Français](README_FR.md)

[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)

---

## 📝 Résumé

Créez des listes de lecture de vidéo à la demande (VOD) pour la télévision en direct, les films et les séries TV à l'aide des codes Xtream ou du format M3U8.

Générez des listes de lecture dynamiques pour la TV en direct, les films et les séries TV à l'aide d'une version factice de Xtream Codes. Créez des listes de lecture IPTV, de films et de séries avec des métadonnées complètes. Les liens de streaming sont localisés à l'aide de TMDB, Real-Debrid, Premiumize et de sources directes. Idéal pour une utilisation avec des applications comme iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player et plus encore.

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Télécharger%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
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

## 🎥 Vidéo de démonstration

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">
<br><br>

## 📸 Captures d'écran

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
    <!-- Ajoutez plus d'images et de lignes au besoin -->
  </tr>
</table>

---

## ✨ Fonctionnalités

- **Génération dynamique** de listes de lecture pour la TV en direct, les films et les séries TV.
- **Intégration multi-sources** avec TMDB, Real Debrid, Premiumize et des sources directes pour une récupération de contenu améliorée.
- **Émulation Xtream Codes** fournissant des métadonnées complètes pour une expérience utilisateur enrichie.
- **TV en direct incluse** avec des sources telles que [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) et plus encore.
- **Guide TV (EPG) détaillé** pour la plupart des chaînes de TV en direct.
- **Mise en cache automatique** des liens de streaming pour garantir une lecture rapide et efficace.
- **VOD Adulte** : +10K films ajoutés (désactivé par défaut).
- **Audio intelligent** : Sélection de la bonne langue pour les releases multi-audio en lisant l'en-tête du fichier (et pas seulement la balise).
- **Expérience Française Optimisée** : Un chemin dédié (`UnlimitedFR` / `?lang=fr`) garantissant un audio français via vérification de l'en-tête et une qualité privilégiant le cache.
- **Sous-titres internationaux** : Prise en charge du portugais européen (pt-PT) et brésilien (pt-BR) avec un système de repli direct vers l'API OpenSubtitles.
- **Tableau de bord analytique M3uListerr** : Statistiques avancées, globe interactif, graphiques et suivi détaillé des sessions.

---

## 🚀 Pour commencer

[![Vignette vidéo](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Configuration** : Commencez par configurer le script avec la [Clé API TMDB](https://developer.themoviedb.org/docs/getting-started) gratuite requise et une clé privée optionnelle pour [Real Debrid](https://real-debrid.com/apitoken) ou [Premiumize](https://www.premiumize.me/account), qui ne sont pas obligatoires.
2. **Intégration Xtream Codes** : Entrez l'adresse IP ou le domaine de votre serveur en tant que Xtream Codes. N'importe quel identifiant et mot de passe fonctionnera (pas d'authentification requise). Cela chargera automatiquement les listes TV, films et séries dans votre application.
3. **Applications non compatibles Xtream Codes** : Si votre application ne gère pas Xtream Codes, ouvrez `http://VOTRE_IP/player_api.php?action=get_vod_streams` dans votre navigateur, localisez `playlist.m3u8` dans le dossier du script et chargez-le comme liste M3U. *Note : Les listes M3U8 ne prennent en charge que les films et la TV en direct.*
4. **Lecture** : Cliquez sur lire ! Le script recherchera en arrière-plan un lien viable. Laissez-lui quelques instants. Les liens sont mis en cache pour environ 3 heures.
5. **Hébergement local** : Utilisez XAMPP ou équivalent pour faire tourner ce script léger directement sur votre ordinateur si vous n'avez pas de serveur web.

---

## 🤖 Qu'est-ce que HeadlessVidX ?​

HeadlessVidX est un outil conçu pour simplifier le développement d'extracteurs vidéo pour les sites de streaming. Il permet à quiconque, sans compétences en programmation, d'ajouter rapidement de nouvelles sources vidéo à des outils tels que 'TMDB TO VOD'.

<table>
  <tr>
    <td align="center"><img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-41-13%20HeadlessVidX%20-%20Home.png" width="400"></td>
    <td align="center"><img src="https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/Screenshot%202024-06-14%20at%2016-40-15%20HeadlessVidX%20-%20Trainer.png" width="400"></td>
  </tr>
</table>

---

## 📜 Création de liste de lecture

Vous n'avez plus besoin d'exécuter manuellement `create_playlist.php` et `create_tv_playlist.php`. Avec le workflow GitHub Actions, ces listes de lecture sont générées automatiquement deux fois par jour. Pour générer les vôtres, définissez `$userCreatePlaylist = true;` dans votre fichier `config.php`.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

---

## 🐳 Déploiement Docker

Vous pouvez désormais déployer l'ensemble du projet très facilement grâce à Docker et Docker Compose.

### Prérequis
- Docker et Docker Compose installés sur votre machine.

### ⚡ Démarrage rapide (Sans git clone)
Lancez directement l'image publique depuis GitHub sans avoir besoin de cloner le dépôt :

```bash
# Créez un répertoire pour stocker les données persistantes (SQLite, logs, sessions)
mkdir -p m3ulisterr_data

# Créez un fichier de configuration vide ou placez-y le vôtre
touch config.php

# Lancez le conteneur en arrière-plan
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

Accédez ensuite à l'application via `http://localhost:8080`.

### Démarrage classique (Avec Docker Compose)
1. Clonez le dépôt.
2. Configurez votre `config.php` ou utilisez des variables d'environnement pour certains paramètres.
3. Exécutez la commande suivante dans le répertoire racine :
   ```bash
   docker-compose up -d
   ```
4. Ouvrez `http://localhost:8080` dans votre navigateur.

### Actions GitHub
Le projet inclut un workflow GitHub Actions `.github/workflows/deploy.yml` qui compile et pousse automatiquement l'image Docker vers le GitHub Container Registry (GHCR) à chaque push sur `main`.

### Variables d'environnement
- `HEADLESSVIDX_ADDRESS` : L'adresse du service HeadlessVidX (par défaut : `localhost:3202`). Dans docker-compose, ceci est défini sur `headlessvidx:3202`.

---

## 📊 Tableau de bord analytique M3uListerr

`dashboard.php` est un panneau de contrôle et d'analyse autonome, protégé par un système de connexion sécurisé. Il enregistre silencieusement chaque événement de lecture dans une base SQLite locale et restitue ces données sur une interface moderne.

### 📸 Captures d'écran du Tableau de bord

- **Vue d'ensemble (Overview)** : 
  ![Overview](wiki/Overview.png)
  *Visualisez l'état global de votre serveur avec des métriques clés (sessions totales, temps de résolution) et des graphiques interactifs détaillant les habitudes de lecture, les FAI de vos utilisateurs, et l'utilisation de vos débrideurs.*
  
- **Globe Interactif (Globe)** : 
  ![Globe](wiki/Globe.png)
  *Découvrez géographiquement d'où proviennent vos utilisateurs grâce à un globe 3D traçant en temps réel les connexions mondiales pour chaque film ou série TV.*

- **Historique des Sessions (Sessions)** : 
  ![Sessions](wiki/Sessions.png)
  *Consultez un tableau détaillé de chaque lecture incluant l'affiche du film/série, le profil de l'utilisateur, la progression exacte (% et durée), la langue sélectionnée, ainsi que l'adresse IP et l'appareil utilisé.*

- **Titres Populaires (Titles)** : 
  ![Titles](wiki/Titles.png)
  *Une grille visuelle et esthétique affichant les affiches des films et séries les plus demandés par vos spectateurs.*

- **Suivi du Cache (Cache Logs)** : 
  ![Cache Logs](wiki/cache%20logs.png)
  *Examinez les performances de mise en cache du système de résolution pour diagnostiquer la vitesse de chargement et l'efficacité de vos requêtes aux débrideurs.*

### Fonctionnalités supplémentaires
- **Configuration** — Éditeur web sécurisé pour modifier votre fichier `config.php` (vérification de la syntaxe et création automatique de sauvegardes incluses).
- **Sécurité Avancée** — Mot de passe haché (Argon2id), base de données SQLite protégée, protection CSRF/SSRF et limitation du taux de connexion. La géolocalisation IP est mise en cache localement via ip-api.com.
- **Accès** — Assurez-vous que le répertoire `m3ulisterr_data/` est inscriptible par le serveur web, puis ouvrez `http://VOTRE_SERVEUR/dashboard.php` pour créer votre compte administrateur.

---

## 🔄 Mises à jour & Changelog

### Mise à jour 09/17/2026

**Extension de la politique de correspondance linguistique AIOStreams — candidats Multi/tagués French acceptés même si le français n'est pas la piste par défaut :**
- **Changement de politique (décision explicite de l'opérateur) :** les candidats français et en mode proxy sont désormais acceptés si la langue demandée est présente *n'importe où* dans le fichier (toute piste audio), et non uniquement lorsqu'elle est la piste par défaut. Le lecteur peut changer de piste plutôt que le serveur rejeter une source valide.
- **L'anglais obtient un vrai niveau de dernier recours :** pour les requêtes `Unlimited`/`?lang=eng`, si aucun candidat anglais ou Multi n'est disponible, le meilleur candidat restant est servi dans sa langue d'origine plutôt que d'échouer.

**Correction du décalage saga/pack — AIOStreams rejette désormais les candidats du mauvais film dans les packs multi-films :**
- **Cause confirmée en production :** AIOStreams renvoyait parfois un candidat issu d'un film *différent* du même pack saga (ex : requête pour Harry Potter: La Chambre des Secrets → fichiers des Reliques de la Mort du même pack 8 films).
- **Correction :** vérification légère de l'année sur `behaviorHints.filename` — si le nom de fichier contient une année explicite différant de celle du titre demandé, le candidat est rejeté. Un nom de fichier sans année est accepté.
- **Régression trouvée et corrigée le même jour** après déploiement en production. Re-vérifiée sur les 8 films Harry Potter réels sur les deux comptes.

**Tableau de bord : les résolutions `&dev=true` n'étaient jamais enregistrées (corrigé) :**
- **Bug :** les branches debug appelaient `writeToCache()` mais ignoraient `m3uLogResolution()`. Toute URL résolue via `&dev=true` affichait des colonnes Release et Debrid vides sur le tableau de bord, et AllDebrid n'apparaissait jamais dans les statistiques globales.
- **Correction :** `m3uLogResolution()` ajouté dans les quatre branches DEBUG avant leur `exit()`.

**Préférence pondérée AllDebrid/Premiumize — désormais contraignante, remplace les niveaux, vérifiée en production :**
- **`$aioDebridWeights` est une vraie préférence par résolution :** un poids comme `alldebrid => 70, premiumize => 30` fait gagner AllDebrid sur ~70% des résolutions, surpassant aussi bien le rang de qualité que le niveau de correspondance linguistique (niveaux 0–2 groupés). Un candidat en cache bat toujours un non mis en cache quel que soit le poids. Le niveau 3 reste un dernier recours absolu.
- **Un poids de 0 est une exclusion stricte :** supprime les candidats de ce service avant tout calcul de niveau ou de qualité.
- **Correction du contrôle de validité des liens mis en cache :** le contrôle faisait confiance inconditionnellement à toute URL `video_proxy.php` sans la vérifier. Corrigé via `checkVideoProxyUpstreamAlive()`.

**Écran de blocage/limite désormais lu comme une vraie vidéo sur tous les lecteurs IPTV :**
- **Corrigé "Source Error" sur IMPlayer, MyTVOnline3, STBEMU, etc. :** l'écran de notification était livré en `multipart/x-mixed-replace` (MJPEG — technique réservée aux navigateurs). C'est maintenant un flux MPEG-TS H.264/AAC généré en direct via ffmpeg, rien n'est écrit sur le disque.
- **Nouveau écran "non disponible pour le moment" :** un titre sans flux disponible affiche une notification vidéo au lieu d'une erreur brute dans le lecteur, pour les films, séries et contenu adulte.

### Mise à jour 09/16/2026

**Corrections AllDebrid sur les deux chemins de résolution, plus améliorations de fiabilité AIOStreams :**
- **AllDebrid/TorBox injoignables via `torrentSites` (corrigé) :** trois fonctions dans `play.php` manquaient `$useAllDebrid`/`$useTorBox` dans leurs déclarations `global` — tous les contrôles évaluaient silencieusement `false`. AllDebrid et TorBox via le chemin torrent direct ne s'exécutaient jamais réellement.
- **Migration API AllDebrid v4.1 (corrigé) :** AllDebrid a arrêté `/v4/magnet/status`. Migré vers `/v4.1/magnet/status` avec sa nouvelle forme de réponse `files[].n`/`.l`. Ajout d'une liste d'extensions vidéo pour éviter de sélectionner des fichiers de sous-titres ou d'affiches.
- **Fausses rejections du contrôle de lecture AIOStreams (corrigé) :** erreurs 5xx transitoires traitées comme permanentes, et `curl_getinfo()` renvoyant `false` pour un Content-Type absent traité comme un mauvais type. Corrigé : réessai unique pour les échecs vraiment transitoires.
- **Mode `--expiring` du préchauffage (nouveau) :** `prewarm.php --expiring[=N]` re-chauffe les entrées du cache durable avant leur expiration, en utilisant le compte et la langue d'origine de chaque entrée. Cron par défaut désormais avec `--expiring=150`.
- **Correction de l'auto-blocage de la boucle locale :** `127.0.0.1`/`::1` sont désormais inconditionnellement approuvés avant tout contrôle de blocage/limite — le serveur qui s'appelle lui-même ne peut pas être usurpé via `REMOTE_ADDR`.

### Mise à jour 09/14/2026
Une mise à jour majeure concernant la fiabilité, les langues, les sous-titres, l'analyse et la sécurité. Points forts :
- **Tableau de bord analytique M3uListerr (nouveau) :** un `dashboard.php` autonome qui enregistre et visualise chaque lecture — un globe interactif, des graphiques et un tableau des sessions affichant le titre/l'affiche, les films vs séries TV, le compte, la langue demandée et audio, la release exacte et le service debrideur utilisé, la progression de la lecture (% et h:mm:ss), les sous-titres proposés, le pays/ville/FAI, l'appareil et le user-agent, l'IP, et le temps de résolution. Protégé par connexion avec un stockage SQLite privé, plus un éditeur `config.php` intégré et une gestion des identifiants. Voir [Tableau de bord M3uListerr](#tableau-de-bord-analytique-m3ulisterr).
- **Audio de la langue correcte :** les releases multi-audio ne lisent plus la mauvaise langue. La piste audio sélectionnée est désormais choisie à partir de l'en-tête du fichier lui-même (par ex. une release "ITA ENG" lit l'anglais, pas l'italien) et la balise HLS `LANGUAGE` rapporte ce qui est réellement lu au lieu de toujours prétendre que c'est de l'anglais.
- **Compte français / `?lang=fr` :** un chemin français dédié qui n'accepte que les releases dont l'audio par défaut est réellement le français (vérifié à partir de l'en-tête du conteneur, pas seulement de la balise), privilégiant les torrents en cache et une échelle de qualité x264 1080p→720p→SD (`?codec=x265` bascule vers une échelle privilégiant le HEVC). Les flux français sont servis via un proxy léger (sans ffmpeg) qui résout la source une fois et diffuse par plages de données, évitant la limitation de débit IP du debrideur.
- **Sous-titres :** les pistes de sous-titres en portugais européen (pt-PT) et brésilien (pt-BR) sont proposées, avec une solution de repli directe via l'API OpenSubtitles lorsque le fournisseur inclus est en panne, et une prise en charge complète des sous-titres pour les épisodes de séries TV.
- **Stabilité de la lecture :** correction des images dupliquées / discontinuités d'horodatage aux limites des segments, et correction d'un bug d'épuisement de mémoire qui produisait silencieusement des segments vides (mise en mémoire tampon sans fin) sur des sources 4K/remux à haut débit.
- **Renforcement de la sécurité :** blocage de l'accès public aux fichiers sensibles (`.git`, `cache.json`, journaux, `config.php`, les données privées du tableau de bord), ajout d'une protection SSRF au proxy vidéo, ainsi qu'une Content-Security-Policy stricte, une protection CSRF, un renforcement des sessions et un verrouillage de connexion sur le tableau de bord.

### Mise à jour 09/28/2025
- **TV en direct :** Correction de la section TV en direct et ajout de DrewLive, une source massive tout-en-un de plus de 7 000 chaînes.
- **Read Debrid :** Correction des vérifications du cache de Read Debrid et ajout de Streamio Sites comme source debrideur (la prise en charge de plus de services de debrideurs arrive bientôt).
- **Sources de flux :** Nettoyage et suppression de plusieurs sources de flux directes dans le script principal et dans HeadlessVidX pour améliorer la fiabilité.
- **VOD Adulte :** Correction de la source VOD adulte, la bibliothèque de 10 000 films pour adultes s'actualise désormais automatiquement chaque dimanche.
- **HeadlessVidX :** Refonte majeure et corrections de bugs. Le logiciel présentait de nombreux problèmes et j'ai passé plusieurs semaines à le stabiliser et à l'amener au niveau souhaité.
- **Général :** Une grande partie du projet était cassée après plus d'un an sans mises à jour. Les choses fonctionnent beaucoup mieux maintenant, et j'ai prévu d'ajouter plus de fonctionnalités dans les prochaines versions.

### Changements et ajouts (plus anciens)
- Ajout du service Premiumize comme alternative à Real-Debrid. (utilisé uniquement avec les sites de torrents)
- Ajout de threads lors de la recherche de liens magnet sur les sites de torrents. (accélère le temps nécessaire pour trouver un lien)
- Ajout et correction de sources directes de films et séries TV ainsi que plus d'extracteurs de liens.
- Ajout de la section sport TheTvApp dans la liste de lecture TV en direct (configurez votre application pour charger l'EPG et la liste de lecture toutes les 12 heures ou moins.)
- Ajout de PlutoTV à la liste de lecture TV en direct (Multilingue ici : https://github.com/matthuisman/i.mjh.nz)
- Refonte des fonctions et de la liste de lecture TV en direct et DaddyLive. (toutes les images de la liste de lecture fonctionnent)
- Correction de nombreux bugs dans les fonctions de recherche et de filtrage des torrents. (les liens sont trouvés beaucoup plus souvent maintenant)
- Correction du tri par résolution et plus de chances d'obtenir des liens de meilleure qualité (sites de torrents)
- Ajout de films pour adultes à la vod (désactivé par défaut)

---

## 🙏 Remerciements Spéciaux

Le code source de ce projet a été initialement créé par **Michell Smith alias [gogetta69](https://github.com/gogetta69)**. 
Si vous appréciez les fondations de ce projet, merci de le soutenir :
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

Le projet a depuis été significativement remanié, modernisé et maintenu par **[CyberPoison](https://github.com/CyberPoison)**.

---

## ⚖️ Avertissement Légal

Ce script récupère des informations sur les films depuis TMDB et recherche du contenu connexe sur des sites Web tiers. La légalité du streaming ou du téléchargement de contenu via ces sites Web est incertaine. Veuillez faire preuve de prudence et tenir compte des implications légales et éthiques de l'utilisation de ce script pour accéder et consommer du contenu protégé par des droits d'auteur. Respectez toujours les lois sur les droits d'auteur et les conditions d'utilisation des sites Web que vous visitez.

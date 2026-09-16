# TMDB to VOD : Liste de lecture gratuite de TV en direct, films et séries \[Xtream Codes & M3U8\]


[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)


## Mise à jour 09/14/2026

Une mise à jour majeure concernant la fiabilité, les langues, les sous-titres, l'analyse et la sécurité. Points forts :

- **Tableau de bord analytique M3uListerr (nouveau) :** un `dashboard.php` autonome qui enregistre et visualise chaque lecture — un globe interactif, des graphiques et un tableau des sessions affichant le titre/l'affiche, les films vs séries TV, le compte, la langue demandée et audio, la release exacte et le service debrideur utilisé, la progression de la lecture (% et h:mm:ss), les sous-titres proposés, le pays/ville/FAI, l'appareil et le user-agent, l'IP, et le temps de résolution. Protégé par connexion avec un stockage SQLite privé, plus un éditeur `config.php` intégré et une gestion des identifiants. Voir [Tableau de bord M3uListerr](#tableau-de-bord-analytique-m3ulisterr).
- **Audio de la langue correcte :** les releases multi-audio ne lisent plus la mauvaise langue. La piste audio sélectionnée est désormais choisie à partir de l'en-tête du fichier lui-même (par ex. une release "ITA ENG" lit l'anglais, pas l'italien) et la balise HLS `LANGUAGE` rapporte ce qui est réellement lu au lieu de toujours prétendre que c'est de l'anglais.
- **Compte français / `?lang=fr` :** un chemin français dédié qui n'accepte que les releases dont l'audio par défaut est réellement le français (vérifié à partir de l'en-tête du conteneur, pas seulement de la balise), privilégiant les torrents en cache et une échelle de qualité x264 1080p→720p→SD (`?codec=x265` bascule vers une échelle privilégiant le HEVC). Les flux français sont servis via un proxy léger (sans ffmpeg) qui résout la source une fois et diffuse par plages de données, évitant la limitation de débit IP du debrideur.
- **Sous-titres :** les pistes de sous-titres en portugais européen (pt-PT) et brésilien (pt-BR) sont proposées, avec une solution de repli directe via l'API OpenSubtitles lorsque le fournisseur inclus est en panne, et une prise en charge complète des sous-titres pour les épisodes de séries TV.
- **Stabilité de la lecture :** correction des images dupliquées / discontinuités d'horodatage aux limites des segments, et correction d'un bug d'épuisement de mémoire qui produisait silencieusement des segments vides (mise en mémoire tampon sans fin) sur des sources 4K/remux à haut débit.
- **Renforcement de la sécurité :** blocage de l'accès public aux fichiers sensibles (`.git`, `cache.json`, journaux, `config.php`, les données privées du tableau de bord), ajout d'une protection SSRF au proxy vidéo, ainsi qu'une Content-Security-Policy stricte, une protection CSRF, un renforcement des sessions et un verrouillage de connexion sur le tableau de bord.

---

## Mise à jour 09/28/2025

- **TV en direct :** Correction de la section TV en direct et ajout de DrewLive, une source massive tout-en-un de plus de 7 000 chaînes.
- **Read Debrid :** Correction des vérifications du cache de Read Debrid et ajout de Streamio Sites comme source debrideur (la prise en charge de plus de services de debrideurs arrive bientôt).
- **Sources de flux :** Nettoyage et suppression de plusieurs sources de flux directes dans le script principal et dans HeadlessVidX pour améliorer la fiabilité.
- **VOD Adulte :** Correction de la source VOD adulte, la bibliothèque de 10 000 films pour adultes s'actualise désormais automatiquement chaque dimanche.
- **HeadlessVidX :** Refonte majeure et corrections de bugs. Le logiciel présentait de nombreux problèmes et j'ai passé plusieurs semaines à le stabiliser et à l'amener au niveau souhaité.
- **Général :** Une grande partie du projet était cassée après plus d'un an sans mises à jour. Les choses fonctionnent beaucoup mieux maintenant, et j'ai prévu d'ajouter plus de fonctionnalités dans les prochaines versions.

---

# Résumé

<p>Créez des listes de lecture de vidéo à la demande (VOD) pour la télévision en direct, les films et les séries TV à l'aide des codes Xtream ou du format M3U8.

Générez des listes de lecture dynamiques pour la TV en direct, les films et les séries TV à l'aide d'une version factice de Xtream Codes. Créez des listes de lecture IPTV, de films et de séries avec des métadonnées complètes. Les liens de streaming sont localisés à l'aide de TMDB, Real-Debrid, Premiumize et de sources directes. Idéal pour une utilisation avec des applications comme iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player et plus encore.</p>

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Télécharger%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="Download ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;"> <!-- Adjust padding as needed -->
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://www.ko-fi.com/img/githubbutton_sm.svg" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

# Vidéo de démonstration

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">
<br><br>

# Captures d'écran

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
    <!-- Ajoutez plus d'images et de lignes au besoin -->
  </tr>
</table>

# Fonctionnalités

- Génération dynamique de listes de lecture pour la TV en direct, les films et les séries TV
- Intégration avec TMDB, Real Debrid, Premiumize et des sources directes pour une récupération de contenu améliorée
- Émulation du logiciel Xtream Codes pour des détails de métadonnées complets
- Inclusion de sources de TV en direct telles que [Daddylive](https://href.li/?https://dlhd.so/24-7-channels.php), [TheTVApp](https://href.li/?https://thetvapp.to/), [MoveOnJoy](https://i.imgur.com/dFazdys.png), [Streamed Su Sports](https://href.li/?https://streamed.pk/), [Pluto TV](https://href.li/?https://downloads.pluto.tv/docs/pluto_tv_channels_listing.pdf) et plus encore.
- La plupart des chaînes de TV en direct incluent des informations détaillées sur le guide TV (EPG).
- Mise en cache automatique des liens de streaming trouvés pour une lecture efficace
- 10K films pour adultes complets ajoutés à la VOD (désactivé par défaut)
- Sélection de l'audio dans la bonne langue pour les releases multi-audio (lit l'en-tête du fichier, pas seulement la balise)
- Chemin français dédié (`UnlimitedFR` / `?lang=fr`) avec un audio français vérifié par en-tête et une échelle de qualité privilégiant le cache
- Pistes de sous-titres en portugais européen (pt-PT) et brésilien (pt-BR), avec un repli direct vers l'API OpenSubtitles
- Tableau de bord analytique **M3uListerr** : statistiques de lecture, un globe interactif, des graphiques et un tableau par session (voir ci-dessous)

# Pour commencer

[![Vignette vidéo](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **Configuration** : Commencez par configurer le script avec la [Clé API TMDB](https://developer.themoviedb.org/docs/getting-started) gratuite requise et une clé privée optionnelle pour [Real Debrid](https://real-debrid.com/apitoken) ou [Premiumize](https://www.premiumize.me/account), qui ne sont pas obligatoires.

2. **Intégration Xtream Codes** : Entrez l'adresse IP ou le domaine comme serveur Xtream Codes. N'importe quel nom d'utilisateur et mot de passe fonctionnera car le script ne nécessite pas d'authentification. Cela chargera automatiquement les listes de lecture de TV en direct, de films et de séries TV dans l'application.

3. **Applications non compatibles avec Xtream Codes** : Si votre application ne prend pas en charge Xtream Codes, chargez http://ADRESSE_IP/player_api.php?action=get_vod_streams (remplacez ADRESSE_IP par l'adresse IP de votre ordinateur) dans votre navigateur, puis localisez le fichier `playlist.m3u8` dans le même dossier que le script et chargez-le comme une liste de lecture M3U. Notez que les listes de lecture M3U8 ne sont disponibles que pour les films et la TV en direct ; les séries TV ne peuvent pas être chargées comme une liste de lecture M3U.

4. **Lecture** : Une fois que tout est configuré et que les listes de lecture sont chargées, vous devriez pouvoir lire une vidéo. En cliquant sur le bouton de lecture, le script recherchera en arrière-plan sur plusieurs sites Web un lien lisible. Veuillez patienter et laisser le temps de trouver un lien et de commencer la diffusion. Le script met en cache et stocke le lien trouvé pendant environ 3 heures, s'alignant sur l'expiration typique du jeton d'accès de la plupart des sources directes, qui se produit à environ 4 heures.

5. **Hébergement local** : Si vous ne disposez pas d'une société d'hébergement pour exécuter ce script extrêmement léger, vous pouvez installer et exécuter un logiciel sur votre ordinateur de bureau comme Xampp.

# Changements et ajouts

- Ajout du service Premiumize comme alternative à Real-Debrid. (utilisé uniquement avec les sites de torrents)
- Ajout de threads lors de la recherche de liens magnet sur les sites de torrents. (accélère le temps nécessaire pour trouver un lien)
- Ajout et correction de sources directes de films et séries TV ainsi que plus d'extracteurs de liens.
- Ajout de la section sport TheTvApp dans la liste de lecture TV en direct (configurez votre application pour charger l'EPG et la liste de lecture toutes les 12 heures ou moins.)
- Ajout de PlutoTV à la liste de lecture TV en direct (Multilingue ici : https://github.com/matthuisman/i.mjh.nz)
- Refonte des fonctions et de la liste de lecture TV en direct et DaddyLive. (toutes les images de la liste de lecture fonctionnent)
- Correction de nombreux bugs dans les fonctions de recherche et de filtrage des torrents. (les liens sont trouvés beaucoup plus souvent maintenant)
- Correction du tri par résolution et plus de chances d'obtenir des liens de meilleure qualité (sites de torrents)
- Ajout de films pour adultes à la vod (désactivé par défaut)<br>

# Qu'est-ce que HeadlessVidX ?​

HeadlessVidX est un outil conçu pour simplifier le développement d'extracteurs vidéo pour les sites Web de streaming. Il fournit une solution facile à utiliser pour les utilisateurs, quelles que soient leurs compétences en programmation, pour ajouter rapidement des sites de streaming vidéo à des outils tels que 'TMDB TO VOD'.
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

# Création de liste de lecture

Vous n'avez plus besoin d'exécuter manuellement create_playlist.php et create_tv_playlist.php. Avec le workflow configuré sur GitHub, ces listes de lecture sont générées automatiquement deux fois par jour. Pour créer votre propre liste de lecture de films et de séries, définissez simplement `$userCreatePlaylist` sur true dans le fichier config.php.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

## Déploiement Docker

Vous pouvez désormais exécuter l'ensemble de la pile à l'aide de Docker et Docker Compose.

### Prérequis
- Docker et Docker Compose installés.

### Démarrage rapide
1. Clonez le dépôt.
2. Configurez votre `config.php` (ou utilisez des variables d'environnement pour certains paramètres).
3. Exécutez la commande suivante dans le répertoire racine :
   ```bash
   docker-compose up -d
   ```
4. Accédez au site Web sur `http://localhost:8080`.

### GitHub Actions
Le projet inclut un workflow GitHub Actions `.github/workflows/deploy.yml` qui compile et pousse automatiquement l'image Docker vers le GitHub Container Registry (GHCR) à chaque push sur `main`.

### Variables d'environnement
Les variables d'environnement suivantes peuvent être utilisées pour configurer le conteneur :
- `HEADLESSVIDX_ADDRESS` : L'adresse du service HeadlessVidX (par défaut : `localhost:3202`). Dans docker-compose, ceci est défini sur `headlessvidx:3202`.

# Tableau de bord analytique M3uListerr

### Dashboard Screenshots

![Overview](wiki/Overview.png)
![Globe](wiki/Globe.png)
![Sessions](wiki/Sessions.png)
![Titles](wiki/Titles.png)
![Cache Logs](wiki/cache%20logs.png)


`dashboard.php` est un panneau de contrôle et d'analyse autonome, protégé par connexion, pour le serveur. Il enregistre un événement léger par lecture (événements de résolution, liste de lecture, segment, sous-titre et proxy) dans un journal privé, les importe dans une base de données SQLite locale, et les restitue sous forme d'un tableau de bord moderne.

### Ce qu'il montre
- **Vue d'ensemble** — total des sessions, résolutions, spectateurs uniques et pays ; temps moyen de résolution et de livraison des segments ; nombre de réussites/échecs du cache ; et des graphiques à barres pour le service debrideur utilisé, le pays, l'appareil/lecteur, le compte, la langue, **le FAI du client** et **les films par rapport aux séries TV**.
- **Globe** — un globe 3D interactif traçant l'endroit d'où chaque film/série TV a été demandé.
- **Sessions** — un tableau par lecture avec l'affiche et le titre, **Type de film/série TV et code d'épisode**, le compte (et son mot de passe), la langue demandée et audio, le **nom exact de la release et ses langues**, le **service debrideur (AD/PM) et fournisseur**, la **progression de la lecture** (% et `h:mm:ss` atteint), les pistes de sous-titres proposées, le pays + drapeau, ville/code postal, **FAI du client**, l'appareil, **user-agent** et IP, ainsi que le temps de résolution (ou un marqueur de cache).
- **Titres** — les films et séries TV les plus demandés sous forme de grille d'affiches.
- **Cache** — un visualiseur pour les entrées `cache.json` du résolveur.
- **Configuration** — modifiez un ensemble autorisé de paramètres `config.php` depuis le navigateur (une sauvegarde horodatée est écrite et la syntaxe du fichier est vérifiée avant son remplacement).
- **Compte** — modifiez le nom d'utilisateur et le mot de passe du tableau de bord.

### Sécurité
- Le premier lancement vous invite à créer un compte administrateur ; le mot de passe est stocké sous forme de hachage (Argon2id) dans une base de données SQLite privée, jamais dans le code.
- Tout l'état est conservé dans un répertoire `m3ulisterr_data/` privé que le serveur web refuse de servir (base de données SQLite, journal d'événements, sessions et un secret par installation) ; il est ignoré par git et ne doit jamais être commité.
- Une Content-Security-Policy stricte (avec un nonce par réponse), des jetons CSRF sur chaque écriture, une liaison/expiration des sessions et une limitation du taux de connexion protègent le tableau de bord. Les bibliothèques de graphiques incluses sont servies depuis le disque après la connexion plutôt que depuis un CDN.
- La géolocalisation IP (pays/ville/FAI) utilise le service gratuit ip-api.com ; chaque IP de spectateur est recherchée une fois et mise en cache.

### Pour commencer
1. Déployez les fichiers comme d'habitude (le tableau de bord ne nécessite aucune configuration supplémentaire).
2. Assurez-vous que l'application peut créer un répertoire `m3ulisterr_data/` inscriptible à côté du site (il est créé automatiquement lorsqu'il est inscriptible).
3. Ouvrez `http://VOTRE_SERVEUR/dashboard.php`, créez le compte administrateur, et connectez-vous. Les données sont importées au premier chargement et chaque fois que vous appuyez sur **Actualiser les données**.


# 🙏 Special Thanks

This project's source code was originally created by **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**. 
If you appreciate the original foundation of this project, please consider supporting them:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

The project has since been significantly refactored, modernized, and maintained by **[CyberPoison](https://github.com/CyberPoison)**.

---

# Avertissement Légal

Ce script récupère des informations sur les films depuis TMDB et recherche du contenu connexe sur des sites Web tiers. La légalité du streaming ou du téléchargement de contenu via ces sites Web est incertaine. Veuillez faire preuve de prudence et tenir compte des implications légales et éthiques de l'utilisation de ce script pour accéder et consommer du contenu protégé par des droits d'auteur. Respectez toujours les lois sur les droits d'auteur et les conditions d'utilisation des sites Web que vous visitez.

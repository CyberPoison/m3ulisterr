# Bienvenue sur le Wiki TMDB to VOD

## Comment ça marche

**TMDB to VOD** permet de créer des listes de lecture de vidéo à la demande (VOD) pour la télévision en direct, les films et les séries TV à l'aide des codes Xtream ou du format M3U8.

Le script génère des listes de lecture dynamiques en utilisant une version factice de Xtream Codes. Il crée des listes de lecture IPTV, de films et de séries avec des métadonnées complètes. Les liens de streaming sont localisés à l'aide de l'API TMDB, et des services tels que Real-Debrid, Premiumize et des sources directes. C'est idéal pour une utilisation avec des applications IPTV comme iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player et bien d'autres.

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="Demo GIF" width="70%">

### HeadlessVidX
Il intègre également **HeadlessVidX**, un outil conçu pour simplifier le développement d'extracteurs vidéo pour les sites Web de streaming. Il fournit une solution facile à utiliser pour ajouter rapidement des sites de streaming vidéo.
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

### Tableau de bord M3uListerr
Un tableau de bord analytique complet, `dashboard.php`, vous donne une vue d'ensemble sur l'utilisation du serveur : sessions, pays, debrideur utilisé, films vs séries, FAI, et bien plus encore, avec un globe 3D interactif et des graphiques détaillés.

---

## Installation

Suivez ces étapes pour commencer à utiliser le script :

1. **Configuration** : Commencez par configurer le script avec la [Clé API TMDB](https://developer.themoviedb.org/docs/getting-started) gratuite requise. Vous pouvez également ajouter une clé privée optionnelle pour [Real Debrid](https://real-debrid.com/apitoken) ou [Premiumize](https://www.premiumize.me/account) (ils ne sont pas obligatoires).

2. **Intégration Xtream Codes** : Dans votre lecteur IPTV, entrez l'adresse IP ou le domaine comme serveur Xtream Codes. N'importe quel nom d'utilisateur et mot de passe fonctionnera car le script ne nécessite pas d'authentification. Cela chargera automatiquement les listes de lecture (TV en direct, Films, Séries).

3. **Applications non compatibles avec Xtream Codes** : 
   Si votre application ne prend pas en charge Xtream Codes :
   - Chargez `http://ADRESSE_IP/player_api.php?action=get_vod_streams` dans votre navigateur (remplacez ADRESSE_IP par l'adresse IP de votre ordinateur).
   - Localisez le fichier `playlist.m3u8` dans le même dossier que le script et chargez-le comme une liste de lecture M3U.
   - *Note : Les listes de lecture M3U8 ne sont disponibles que pour les films et la TV en direct.*

4. **Hébergement local** : Si vous ne disposez pas d'un serveur d'hébergement, vous pouvez installer le script sur votre ordinateur de bureau en utilisant un logiciel comme **XAMPP**.

5. **Tableau de bord** : Assurez-vous que l'application peut créer un répertoire `m3ulisterr_data/` inscriptible. Ouvrez `http://VOTRE_SERVEUR/dashboard.php` pour configurer le compte administrateur.

---

## Docker

Vous pouvez facilement exécuter l'ensemble de la pile à l'aide de Docker et Docker Compose.

### Prérequis
- Docker et Docker Compose installés sur votre machine.

### Démarrage rapide
1. Clonez le dépôt.
2. Configurez votre `config.php` (ou utilisez des variables d'environnement pour certains paramètres).
3. Exécutez la commande suivante dans le répertoire racine :
   ```bash
   docker-compose up -d
   ```
4. Accédez au site Web sur `http://localhost:8080`.

### Variables d'environnement
Vous pouvez utiliser la variable suivante pour configurer le conteneur :
- `HEADLESSVIDX_ADDRESS` : L'adresse du service HeadlessVidX (par défaut : `localhost:3202`). Dans docker-compose, ceci est défini sur `headlessvidx:3202`.

---

## Commandes et configuration

- Pour créer votre propre liste de lecture de films et de séries, définissez simplement `$userCreatePlaylist` sur `true` dans le fichier `config.php`. Sur GitHub, les listes de lecture sont générées automatiquement deux fois par jour grâce au workflow.
- Paramètres d'URL spécifiques :
  - **VOD en Français** : Utilisez le chemin français dédié avec `?lang=fr` ou le nom de compte `UnlimitedFR`.
  - **Codec spécifique** : Utilisez `?codec=x265` pour basculer vers une échelle de qualité privilégiant le HEVC.



### Dashboard Screenshots

![Overview](Overview.png)
![Globe](Globe.png)
![Sessions](Sessions.png)
![Titles](Titles.png)
![Cache Logs](cache%20logs.png)


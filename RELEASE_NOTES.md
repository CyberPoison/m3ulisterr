# 🚀 Release Notes


<details><summary><b>🌐 English Changelog</b></summary>


### 📅 Update 09/16/2026

**AllDebrid fixes, both on the AIOStreams path and the direct-torrent path:**
- **AIOStreams no longer favors Premiumize by default:** when AIOStreams offers multiple equally-good cached streams (same tier, same quality) from different debrid services, candidates are now shuffled before the final sort so the tie is broken at random instead of always landing on whichever service the AIOStreams API happened to list first. Genuine quality/cache-status differences still decide the winner every time — this only spreads selection across debrid services (AllDebrid included) when they're truly tied, giving AllDebrid a fair shot instead of Premiumize winning by array-order accident.
- **Direct-AllDebrid resolving fixed (`torrentSites` path, `debrid.php`):** AllDebrid discontinued their `/v4/magnet/status` endpoint, which silently broke every direct AllDebrid resolve (magnets uploaded fine, but status checks always failed). Migrated to the new `/v4.1/magnet/status` endpoint and its different response shape. Also fixed a related bug this migration exposed: the file list returned by that endpoint includes every file in a torrent (subtitles, posters, samples...), not just the video, so a missing extension filter could pick a non-video file — now only actual video files are considered.
- **Verified against the live AllDebrid API** with a real magnet end-to-end (upload → status → unlock → final streamable link, including HTTP range-request support needed for seeking) and against real AIOStreams data confirming Premiumize/AllDebrid ties genuinely occur on real titles.

### 📅 Update 09/14/2026

**A large reliability, language, subtitle, analytics, and security pass. Highlights:**
- **M3uListerr analytics dashboard (new):** a self-contained `dashboard.php` that logs and visualizes every playback. See [M3uListerr Dashboard](#-m3ulisterr-analytics-dashboard).
- **Correct-language audio:** multi-audio releases no longer play the wrong language. The picked audio track is now chosen from the file's own header (e.g., an "ITA ENG" release plays English, not Italian) and the HLS `LANGUAGE` tag reports what actually plays instead of always claiming English.
- **French account / `?lang=fr`:** a dedicated French path that only accepts releases whose default audio is really French, preferring cached torrents and an x264 1080p→720p→SD quality ladder. French streams are served through a lightweight byte-proxy (no ffmpeg) that resolves the source once and streams ranges, avoiding debrid IP rate-limiting.
- **Subtitles:** both European (pt-PT) and Brazilian (pt-BR) Portuguese subtitle tracks are offered, with a direct OpenSubtitles API fallback for when the bundled provider is down, and full subtitle support for TV series episodes.
- **Playback stability:** fixed duplicated frames / timestamp discontinuities at segment boundaries, and fixed a memory-exhaustion bug that silently produced empty segments (endless buffering) on high-bitrate 4K/remux sources.
- **Security hardening:** blocked public access to sensitive files (`.git`, `cache.json`, logs, `config.php`, the dashboard's private data), added an SSRF guard to the video proxy, and a strict Content-Security-Policy, CSRF protection, session hardening, and login lockout on the dashboard.
- **Multi-service debrid with key fallback (new):** the torrent resolve path now supports **Real-Debrid, Premiumize, AllDebrid and TorBox** (`debrid.php`), tried in order until one returns a working link. Each service can hold **as many API keys as you like** via `$debridApiKeys` in `config.php`; when one key hits its quota / fair-use / hoster limit the resolver automatically rotates to the next key for that service.
- **Faster first play + durable cache (new):** resolved stream URLs are now persisted to a durable SQLite store (source of truth) that survives deploys, in addition to the temporary hot `cache.json`.
- **Config editor upgrades:** the dashboard `config.php` editor can now edit credentials and URLs with show/hide masking.
- **IP blocking & per-IP daily rate limit (new):** block any abusive client IP with one click from the dashboard *Sessions* table. Separately, configurable per-IP daily request limits in `config.php` cap how many titles a single IP may request per day, with separate caps for movies and TV episodes.
- **IP whitelist (new):** whitelist your own office/VPN/dev IP with one click from the dashboard *Sessions* table (blue shield button).
- **Adult content in the dashboard (new):** Sessions and Titles now show the correct poster, name, and id for adult titles too.

### 📅 Update 09/28/2025

- **Live TV:** Fixed the Live TV section and added DrewLive, a massive all in one source of 7,000+ channels.
- **Read Debrid:** Fixed Read Debrid cache checks and added Streamio Sites as a debrid source.
- **Stream sources:** Cleaned up and removed several direct stream sources in both the main script and HeadlessVidX to improve reliability.
- **Adult VOD:** Fixed the Adult VOD source, the 10,000 title adult movie library now refreshes automatically every Sunday.
- **HeadlessVidX:** Major overhaul and bug fixes. The software had numerous issues and I spent several weeks stabilizing it.
- **Overall:** Much of the project had broken after more than a year without updates. Things are working much better now, and I’ve got plans to add more features in upcoming releases.

### 📌 Previous Changes and Additions
- Added the Premiumize service as an alternative to Real-Debrid. (used only with torrent sites)
- Added threads when searching torrent sites for magnet links. (speeds up the time it takes to find a link)
- Added and fixed direct movie and TV show sources as well as more link extractors.
- Added TheTvApp sports section in the Live TV Playlist (set your app to load EPG and playlist every 12 hours or less.)
- Added PlutoTV to the live TV playlist (Multi Languages Here: https://github.com/matthuisman/i.mjh.nz)
- Redesigned the Live TV and DaddyLive functions and playlist. (all of the images in the playlist are working)
- Fixed a lot of bugs in the torrent search and filtering functions. (it finds links much more often now)
- Fixed the sorting by resolution and more likely to get higher quality links (torrent sites)
- Added adult movies to vod (disabled by default)

---


</details>

<details><summary><b>🌐 AR Changelog</b></summary>


### تحديث 14/09/2026

تحديث كبير يركز على الموثوقية، اللغة، الترجمة، التحليلات والأمان. أبرز الميزات:

- <strong>لوحة تحكم تحليلات M3uListerr (جديدة):</strong> ملف `dashboard.php` مستقل يسجل ويصور كل عملية تشغيل — كرة أرضية تفاعلية، رسوم بيانية وجدول جلسات يعرض العنوان/الملصق، الفيلم مقابل المسلسل التلفزيوني، الحساب، اللغة المطلوبة والصوتية، الإصدار الدقيق وخدمة debrid المستخدمة، تقدم التشغيل (النسبة المئوية و h:mm:ss)، الترجمات المعروضة، البلد/المدينة/مزود خدمة الإنترنت، الجهاز ووكيل المستخدم، عنوان IP، ووقت الاستجابة. محمي بتسجيل دخول مع متجر SQLite خاص، بالإضافة إلى محرر `config.php` مدمج وإدارة بيانات الاعتماد.
- <strong>صوت باللغة الصحيحة:</strong> لم تعد الإصدارات متعددة الصوتيات تشغل لغة خاطئة. يتم الآن اختيار المسار الصوتي من رأس الملف نفسه (على سبيل المثال، إصدار "ITA ENG" يعرض الإنجليزية، وليس الإيطالية) وتعرض علامة `LANGUAGE` في HLS ما يتم تشغيله فعليًا بدلاً من الادعاء دائمًا أنها إنجليزية.
- <strong>حساب فرنسي / `?lang=fr`:</strong> مسار فرنسي مخصص يقبل فقط الإصدارات التي يكون الصوت الافتراضي فيها فرنسيًا حقًا (يتم التحقق منه من رأس الحاوية، وليس فقط من العلامة)، مع تفضيل التورنت المخزن مؤقتًا وسلم جودة x264 1080p→720p→SD (يؤدي `?codec=x265` إلى التبديل إلى سلم يفضل HEVC أولاً). يتم تقديم التدفقات الفرنسية من خلال وكيل بايت خفيف الوزن (بدون ffmpeg) يحل المصدر مرة واحدة ويبث النطاقات، متجنبًا تقييد معدل عنوان IP الخاص بـ debrid.
- <strong>الترجمات:</strong> يتم تقديم مسارات الترجمة البرتغالية الأوروبية (pt-PT) والبرازيلية (pt-BR)، مع واجهة برمجة تطبيقات احتياطية مباشرة من OpenSubtitles عندما يكون المزود المدمج معطلاً، ودعم كامل للترجمة لحلقات المسلسلات التلفزيونية.
- <strong>استقرار التشغيل:</strong> تم إصلاح الإطارات المكررة / الانقطاعات الزمنية عند حدود المقاطع، وتم إصلاح خطأ استنفاد الذاكرة الذي أنتج بصمت مقاطع فارغة (تخزين مؤقت لا نهاية له) على مصادر 4K / remux ذات معدل البت العالي.
- <strong>تعزيز الأمان:</strong> منع الوصول العام إلى الملفات الحساسة (`.git`، `cache.json`، السجلات، `config.php`، البيانات الخاصة بلوحة التحكم)، تمت إضافة حارس SSRF إلى وكيل الفيديو، وسياسة Content-Security-Policy صارمة، وحماية من CSRF، وتقوية الجلسة وقفل تسجيل الدخول على لوحة التحكم.

### تحديث 28/09/2025

- <strong>البث التلفزيوني المباشر:</strong> تم إصلاح قسم البث التلفزيوني المباشر وإضافة DrewLive، وهو مصدر شامل ضخم يضم أكثر من 7000 قناة.
- <strong>Read Debrid:</strong> تم إصلاح فحوصات ذاكرة التخزين المؤقت لـ Read Debrid وإضافة Streamio Sites كمصدر debrid (سيتم دعم المزيد من خدمات debrid قريبًا).
- <strong>مصادر البث:</strong> تم تنظيف وإزالة العديد من مصادر البث المباشر في كل من البرنامج النصي الرئيسي و HeadlessVidX لتحسين الموثوقية.
- <strong>VOD للبالغين:</strong> تم إصلاح مصدر VOD للبالغين، حيث يتم الآن تحديث مكتبة أفلام البالغين التي تضم 10000 عنوان تلقائيًا كل يوم أحد.
- <strong>HeadlessVidX:</strong> إصلاح شامل وإصلاحات للأخطاء. كان البرنامج يعاني من العديد من المشكلات وقضيت عدة أسابيع في تثبيته ورفعه إلى المستوى الذي أردته.
- <strong>بشكل عام:</strong> تعطل جزء كبير من المشروع بعد أكثر من عام دون تحديثات. تعمل الأمور بشكل أفضل بكثير الآن، ولدي خطط لإضافة المزيد من الميزات في الإصدارات القادمة.

### التغييرات والإضافات السابقة

- تمت إضافة خدمة Premiumize كبديل لـ Real-Debrid. (تستخدم فقط مع مواقع التورنت)
- تمت إضافة خيوط عند البحث في مواقع التورنت عن روابط المغناطيس. (يسرع الوقت المستغرق للعثور على رابط)
- تمت إضافة وإصلاح مصادر الأفلام والبرامج التلفزيونية المباشرة بالإضافة إلى المزيد من مستخرجات الروابط.
- تمت إضافة قسم الرياضة TheTvApp في قائمة تشغيل البث التلفزيوني المباشر (اضبط تطبيقك لتحميل EPG وقائمة التشغيل كل 12 ساعة أو أقل).
- تمت إضافة PlutoTV إلى قائمة تشغيل البث التلفزيوني المباشر (لغات متعددة هنا: https://github.com/matthuisman/i.mjh.nz)
- إعادة تصميم وظائف البث التلفزيوني المباشر و DaddyLive وقائمة التشغيل. (جميع الصور في قائمة التشغيل تعمل)
- تم إصلاح الكثير من الأخطاء في وظائف البحث عن التورنت وتصفيتها. (يجد الروابط في كثير من الأحيان الآن)
- تم إصلاح الفرز حسب الدقة ومن المرجح أن تحصل على روابط عالية الجودة (مواقع التورنت)
- تمت إضافة أفلام البالغين إلى vod (معطلة افتراضيًا)


</details>

<details><summary><b>🌐 DE Changelog</b></summary>


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


</details>

<details><summary><b>🌐 EL Changelog</b></summary>


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


</details>

<details><summary><b>🌐 FR Changelog</b></summary>


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


</details>

<details><summary><b>🌐 HE Changelog</b></summary>


### 📅 עדכון 14/09/2026
עדכון ענק הכולל שיפורים דרמטיים באמינות, תמיכה בשפות, מערכת כתוביות משופרת, כלי ניתוח נתונים (Analytics) ואבטחה. נקודות עיקריות:
- **לוח בקרה וניתוח נתונים M3uListerr (חדש):** קובץ `dashboard.php` עצמאי שמתעד ומציג חזותית כל ניגון (פירוט נרחב מופיע מעלה תחת סעיף לוח הבקרה).
- **שמע בשפה נכונה:** שחרורים מרובי-שמע (multi-audio) כבר אינם מנגנים שפה שגויה. רצועת השמע מוגדרת כעת מכותרת הקובץ עצמו ותגית ה-HLS מדווחת באמינות מה באמת מתנגן.
- **חשבון צרפתי / `?lang=fr`:** נתיב ייעודי לצרפתית המקבל רק שחרורים ששמע הברירת מחדל שלהם הוא באמת צרפתית, מעדיף טורנטים שמורים במטמון (cached) וסולם איכויות. הזרמות בצרפתית מועברות כעת דרך פרוקסי קל-משקל.
- **כתוביות:** רצועות כתוביות בפורטוגזית אירופאית וברזילאית מוצעות, עם מנגנון גיבוי ישיר של API של OpenSubtitles כאשר הספק המובנה אינו זמין. נוספה תמיכה מלאה בכתוביות לפרקי סדרות.
- **יציבות ניגון:** תוקנו בעיות נפוצות של פריימים כפולים או קטיעות חותם-זמן (Timestamp), ותוקן באג קריטי של מיצוי זיכרון.
- **הקשחת אבטחה:** חסימת גישה ציבורית לקבצים רגישים, הוספת הגנת SSRF לפרוקסי הוידאו ויישום CSP קפדני ללוח הבקרה.

### 📅 עדכון 28/09/2025
- **טלוויזיה חיה:** תוקן מדור הטלוויזיה החיה והתווסף *DrewLive* כמקור עצום של מעל ל-7,000 ערוצים.
- **Read Debrid:** תוקנו בדיקות המטמון של Read Debrid והתווספו אתרי Streamio כמקור debrid.
- **מקורות הזרמה (Stream):** המערכת נוקתה והוסרו ממנה מספר מקורות הזרמה ישירים ובעייתיים.
- **VOD למבוגרים:** תוקן מקור ה-VOD למבוגרים, הספריה הכוללת מעל 10,000 סרטים למבוגרים מתרעננת כעת אוטומטית בכל יום ראשון.
- **HeadlessVidX:** שיפוץ מקיף ותיקוני באגים נרחבים.
- **באופן כללי:** רוב הפרויקט שוקם לאחר יותר משנה ללא עדכונים. הדברים עובדים בצורה חלקה וטובה הרבה יותר.

### 🛠️ שינויים ותוספות ישנים יותר
- התווסף שירות Premiumize כחלופה ל-Real-Debrid.
- נוספו תהליכונים (Threads) מהירים בעת חיפוש באתרי טורנט אחר קישורי מגנט.
- התווספו ותוקנו מקורות סרטים וסדרות טלוויזיה ישירים, וכן מחלצי קישורים.
- הוסף מדור ספורט של *TheTvApp*.
- התווסף ערוץ *PlutoTV* לרשימת ההשמעה של הטלוויזיה החיה.
- עוצבו מחדש פונקציות הטלוויזיה החיה ו-*DaddyLive*.
- תוקנו הרבה באגים קטנים בפונקציות החיפוש וסינון הטורנטים.
- הוספו סרטים למבוגרים (מושבת כברירת מחדל).

---


</details>

<details><summary><b>🌐 IT Changelog</b></summary>


### Aggiornamento 14/09/2026
Un grande aggiornamento riguardante affidabilità, lingua, sottotitoli, analisi e sicurezza. Punti salienti:
- **Dashboard analitica M3uListerr (nuovo):** un `dashboard.php` indipendente che registra e visualizza ogni riproduzione — un globo interattivo, grafici e una tabella delle sessioni che mostra titolo/locandina, film vs serie TV, account, lingua richiesta e audio, l'esatta release e il servizio debrid utilizzato, il progresso della riproduzione (% e h:mm:ss), i sottotitoli offerti, paese/città/ISP, dispositivo e user-agent, IP e tempo di risoluzione. Protetto da login con un archivio privato SQLite, oltre a un editor integrato per `config.php` e gestione delle credenziali. Vedi [Dashboard analitica M3uListerr](#dashboard-analitica-m3ulisterr).
- **Audio nella lingua corretta:** le release multi-audio non riproducono più la lingua sbagliata. La traccia audio selezionata viene ora scelta dall'intestazione del file stesso (es. una release "ITA ENG" riproduce l'inglese, non l'italiano) e il tag `LANGUAGE` dell'HLS riporta ciò che viene effettivamente riprodotto invece di dichiarare sempre l'inglese.
- **Account francese / `?lang=fr`:** un percorso francese dedicato che accetta solo release il cui audio predefinito è realmente il francese (verificato dall'intestazione del contenitore, non solo dal tag), preferendo i torrent in cache e una scala di qualità x264 1080p→720p→SD (`?codec=x265` passa a una scala che privilegia l'HEVC). Gli stream francesi vengono serviti tramite un proxy di byte leggero (senza ffmpeg) che risolve la fonte una volta e trasmette a blocchi, evitando le limitazioni di frequenza IP del debrid.
- **Sottotitoli:** sono offerti sia i sottotitoli in portoghese europeo (pt-PT) che brasiliano (pt-BR), con un fallback diretto alle API di OpenSubtitles per quando il provider integrato è offline, e pieno supporto ai sottotitoli per gli episodi delle serie TV.
- **Stabilità di riproduzione:** risolti i problemi di frame duplicati/discontinuità dei timestamp ai confini dei segmenti, e risolto un bug di esaurimento della memoria che produceva silenziosamente segmenti vuoti (buffering infinito) su sorgenti 4K/remux ad alto bitrate.
- **Rafforzamento della sicurezza:** bloccato l'accesso pubblico ai file sensibili (`.git`, `cache.json`, log, `config.php`, dati privati della dashboard), aggiunta una protezione SSRF al proxy video, e una rigorosa Content-Security-Policy, protezione CSRF, rafforzamento delle sessioni e blocco dei login sulla dashboard.

### Aggiornamento 28/09/2025
- **TV in Diretta:** Risolta la sezione della TV in Diretta e aggiunto DrewLive, un'enorme fonte all-in-one di oltre 7.000 canali.
- **Read Debrid:** Risolti i controlli della cache di Read Debrid e aggiunto Streamio Sites come fonte debrid (il supporto per altri servizi debrid è in arrivo).
- **Fonti di streaming:** Pulite e rimosse diverse fonti di streaming diretto sia nello script principale che in HeadlessVidX per migliorare l'affidabilità.
- **VOD per Adulti:** Risolta la fonte del VOD per adulti, la libreria di 10.000 film per adulti si aggiorna ora automaticamente ogni domenica.
- **HeadlessVidX:** Importante revisione e correzione di bug. Il software aveva numerosi problemi e ho trascorso diverse settimane per stabilizzarlo e portarlo allo standard desiderato.
- **In generale:** Gran parte del progetto si era interrotta dopo più di un anno senza aggiornamenti. Le cose funzionano molto meglio ora e ho intenzione di aggiungere ulteriori funzionalità nelle prossime versioni.

### Modifiche e Aggiunte Precedenti
- Aggiunto il servizio Premiumize come alternativa a Real-Debrid. (utilizzato solo con siti torrent)
- Aggiunti i thread durante la ricerca di link magnetici sui siti torrent. (velocizza il tempo impiegato per trovare un link)
- Aggiunte e corrette le fonti dirette per film e programmi TV, nonché più estrattori di link.
- Aggiunta la sezione sportiva di TheTvApp nella Playlist TV in Diretta (imposta la tua app per ricaricare EPG e playlist ogni 12 ore o meno).
- Aggiunto PlutoTV alla playlist TV in diretta (Multi-Lingue Qui: https://github.com/matthuisman/i.mjh.nz)
- Riprogettate le funzioni e la playlist per TV in Diretta e DaddyLive. (tutte le immagini nella playlist sono funzionanti)
- Risolti molti bug nelle funzioni di ricerca e filtraggio dei torrent. (ora trova i link molto più spesso)
- Risolto l'ordinamento in base alla risoluzione e aumentata la probabilità di ottenere link di qualità superiore (siti torrent)
- Aggiunti film per adulti al vod (disabilitati di default)

---


</details>

<details><summary><b>🌐 PT Changelog</b></summary>


### Atualização 14/09/2026
Uma grande revisão de estabilidade, idioma, legendas, análise de dados e segurança. Destaques:
- **Dashboard analítico M3uListerr (novo):** Integração total do `dashboard.php` independente. Protegido por login privado, estatísticas completas, monitoramento em tempo real, visualização de cache e editor do arquivo `config.php` diretamente no navegador.
- **Áudio no Idioma Correto:** Faixas de áudio escolhidas a dedo utilizando a leitura de cabeçalhos binários para reproduzir fielmente o que o nome do arquivo sugere (ex: evita erro de tocar Inglês em releases que são Dublados e Legendados).
- **Conta Francesa / `?lang=fr`:** Um caminho de rede projetado especificamente para priorizar torrents em Francês real com um proxy ultraleve próprio que não precisa do ffmpeg, otimizando o uso do debrid e qualidade do stream.
- **Legendas PT-BR e PT-PT:** Integração reforçada para usuários falantes da língua portuguesa (com fallback ao OpenSubtitles caso a fonte principal caia).
- **Estabilidade Aprimorada:** Resoluções para saltos, quadros duplicados no buffer e correções de estouro de memória durante a reprodução de mídias de altíssima taxa de bits (ex: 4K remuxes).
- **Segurança Máxima:** Controle severo de acesso público em diretórios como `.git`, arquivos `.json`, `config.php` e implementações SSRF robustas no proxy.

### Atualização 28/09/2025
- **TV ao Vivo:** Adição do DrewLive, aumentando a lista para mais de 7.000 canais ao vivo.
- **Streamio Sites & Debrid:** Adicionada nova integração de Debrid para aumentar as taxas de sucesso e caches corrigidos.
- **HeadlessVidX Aprimorado:** Grande reformulação do componente após meses inativo. Estabilização massiva do código.
- **Limpeza e Organização:** Remoção de fontes mortas para manter o software enxuto e produtivo.

---


</details>
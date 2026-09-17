# 🎥 TMDB ל-VOD: רשימות השמעה בחינם לערוצי טלוויזיה, סרטים וסדרות [Xtream Codes ו-M3U8]

🌐 [English](README.md) | [עברית](README_HE.md)

[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="הורדת ZIP">
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

## 📝 סיכום

יצירת רשימות השמעה של טלוויזיה חיה, סרטים וסדרות (VOD) תוך שימוש ב-Xtream Codes או בפורמט M3U8.

המערכת מאפשרת יצירת רשימות השמעה דינמיות באמצעות גרסת דמה (אמולציה) של Xtream Codes. היא מייצרת רשימות IPTV, סרטים וסדרות עם מטא-דאטה מקיף. קישורי הזרמה (סטרימינג) מאותרים בזמן אמת באמצעות TMDB, Real-Debrid, Premiumize ומקורות ישירים. הפתרון אידיאלי לשימוש עם אפליקציות כגון iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player ועוד.

---

## 🎬 סרטון הדגמה

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="GIF הדגמה" width="70%">

<br><br>

## 📸 צילומי מסך

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

---

## ✨ מאפיינים עיקריים

- יצירת רשימות השמעה דינמיות לטלוויזיה חיה, סרטים וסדרות טלוויזיה.
- אינטגרציה עם TMDB, Real Debrid, Premiumize ומקורות ישירים לשליפת תוכן משופרת.
- אמולציה מלאה של תוכנת Xtream Codes לקבלת פרטי מטא-דאטה עשירים ומלאים.
- כולל מקורות טלוויזיה חיה כגון Daddylive, TheTVApp, MoveOnJoy, Streamed Su Sports, Pluto TV ועוד.
- רוב ערוצי הטלוויזיה החיה כוללים מידע מפורט של מדריך שידורים (EPG).
- אחסון אוטומטי במטמון (Caching) של קישורי הזרמה שנמצאו להפעלה מהירה ויעילה.
- 10,000 סרטים למבוגרים באורך מלא נוספו ל-VOD (אפשרות זו מושבתת כברירת מחדל).
- בחירת שמע חכמה בשפה נכונה לשחרורים הכוללים מספר ערוצי שמע (multi-audio).
- נתיב ייעודי לתוכן בשפה הצרפתית.
- תמיכה מלאה בכתוביות, כולל פורטוגזית.
- **לוח בקרה M3uListerr** לניתוח נתונים וצפייה בסטטיסטיקות מתקדמות.

---

## 🚀 תחילת העבודה

[![תמונת סרטון](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **תצורה (Configuration)**: התחילו בהגדרת הסקריפט עם מפתח חינמי של [TMDB API](https://developer.themoviedb.org/docs/getting-started). בנוסף, מומלץ (אך לא חובה) לספק מפתח פרטי עבור [Real Debrid](https://real-debrid.com/apitoken) או [Premiumize](https://www.premiumize.me/account).
2. **אינטגרציה של Xtream Codes**: הזינו באפליקציה שלכם את כתובת ה-IP או הדומיין של השרת כשרת Xtream Codes. כל שם משתמש וסיסמה יעבדו מכיוון שהסקריפט אינו דורש אימות. פעולה זו תטען אוטומטית את רשימות ההשמעה של הטלוויזיה החיה, הסרטים וסדרות הטלוויזיה לתוך האפליקציה.
3. **אפליקציות שאינן Xtream Codes**: אם האפליקציה שלכם אינה תומכת ב-Xtream Codes, פתחו בדפדפן את הכתובת `http://IP_ADDRESS/player_api.php?action=get_vod_streams` (החליפו את `IP_ADDRESS` בכתובת ה-IP של השרת שלכם), לאחר מכן אתרו את קובץ ה-`playlist.m3u8` באותה תיקייה של הסקריפט וטענו אותו כרשימת השמעה M3U. שימו לב: רשימות M3U8 זמינות רק עבור סרטים וטלוויזיה חיה.
4. **ניגון**: ברגע שהכל מוגדר והרשימות טעונות, הוידאו מוכן לניגון. לחיצה על כפתור ההפעלה תפעיל את הסקריפט שיחפש ברקע, במספר רב של אתרים, עבור קישור שניתן לנגן. אנא היו סבלניים ואפשרו למערכת זמן למצוא קישור ולהתחיל את ההזרמה.
5. **אחסון מקומי**: אם אין לכם שרת או חברת אחסון, תוכלו להתקין ולהפעיל את הסקריפט קל-המשקל הזה ישירות על המחשב השולחני שלכם באמצעות תוכנות כגון XAMPP.

---

## 🤖 מה זה HeadlessVidX?

**HeadlessVidX** הוא כלי שנועד לפשט את הפיתוח של חלצי (Extractors) וידאו עבור אתרי סטרימינג. הוא מספק פתרון נגיש וקל לשימוש המאפשר למשתמשים להוסיף במהירות אתרי הזרמת וידאו חדשים לכלים כמו המערכת שלנו.

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

---

## 📋 יצירת רשימת השמעה

כבר אין צורך להריץ ידנית את הקבצים `create_playlist.php` ו-`create_tv_playlist.php`. הודות לתזרים העבודה המוגדר ב-GitHub (GitHub Actions), רשימות אלו נוצרות אוטומטית פעמיים ביום. אם תרצו ליצור רשימות משלכם, פשוט שנו את הערך של `$userCreatePlaylist` ל-`true` בקובץ `config.php`.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

---

## 🐳 פריסה ב-Docker

כעת תוכלו להריץ את כל הסטאק באמצעות Docker ו-Docker Compose בקלות ובמהירות.

### ⚡ התחלה מהירה (ללא צורך ב-git clone)
תוכלו להריץ את התמונה הפומבית (Public Image) ישירות משורת הפקודה:

```bash
mkdir -p m3ulisterr_data
touch config.php
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

### 🛠️ דרישות קדם מקומיות
- תוכנות Docker ו-Docker Compose מותקנות על השרת/המחשב.

### 📦 פריסה מקומית (עם Docker Compose)
1. שכפלו את המאגר באמצעות `git clone`.
2. הגדירו את קובץ ה-`config.php` שלכם (או השתמשו במשתני סביבה).
3. הריצו את הפקודה הבאה בתיקיית השורש של הפרויקט:
   ```bash
   docker-compose up -d
   ```
4. גשו לאתר בכתובת `http://localhost:8080`.

### ⚙️ GitHub Actions
הפרויקט כולל תזרים עבודה של GitHub Actions (בקובץ `.github/workflows/deploy.yml`) שבונה ודוחף אוטומטית את תמונת ה-Docker אל ה-GitHub Container Registry (GHCR) בכל דחיפה (Push) לבראנץ' `main`.

### 🌐 משתני סביבה (Environment Variables)
- `HEADLESSVIDX_ADDRESS`: כתובת שירות ה-HeadlessVidX (ברירת המחדל היא `localhost:3202`).

---

## 📊 לוח בקרה M3uListerr

קובץ ה-`dashboard.php` משמש כלוח בקרה (דשבורד) פנימי מתקדם עם מערכת התחברות לניתוח נתונים ושליטה במערכת. הלוח רושם כל ניגון ללוג פרטי, המיובא אוטומטית לבסיס נתונים (SQLite), ומציג את הנתונים בממשק מודרני ונגיש.

### 🛡️ אבטחה בלוח הבקרה
- **הצפנה**: סיסמאות מנהל נשמרות באופן מאובטח באמצעות אלגוריתם Argon2id.
- **פרטיות**: כל המידע והלוגים נשמרים בתיקייה המוגנת `m3ulisterr_data/`.
- **הגנות**: המערכת כוללת Content Security Policy (CSP) קפדני, הגנה מפני התקפות CSRF, ומנגנון למניעת ניסיונות התחברות מרובים (Rate Limiting).

### 🚀 איך מתחילים להשתמש בלוח הבקרה
1. פרסו את הקבצים כרגיל (באמצעות Docker או אחסון רגיל).
2. ודאו שהאפליקציה או השרת יכולים ליצור תיקייה ניתנת לכתיבה בשם `m3ulisterr_data/`.
3. פתחו את הכתובת `http://YOUR_SERVER/dashboard.php` בדפדפן שלכם, צרו חשבון מנהל בהפעלה הראשונה, והתחברו.

### 🖼️ צילומי מסך והסברים על הלוח

![Overview](wiki/Overview.png)
- **סקירה כללית (Overview):** מסך הבית של לוח הבקרה מציג נתונים סטטיסטיים מרכזיים ומגמות צפייה. דרך מסך זה ניתן לקבל תמונת מצב מהירה על כמות ההפעלות, צופים ייחודיים ומדדי שימוש כלליים, המאפשרים למנהלים להבין את היקף הפעילות במערכת בזמן אמת.

![Globe](wiki/Globe.png)
- **גלובוס (Globe):** מפה גלובלית אינטראקטיבית בתלת-ממד הממחישה את פריסת הצופים שלכם ברחבי העולם. כלי ויזואלי מרהיב המאפשר מעקב אחר מיקומי התחברות ומדינות מובילות מהן מגיעות הבקשות לניגון.

![Sessions](wiki/Sessions.png)
- **הפעלות (Sessions):** טבלה מתקדמת ומפורטת המספקת צלילה עמוקה לתוך כל פעולת ניגון במערכת. מאפשרת לנטר מידע קריטי כגון סוג התוכן (סרט/סדרה), התקדמות הניגון (אחוזים וזמן מדויק), שפת השמע המבוקשת והבפועל, ספק שירותי ה-Debrid, המכשיר שבו נעשה שימוש, כתובת ה-IP, וספק האינטרנט.

![Titles](wiki/Titles.png)
- **כותרים (Titles):** אזור ייעודי למעקב אחר התוכן הפופולרי ביותר. מציג את הסרטים והסדרות המבוקשים ביותר במערכת, ועוזר לזהות מגמות צפייה ומהם התכנים שמושכים הכי הרבה קהל בכל רגע נתון.

![Cache Logs](wiki/cache%20logs.png)
- **יומני מטמון (Cache Logs):** כלי טכני מתקדם לצפייה וניתוח של נתוני המטמון (Cache). מאפשר לזהות אילו תכנים נטענים מהר יותר בזכות שמירתם מראש, לסייע בפתרון תקלות, ולשפר משמעותית את ביצועי המערכת וזמני הטעינה עבור המשתמשים.

---

## 🔄 עדכונים ושינויים

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

## 🙏 Special Thanks

This project's source code was originally created by **Michell Smith a.k.a [gogetta69](https://github.com/gogetta69)**. 
If you appreciate the original foundation of this project, please consider supporting them:

[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Michell-F16061?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gogetta69)

The project has since been significantly refactored, modernized, and maintained by **[CyberPoison](https://github.com/CyberPoison)**.

---

## ⚖️ הצהרה משפטית

סקריפט זה שולף מידע על סרטים מ-TMDB ומחפש תוכן קשור באתרי צד-שלישי. חוקיות ההזרמה או ההורדה דרך אתרים אלו אינה ודאית. אנא הפעילו שיקול דעת בנוגע להשלכות החוקיות והאתיות של שימוש בסקריפט זה לצריכת תוכן המוגן בזכויות יוצרים. יש לכבד תמיד חוקי זכויות יוצרים ותנאי שירות של האתרים בהם אתם מבקרים. המפתחים לא יישאו בשום אחריות לשימוש בלתי חוקי במערכת.

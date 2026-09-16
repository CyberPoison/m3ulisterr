# TMDB ל-VOD: רשימות השמעה בחינם לערוצי טלוויזיה, סרטים וסדרות \[Xtream Codes ו-M3U8\]


[![Build and Deploy](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml/badge.svg)](https://github.com/CyberPoison/m3ulisterr/actions/workflows/ci.yml)
[![Docker Image Version (latest)](https://img.shields.io/badge/docker-latest-blue.svg?logo=docker)](https://ghcr.io/cyberpoison/m3ulisterr:latest)


## עדכון 14/09/2026

עדכון גדול של אמינות, שפות, כתוביות, ניתוח נתונים (analytics) ואבטחה. נקודות עיקריות:

- <strong>לוח בקרה וניתוח נתונים M3uListerr (חדש):</strong> קובץ `dashboard.php` עצמאי שמתעד ומציג חזותית כל ניגון - גלובוס אינטראקטיבי, תרשימים וטבלת הפעלות המציגה כותרת/פוסטר, סרט מול סדרת טלוויזיה, חשבון, שפת השמע המבוקשת והבפועל, השחרור (release) המדויק ושירות ה-Debrid בו נעשה שימוש, התקדמות הניגון (% ו-h:mm:ss), כתוביות מוצעות, מדינה/עיר/ספק אינטרנט, מכשיר ו-user-agent, כתובת IP וזמן פענוח (resolve time). מוגן בהתחברות עם מאגר SQLite פרטי, בתוספת עורך `config.php` מובנה וניהול הרשאות. ראו [לוח בקרה M3uListerr](#לוח-בקרה-m3ulisterr).
- <strong>שמע בשפה נכונה:</strong> שחרורים מרובי-שמע (multi-audio) כבר אינם מנגנים שפה שגויה. רצועת השמע הנבחרת נבחרת כעת מכותרת הקובץ עצמו ותגית ה-HLS `LANGUAGE` מדווחת מה באמת מתנגן.
- <strong>חשבון צרפתי / `?lang=fr`:</strong> נתיב ייעודי לצרפתית המקבל רק שחרורים ששמע הברירת מחדל שלהם הוא באמת צרפתית, מעדיף טורנטים שמורים במטמון (cached) וסולם איכויות. הזרמות בצרפתית מועברות דרך פרוקסי קל-משקל.
- <strong>כתוביות:</strong> רצועות כתוביות בפורטוגזית אירופאית וברזילאית מוצעות, עם גיבוי ישיר של API של OpenSubtitles כאשר הספק המובנה אינו זמין, ותמיכה מלאה בכתוביות לפרקי סדרות טלוויזיה.
- <strong>יציבות ניגון:</strong> תוקנו בעיות של פריימים כפולים / קטיעות חותם-זמן, ותוקן באג של מיצוי זיכרון.
- <strong>הקשחת אבטחה:</strong> חסימת גישה ציבורית לקבצים רגישים, נוספה הגנת SSRF לפרוקסי הוידאו ו-CSP קפדני ללוח הבקרה.

---

## עדכון 28/09/2025

- <strong>טלוויזיה חיה:</strong> תוקן מדור הטלוויזיה החיה והתווסף DrewLive, מקור של מעל 7,000 ערוצים.
- <strong>Read Debrid:</strong> תוקנו בדיקות מטמון של Read Debrid והתווספו אתרי Streamio כמקור debrid.
- <strong>מקורות הזרמה (Stream):</strong> נוקו והוסרו מספר מקורות הזרמה ישירים.
- <strong>VOD למבוגרים:</strong> תוקן מקור ה-VOD למבוגרים, הספריה של 10,000 סרטים למבוגרים מתרעננת כעת אוטומטית בכל יום ראשון.
- <strong>HeadlessVidX:</strong> שיפוץ מקיף ותיקוני באגים.
- <strong>באופן כללי:</strong> רוב הפרויקט נשבר לאחר יותר משנה ללא עדכונים. הדברים עובדים הרבה יותר טוב עכשיו.

---

# סיכום

<p>יצירת רשימות השמעה של טלוויזיה חיה, סרטים וסדרות (VOD) תוך שימוש ב-Xtream Codes או בפורמט M3U8.

יצירת רשימות השמעה דינמיות באמצעות גרסת דמה של Xtream Codes. יצירת רשימות IPTV, סרטים וסדרות עם מטא-דאטה מקיף. קישורי הזרמה מאותרים באמצעות TMDB, Real-Debrid, Premiumize ומקורות ישירים. אידיאלי לשימוש עם אפליקציות כמו iMplayer, Tivimate, IPTV Streamers Pro, XCIPTV Player ועוד.</p>

<table style="border-collapse: collapse; border: none;">
  <tr>
    <td style="border: none;">
      <a href="https://github.com/CyberPoison/m3ulisterr/archive/refs/tags/latest.zip">
        <img src="https://img.shields.io/badge/Download%20ZIP-latest-blue?style=for-the-badge&logo=github" alt="הורדת ZIP">
      </a>
    </td>
    <td style="border: none; padding-left: 10px;">
      <a href="https://ko-fi.com/gogetta69">
        <img src="https://www.ko-fi.com/img/githubbutton_sm.svg" alt="Ko-fi">
      </a>
    </td>
  </tr>
</table>

# סרטון הדגמה

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="GIF הדגמה" width="70%">
<br><br>

# צילומי מסך

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

# מאפיינים

- יצירת רשימות השמעה דינמיות לטלוויזיה חיה, סרטים וסדרות טלוויזיה
- אינטגרציה עם TMDB, Real Debrid, Premiumize ומקורות ישירים לשליפת תוכן משופרת
- אמולציה של תוכנת Xtream Codes לקבלת פרטי מטא-דאטה מלאים
- כולל מקורות טלוויזיה חיה כגון Daddylive, TheTVApp, MoveOnJoy, Streamed Su Sports, Pluto TV ועוד.
- רוב ערוצי הטלוויזיה החיה כוללים מידע מפורט של מדריך טלוויזיה (EPG).
- אחסון אוטומטי במטמון (Caching) של קישורי הזרמה שנמצאו להפעלה יעילה
- 10K סרטים למבוגרים באורך מלא התווספו ל-VOD (מושבת כברירת מחדל)
- בחירת שמע בשפה נכונה לשחרורים מרובי שמע
- נתיב ייעודי לצרפתית
- כתוביות בפורטוגזית
- **M3uListerr** לוח בקרה לניתוח נתונים

# תחילת העבודה

[![תמונת סרטון](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)

1. **תצורה**: התחילו בהגדרת הסקריפט עם מפתח חופשי של [TMDB API](https://developer.themoviedb.org/docs/getting-started) ומפתח פרטי אופציונלי עבור [Real Debrid](https://real-debrid.com/apitoken) או [Premiumize](https://www.premiumize.me/account), שאינם חובה.
2. **אינטגרציה של Xtream Codes**: הזינו את כתובת ה-IP או הדומיין כשרת Xtream Codes. כל שם משתמש וסיסמה יעבדו מכיוון שהסקריפט אינו דורש אימות. פעולה זו תטען אוטומטית את רשימות ההשמעה של טלוויזיה חיה, סרטים וסדרות טלוויזיה לתוך האפליקציה.
3. **אפליקציות שאינן Xtream Codes**: אם האפליקציה שלכם אינה תומכת ב-Xtream Codes, טענו http://IP_ADDRESS/player_api.php?action=get_vod_streams (החליפו את IP_ADDRESS בכתובת ה-IP של המחשב שלכם) בדפדפן שלכם, לאחר מכן אתרו את `playlist.m3u8` באותה תיקייה של הסקריפט וטענו אותו כרשימת השמעה M3U. שימו לב שרשימות M3U8 זמינות רק לסרטים וטלוויזיה חיה.
4. **ניגון**: ברגע שהכל מוגדר והרשימות טעונות, אמורה להיות לכם אפשרות לנגן וידאו. לחיצה על כפתור ההפעלה תפעיל את הסקריפט לחפש באתרים מרובים ברקע עבור קישור ניתן לניגון. אנא היו סבלניים ואפשרו זמן מסוים עד שימצא קישור ותתחיל ההזרמה.
5. **אחסון מקומי**: אם אין לכם חברת אחסון להפעלת סקריפט קל משקל זה, אתם יכולים להתקין ולהפעיל תוכנה על המחשב השולחני שלכם כמו Xampp.

# שינויים ותוספות
- התווסף שירות Premiumize כחלופה ל-Real-Debrid.
- נוספו תהליכונים (threads) בעת חיפוש באתרי טורנט אחרי קישורי מגנט.
- התווספו ותוקנו מקורות סרטים וסדרות טלוויזיה ישירים וכן מחלצי קישורים.
- הוסף מדור ספורט של TheTvApp.
- התווסף PlutoTV לרשימת ההשמעה של טלוויזיה חיה.
- עוצבו מחדש פונקציות הטלוויזיה החיה ו-DaddyLive.
- תוקנו הרבה באגים בפונקציות החיפוש וסינון הטורנטים.
- הוספו סרטים למבוגרים (מושבת כברירת מחדל).

# מה זה HeadlessVidX?

HeadlessVidX הוא כלי שנועד לפשט את הפיתוח של מחלצי וידאו עבור אתרי סטרימינג. הוא מספק פתרון קל לשימוש עבור משתמשים להוספה מהירה של אתרי הזרמת וידאו לכלים כמו 'TMDB TO VOD'.

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

# יצירת רשימת השמעה

אינכם צריכים יותר להריץ ידנית את create_playlist.php ו-create_tv_playlist.php. עם תזרים העבודה המוגדר ב-GitHub, רשימות אלו נוצרות אוטומטית פעמיים ביום. ליצירת רשימות משלכם, פשוט הגדירו את `$userCreatePlaylist` ל-`true` בקובץ config.php.

https://github.com/user-attachments/assets/c6af6149-c170-45fc-a6ac-32edd1b3405b

## פריסה ב-Docker

כעת תוכלו להריץ את כל הסטאק באמצעות Docker ו-Docker Compose.

### דרישות קדם
- Docker ו-Docker Compose מותקנים.

### התחלה מהירה
1. שכפלו את המאגר (clone).
2. הגדירו את ה-`config.php` שלכם (או השתמשו במשתני סביבה).
3. הריצו את הפקודה הבאה בתיקיית השורש:
   ```bash
   docker-compose up -d
   ```
4. גשו לאתר בכתובת `http://localhost:8080`.

### GitHub Actions
הפרויקט כולל תזרים עבודה של GitHub Actions בקובץ `.github/workflows/deploy.yml` שבונה ודוחף אוטומטית את ה-Docker Image ל-GitHub Container Registry (GHCR) בכל דחיפה ל-`main`.

### משתני סביבה
- `HEADLESSVIDX_ADDRESS`: כתובת שירות ה-HeadlessVidX (ברירת מחדל: `localhost:3202`).

# לוח בקרה M3uListerr

### Dashboard Screenshots

![Overview](wiki/Overview.png)
![Globe](wiki/Globe.png)
![Sessions](wiki/Sessions.png)
![Titles](wiki/Titles.png)
![Cache Logs](wiki/cache%20logs.png)

`dashboard.php` הוא לוח בקרה פנימי עם התחברות לניתוח נתונים ושליטה. הוא רושם כל ניגון ללוג פרטי המיובא ל-SQLite ומציג דשבורד מודרני.

### מה זה מציג
- **סקירה כללית** - סך ההפעלות, צופים ייחודיים, מדינות ותרשימים שונים.
- **גלובוס** - גלובוס תלת-ממדי אינטראקטיבי.
- **הפעלות** - טבלה המציגה פירוט של כל סרט, איכות, מכשיר ומיקום.
- **כותרים** - הסרטים והסדרות המבוקשים ביותר.
- **מטמון** - צופה לערכי המטמון.
- **הגדרות** - עריכת הגדרות ה-`config.php`.
- **חשבון** - שינוי סיסמה למנהל.

### אבטחה
- סיסמאות נשמרות באופן מאובטח (Argon2id).
- כל המידע נשמר בתיקייה מוגנת `m3ulisterr_data/`.
- CSP קפדני, הגנת CSRF ומגבלות התחברות.

### איך מתחילים
1. פרסו את הקבצים כרגיל.
2. ודאו שהאפליקציה יכולה ליצור תיקייה ניתנת לכתיבה `m3ulisterr_data/`.
3. פתחו את `http://YOUR_SERVER/dashboard.php`, צרו חשבון מנהל והתחברו.

# הצהרה משפטית

סקריפט זה שולף מידע על סרטים מ-TMDB ומחפש תוכן קשור באתרי צד-שלישי. חוקיות ההזרמה או ההורדה דרך אתרים אלו אינה ודאית. אנא הפעילו שיקול דעת בנוגע להשלכות החוקיות והאתיות של שימוש בסקריפט זה לצריכת תוכן המוגן בזכויות יוצרים. יש לכבד תמיד חוקי זכויות יוצרים ותנאי שירות של האתרים בהם אתם מבקרים.

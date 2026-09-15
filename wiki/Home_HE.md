# TMDB to VOD - מדריך ומסמכי עזר (Wiki)

ברוכים הבאים לוויקי הרשמי של TMDB to VOD! כאן תמצאו מדריך מקיף בעברית שיעזור לכם להתקין, להגדיר ולהבין איך המערכת עובדת.

## תוכן עניינים
1. [איך זה עובד](#איך-זה-עובד)
2. [התקנה (Installation)](#התקנה-installation)
3. [דוקר (Docker)](#דוקר-docker)
4. [פקודות נפוצות (Commands)](#פקודות-נפוצות-commands)

---

## איך זה עובד

המערכת **TMDB to VOD** מאפשרת לכם ליצור רשימות השמעה (Playlists) דינמיות לערוצי טלוויזיה חיים, סרטים וסדרות על ידי שימוש בגרסת דמה של תוכנת **Xtream Codes** או באמצעות קובצי **M3U8**.

<img src="https://github.com/user-attachments/assets/7925cf0a-63b7-43ab-8a1e-d099306985fe" alt="GIF הדגמה" width="70%">

המערכת מייצרת רשימות מלאות הכוללות מטא-דאטה מקיף מ-TMDB (תקצירים, תמונות, שחקנים ועוד). כאשר אתם בוחרים לנגן וידאו (סרט או פרק בסדרה), הסקריפט עובד מאחורי הקלעים ומאתר קישורי הזרמה (Streams) באמצעות אתרים שונים, ובנוסף תומך באינטגרציה עם שירותי דה-בריד כמו **Real-Debrid** או **Premiumize**. לאחר מציאת הקישור (תהליך שיכול לקחת מספר שניות), הסקריפט שומר את הקישור בזיכרון המטמון (Cache) כדי לאפשר צפייה חלקה בעתיד (בדרך כלל למשך כ-3-4 שעות).

המערכת מתאימה במיוחד לאפליקציות IPTV מובילות כמו:
- iMplayer
- Tivimate
- IPTV Streamers Pro
- XCIPTV Player

### HeadlessVidX
הפרויקט משלב כלי בשם **HeadlessVidX** – כלי שנועד לפשט את הפיתוח של מחלצי וידאו (Video Extractors) לאתרי סטרימינג שונים, גם ללא ידע רב בתכנות.
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

## התקנה (Installation)

כדי להתחיל להשתמש בסקריפט, בצעו את השלבים הבאים:

1. **מפתח API**: השיגו מפתח API חינמי מ-[TMDB](https://developer.themoviedb.org/docs/getting-started).
2. **חשבונות Premium (אופציונלי)**: אם יש לכם חשבון ב-[Real Debrid](https://real-debrid.com/apitoken) או [Premiumize](https://www.premiumize.me/account), מומלץ להכניס את מפתחות ה-API שלהם לקובץ ההגדרות (config.php) לקבלת מקורות איכותיים ומהירים יותר.
3. **אחסון (Hosting) או שרת מקומי**: 
   - תוכלו להעלות את הקבצים לכל שרת אינטרנט התומך ב-PHP (מומלץ).
   - אם אין לכם שרת, תוכלו להתקין תוכנה כמו **XAMPP** על המחשב האישי שלכם ולהריץ את המערכת מקומית.
4. **לוח בקרה (M3uListerr Dashboard)**:
   - ודאו שלמערכת יש הרשאות כתיבה כדי ליצור את התיקייה `m3ulisterr_data/`.
   - כנסו לכתובת `http://YOUR_SERVER/dashboard.php` בדפדפן שלכם, צרו חשבון מנהל מערכת וצפו בסטטיסטיקות הצפייה והניהול.
5. **הגדרת Xtream Codes נגן ה-IPTV**:
   - באפליקציית ה-IPTV שלכם, הוסיפו חיבור חדש מסוג Xtream Codes.
   - בכתובת השרת הזינו את הכתובת שבה התקנתם את המערכת (למשל, ה-IP של המחשב שלכם).
   - שם משתמש וסיסמה: אפשר להזין כל דבר שתרצו, המערכת אינה דורשת אימות.
6. **שימוש בקובץ M3U8 (לנגנים שאינם תומכים ב-Xtream Codes)**:
   - גלשו לכתובת: `http://IP_ADDRESS/player_api.php?action=get_vod_streams`
   - המערכת תיצור קובץ `playlist.m3u8` באותה תיקייה. תוכלו לטעון אותו לנגן שלכם כרשימת השמעה רגילה (עובד לסרטים וערוצים חיים בלבד).

---

## דוקר (Docker)

הדרך הקלה והמהירה ביותר להריץ את המערכת כולה היא באמצעות Docker ו-Docker Compose.

### דרישות מוקדמות
- Docker מותקן על המחשב או השרת שלכם.
- Docker Compose.

### התחלה מהירה עם דוקר
1. הורידו או עשו Clone למאגר (Repository) של הפרויקט.
2. הגדירו את קובץ ה-`config.php` שלכם או השתמשו במשתני סביבה.
3. פתחו את מסוף הפקודות (Terminal) בתיקיית השורש של הפרויקט.
4. הריצו את הפקודה להפעלת הקונטיינרים:
   ```bash
   docker-compose up -d
   ```
5. המערכת תהיה זמינה בדפדפן בכתובת: `http://localhost:8080`.

**משתני סביבה מיוחדים לדוקר**:
- `HEADLESSVIDX_ADDRESS`: כתובת שירות ה-HeadlessVidX (ברירת המחדל בפרויקט היא `localhost:3202`, ובתוך ה-docker-compose מוגדר כ-`headlessvidx:3202`).

---

## פקודות נפוצות (Commands)

להלן רשימה של פקודות רלוונטיות, בעיקר לתפעול פריסת Docker ויצירת רשימות:

- **הפעלת המערכת בדוקר (ברקע)**:
  ```bash
  docker-compose up -d
  ```

- **כיבוי המערכת בדוקר**:
  ```bash
  docker-compose down
  ```

- **יצירת רשימות השמעה באופן ידני (לגרסאות ישנות יותר או כשצריך מיידית)**:
  - פקודה ליצירת רשימת סרטים: עליכם להריץ בדפדפן או דרך שורת הפקודה את הקובץ `create_playlist.php`.
  - פקודה ליצירת רשימת סדרות: `create_tv_playlist.php`.
  *הערה: בגרסאות החדשות הרשימות נוצרות אוטומטית פעמיים ביום על ידי GitHub Actions. אם אתם רוצים לאפשר יצירה עצמאית תמיד, שנו את המשתנה `$userCreatePlaylist = true;` בקובץ `config.php`.*

- **הורדת רשימת M3U באמצעות דפדפן**:
  ```text
  http://<YOUR_IP>/player_api.php?action=get_vod_streams
  ```

[![תמונת סרטון](https://raw.githubusercontent.com/gogetta69/TMDB-To-VOD-Playlist/main/images/thumb.PNG)](https://rumble.com/embed/v54v3nx/?pub=4)



### Dashboard Screenshots

![Overview](Overview.png)
![Globe](Globe.png)
![Sessions](Sessions.png)
![Titles](Titles.png)
![Cache Logs](cache%20logs.png)


# دليل الاستخدام لمشروع TMDB إلى VOD

مرحبًا بك في دليل الاستخدام الخاص بمشروع TMDB إلى VOD. توفر هذه الصفحة الشاملة كل ما تحتاجه لفهم المشروع، وكيفية عمله، وكيفية تثبيته وتشغيله سواء بالطريقة التقليدية أو باستخدام Docker.

## كيف يعمل (How it Works)

### الملخص
يتيح لك هذا المشروع إنشاء قوائم تشغيل فيديو حسب الطلب (VOD) للبث التلفزيوني المباشر والأفلام والمسلسلات التلفزيونية باستخدام Xtream Codes أو تنسيق M3U8.

يقوم بإنشاء قوائم تشغيل ديناميكية باستخدام نسخة محاكاة من Xtream Codes مع تفاصيل وصفية شاملة. يتم العثور على روابط البث باستخدام مصادر مثل TMDB و Real-Debrid و Premiumize ومصادر مباشرة. مثالي للاستخدام مع تطبيقات مثل iMplayer و Tivimate و IPTV Streamers Pro وغيرها.

### الميزات
- إنشاء قوائم تشغيل ديناميكية للبث التلفزيوني المباشر والأفلام والمسلسلات.
- التكامل مع TMDB، Real Debrid، Premiumize، والمصادر المباشرة.
- محاكاة Xtream Codes للحصول على البيانات الوصفية.
- التخزين المؤقت التلقائي للروابط.
- دعم الترجمات (pt-PT، pt-BR) واختيار الصوت الدقيق للإصدارات المتعددة.
- لوحة تحكم تحليلات M3uListerr لمتابعة الإحصائيات والجلسات.
- اختيار الصوت باللغة الصحيحة للإصدارات متعددة الصوتيات.
- مسار فرنسي مخصص (`UnlimitedFR` / `?lang=fr`) وتفضيل الجودة والتخزين المؤقت.

### ما هو HeadlessVidX؟
HeadlessVidX هي أداة مصممة لتبسيط تطوير مستخرجات الفيديو لمواقع البث. يوفر حلاً سهل الاستخدام للمستخدمين لإضافة مواقع بث الفيديو بسرعة إلى أدوات مثل 'TMDB TO VOD'.
<br>
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

### إنشاء قائمة تشغيل
لا تحتاج إلى تشغيل `create_playlist.php` و `create_tv_playlist.php` يدويًا، حيث يتم إنشاء قوائم التشغيل هذه تلقائيًا مرتين في اليوم عبر سير عمل GitHub. لإنشاء قوائم التشغيل الخاصة بك، قم بتعيين `$userCreatePlaylist = true` في ملف `config.php`.

## التثبيت (Installation)

### البدء السريع
1. **التكوين**: قم بإعداد البرنامج النصي باستخدام مفتاح واجهة برمجة تطبيقات [TMDB API Key](https://developer.themoviedb.org/docs/getting-started) المجاني ومفتاح خاص اختياري لـ Real Debrid أو Premiumize.
2. **تكامل Xtream Codes**: أدخل عنوان IP أو المجال كخادم Xtream Codes. أي اسم مستخدم وكلمة مرور سيعملان لتحميل قوائم التشغيل.
3. **التطبيقات التي لا تدعم Xtream Codes**: قم بزيارة `http://IP_ADDRESS/player_api.php?action=get_vod_streams` وحدد موقع `playlist.m3u8` لتحميله كقائمة تشغيل M3U (للأفلام والبث التلفزيوني فقط).
4. **التشغيل**: انقر فوق زر التشغيل للسماح للبرنامج النصي بالبحث في مواقع الويب في الخلفية عن رابط قابل للتشغيل (يستغرق الأمر بعض الوقت).
5. **الاستضافة المحلية**: يمكنك تشغيله باستخدام برامج مثل Xampp إذا لم يكن لديك استضافة ويب.

## Docker (النشر عبر Docker)

يمكنك تشغيل الحزمة بأكملها بسهولة باستخدام Docker و Docker Compose.

### المتطلبات الأساسية
- تثبيت Docker و Docker Compose على جهازك.

### خطوات النشر
1. قم باستنساخ المستودع (Clone the repository).
2. قم بتكوين ملف `config.php` (أو استخدم متغيرات البيئة).
3. افتح الطرفية (Terminal) وانتقل إلى الدليل الجذر للمشروع.
4. قم بتشغيل أمر التشغيل الخاص بـ Docker.
5. قم بالوصول إلى الموقع عبر `http://localhost:8080`.

## الأوامر (Commands)

إليك أهم الأوامر المستخدمة لتشغيل المشروع بواسطة Docker:

```bash
# لتشغيل الحاويات في الخلفية
docker-compose up -d

# لإيقاف الحاويات
docker-compose down

# لعرض سجلات الحاويات
docker-compose logs -f
```

### متغيرات البيئة لـ Docker
- `HEADLESSVIDX_ADDRESS`: عنوان خدمة HeadlessVidX (الافتراضي: `localhost:3202`). في docker-compose، يتم تعيين هذا إلى `headlessvidx:3202`.

---
*ملاحظة: هذا المشروع مخصص للأغراض التعليمية ولتسهيل الوصول إلى المحتوى. يرجى احترام قوانين حقوق الطبع والنشر.*



### Dashboard Screenshots

![Overview](Overview.png)
![Globe](Globe.png)
![Sessions](Sessions.png)
![Titles](Titles.png)
![Cache Logs](cache%20logs.png)


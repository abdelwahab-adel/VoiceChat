<div align="center">

# 🎙️ VoiceChat

### منصة دردشة صوتية احترافية وجاهزة للإنتاج

بديل متكامل مفتوح المصدر لمنصات مثل **SoulChill**، مبني بالكامل بلغة **PHP** بمعمارية MVC أصلية (بدون أي فريمورك خارجي) وقاعدة بيانات **MySQL**.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](#-الترخيص)
[![Composer](https://img.shields.io/badge/Composer-PSR--4-885630?logo=composer&logoColor=white)](https://getcomposer.org)

[المزايا](#-المزايا) •
[التقنيات](#-التقنيات-المستخدمة) •
[التثبيت على Windows](#-التثبيت-على-windows--xampp) •
[التثبيت على Linux/macOS](#-التثبيت-على-linuxmacos) •
[سجل الإصلاحات](#-سجل-الإصلاحات-المطبقة-على-هذه-النسخة) •
[واجهة API](#-توثيق-api-جاهز-للموبايل) •
[الترخيص](#-الترخيص)

</div>

---

## 📖 نظرة عامة

**VoiceChat** منصة دردشة صوتية جماعية متكاملة، تضم غرفًا صوتية بمقاعد مايك، وكالات (Agencies)، نظام هدايا وعملات، رسائل خاصة، لوحات صدارة، إشعارات لحظية عبر WebSocket، ولوحة تحكم إدارية كاملة. المشروع مصمم ليكون **جاهزًا للربط مباشرة مع تطبيقات موبايل native** (Android / iOS) عبر REST API + JWT.

> ⚠️ **هذه النسخة من README محدّثة بعد جلسة تثبيت فعلية شملت اكتشاف وإصلاح عدة أخطاء في الكود الأصلي.** راجع قسم [سجل الإصلاحات](#-سجل-الإصلاحات-المطبقة-على-هذه-النسخة) قبل التثبيت من مصدر آخر.

---

## ✨ المزايا

### 🎙️ الغرف الصوتية
- غرف بـ 8 أو 16 مقعد مايك، رفع اليد، قبول/رفض، كتم/فتح الصوت، قفل/فتح المقاعد، تبديل المقاعد
- غرف عامة، خاصة، ومحمية بكلمة مرور
- دردشة داخل الغرفة (نص، إيموجي، هدايا، رسائل نظام)
- قائمة مشاركين لحظية بحالة الاتصال

### 🌐 صوت WebRTC
اتصال صوتي مباشر Peer-to-Peer، إلغاء الصدى، تقليل الضوضاء، ضبط تلقائي لمستوى الصوت، كشف نشاط الصوت، إعادة اتصال تلقائية

### 👤 المستخدمون
تسجيل بالبريد/الهاتف، مصادقة JWT + جلسات، ملف شخصي كامل، متابعة/أصدقاء/حظر/إبلاغ، مستويات وXP وأوسمة، إعدادات خصوصية

### 🏢 الوكالات (Agencies)
إنشاء وكالة، أدوار (مالك/مشرف/عضو)، طلبات انضمام بموافقة إدارية، لوحة صدارة وإحصائيات، غرف مملوكة للوكالة

### 🎁 الهدايا والاقتصاد الداخلي
كتالوج 16+ هدية بمستويات ندرة، نظام عملات، حركات هدايا متحركة، لوحات صدارة، سجل معاملات كامل

### 💬 الرسائل الخاصة
محادثات 1:1 (نص/صور/صوت/ملفات)، حالة اتصال، مؤشر كتابة، إشعارات قراءة، عداد غير مقروء

### 🔔 الإشعارات
لحظية عبر WebSocket — طلبات صداقة، هدايا، دعوات، رسائل

### 🛡️ لوحة تحكم إدارية
إحصائيات، إدارة مستخدمين (حظر/دور/رصيد)، إشراف غرف ووكالات، كتالوج هدايا CRUD، بلاغات، إعلانات، إعدادات، سجل نشاطات

### 🔍 البحث ولوحة الصدارة
بحث مستخدمين/غرف/وكالات مع فلاتر، لوحة صدارة شاملة

---

## 🧱 التقنيات المستخدمة

| الطبقة | التقنية |
|---|---|
| اللغة | PHP 8.2+ (المشروع يعمل بدون مشاكل على 8.2 رغم أن `composer.json` يطلب 8.3+) |
| المعمارية | MVC أصلي (PSR-4) — بدون Laravel |
| قاعدة البيانات | MySQL 5.7+ / 8.0 |
| المصادقة | JWT + جلسات (Sessions) |
| الاتصال اللحظي | WebSocket (PHP أصلي عبر إضافة `sockets`) |
| الصوت | WebRTC (Peer-to-Peer) |
| الواجهة الأمامية | Bootstrap 5, JavaScript ES6, CSS مخصص — **لا يوجد Node.js/npm في هذا المشروع** |
| إدارة الحزم | Composer (PSR-4 autoload) |
| البريد الإلكتروني | PHPMailer (SMTP) |
| السجلات | Monolog |
| معالجة الصور | Intervention/Image |

---

## 📁 هيكل المشروع

```
voicechat/
├── app/
│   ├── Console/             # مهام Cron
│   ├── Controllers/{Api,Admin,Web}/
│   ├── Core/                # Router, DB, Request, Response, View, Model, Controller, Application
│   ├── Helpers/ Middleware/ Models/ Services/ Views/
├── config/
├── database/{schema.sql, seed.sql}
├── public/{index.php, ws.php, assets/, uploads/}
├── routes/web.php
├── storage/{logs, cache, sessions}
├── composer.json
├── cron.php
└── .env.example
```

---

## 🪟 التثبيت على Windows / XAMPP

هذا الدليل موثّق فعليًا من تثبيت حقيقي على Windows + XAMPP وتم حل كل عقبة وُوجهت أثناءه.

### 1) المتطلبات
- **XAMPP** مع PHP 8.2 أو أحدث (Apache وMySQL)
- **Composer** ([getcomposer.org](https://getcomposer.org))
- PowerShell (مدمج في Windows)

### 2) تفعيل إضافات PHP المطلوبة

افتح `C:\xampp\php\php.ini` بمفكرة، وتأكد أن هذه الأسطر **بدون** فاصلة منقوطة `;` في البداية:

```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=curl
extension=gd
extension=sockets
extension=zip
```

> 🔑 `sockets` ضروري لتشغيل سيرفر WebSocket، و`zip` ضروري كي يستطيع Composer تحميل الحزم بدون الحاجة لـ Git.

تحقق من التفعيل بعد الحفظ:
```powershell
& "C:\xampp\php\php.exe" -m | Select-String "sockets|zip|pdo_mysql"
```

### 3) تثبيت الاعتماديات

بما أن `composer.json` يطلب `php: ">=8.3"` بينما XAMPP غالبًا يأتي بـ 8.2 (والكود فعليًا متوافق مع 8.2 — لا يستخدم أي ميزة حصرية بـ 8.3)، ثبّت مع تجاوز فحص الإصدار:

```powershell
cd C:\xampp\htdocs\pro\voicechat
composer install --ignore-platform-reqs
```

تحقق من نجاح التثبيت:
```powershell
Test-Path vendor\autoload.php   # يجب أن تكون True
```

### 4) إعداد البيئة

```powershell
Copy-Item .env.example .env
notepad .env
```

عدّل بيانات قاعدة البيانات (القيم الافتراضية لـ XAMPP):
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=voicechat
DB_USERNAME=root
DB_PASSWORD=
```

### 5) إنشاء قاعدة البيانات

**عبر phpMyAdmin (الأسهل):** شغّل MySQL من لوحة XAMPP → افتح `http://localhost/phpmyadmin` → أنشئ قاعدة بيانات `voicechat` بـ Collation `utf8mb4_unicode_ci` → Import تباعًا: `database\schema.sql` ثم `database\seed.sql`.

**أو عبر PowerShell:**
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE voicechat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Get-Content database\schema.sql | & "C:\xampp\mysql\bin\mysql.exe" -u root voicechat
Get-Content database\seed.sql | & "C:\xampp\mysql\bin\mysql.exe" -u root voicechat
```

### 6) تشغيل السيرفرين (نافذتا PowerShell منفصلتان)

```powershell
# نافذة 1 — سيرفر HTTP
& "C:\xampp\php\php.exe" -S 0.0.0.0:8000 -t public

# نافذة 2 — سيرفر WebSocket
& "C:\xampp\php\php.exe" public/ws.php
```

⚠️ لا تكتب أمرين في نفس السطر/النافذة — كل أمر يبقى يعمل (blocking) في نافذته الخاصة.

افتح المتصفح على **http://localhost:8000** وسجّل دخول بـ `admin` / `Admin@12345`.

---

## 🐧 التثبيت على Linux/macOS

```bash
cd voicechat
composer install
cp .env.example .env
# عدّل .env بمعلومات قاعدة البيانات

mysql -u root -p -e "CREATE DATABASE voicechat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p voicechat < database/schema.sql
mysql -u root -p voicechat < database/seed.sql

chmod -R 775 storage public/uploads

php -S 0.0.0.0:8000 -t public      # تيرمنال 1
php public/ws.php                  # تيرمنال 2
```

أو استخدم السكربت التفاعلي الجاهز: `chmod +x setup.sh && ./setup.sh`

### بيانات الدخول الافتراضية

| الدور | اسم المستخدم | كلمة المرور |
|---|---|---|
| 👑 أدمن | `admin` | `Admin@12345` |
| 👤 مستخدم تجريبي | `demo` | `Demo@12345` |

⚠️ **غيّر بيانات الأدمن فورًا في بيئة الإنتاج.**

---

## 🩹 سجل الإصلاحات المطبقة على هذه النسخة

النسخة الأصلية للكود تحتوي على 3 أخطاء برمجية حقيقية تم اكتشافها وإصلاحها أثناء التثبيت. إن كنت تحمّل الكود من مصدر أصلي/أقدم، تأكد من تطبيق هذه الإصلاحات:

### 1. خطأ في تعريف مسارات API (`routes/web.php`)
كل مسارات `/api/*` (38 مسارًا) كانت معرَّفة بصيغة نصية `'Controller@method'`، بينما `Router::get()`/`post()` مُعرَّفة بصرامة لقبول `array|Closure` فقط. **النتيجة:** `TypeError` فوري عند أول طلب لأي مسار API.
**الإصلاح:** تحويل جميع المسارات إلى الصيغة `[ControllerClass::class, 'method']` وإضافة استيرادات الكلاسات المطلوبة.

### 2. استيراد ناقص لـ Request/Response في 31 متحكمًا
كل متحكمات `Web`, `Admin`, `Api` تقريبًا تستخدم `Request $request, Response $response` في الـ constructor دون استيراد `use App\Core\Request;` و`use App\Core\Response;`. **النتيجة:** `Class "App\Controllers\...\Request" does not exist`.
**الإصلاح:** إضافة سطري الاستيراد الناقصين في كل ملف متأثر.

### 3. تعارض توقيع دالة `create()` في موديلَي Announcement وReport
كلا الكلاسين أعاد تعريف `create()` الموروثة من `App\Core\Model` بتوقيع مختلف (معاملات إضافية)، وهو ما يخالف مبدأ Liskov Substitution ويُسبب `Fatal Error` فوري عند تحميل الكلاس.
**الإصلاح:** إعادة تسمية الدالتين إلى `createAnnouncement()` و`createReport()` وتحديث كل نقاط الاستدعاء في `AnnouncementAdminController`, `UserApiController`, `ProfileController`.

---

## 🔧 الإعداد للإنتاج (Nginx)

```nginx
server {
    listen 80;
    server_name voicechat.local;
    root /var/www/voicechat/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /ws {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

شغّل سيرفر WebSocket كخدمة دائمة عبر systemd/supervisor. يتطلب **HTTPS** إلزاميًا في الإنتاج لعمل `getUserMedia`.

---

## 📡 توثيق API (جاهز للموبايل)

جميع نقاط النهاية تحت `/api` وتُرجع JSON. الطلبات المحمية تتطلب ترويسة `Authorization: Bearer <access_token>`.

| المجموعة | أهم النقاط |
|---|---|
| **المصادقة** | `POST /api/auth/login` · `POST /api/auth/register` · `POST /api/auth/refresh` · `GET /api/me` |
| **الغرف** | `GET /api/rooms` · `POST /api/rooms/{id}/join` · `POST /api/rooms/{id}/seat` · `POST /api/rooms/{id}/mic` · `POST /api/rooms/{id}/signaling` |
| **الهدايا** | `GET /api/gifts` · `POST /api/gifts/send` · `GET /api/gifts/history` |
| **الرسائل** | `GET /api/messages` · `POST /api/messages/{userId}/send` |
| **المستخدمون** | `GET /api/users/{username}` · `POST /api/users/{userId}/follow` · `POST /api/users/{userId}/report` |
| **أخرى** | `GET /api/agencies` · `GET /api/leaderboard` · `GET /api/search` |

### 🔌 بروتوكول WebSocket

الاتصال: `ws://your-host:8080/?token=<JWT>` — أول إطار:
```json
{ "type": "hello", "room_id": 1, "token": "<JWT>" }
```
أهم الأحداث: `chat_message` · `user_joined`/`left` · `seat_taken`/`freed` · `hand_raised`/`lowered` · `offer`/`answer`/`ice` · `gift_received` · `typing`

---

## 🔐 الأمان

CSRF على كل طلب يغيّر الحالة · تهريب مخرجات تلقائي (XSS) · استعلامات PDO مُجهّزة حصريًا · bcrypt (cost 12) · JWT موقّع HS256 مع Refresh Tokens مشفّرة · Rate Limiting حسب IP · صلاحيات حسب الدور · جلسات HttpOnly/SameSite · نظام حظر مع تسجيل كامل

---

## 🐛 استكشاف الأخطاء وإصلاحها

| المشكلة | الحل |
|---|---|
| `chmod` غير معروف في PowerShell | تجاهل الأمر على Windows، غير مطلوب |
| فشل `composer install` بسبب PHP version | استخدم `--ignore-platform-reqs` (الكود متوافق مع 8.2) |
| `Failed to download ... zip extension missing` | فعّل `extension=zip` في `php.ini` |
| `Call to undefined function socket_create()` | فعّل `extension=sockets` في `php.ini` وأعد التشغيل |
| WebSocket لا يتصل | تأكد من تشغيل `php public/ws.php` في نافذة منفصلة وأن المنفذ 8080 مفتوح |
| فشل رفع الملفات | تحقق من صلاحيات `public/uploads/` |
| لا يوجد صوت | HTTPS مطلوب لـ `getUserMedia` في الإنتاج — `localhost` يعمل بدونه في التطوير فقط |

---

## 📲 تطبيقات الموبايل

نفس الـ API قابل للاستهلاك من تطبيقات Android (Kotlin/Java) وiOS (Swift) أصلية — راجع `docs/mobile.md`.

## 📜 الترخيص

MIT — حرّ الاستخدام والتعديل وإعادة التوزيع.

---

<div align="center">

صُنع بـ ❤️ كمرجع متكامل لمنصة دردشة صوتية حديثة

</div>

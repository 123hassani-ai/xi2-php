# زیتو (Xi2) - راهنمای نصب و راه‌اندازی

## 🎯 درباره پروژه
زیتو یک پلتفرم مدرن و کاربرپسند برای آپلود، مدیریت و اشتراک‌گذاری تصاویر است که با تمرکز بر تجربه کاربری فارسی‌زبان طراحی شده است.

## 📋 نیازمندی‌های سیستم

### سرور محلی (XAMPP/WAMP)
- **PHP**: 7.4 یا بالاتر (توصیه: 8.1+)
- **MySQL**: 5.7 یا بالاتر
- **Apache**: 2.4+
- **Extensions مورد نیاز**:
  - gd (پردازش تصاویر)
  - pdo_mysql (پایگاه داده)
  - mbstring (پردازش متن فارسی)
  - fileinfo (تشخیص نوع فایل)
  - json (پردازش JSON)

### مرورگر
- Chrome 80+ / Firefox 75+ / Safari 13+
- پشتیبانی از PWA و Service Workers

## 🛠️ نصب و راه‌اندازی

### مرحله 1: دانلود و کپی فایل‌ها
```bash
# کپی پروژه به دایرکتوری htdocs
cp -r xi2-01 /xampp/htdocs/
# یا در ویندوز
# کپی پوشه xi2-01 به C:\xampp\htdocs\
```

### مرحله 2: راه‌اندازی پایگاه داده
```bash
# مراجعه به فایل نصب
http://localhost/xi2-01/src/database/install.php
```

یا اجرای مستقیم:
```bash
cd /xampp/htdocs/xi2-01/src/database/
php install.php
```

### مرحله 3: تنظیم مجوزات
```bash
# دسترسی نوشتن به پوشه آپلود
chmod 755 storage/
chmod 755 storage/uploads/
chmod 755 storage/cache/
chmod 755 storage/logs/
```

### مرحله 4: تست نصب
مراجعه به: `http://localhost/xi2-01/public/`

## 📁 ساختار پروژه

```
xi2-01/
├── 📁 public/              # فایل‌های عمومی
│   ├── index.html          # صفحه اصلی
│   ├── manifest.json       # تنظیمات PWA
│   └── service-worker.js   # Service Worker (آینده)
├── 📁 src/                 # کد منبع
│   ├── 📁 assets/          # دارایی‌های استاتیک
│   │   ├── 📁 css/         # استایل‌ها
│   │   │   ├── main.css    # استایل اصلی
│   │   │   └── components.css # کامپوننت‌ها
│   │   ├── 📁 js/          # جاوااسکریپت
│   │   │   ├── main.js     # اسکریپت اصلی
│   │   │   ├── upload.js   # مدیریت آپلود
│   │   │   └── auth.js     # احراز هویت
│   │   ├── 📁 fonts/       # فونت‌ها
│   │   └── 📁 images/      # تصاویر
│   ├── 📁 database/        # پایگاه داده
│   │   ├── config.php      # تنظیمات DB
│   │   └── install.php     # اسکریپت نصب
│   ├── 📁 api/             # API endpoints (آینده)
│   └── 📁 modules/         # ماژول‌های عملکردی (آینده)
├── 📁 storage/             # فضای ذخیره‌سازی
│   ├── 📁 uploads/         # فایل‌های آپلودی
│   ├── 📁 cache/           # کش
│   └── 📁 logs/            # لاگ‌ها
└── 📁 docs/                # مستندات
    ├── mvp-proposal.md     # پروپوزال پروژه
    └── installation.md     # راهنمای نصب (این فایل)
```

## 🔧 تنظیمات پیشرفته

### تنظیمات PHP (php.ini)
```ini
# حجم آپلود
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M

# Extensions
extension=gd
extension=pdo_mysql
extension=mbstring
extension=fileinfo
extension=json
```

### تنظیمات Apache (.htaccess)
```apache
# URL Rewriting (آینده)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# GZIP Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

## 📊 پایگاه داده

### جداول اصلی

#### users - کاربران
```sql
- id: شناسه کاربر
- full_name: نام کامل
- mobile: شماره موبایل (منحصر به فرد)
- password_hash: رمز عبور هش شده
- status: وضعیت (active/inactive/banned)
- level: سطح کاربری (1-5)
- otp_code: کد تایید موقت
- otp_expires: انقضای کد تایید
```

#### uploads - فایل‌های آپلودی
```sql
- id: شناسه فایل
- user_id: شناسه کاربر
- file_name: نام فایل در سرور
- original_name: نام اصلی فایل
- file_path: مسیر فایل
- short_link: لینک کوتاه (8 کاراکتر)
- view_count: تعداد بازدید
- compression_level: سطح فشرده‌سازی
```

#### settings - تنظیمات
```sql
- key_name: نام تنظیم
- value: مقدار
- category: دسته‌بندی
- is_public: قابل دسترسی در کلاینت
```

## 🎨 ویژگی‌های پیاده‌سازی شده

### ✅ فرانت‌اند
- **رابط کاربری فارسی**: طراحی RTL با فونت وزیرمتن
- **PWA**: پیکربندی اولیه با Manifest
- **ریسپانسیو**: سازگار با موبایل و دسکتاپ
- **منطقه آپلود**: Drag & Drop با پیش‌نمایش
- **مودال احراز هویت**: ورود، ثبت‌نام، تایید OTP
- **نوتیفیکیشن سیستم**: اعلان‌های زیبا و کاربرپسند

### ✅ جاوااسکریپت
- **کلاس Xi2App**: مدیریت کلی اپلیکیشن
- **کلاس Xi2Upload**: مدیریت آپلود و دوربین
- **کلاس Xi2Auth**: مدیریت احراز هویت
- **PWA Support**: Service Worker و نصب اپلیکیشن
- **API Integration**: آماده اتصال به بک‌اند

### ✅ بک‌اند (پایگاه داده)
- **کلاس Database**: مدیریت اتصال و queries
- **Schema کامل**: جداول users, uploads, settings و...
- **اسکریپت نصب**: راه‌اندازی خودکار
- **تنظیمات پیش‌فرض**: کانفیگ اولیه سیستم

## 🔜 مراحل بعدی (در چت جدید)

### فاز 2: API و بک‌اند
- [ ] ایجاد API endpoints
- [ ] سیستم احراز هویت PHP
- [ ] آپلود و پردازش تصاویر
- [ ] مدیریت session و JWT

### فاز 3: ویژگی‌های پیشرفته
- [ ] فشرده‌سازی تصاویر
- [ ] ایجاد thumbnail
- [ ] سیستم کش
- [ ] پنل مدیریت

## 🐛 رفع مشکلات متداول

### خطای اتصال پایگاه داده
```php
// بررسی کنید MySQL در حال اجرا باشد
// در XAMPP Control Panel چک کنید
// تنظیمات در src/database/config.php
```

### خطای مجوز فایل
```bash
# در macOS/Linux
chmod -R 755 storage/
chown -R www-data:www-data storage/

# در Windows از Properties > Security استفاده کنید
```

### مشکل فونت فارسی
```css
/* اطمینان از بارگیری فونت */
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap');
```

## 📞 پشتیبانی

برای ادامه توسعه، مشکلات یا سوالات، چت جدید ایجاد کنید و این مستندات را ارائه دهید.

### اطلاعات مهم برای چت بعدی:
- ✅ ساختار پروژه کامل
- ✅ پایگاه داده آماده
- ✅ فرانت‌اند پایه کامل
- ⏳ نیاز به API و بک‌اند PHP
- ⏳ سیستم آپلود واقعی
- ⏳ پردازش تصاویر

---

**نسخه**: 1.0.0-alpha  
**تاریخ**: 29 آگست 2025  
**توسعه‌دهنده**: تیم زیتو (Xi2)

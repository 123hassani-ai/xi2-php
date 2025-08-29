# 🎯 زیتو (Xi2) - پلتفرم اشتراک‌گذاری تصاویر

> **سریع‌ترین و زیباترین راه برای آپلود، مدیریت و اشتراک‌گذاری تصاویر**

![زیتو Banner](docs/assets/banner.png)

## 📋 فهرست مطالب

- [معرفی پروژه](#-معرفی-پروژه)
- [ویژگی‌ها](#-ویژگیها)  
- [نصب و راه‌اندازی](#-نصب-و-راهاندازی)
- [استفاده](#-استفاده)
- [API Reference](#-api-reference)
- [ساختار پروژه](#-ساختار-پروژه)
- [تکنولوژی‌ها](#-تکنولوژیها)
- [توسعه](#-توسعه)
- [مشارکت](#-مشارکت)
- [لایسنس](#-لایسنس)

## 🚀 معرفی پروژه

**زیتو (Xi2)** یک پلتفرم مدرن و قدرتمند برای اشتراک‌گذاری تصاویر است که با تکنولوژی‌های روز دنیا طراحی شده. این پروژه ترکیبی از سادگی در استفاده، امنیت بالا و عملکرد فوق‌العاده ارائه می‌دهد.

### 🎯 هدف پروژه
- ایجاد تجربه‌ای روان و لذت‌بخش برای کاربران
- پشتیبانی کامل از زبان فارسی و راست‌چین  
- عملکرد سریع و قابل اعتماد
- رابط کاربری مدرن و جذاب

## ✨ ویژگی‌ها

### 🔐 احراز هویت و امنیت
- ثبت‌نام و ورود با شماره موبایل
- تایید هویت با کد OTP
- رمزگذاری پیشرفته رمز عبور (bcrypt)
- نشست‌های امن با توکن‌های منقضی‌شونده
- محافظت در برابر حملات CSRF

### 📸 مدیریت تصاویر
- آپلود drag & drop
- پردازش خودکار تصاویر
- تولید thumbnail ها
- فشرده‌سازی هوشمند
- پشتیبانی از فرمت‌های مختلف (JPEG, PNG, WebP)
- حداکثر سایز ۱۰ مگابایت

### 🌐 Progressive Web App (PWA)
- قابلیت نصب روی دستگاه
- عملکرد آفلاین
- Service Worker پیشرفته
- Caching هوشمند
- Push Notifications

### 📱 طراحی پاسخگو
- Responsive design
- موبایل فرست
- تم تیره و روشن خودکار
- انیمیشن‌های نرم
- فونت فارسی Vazirmatn

### 📊 آمارگیری و تحلیل
- آمار بازدید تصاویر
- آمار دانلود
- گزارش‌های کاربری
- لاگ فعالیت‌ها
- آنالیتیکس پیشرفته

## 🛠 نصب و راه‌اندازی

### پیش‌نیازها
- PHP 8.0 یا بالاتر
- MySQL 5.7 یا MariaDB 10.3+
- Apache/Nginx یا سرور توسعه PHP
- Extensions: GD, PDO, mbstring, fileinfo

### نصب سریع

1. **کلون پروژه:**
```bash
git clone https://github.com/yourusername/xi2-php.git
cd xi2-php/xi2-01
```

2. **پیکربندی پایگاه داده:**
```bash
# ایجاد پایگاه داده
mysql -u root -p
CREATE DATABASE xi2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. **تنظیمات پایگاه داده:**
```bash
# ویرایش فایل config
nano src/database/config.php

# اجرای نصب
cd src/database
php install.php
```

4. **راه‌اندازی سرور:**
```bash
# سرور توسعه PHP
php -S localhost:8000 -t public

# یا Apache/Nginx
# کپی پوشه پروژه به htdocs/www
```

### تنظیمات محیط تولید

```php
// src/database/config.php
private function loadConfig() {
    if (getenv('APP_ENV') === 'production') {
        $this->host = getenv('DB_HOST') ?? 'localhost';
        $this->username = getenv('DB_USERNAME');
        $this->password = getenv('DB_PASSWORD');
        $this->database = getenv('DB_DATABASE');
    }
    // ... local config
}
```

## 💻 استفاده

### کاربر عادی

1. **ثبت‌نام:**
   - وارد کردن نام، موبایل و رمز عبور
   - دریافت کد تایید SMS
   - فعال‌سازی حساب کاربری

2. **آپلود تصویر:**
   - کلیک روی منطقه آپلود
   - یا drag & drop تصویر
   - انتظار برای پردازش
   - دریافت لینک اشتراک‌گذاری

3. **مدیریت تصاویر:**
   - مشاهده لیست آپلودها
   - ویرایش عنوان و توضیحات
   - حذف تصاویر
   - مشاهده آمار

### توسعه‌دهنده

#### استفاده از API

```javascript
// ثبت‌نام
const response = await fetch('/api/auth/register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        name: 'علی احمدی',
        mobile: '09123456789',
        password: '123456'
    })
});
```

```javascript
// آپلود تصویر
const formData = new FormData();
formData.append('image', file);

const response = await fetch('/api/upload/upload.php', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` },
    body: formData
});
```

## 📡 API Reference

### Authentication APIs

#### POST `/api/auth/register.php`
ثبت‌نام کاربر جدید

**Body:**
```json
{
    "name": "نام کامل",
    "mobile": "09123456789", 
    "password": "رمز عبور"
}
```

**Response:**
```json
{
    "success": true,
    "message": "ثبت‌نام موفقیت‌آمیز بود",
    "data": {
        "userId": "1",
        "mobile": "09123456789",
        "needsVerification": true,
        "otpExpires": "2025-08-29 13:43:33"
    }
}
```

#### POST `/api/auth/login.php`
ورود کاربر

**Body:**
```json
{
    "mobile": "09123456789",
    "password": "رمز عبور"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "fullName": "علی احمدی",
            "mobile": "09123456789",
            "status": "active"
        },
        "token": "session_token_here",
        "stats": {
            "totalUploads": 0,
            "totalSize": 0,
            "totalViews": 0
        }
    }
}
```

## 🏗 ساختار پروژه

```
xi2-01/
├── 📁 public/              # فایل‌های عمومی
│   ├── index.php           # نقطه ورود اصلی + Router
│   ├── manifest.json       # PWA Manifest
│   └── service-worker.js   # Service Worker
│
├── 📁 src/                 # کد منبع
│   ├── 📁 api/            # API Endpoints
│   │   ├── config.php     # کانفیگوریشن API + ApiManager
│   │   ├── 📁 auth/       # API های احراز هویت
│   │   │   ├── register.php
│   │   │   ├── login.php
│   │   │   ├── verify-otp.php
│   │   │   └── logout.php
│   │   └── 📁 upload/     # API های آپلود
│   │       ├── upload.php
│   │       ├── list.php
│   │       └── delete.php
│   │
│   ├── 📁 database/       # پایگاه داده
│   │   ├── config.php     # کلاس Database + Connection
│   │   └── install.php    # نصب پایگاه داده
│   │
│   └── 📁 assets/         # منابع استاتیک
│       ├── 📁 css/        # فایل‌های استایل
│       ├── 📁 js/         # فایل‌های جاوا اسکریپت
│       └── 📁 images/     # تصاویر پروژه
│
├── 📁 storage/            # ذخیره‌سازی
│   ├── 📁 uploads/        # فایل‌های آپلود شده
│   ├── 📁 cache/          # فایل‌های کش
│   └── 📁 logs/           # لاگ‌ها
│
├── 📁 docs/               # مستندات
└── README.md              # این فایل
```

## 🔧 تکنولوژی‌ها

### Backend
- **PHP 8.0+** - زبان برنامه‌نویسی سرور
- **MySQL 5.7+** - پایگاه داده رابطه‌ای
- **PDO** - لایه انتزاع پایگاه داده
- **bcrypt** - رمزگذاری رمز عبور
- **GD Extension** - پردازش تصاویر

### Frontend  
- **HTML5** - نشانه‌گذاری مدرن
- **CSS3** - استایل‌دهی پیشرفته
- **Vanilla JavaScript** - منطق سمت کلاینت
- **PWA APIs** - قابلیت‌های Progressive Web App
- **Fetch API** - ارتباط با سرور

### Database Schema
```sql
-- کاربران
users (id, full_name, mobile, password_hash, status, level, otp_code, otp_expires, created_at, updated_at, last_login, login_count)

-- آپلودها  
uploads (id, user_id, original_name, stored_name, file_path, thumbnail_path, file_size, mime_type, title, description, views, downloads, is_public, created_at, updated_at)

-- نشست‌ها
user_sessions (id, user_id, session_token, expires_at, is_active, device_info, ip_address, created_at, last_activity)

-- تنظیمات
settings (id, key, value, type, description, created_at, updated_at)

-- لاگ فعالیت‌ها
activity_logs (id, user_id, action, details, ip_address, user_agent, created_at)

-- آمار آپلودها
upload_stats (id, upload_id, views, downloads, shares, last_viewed, created_at, updated_at)
```

## 📊 آمار پروژه

- **خطوط کد:** ~2,500 PHP + 1,200 JavaScript + 800 CSS
- **فایل‌ها:** 25 فایل اصلی
- **API Endpoints:** 7 endpoint
- **Database Tables:** 6 جدول
- **زمان توسعه:** 2 هفته (فاز 1 و 2)

## 🚀 نقشه راه (Roadmap)

### فاز 3️⃣ - ویژگی‌های پیشرفته
- [ ] ویرایش تصاویر آنلاین  
- [ ] فیلترها و افکت‌ها
- [ ] پوشه‌بندی تصاویر
- [ ] اشتراک‌گذاری اجتماعی
- [ ] API عمومی برای توسعه‌دهندگان

### فاز 4️⃣ - مقیاس‌پذیری
- [ ] CDN integration
- [ ] Redis caching
- [ ] Load balancing
- [ ] Microservices architecture
- [ ] Docker containerization

## 🤝 مشارکت

ما از مشارکت شما استقبال می‌کنیم! 

### نحوه مشارکت:
1. Fork کردن پروژه
2. ایجاد branch جدید (`git checkout -b feature/amazing-feature`)
3. Commit تغییرات (`git commit -m 'Add amazing feature'`)
4. Push به branch (`git push origin feature/amazing-feature`)
5. ایجاد Pull Request

## 📞 تماس

- **توسعه‌دهنده:** تیم Xi2
- **ایمیل:** [info@xi2.app](mailto:info@xi2.app)
- **وبسایت:** [https://xi2.app](https://xi2.app)

---

<div align="center">
  <p>ساخته شده با ❤️ برای جامعه توسعه‌دهندگان فارسی‌زبان</p>
  <p><strong>زیتو (Xi2) - تصاویرتان را رها کنید</strong></p>
</div>

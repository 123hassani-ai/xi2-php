# 🎯 زیتو (Xi2) - پروژه اشتراک‌گذاری تصاویر

پلتفرم مدرن و کاربرپسند برای آپلود، مدیریت و اشتراک‌گذاری تصاویر با تمرکز بر تجربه کاربری فارسی‌زبان.

## ✨ ویژگی‌های کلیدی

- 🚀 **آپلود سریع**: Drag & Drop و دسترسی مستقیم دوربین
- 📱 **PWA**: قابلیت نصب و عملکرد آفلاین
- 🎨 **طراحی فارسی**: RTL، فونت وزیرمتن، تجربه بومی
- 🔐 **امنیت بالا**: احراز هویت دو مرحله‌ای با OTP
- 📊 **آمارگیری**: داشبورد پیشرفته و گزارش‌گیری
- 🔗 **لینک کوتاه**: اشتراک‌گذاری آسان

## 📋 وضعیت پروژه

### ✅ تکمیل شده (60%)
- فرانت‌اند کامل (HTML, CSS, JS)
- پایگاه داده و Schema
- مدیریت احراز هویت کلاینت
- سیستم آپلود کلاینت
- PWA Configuration
- مستندات کامل

### 🔄 در حال توسعه
- API Endpoints
- پردازش تصاویر
- سیستم احراز هویت سرور
- داشبورد مدیریت

## 🛠️ نصب

### نیازمندی‌ها
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Extensions: gd, pdo_mysql, mbstring

### راه‌اندازی سریع
```bash
# 1. کپی به htdocs
cp -r xi2-01 /xampp/htdocs/

# 2. نصب پایگاه داده
http://localhost/xi2-01/src/database/install.php

# 3. مشاهده نتیجه
http://localhost/xi2-01/public/
```

## 📁 ساختار

```
xi2-01/
├── public/              # صفحات عمومی
├── src/
│   ├── assets/          # CSS, JS, فونت‌ها
│   ├── database/        # کانفیگ و نصب DB
│   ├── api/             # API endpoints (آینده)
│   └── modules/         # ماژول‌های عملکردی
├── storage/             # فایل‌های آپلودی
└── docs/                # مستندات کامل
```

## 🎨 تکنولوژی

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 8+, MySQL
- **PWA**: Service Workers, Web App Manifest
- **UI**: Mobile-first, RTL, Responsive
- **Icons**: Unicode Emoji (سازگاری کامل)

## 📖 مستندات

- [`docs/installation.md`](docs/installation.md) - راهنمای نصب کامل
- [`docs/technical-docs.md`](docs/technical-docs.md) - مستندات فنی
- [`docs/quick-start.md`](docs/quick-start.md) - شروع سریع
- [`docs/mvp-proposal.md`](docs/mvp-proposal.md) - پروپوزال پروژه

## 🚀 توسعه

### فعال سازی محیط توسعه
```bash
# نصب XAMPP
# کپی پروژه
# اجرای install.php
# شروع توسعه!
```

### مرحله بعدی
برای ادامه توسعه، API ها و بک‌اند PHP:
```
نیاز به: login.php, upload.php, image processing
وضعیت: آماده شروع فاز 2
```

## 📞 پشتیبانی

برای سوالات و مشکلات:
- مراجعه به مستندات در پوشه `docs/`
- بررسی فایل‌های نمونه در پروژه

## 📄 مجوز

این پروژه تحت مجوز MIT منتشر شده است.

---

**نسخه**: 1.0.0-alpha  
**آخرین بروزرسانی**: 29 آگست 2025  
**توسعه‌دهنده**: تیم زیتو (Xi2)

⭐ اگر پروژه مفید بود، ستاره بدهید!

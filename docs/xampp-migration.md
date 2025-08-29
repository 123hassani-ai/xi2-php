# 🔄 راهنمای انتقال پروژه زیتو به XAMPP

## مقدمه
این مستند راهنمای کامل انتقال پروژه زیتو (Xi2) از محیط development محلی به XAMPP است.

## 📋 پیش‌نیازها
- XAMPP نصب شده روی سیستم
- دسترسی admin برای کپی فایل‌ها
- مرورگر برای تست

## 🚀 مراحل انتقال

### 1. کپی کردن پروژه

```bash
# کپی کردن پروژه به htdocs با نام xi2.ir
sudo cp -R /Users/macminim4/MyApp/xi2-php/xi2-01 /Applications/XAMPP/xamppfiles/htdocs/xi2.ir

# تنظیم مالکیت فایل‌ها
sudo chown -R macminim4:admin /Applications/XAMPP/xamppfiles/htdocs/xi2.ir
```

### 2. تنظیم Database Configuration

#### ✅ تنظیمات XAMPP MySQL:
- **Host**: localhost
- **Port**: 3307
- **Username**: root  
- **Password**: Mojtab@123
- **Database**: xi2_db

#### 📁 فایل: `src/database/config.php`
```php
private function loadConfig() {
    // تنظیمات XAMPP
    $this->host = 'localhost:3307';
    $this->username = 'root';
    $this->password = 'Mojtab@123';
    $this->database = 'xi2_db';
    $this->charset = 'utf8mb4';
}
```

### 3. ایجاد دیتابیس

```bash
# اتصال به MySQL با پورت 3307
/Applications/XAMPP/xamppfiles/bin/mysql -u root -P 3307 -h localhost

# ایجاد دیتابیس
CREATE DATABASE IF NOT EXISTS xi2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. نصب Schema دیتابیس

```bash
# اجرای نصب از طریق مرورگر
http://localhost/xi2.ir/src/database/install.php

# یا از طریق curl
curl -s http://localhost/xi2.ir/src/database/install.php
```

## 🔧 تنظیمات مهم

### تغییر فایل install.php
اگر install.php خطا می‌دهد، تنظیمات زیر را اعمال کنید:

```php
// در فایل src/database/install.php
$host = 'localhost:3307';
$username = 'root'; 
$password = '';
$database = 'xi2_db';
```

### بررسی DSN String
```php
$dsn = "mysql:host=localhost;port=3307;dbname=xi2_db;charset=utf8mb4";
```

## 🌐 URL های دسترسی

### صفحات اصلی:
- **صفحه اصلی**: `http://localhost/xi2.ir/public/`
- **نصب دیتابیس**: `http://localhost/xi2.ir/src/database/install.php`
- **phpMyAdmin**: `http://localhost/phpmyadmin/`

### API Endpoints:
- **Register**: `http://localhost/xi2.ir/src/api/auth/register.php`
- **Login**: `http://localhost/xi2.ir/src/api/auth/login.php`  
- **Upload**: `http://localhost/xi2.ir/src/api/upload/upload.php`

## 🧪 تست عملکرد

### تست API ثبت‌نام:
```bash
curl -X POST http://localhost/xi2.ir/src/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"name":"کاربر تست","mobile":"09123456789","password":"123456"}'
```

### تست دیتابیس:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -P 3307 -e "USE xi2_db; SHOW TABLES;"
```

## 📁 ساختار فایل‌ها در XAMPP

```
/Applications/XAMPP/xamppfiles/htdocs/xi2.ir/
├── public/                 # صفحات عمومی
│   ├── index.html         # صفحه اصلی
│   └── index.php          # PHP entry point
├── src/
│   ├── api/               # API endpoints
│   ├── assets/            # CSS, JS, تصاویر
│   ├── database/          # کانفیگ DB
│   └── modules/           # ماژول‌ها
├── storage/               # فایل‌های آپلودی
├── docs/                  # مستندات
└── test_*.php            # فایل‌های تست
```

## ⚠️ نکات مهم

### مجوزهای فایل:
```bash
# اطمینان از مجوزهای درست
chmod 755 /Applications/XAMPP/xamppfiles/htdocs/xi2.ir/
chmod -R 644 /Applications/XAMPP/xamppfiles/htdocs/xi2.ir/storage/
chmod -R 755 /Applications/XAMPP/xamppfiles/htdocs/xi2.ir/storage/uploads/
```

### لاگ خطاها:
- **Apache Error Log**: `/Applications/XAMPP/xamppfiles/logs/error_log`
- **PHP Error Log**: بررسی در phpMyAdmin یا terminal

### بک‌آپ:
```bash
# بک‌آپ دیتابیس
/Applications/XAMPP/xamppfiles/bin/mysqldump -u root -P 3307 xi2_db > xi2_backup.sql

# بک‌آپ فایل‌ها
tar -czf xi2-backup.tar.gz /Applications/XAMPP/xamppfiles/htdocs/xi2.ir/
```

## 🔄 مرحله بعدی

پس از انتقال موفق:

1. **باز کردن workspace جدید**: 
   - Path: `/Applications/XAMPP/xamppfiles/htdocs/xi2.ir/`
   - URL: `http://localhost/xi2.ir/public/`

2. **تست کامل عملکرد**:
   - صفحه اصلی
   - API های authentication  
   - آپلود فایل
   - دیتابیس operations

3. **شروع توسعه Backend**:
   - تکمیل API endpoints
   - پیاده‌سازی file upload واقعی
   - بهبود امنیت و error handling

## 📞 عیب‌یابی سریع

### اگر صفحه باز نشد:
- بررسی Apache در XAMPP Control Panel
- چک کردن path: `/Applications/XAMPP/xamppfiles/htdocs/xi2.ir/`

### اگر دیتابیس وصل نشد:
- بررسی MySQL در XAMPP (پورت 3307)
- تست اتصال: `mysql -u root -P 3307 -h localhost`

### اگر API خطا داد:
- بررسی error logs
- چک کردن JSON response با `curl -v`

---

**تاریخ ایجاد**: 29 آگست 2025  
**آخرین بروزرسانی**: 29 آگست 2025  
**وضعیت**: آماده برای توسعه 🚀

## ✅ چک‌لیست تکمیل انتقال

- [ ] پروژه کپی شده به `/Applications/XAMPP/xamppfiles/htdocs/xi2.ir/`
- [ ] Database config تنظیم شده (پورت 3307)
- [ ] دیتابیس xi2_db ایجاد شده
- [ ] جداول نصب شده (install.php اجرا شده)
- [ ] صفحه اصلی باز می‌شود: `http://localhost/xi2.ir/public/`
- [ ] API register تست شده و کار می‌کند
- [ ] phpMyAdmin دسترسی دارد به دیتابیس

**وقتی همه موارد بالا ✅ شدند، پروژه آماده ادامه توسعه است!**

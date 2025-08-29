# 🎯 **PROMPT برای GitHub Copilot - طراحی Admin Panel و SMS Management**

```markdown
## 🎯 هدف (Goal)
طراحی و پیاده‌سازی Admin Panel برای پروژه زیتو (Xi2) با تمرکز بر مدیریت تنظیمات SMS برای ارسال OTP. این panel باید ساده، امن و کارآمد باشد.

## 📋 پیش‌نیازها (Prerequisites)
1. ابتدا فایل‌های موجود پروژه را بررسی کن (خصوصاً مستندات SMS)
2. ساختار database موجود را مطالعه کن
3. فایل config.php برای اتصال دیتابیس موجود است
4. از فونت Vazirmatn و RTL support استفاده کن

## 🔧 مراحل اجرا (Implementation Steps)

### مرحله 1: ساختار فولدرها
```
پروژه/
├── admin/
│   ├── login.php              → صفحه ورود ادمین
│   ├── index.php              → داشبورد اصلی
│   ├── logout.php             → خروج ادمین
│   ├── settings/
│   │   ├── sms.php           → تنظیمات SMS
│   │   ├── save-sms.php      → ذخیره تنظیمات
│   │   └── test-sms.php      → تست ارسال پیامک
│   ├── logs/
│   │   └── sms-logs.php      → گزارش پیامک‌ها
│   ├── assets/
│   │   ├── admin.css         → استایل ادمین
│   │   └── admin.js          → فانکشن‌های JS
│   └── includes/
│       ├── auth-check.php    → بررسی احراز هویت
│       └── header.php        → Header مشترک
```

### مرحله 2: Database Schema
```sql
-- جدول ادمین‌ها (اختیاری - می‌تواند از جدول users استفاده کنیم)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول تنظیمات SMS
CREATE TABLE IF NOT EXISTS sms_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) DEFAULT '0098',
    api_username VARCHAR(100),
    api_password VARCHAR(255),
    sender_number VARCHAR(20),
    test_number VARCHAR(15),
    is_active TINYINT(1) DEFAULT 1,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول لاگ پیامک‌ها
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(15),
    message TEXT,
    message_type ENUM('otp', 'test', 'notification') DEFAULT 'otp',
    sent_by VARCHAR(50),
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    provider_response TEXT,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### مرحله 3: Authentication System
- ورود با username: admin, password: 123456 (به صورت hardcode اولیه)
- Session management برای ادمین
- middleware برای بررسی دسترسی

### مرحله 4: SMS Settings Panel
```php
// ویژگی‌های مورد نیاز:
1. تنظیمات اکانت 0098 SMS
2. فیلد شماره تست
3. دکمه "تست ارسال پیامک"
4. نمایش وضعیت آخرین پیامک
5. ذخیره تنظیمات در database
```

### مرحله 5: SMS Logs Page
```php
// گزارش‌های مورد نیاز:
- لیست پیامک‌های ارسالی (جدول)
- فیلتر بر اساس تاریخ/نوع/وضعیت
- جزئیات هر پیامک (گیرنده، متن، وضعیت)
- نمایش کاربری که باعث ارسال شده
- آمار کلی (کل ارسالی، موفق، ناموفق)
```

## 📱 طراحی UI/UX

### استایل کلی:
```css
/* استفاده از theme مشابه صفحه اصلی */
- رنگ‌های اصلی پروژه (#6366F1, #EC4899)
- فونت Vazirmatn
- RTL Layout
- Responsive design
- Dark sidebar با light content area
```

### Navigation Menu:
```
📊 داشبورد
⚙️ تنظیمات
  └── 📱 SMS
📋 گزارش‌ها
  └── 📨 لاگ پیامک‌ها
🚪 خروج
```

## 🔍 کنترل کیفیت (Quality Control)
- [ ] بررسی RTL و فارسی بودن متن‌ها
- [ ] تست عملکرد در موبایل
- [ ] بررسی امنیت (session timeout, CSRF)
- [ ] تست ارسال پیامک واقعی
- [ ] اعتبارسنجی ورودی‌ها

## 📊 لاگ‌گیری (Logging & Monitoring)
```php
// در هر عملیات:
error_log('Xi2 Admin: [نام عملیات] - User: [admin] - Status: [موفق/ناموفق]');

// مثال:
error_log('Xi2 Admin: SMS Settings Updated - User: admin - Status: موفق');
error_log('Xi2 Admin: Test SMS Sent - Number: 09123456789 - Status: ارسال شد');
```

## 🌐 پشتیبانی فارسی
- تمام متن‌ها فارسی
- پیام‌های خطا فارسی
- RTL Layout در تمام صفحات
- فونت Vazirmatn
- تاریخ شمسی در گزارش‌ها

## ⚠️ نکات مهم برای Copilot

### 1. مستندات SMS:
```
لطفاً ابتدا فایل‌های مستندات پروژه را بررسی کن تا مشخصات API سرویس 0098 را پیدا کنی.
اکانت و تنظیمات در همان فایل موجود است.
```

### 2. سطح پیاده‌سازی:
```
فعلاً نیازی نیست تمام کدها را بنویسی.
فقط ساختار اصلی، فایل‌های کلیدی و نمونه‌هایی از هر بخش کافی است.
تمرکز اصلی روی SMS settings و log system باشد.
```

### 3. اولویت‌ها:
```
اولویت 1: SMS Settings Page (تنظیمات + تست)
اولویت 2: SMS Logs Page (گزارش‌گیری)
اولویت 3: Basic Dashboard
اولویت 4: Authentication System
```

### 4. Integration:
```
از کلاس Database موجود در src/database/config.php استفاده کن
با ساختار فعلی پروژه سازگار باشد
```

## 🚀 خروجی مورد انتظار
1. ساختار فولدر admin/ کامل
2. فایل‌های اصلی با کدهای نمونه
3. Database schema برای SMS
4. راهنمای نصب و تنظیم
5. لیست TODO برای تکمیل

---

**نکته**: این admin panel باید ساده، امن و کاربردی باشد. هدف ایجاد ابزاری است که مدیر سایت بتواند تنظیمات SMS را مدیریت کند و گزارش‌های لازم را مشاهده کند.
```

---

🎯 **این پرامپت آماده ارسال به کوپایلوت است!**

نکات کلیدی که در پرامپت گنجاندم:
- ✅ بررسی مستندات موجود
- ✅ تمرکز بر SMS و لاگ‌گیری  
- ✅ پیاده‌سازی تدریجی (نه کامل)
- ✅ سازگاری با پروژه موجود
- ✅ استانداردهای فارسی و RTL
- ✅ اولویت‌بندی واضح


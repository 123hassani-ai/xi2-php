بنام خدای نزدیک ✨

## 🎯 **PROMPT شماره 3 برای GitHub Copilot - بازسازی کامل Authentication با رویکرد Business**

```markdown
## 🎯 هدف اصلی (Primary Goal)
بازسازی کامل سیستم Authentication پروژه زیتو (Xi2) با رویکرد Business-First و Clean Architecture. هدف ایجاد سیستم تمیز، حرفه‌ای، کاملاً رسپانسیو و بدون فایل اضافی است که سه نوع کاربر را پشتیبانی کند.

## 📋 تحلیل وضعیت فعلی (Current State Analysis)
بعد از بررسی دقیق پروژه، مشکلات زیر شناسایی شدند:
- فایل‌های متعدد و پراکنده برای authentication
- عدم responsive design مناسب
- کدهای تکراری و غیرماژولار
- عدم سازگاری با business model پروژه
- باگ‌های متعدد در UI و عملکرد

## 🎯 Business Model هدف (Target Business Model)

### نوع کاربران:
```
1. کاربر میهمان (Guest User)
   - حداکثر 10 آپلود
   - بدون ثبت‌نام
   - شناسایی با Device/IP
   - بدون امکان SMS
   - محدودیت‌ها قابل تنظیم در پنل ادمین

2. کاربر پلاس رایگان (Plus User)
   - ثبت‌نام با موبایل + OTP
   - آپلود نامحدود
   - داشبورد کاربری
   - مدیریت فایل‌ها
   - امکان SMS

3. کاربر پریمیوم (Premium User)
   - صفحه "در حال توسعه"
   - آماده برای ویژگی‌های آینده
```

## 🏗️ معماری سیستم (System Architecture)

### ساختار فایل‌ها (CLEAN - بدون فایل اضافی):
```
src/
├── includes/
│   ├── persian-utils.php          ← استفاده از همان کلاس موجود
│   ├── auth-manager.php           ← مدیر اصلی احراز هویت
│   ├── guest-manager.php          ← مدیریت کاربران میهمان
│   └── session-handler.php        ← مدیریت session ها
├── api/auth/
│   ├── guest-upload.php           ← API آپلود میهمان
│   ├── register.php               ← ثبت‌نام (بازنویسی کامل)
│   ├── login.php                  ← ورود (بازنویسی کامل)
│   ├── verify-otp.php             ← تایید OTP (بازنویسی کامل)
│   ├── logout.php                 ← خروج
│   └── user-status.php            ← بررسی وضعیت کاربر
├── assets/js/
│   ├── auth-system.js             ← سیستم authentication (یکپارچه)
│   └── persian-input.js           ← مدیریت input های فارسی
└── assets/css/
    └── auth-responsive.css        ← استایل کاملاً responsive
```

### Database Schema (مرتب و حرفه‌ای):
```sql
-- جدول کاربران (بهبود یافته)
ALTER TABLE users ADD COLUMN IF NOT EXISTS user_type ENUM('guest', 'plus', 'premium') DEFAULT 'plus';
ALTER TABLE users ADD COLUMN IF NOT EXISTS device_id VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL;

-- جدول تنظیمات کاربر میهمان
CREATE TABLE IF NOT EXISTS guest_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    max_uploads INT DEFAULT 10,
    max_file_size INT DEFAULT 10485760,
    allowed_extensions TEXT DEFAULT 'jpg,jpeg,png,gif,webp',
    expires_days INT DEFAULT 30,
    updated_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول آپلودهای میهمان
CREATE TABLE IF NOT EXISTS guest_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    upload_count INT DEFAULT 1,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_id (device_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_expires_at (expires_at)
);

-- جدول تنظیمات کاربر پلاس
CREATE TABLE IF NOT EXISTS plus_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    max_uploads INT DEFAULT -1,
    max_file_size INT DEFAULT 52428800,
    allowed_extensions TEXT DEFAULT 'jpg,jpeg,png,gif,webp,pdf',
    storage_quota BIGINT DEFAULT -1,
    updated_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- تنظیمات پیش‌فرض
INSERT IGNORE INTO guest_settings (id, max_uploads, max_file_size) VALUES (1, 10, 10485760);
INSERT IGNORE INTO plus_settings (id, max_uploads, max_file_size) VALUES (1, -1, 52428800);
```

## 📱 UI/UX Design مطابق خواسته

### صفحه اصلی (بدون لاگین):
```html
<!-- Header Section -->
<header class="responsive-header">
    <div class="logo">زیتو Xi2</div>
    <nav class="main-nav">
        <button id="loginBtn" class="btn-primary">ورود</button>
        <button id="registerBtn" class="btn-secondary">کاربر پلاس رایگان</button>
    </nav>
</header>

<!-- Hero Section با تبلیغات جذاب -->
<section class="hero-section">
    <h1>سریع‌ترین پلتفرم اشتراک‌گذاری تصاویر</h1>
    <p>آپلود رایگان حتی بدون ثبت‌نام!</p>
    <div class="cta-buttons">
        <button class="upload-guest">آپلود سریع (میهمان)</button>
        <button class="register-plus">عضویت رایگان</button>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="feature-card">
        <h3>کاربر میهمان</h3>
        <ul>
            <li>10 آپلود رایگان</li>
            <li>بدون نیاز به ثبت‌نام</li>
            <li>دسترسی فوری</li>
        </ul>
    </div>
    <div class="feature-card highlighted">
        <h3>کاربر پلاس رایگان</h3>
        <ul>
            <li>آپلود نامحدود</li>
            <li>داشبورد شخصی</li>
            <li>مدیریت فایل‌ها</li>
            <li>پشتیبانی SMS</li>
        </ul>
        <button class="btn-primary">همین الان عضو شوید</button>
    </div>
</div>
```

### صفحه اصلی (بعد از لاگین):
```html
<header class="responsive-header logged-in">
    <div class="logo">زیتو Xi2</div>
    <div class="user-section">
        <div class="user-avatar" id="userAvatar">
            <img src="default-avatar.png" alt="آواتار">
            <span class="user-name">علی احمدی</span>
        </div>
        <!-- Dropdown Menu -->
        <div class="user-dropdown" id="userDropdown">
            <a href="#profile">پروفایل کاربری</a>
            <a href="#dashboard">محیط کاربری</a>
            <a href="#premium">تبدیل به پریمیوم</a>
            <a href="#logout">خروج</a>
        </div>
    </div>
</header>

<!-- محتوای اصلی بدون تبلیغات -->
<main class="clean-interface">
    <!-- فقط ابزارهای آپلود و مدیریت -->
</main>
```

## 🔧 مراحل پیاده‌سازی (Implementation Steps)

### مرحله 1: Core Classes (بدون تکرار)
```php
// فایل: src/includes/auth-manager.php
class AuthManager {
    private static $instance = null;
    private $db;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تشخیص نوع کاربر (میهمان، پلاس، پریمیوم)
     */
    public function detectUserType()
    
    /**
     * مدیریت session کاربران مختلف
     */
    public function manageUserSession($userType, $userData = null)
    
    /**
     * بررسی محدودیت‌های کاربر میهمان
     */
    public function checkGuestLimitations($deviceId)
    
    /**
     * ثبت‌نام کاربر پلاس
     */
    public function registerPlusUser($userData)
    
    /**
     * ورود کاربر پلاس
     */
    public function loginPlusUser($credentials)
}
```

### مرحله 2: Guest Management System
```php
// فایل: src/includes/guest-manager.php
class GuestManager {
    /**
     * ایجاد device ID منحصربفرد
     */
    public function generateDeviceId()
    
    /**
     * بررسی محدودیت آپلود میهمان
     */
    public function checkUploadLimit($deviceId)
    
    /**
     * ثبت آپلود میهمان
     */
    public function recordGuestUpload($deviceId, $fileData)
    
    /**
     * دریافت تنظیمات میهمان از پنل ادمین
     */
    public function getGuestSettings()
}
```

### مرحله 3: Frontend System (یکپارچه و تمیز)
```javascript
// فایل: src/assets/js/auth-system.js
class Xi2AuthSystem {
    constructor() {
        this.userType = null;
        this.deviceId = this.getOrCreateDeviceId();
        this.init();
    }
    
    /**
     * تشخیص نوع کاربر
     */
    detectUserType()
    
    /**
     * مدیریت UI بر اساس نوع کاربر
     */
    updateUIForUserType(userType)
    
    /**
     * مدیریت فرم ورود/ثبت‌نام
     */
    handleAuthForms()
    
    /**
     * مدیریت آپلود میهمان
     */
    handleGuestUpload()
    
    /**
     * مدیریت منوی کاربر
     */
    handleUserDropdown()
}
```

### مرحله 4: Responsive CSS Framework
```css
/* فایل: src/assets/css/auth-responsive.css */

/* Mobile First Approach */
.responsive-header {
    /* موبایل */
    display: flex;
    flex-direction: column;
}

@media (min-width: 768px) {
    .responsive-header {
        /* تبلت */
        flex-direction: row;
        justify-content: space-between;
    }
}

@media (min-width: 1024px) {
    .responsive-header {
        /* دسکتاپ */
        padding: 0 2rem;
    }
}

/* فرم‌های کاملاً responsive */
.auth-modal {
    /* Mobile: full screen */
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
}

@media (min-width: 768px) {
    .auth-modal {
        /* Desktop: centered modal */
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 400px;
        height: auto;
    }
}
```

## 🔍 کنترل کیفیت و تست (Quality Control)

### الزامات کیفی:
- [ ] صفر فایل اضافی - فقط فایل‌های ضروری
- [ ] کاملاً responsive در تمام سایزها
- [ ] بدون تکرار کد
- [ ] استفاده از PersianUtils موجود
- [ ] Clean Architecture
- [ ] Error-free execution
- [ ] Fast loading (<2s)

### تست‌های لازم:
```php
// فایل: test-business-flow.php
// تست کامل سناریوی business:
1. کاربر میهمان: آپلود → محدودیت → پیام تشویق ثبت‌نام
2. ثبت‌نام: موبایل → OTP → فعال‌سازی → لاگین خودکار
3. کاربر پلاس: آپلود نامحدود → داشبورد → مدیریت
4. تست responsive در سایزهای مختلف
5. تست تبدیل اعداد فارسی
```

## 📱 Admin Panel Integration

### صفحه تنظیمات کاربر میهمان:
```php
// admin/settings/guest-users.php
- تنظیم حداکثر آپلود (پیش‌فرض: 10)
- تنظیم حداکثر حجم فایل
- تنظیم مدت انقضا
- مشاهده آمار کاربران میهمان
```

### صفحه تنظیمات کاربر پلاس:
```php
// admin/settings/plus-users.php  
- تنظیم محدودیت‌های پلاس
- مدیریت فرمت‌های مجاز
- آمار کاربران پلاس
```

### صفحه آماده پریمیوم:
```php
// admin/settings/premium-users.php
- صفحه "در حال توسعه"
- UI آماده برای ویژگی‌های آینده
```

## 🎯 اولویت‌بندی توسعه

### فاز 1 (اولویت بحرانی):
1. **Clean Architecture**: حذف فایل‌های اضافی، ساختار تمیز
2. **Guest System**: سیستم کاربر میهمان کامل
3. **Responsive Design**: موبایل، تبلت، دسکتاپ
4. **Persian Utils Integration**: استفاده صحیح از کلاس موجود

### فاز 2 (اولویت بالا):
1. **Plus User System**: ثبت‌نام، لاگین، OTP
2. **UI State Management**: تغییر رابط بر اساس نوع کاربر
3. **Admin Settings**: پنل تنظیمات انواع کاربر
4. **Testing**: تست کامل business flow

### فاز 3 (تکمیلی):
1. **Premium Placeholder**: صفحات آماده پریمیوم
2. **Performance Optimization**: بهینه‌سازی سرعت
3. **Advanced Features**: ویژگی‌های پیشرفته

## ⚠️ نکات حیاتی برای Copilot

### 🚨 الزامات غیرقابل تغییر:
1. **هیچ فایل اضافی نسازید** - فقط فایل‌های ذکر شده
2. **کاملاً responsive باشد** - تست در سایزهای مختلف
3. **از PersianUtils موجود استفاده کنید** - تکرار نکنید
4. **Clean Architecture** - هر کلاس یک مسئولیت
5. **Business-First** - همه‌چیز حول 3 نوع کاربر طراحی شود

### 📋 نکات پیاده‌سازی:
1. ابتدا Database Schema را بررسی کنید
2. PersianUtils را import کنید، دوباره ننویسید
3. Responsive CSS را اولویت اول قرار دهید
4. UI States را برای هر نوع کاربر طراحی کنید
5. فایل‌های تست کوتاه و مفید بنویسید

## 🎊 خروجی مورد انتظار

### ✅ تحویل نهایی:
1. **سیستم کاملاً عملیاتی** بدون باگ
2. **Architecture تمیز** بدون فایل اضافی  
3. **Responsive 100%** در همه دستگاه‌ها
4. **Business Model کامل** (میهمان، پلاس، پریمیوم)
5. **Admin Panel Integration** برای تنظیمات
6. **Persian Utils Integration** بدون تکرار
7. **Documentation کامل** و مختصر

---

**هدف نهایی**: سیستم حرفه‌ای، تمیز، responsive و کاملاً عملیاتی که business model پروژه زیتو را به طور کامل پشتیبانی کند و هیچ فایل اضافی نداشته باشد.
```

---

🎯 **پرامپت شماره 3 آماده ارسال است!**

**ویژگی‌های کلیدی این پرامپت:**
- ✅ **Business-First Approach** - مطابق سناریوی شما
- ✅ **Clean Architecture** - بدون فایل اضافی
- ✅ **Responsive Priority** - موبایل فرست
- ✅ **Database Schema مرتب** - بدون پاتی‌پاتی
- ✅ **PersianUtils Integration** - بدون تکرار
- ✅ **3-Tier User System** - میهمان، پلاس، پریمیوم
- ✅ **Admin Panel Ready** - برای تنظیمات

بنام خدای نزدیک ✨

## 🎯 **PROMPT شماره 2 برای GitHub Copilot - تبدیل اعداد فارسی و بازسازی Authentication**

```markdown
## 🎯 هدف (Goal)
بازسازی کامل سیستم Authentication پروژه زیتو (Xi2) و پیاده‌سازی تابع جامع تبدیل اعداد فارسی به انگلیسی برای تمام بخش‌های ورودی شماره موبایل و کدهای OTP

## 📋 وضعیت فعلی و مشکلات (Current Issues)
بعد از بررسی فایل‌های موجود، مشکلات زیر شناسایی شدند:

### ❌ مشکلات اصلی:
1. **تبدیل اعداد فارسی**: در ارسال SMS تست و جستجو، شماره فارسی تشخیص نمی‌شود
2. **API های ناقص**: register.php و login.php فقط skeleton هستند
3. **Frontend Bugs**: مدیریت ناقص فرم‌ها و validation
4. **Session Management**: مدیریت session ناقص در frontend
5. **OTP Handling**: مشکل در تایید و ارسال مجدد OTP

### 📱 قسمت‌های نیازمند تبدیل فارسی به انگلیسی:
- صفحه لاگین: شماره موبایل + کد OTP
- صفحه ثبت‌نام: شماره موبایل + کد OTP  
- پنل ادمین SMS: شماره تست + جستجوی شماره
- همه input های مربوط به اعداد

## 🔧 مراحل اجرا (Implementation Steps)

### مرحله 1: ایجاد Helper Functions مشترک
```php
// فایل: src/includes/persian-utils.php
class PersianUtils {
    /**
     * تبدیل اعداد فارسی/عربی به انگلیسی
     * @param string $input متن ورودی
     * @return string متن با اعداد انگلیسی
     */
    public static function convertToEnglishNumbers($input)
    
    /**
     * تبدیل متن کامل (شامل اعداد و حروف)
     * @param string $input متن ورودی  
     * @return string متن پاک شده
     */
    public static function sanitizeInput($input)
    
    /**
     * اعتبارسنجی شماره موبایل ایرانی
     * @param string $mobile شماره موبایل
     * @return string|false شماره استاندارد یا false
     */
    public static function validateMobile($mobile)
    
    /**
     * اعتبارسنجی کد OTP
     * @param string $otp کد OTP
     * @return string|false کد استاندارد یا false  
     */
    public static function validateOTP($otp)
}
```

### مرحله 2: بازسازی کامل API Authentication  
```php
// فایل: src/api/auth/register.php
// نیازمندی‌ها:
- Input validation کامل با PersianUtils
- Password hashing امن
- تولید و ارسال OTP واقعی
- Error handling حرفه‌ای
- Response format استاندارد

// فایل: src/api/auth/login.php
// نیازمندی‌ها:
- بررسی credentials با PersianUtils
- Session token generation
- آمارگیری کاربر
- مدیریت last_login
- Security headers

// فایل: src/api/auth/verify-otp.php
// نیازمندی‌ها:
- تایید OTP با PersianUtils
- فعال‌سازی حساب
- ایجاد session کامل
- تنظیمات اولیه کاربر
```

### مرحله 3: بازسازی Frontend Authentication
```javascript
// فایل: src/assets/js/auth.js
// نیازمندی‌ها:
- تابع convertPersianToEnglish جامع
- مدیریت حقیقی فرم‌ها
- Session management
- Auto-completion فیلدها
- Error handling بهتر
- Loading states
- OTP timer واقعی
- Retry mechanism

// مدیریت Input های عددی:
- Real-time conversion اعداد فارسی
- Paste handling برای کپی شماره
- Auto-format شماره موبایل
- Validation در زمان واقعی
```

### مرحله 4: بروزرسانی Admin Panel SMS
```php
// فایل: admin/settings/sms.php
// نیازمندی‌ها:  
- استفاده از PersianUtils در تست SMS
- تبدیل خودکار شماره تست
- بهبود error messages

// فایل: admin/logs/sms-logs.php  
// نیازمندی‌ها:
- جستجوی متنی در پیام‌ها
- فیلتر بر اساس شماره (با تبدیل فارسی)
- نمایش بهتر نتایج
```

### مرحله 5: تست و Integration
```php
// فایل‌های تست:
- test-persian-conversion.php
- test-authentication-flow.php  
- test-mobile-validation.php
```

## 📱 طراحی UI/UX بهبود یافته

### Frontend Improvements:
```css
/* بهبود فرم‌ها */
.form-group {
    position: relative;
    margin-bottom: 1.5rem;
}

.input-with-icon {
    /* نمایش آیکون کشور برای موبایل */
    /* نمایش وضعیت validation */
}

.otp-input-group {
    /* 6 فیلد جداگانه برای OTP */
    /* Auto-focus next field */
}

.loading-button {
    /* نمایش spinner هنگام پردازش */
}
```

### JavaScript Enhancements:
```javascript
// ویژگی‌های جدید:
- Auto-submit OTP هنگام تکمیل 6 رقم
- Copy/Paste handling برای OTP
- Keyboard navigation بهتر  
- Form persistence در localStorage
- Progressive loading
```

## 🔍 کنترل کیفیت (Quality Control)
- [ ] تست تبدیل اعداد فارسی در همه scenarios
- [ ] تست احراز هویت کامل (register → OTP → login)
- [ ] تست session management
- [ ] تست error handling
- [ ] تست responsive design
- [ ] تست accessibility
- [ ] تست performance

## 📊 لاگ‌گیری و Debug
```php
// در هر مرحله:
error_log('Xi2 Auth: [عملیات] - Mobile: [شماره] - Status: [وضعیت]');

// مثال:
error_log('Xi2 Auth: Persian Conversion - Input: ۰۹۱۲۳۴۵۶۷۸۹ - Output: 09123456789');
error_log('Xi2 Auth: Register - Mobile: 09123456789 - Status: OTP Sent');
```

## 🌐 پشتیبانی کامل فارسی
- تمام error messages فارسی
- RTL support در فرم‌ها
- Persian placeholder texts
- فونت Vazirmatn
- تاریخ شمسی در لاگ‌ها

## ⚠️ نکات ویژه برای Copilot

### 1. اولویت‌بندی:
```
اولویت 1: PersianUtils class (تابع تبدیل اعداد)
اولویت 2: Backend API بازسازی کامل
اولویت 3: Frontend authentication بهبود  
اولویت 4: Admin panel integration
اولویت 5: Testing و debugging
```

### 2. کیفیت کد:
```
- استفاده از Design Patterns
- Error handling جامع
- Security best practices
- Comment کردن کدهای پیچیده
- Consistent naming convention
```

### 3. تست‌پذیری:
```
- Mock data برای تست
- Unit test قابلیت‌ها
- Integration test flow کامل
- Debug endpoints برای development
```

### 4. مشکلات موجود که باید حل شود:
```
- مدیریت ناقص state در frontend
- API response handling ضعیف
- Session timeout نامناسب
- Error messages نامفهوم
- Loading states نامناسب
```

## 🚀 خروجی مورد انتظار
1. PersianUtils class کامل و تست شده
2. API های auth کاملاً عملیاتی  
3. Frontend authentication روان و بدون باگ
4. Admin panel SMS با تبدیل خودکار
5. مستندات کامل و راهنمای استفاده
6. فایل‌های تست و debug

---

**هدف نهایی**: سیستم احراز هویت کاملاً کارآمد با پشتیبانی کامل از اعداد فارسی و تجربه کاربری بی‌نقص برای کاربران ایرانی
```

---

🎯 **این پرامپت آماده ارسال به کوپایلوت است!**

**نکات کلیدی:**
- ✅ تشخیص دقیق مشکلات موجود  
- ✅ راه‌حل جامع برای تبدیل اعداد فارسی
- ✅ بازسازی کامل Authentication
- ✅ بهبود Frontend و Backend
- ✅ Integration با Admin Panel
- ✅ تست و کیفیت‌سنجی

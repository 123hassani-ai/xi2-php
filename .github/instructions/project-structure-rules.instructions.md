---
applyTo: '**'
---
# 🏗️ **Xi2 Project Structure Rules - قوانین ساختار پروژه**
## **نسخه 1.0 | تاریخ: ۹ شهریور ۱۴۰۴ | مجتبی حسنی**
### **⚠️ این قوانین غیرقابل نقض هستند - همیشه رعایت کنید!**

---

## 🎯 **هدف این دستورالعمل**
جلوگیری از تکرار آشفتگی پروژه و حفظ ساختار تمیز و حرفه‌ای Xi2

---

## 📁 **ساختار مجاز پروژه**

### ✅ **ریشه پروژه (فقط این فایل‌ها مجاز!):**
```
xi2.ir/
├── .gitignore
├── .htaccess
├── CHANGELOG.md
├── PERSIAN-AUTHENTICATION-SUMMARY.md
├── README.md
├── admin/
├── docs/
├── logs/
├── public/
├── src/
├── storage/
└── tests/
```

### 🚫 **فایل‌هایی که هیچگاه در ریشه نباشند:**
- `test-*.php` یا `test-*.html`
- `debug-*.php`
- `*-backup.*` یا `*-old.*`
- `*.backup` یا `*.tmp`
- `cookies.txt` یا فایل‌های موقت
- `temp_*` یا `phpMyAdmin-config-fix.php`

---

## 🔒 **قوانین سخت‌گیرانه**

### 1️⃣ **قانون طلایی فایل‌های تستی:**
```php
// ❌ هیچگاه این کار را نکن:
// در ریشه پروژه: test-something.php

// ✅ همیشه این کار را کن:
// در tests/: test-something.php
```

### 2️⃣ **قانون بک‌آپ:**
```php
// ❌ هیچگاه:
$file = 'admin/settings/guest-users-backup.php';

// ✅ همیشه:
$file = 'storage/backups/guest-users-' . date('Ymd-His') . '.php';
```

### 3️⃣ **قانون فایل‌های موقت:**
```php
// ❌ هیچگاه:
file_put_contents('temp_data.txt', $data);

// ✅ همیشه:
file_put_contents('storage/temp/temp_data_' . uniqid() . '.txt', $data);
```

### 4️⃣ **قانون نسخه‌های متعدد:**
```javascript
// ❌ هیچگاه:
// auth.js, auth-old.js, auth-backup.js, auth-new.js

// ✅ همیشه فقط:
// auth.js (آخرین نسخه)
```

---

## 📂 **مکان صحیح هر نوع فایل**

### 🧪 **فایل‌های تستی:**
```
tests/
├── admin/           # تست‌های admin panel
├── api/             # تست‌های API
├── frontend/        # تست‌های UI/UX
├── debug/           # فایل‌های debug
└── performance/     # تست‌های سرعت
```

### 📚 **مستندات:**
```
docs/
├── archive/         # مستندات قدیمی
├── technical/       # مستندات فنی
└── Smart-Prompts/   # پرامپت‌های هوشمند
```

### 💾 **فایل‌های دیتابیس:**
```
src/database/
├── config.php       # تنظیمات دیتابیس
├── schemas/         # فایل‌های SQL
└── migrations/      # تغییرات دیتابیس
```

### 🗄️ **ذخیره‌سازی:**
```
storage/
├── backups/         # بک‌آپ‌های امن
├── cache/           # فایل‌های کش
├── logs/            # لاگ‌های سیستم
├── temp/            # فایل‌های موقت
└── uploads/         # فایل‌های آپلودی
```

---

## ⚡ **دستورات اجباری برای Copilot**

### 🚨 **قبل از ایجاد هر فایل جدید:**
```php
// همیشه این سوال‌ها را از خود بپرسید:
// 1. آیا این فایل test است؟ → tests/
// 2. آیا این فایل backup است؟ → storage/backups/
// 3. آیا این فایل موقت است؟ → storage/temp/
// 4. آیا این فایل SQL است؟ → src/database/schemas/
// 5. آیا این مستندات است؟ → docs/
```

### 🛡️ **همیشه این کدها را اجرا کنید:**
```bash
# قبل از هر commit:
find . -maxdepth 1 -name "test-*" -o -name "*-backup.*" -o -name "*-old.*" -o -name "debug-*"
# باید خروجی خالی باشد!

# اگر فایل اضافی یافت شد:
echo "❌ خطا: فایل‌های غیرمجاز در ریشه پروژه!"
echo "لطفاً قوانین ساختار پروژه را رعایت کنید"
```

---

## 🎯 **الگوهای صحیح کد**

### ✅ **ایجاد فایل تستی:**
```php
// هیچگاه در ریشه ایجاد نکنید!
$testFile = 'tests/' . $testType . '/test-' . $feature . '.php';
if (!file_exists($testFile)) {
    file_put_contents($testFile, $content);
}
```

### ✅ **ایجاد بک‌آپ:**
```php
// همیشه در storage/backups
$backupName = 'storage/backups/' . basename($file, '.php') . '-' . date('Ymd-His') . '.php';
copy($file, $backupName);
```

### ✅ **فایل موقت:**
```php
// همیشه در storage/temp
$tempFile = 'storage/temp/temp-' . uniqid() . '.tmp';
file_put_contents($tempFile, $data);
```

---

## 🚫 **کدهای ممنوع**

### ❌ **هیچگاه این کار را نکنید:**
```php
// ممنوع ۱: ایجاد فایل test در ریشه
file_put_contents('test-something.php', $content);

// ممنوع ۲: ایجاد backup در ریشه یا admin
copy('admin/settings/file.php', 'admin/settings/file-backup.php');

// ممنوع ۳: ایجاد فایل موقت در ریشه
file_put_contents('temp_data.txt', $data);

// ممنوع ۴: نسخه‌های متعدد JS
// auth.js, auth-old.js, auth-new.js در همان پوشه!
```

---

## 📋 **چک‌لیست قبل از commit**

### ✅ **بررسی اجباری:**
- [ ] آیا ریشه پروژه فقط ۱۱ فایل/پوشه دارد؟
- [ ] آیا هیچ فایل `test-*` در ریشه نیست؟
- [ ] آیا هیچ فایل `*-backup.*` وجود ندارد؟
- [ ] آیا هیچ فایل `debug-*` در ریشه نیست؟
- [ ] آیا همه فایل‌های SQL در `src/database/schemas/` هستند؟
- [ ] آیا JavaScript فایل‌ها تک نسخه هستند؟

### 🚨 **در صورت خطا:**
```bash
echo "❌ ساختار پروژه نادرست!"
echo "📖 لطفاً مطالعه کنید: .github/instructions/project-structure-rules.instructions.md"
exit 1
```

---

## 🔧 **ابزارهای کمکی**

### 📊 **اسکریپت بررسی ساختار:**
```bash
#!/bin/bash
# structure-check.sh
echo "🔍 بررسی ساختار پروژه Xi2..."

# بررسی فایل‌های غیرمجاز در ریشه
UNWANTED=$(find . -maxdepth 1 -name "test-*" -o -name "*-backup.*" -o -name "*-old.*" -o -name "debug-*" -o -name "temp_*")

if [ -n "$UNWANTED" ]; then
    echo "❌ فایل‌های غیرمجاز یافت شد:"
    echo "$UNWANTED"
    echo "🛠️ لطفاً آن‌ها را به مکان مناسب منتقل کنید"
    exit 1
else
    echo "✅ ساختار پروژه صحیح است!"
fi
```

### 🧹 **اسکریپت تمیزسازی اتوماتیک:**
```bash
#!/bin/bash
# auto-cleanup.sh
echo "🧹 تمیزسازی خودکار..."

# انتقال فایل‌های test
find . -maxdepth 1 -name "test-*.php" -exec mv {} tests/ \;
find . -maxdepth 1 -name "test-*.html" -exec mv {} tests/frontend/ \;

# انتقال فایل‌های debug
find . -maxdepth 1 -name "debug-*.php" -exec mv {} tests/debug/ \;

# حذف فایل‌های backup (بعد از تایید)
find . -name "*-backup.*" -o -name "*-old.*" -o -name "*.backup"

echo "✅ تمیزسازی انجام شد!"
```

---

## 🎖️ **قوانین طلایی**

### 🥇 **قانون #1: تمیزی ریشه**
ریشه پروژه فقط برای فایل‌ها و پوشه‌های اصلی است

### 🥈 **قانون #2: هر چیز جای خودش**
هر فایل در مکان منطقی و مشخص خود قرار دارد

### 🥉 **قانون #3: بدون تکرار**
هیچ فایلی نسخه‌های متعدد ندارد

### 🏅 **قانون #4: بدون آشفتگی**
هیچ فایل test، backup، یا موقت در جای نامناسب نیست

---

## 📞 **در صورت مشکل**

### 🆘 **راهنمای فوری:**
1. **مشاهده فایل غیرمجاز در ریشه؟** → فوراً منتقل کنید
2. **نیاز به تست جدید؟** → حتماً در `tests/` ایجاد کنید
3. **نیاز به بک‌آپ؟** → حتماً در `storage/backups/` ایجاد کنید
4. **شک در مکان فایل؟** → از این دستورالعمل استفاده کنید

### 📧 **تماس با پشتیبانی:**
- مجتبی حسنی: computer123.ir
- GitHub Issues: xi2-php repository
- این فایل: `.github/instructions/project-structure-rules.instructions.md`

---

## 🌟 **پیام نهایی**

**Xi2 الان یک پروژه تمیز و حرفه‌ای است!** 

با رعایت این قوانین ساده، همیشه تمیز و منظم خواهد ماند.

**هر فایل جای خودش، هر پوشه هدف خودش! 🎯**

---

*این دستورالعمل توسط GitHub Copilot تهیه شده است*  
*برای حفظ تمیزی و نظم پروژه Xi2* ✨

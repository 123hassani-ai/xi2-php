---
applyTo: '**'
---
# 🚨 **Xi2 Quick Rules - قوانین سریع**
### **یادآوری فوری برای GitHub Copilot**

---

## ⚡ **قوانین ۳۰ ثانیه‌ای**

### 🚫 **هیچگاه در ریشه پروژه:**
- `test-*.php` یا `test-*.html`
- `debug-*.php` یا `*-debug.*`
- `*-backup.*` یا `*-old.*`
- `temp_*` یا `*.tmp`

### ✅ **همیشه در مکان صحیح:**
- فایل‌های تست → `tests/`
- فایل‌های بک‌آپ → `storage/backups/`
- فایل‌های موقت → `storage/temp/`
- فایل‌های SQL → `src/database/schemas/`

### 🎯 **یک قانون طلایی:**
**اگر مطمئن نیستی کجا بسازی، از ریشه دور شو!**

---

## 🔥 **چک سریع قبل از commit:**
```bash
find . -maxdepth 1 -name "test-*" -o -name "*-backup.*" -o -name "debug-*"
# باید خالی باشد!
```

---

*کوتاه، مفید، قطعی! ✨*

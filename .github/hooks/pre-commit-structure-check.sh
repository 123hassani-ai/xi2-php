#!/bin/bash
# Xi2 Pre-commit Hook
# جلوگیری از commit فایل‌های غیرمجاز

echo "🔍 بررسی ساختار پروژه Xi2..."

# بررسی فایل‌های غیرمجاز در ریشه
UNWANTED=$(find . -maxdepth 1 -name "test-*.php" -o -name "test-*.html" -o -name "debug-*.php" -o -name "*-backup.*" -o -name "*-old.*" -o -name "temp_*" | head -10)

if [ -n "$UNWANTED" ]; then
    echo "❌ خطا: فایل‌های غیرمجاز در ریشه پروژه یافت شد:"
    echo "$UNWANTED"
    echo ""
    echo "🛠️  راه‌حل:"
    echo "   • فایل‌های test-* را به tests/ منتقل کنید"
    echo "   • فایل‌های debug-* را به tests/debug/ منتقل کنید"  
    echo "   • فایل‌های *-backup.* را به storage/backups/ منتقل کنید"
    echo "   • فایل‌های temp_* را حذف یا به storage/temp/ منتقل کنید"
    echo ""
    echo "📖 راهنما: .github/instructions/project-structure-rules.instructions.md"
    exit 1
fi

# بررسی تعداد فایل‌ها در ریشه (نباید بیش از 12 باشد)
ROOT_FILES=$(ls -1 | wc -l | xargs)
if [ "$ROOT_FILES" -gt 12 ]; then
    echo "⚠️  هشدار: تعداد فایل‌ها در ریشه ($ROOT_FILES) زیاد است"
    echo "   مطلوب: حداکثر 11-12 فایل/پوشه"
fi

echo "✅ ساختار پروژه صحیح است!"
exit 0

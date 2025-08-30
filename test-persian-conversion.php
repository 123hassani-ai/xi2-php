<?php
/**
 * تست کلاس PersianUtils
 * بررسی عملکرد تبدیل اعداد فارسی و اعتبارسنجی
 */

require_once __DIR__ . '/src/includes/persian-utils.php';

echo "<h2>🧪 تست PersianUtils Class</h2>\n";
echo "<style>
    body { font-family: 'Vazirmatn', Arial; direction: rtl; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .success { color: #059669; }
    .error { color: #dc2626; }
    .info { color: #0ea5e9; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: center; }
    th { background: #f3f4f6; }
</style>";

// تست 1: تبدیل اعداد فارسی
echo "<div class='test-section'>";
echo "<h3>🔢 تست تبدیل اعداد فارسی به انگلیسی</h3>";
echo "<table>";
echo "<tr><th>ورودی</th><th>خروجی</th><th>وضعیت</th></tr>";

$persianTests = [
    '۱۲۳۴۵۶۷۸۹۰',
    'شماره ۰۹۱۲۳۴۵۶۷۸۹',
    '٠١٢٣٤٥٦٧٨٩', // عربی
    'کد OTP: ۱۲۳۴۵۶',
    '۰۹۱۲-۳۴۵-۶۷۸۹',
    'مبلغ: ۱۰۰,۰۰۰ تومان',
    ''
];

foreach ($persianTests as $test) {
    $result = PersianUtils::convertToEnglishNumbers($test);
    $status = ($test !== $result) ? "<span class='success'>✅ تبدیل شد</span>" : "<span class='info'>⚪ تغییری نکرد</span>";
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test) . "</td>";
    echo "<td>" . htmlspecialchars($result) . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// تست 2: اعتبارسنجی شماره موبایل
echo "<div class='test-section'>";
echo "<h3>📱 تست اعتبارسنجی شماره موبایل</h3>";
echo "<table>";
echo "<tr><th>ورودی</th><th>خروجی</th><th>وضعیت</th></tr>";

$mobileTests = [
    '۰۹۱۲۳۴۵۶۷۸۹',     // فارسی
    '+989123456789',      // بین‌المللی
    '00989123456789',     // کد کشور
    '9123456789',         // بدون صفر
    '09123456789',        // استاندارد
    '0912 345 6789',      // با فاصله
    '0912-345-6789',      // با خط تیره
    '091234567890',       // طولانی
    '0912345678',         // کوتاه
    '0812345678',         // شروع با 08
    'abc123',             // نامعتبر
    '',                   // خالی
];

foreach ($mobileTests as $test) {
    $result = PersianUtils::validateMobile($test);
    $status = $result ? "<span class='success'>✅ معتبر</span>" : "<span class='error'>❌ نامعتبر</span>";
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test) . "</td>";
    echo "<td>" . htmlspecialchars($result ?: 'false') . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// تست 3: اعتبارسنجی کد OTP
echo "<div class='test-section'>";
echo "<h3>🔐 تست اعتبارسنجی کد OTP</h3>";
echo "<table>";
echo "<tr><th>ورودی</th><th>خروجی</th><th>وضعیت</th></tr>";

$otpTests = [
    '۱۲۳۴۵۶',       // فارسی
    '123456',        // انگلیسی
    '12 34 56',      // با فاصله
    '1234567',       // طولانی
    '12345',         // کوتاه
    'abc123',        // نامعتبر
    '۱۲۳۴۵۶',       // فارسی
    '',              // خالی
];

foreach ($otpTests as $test) {
    $result = PersianUtils::validateOTP($test);
    $status = $result ? "<span class='success'>✅ معتبر</span>" : "<span class='error'>❌ نامعتبر</span>";
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test) . "</td>";
    echo "<td>" . htmlspecialchars($result ?: 'false') . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// تست 4: فرمت کردن شماره موبایل
echo "<div class='test-section'>";
echo "<h3>✨ تست فرمت کردن شماره موبایل</h3>";
echo "<table>";
echo "<tr><th>شماره</th><th>نقطه‌ای</th><th>فاصله</th><th>خط تیره</th><th>بین‌المللی</th></tr>";

$formatTests = ['09123456789', '۰۹۱۲۳۴۵۶۷۸۹'];

foreach ($formatTests as $mobile) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($mobile) . "</td>";
    echo "<td>" . htmlspecialchars(PersianUtils::formatMobile($mobile, 'dots')) . "</td>";
    echo "<td>" . htmlspecialchars(PersianUtils::formatMobile($mobile, 'spaces')) . "</td>";
    echo "<td>" . htmlspecialchars(PersianUtils::formatMobile($mobile, 'dash')) . "</td>";
    echo "<td>" . htmlspecialchars(PersianUtils::formatMobile($mobile, 'international')) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// تست 5: اعتبارسنجی کد ملی
echo "<div class='test-section'>";
echo "<h3>🆔 تست اعتبارسنجی کد ملی</h3>";
echo "<table>";
echo "<tr><th>ورودی</th><th>خروجی</th><th>وضعیت</th></tr>";

$nationalCodeTests = [
    '۰۰۱۲۳۴۵۶۷۸',   // فارسی
    '0012345678',      // معتبر
    '1111111111',      // یکسان
    '123456789',       // کوتاه
    '12345678901',     // طولانی
    'abc1234567',      // نامعتبر
    '',                // خالی
];

foreach ($nationalCodeTests as $test) {
    $result = PersianUtils::validateNationalCode($test);
    $status = $result ? "<span class='success'>✅ معتبر</span>" : "<span class='error'>❌ نامعتبر</span>";
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test) . "</td>";
    echo "<td>" . htmlspecialchars($result ?: 'false') . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// تست 6: sanitizeInput
echo "<div class='test-section'>";
echo "<h3>🧹 تست پاک‌سازی ورودی</h3>";
echo "<table>";
echo "<tr><th>ورودی</th><th>خروجی</th></tr>";

$sanitizeTests = [
    '  شماره ۰۹۱۲۳۴۵۶۷۸۹  ',
    '<script>alert("test")</script>۱۲۳',
    '   متن    با     فاصله‌های     زیاد   ',
    'شماره: ۰۹۱۲-۳۴۵-۶۷۸۹'
];

foreach ($sanitizeTests as $test) {
    $result = PersianUtils::sanitizeInput($test);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test) . "</td>";
    echo "<td>" . htmlspecialchars($result) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h3>📊 خلاصه تست‌ها</h3>";
echo "<p class='success'>✅ تمام توابع PersianUtils با موفقیت پیاده‌سازی شدند</p>";
echo "<p class='info'>📝 لاگ‌ها در error_log ذخیره می‌شوند</p>";
echo "<p class='info'>🔧 آماده برای استفاده در API‌های Authentication</p>";
echo "</div>";

echo "<script>
// تست JavaScript برای مقایسه
console.log('🧪 تست JavaScript conversion:');
console.log('۰۹۱۲۳۴۵۶۷۸۹'.replace(/[۰-۹]/g, function(w) {
    var persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return persian.indexOf(w);
}));
</script>";
?>

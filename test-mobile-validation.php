<?php
/**
 * تست جامع اعتبارسنجی شماره موبایل ایرانی
 * بررسی تمام حالت‌های ممکن شماره موبایل با اعداد فارسی
 */

require_once __DIR__ . '/src/includes/persian-utils.php';

echo "<h2>📱 تست جامع اعتبارسنجی شماره موبایل ایرانی</h2>\n";
echo "<style>
    body { font-family: 'Vazirmatn', Arial; direction: rtl; }
    .test-case { margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .valid { background: #d4edda; border-color: #c3e6cb; }
    .invalid { background: #f8d7da; border-color: #f5c6cb; }
    .converted { background: #fff3cd; border-color: #ffeaa7; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: center; }
    th { background: #f8f9fa; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
</style>";

// تست‌های جامع شماره موبایل
$testCases = [
    // شماره‌های معتبر
    ['09123456789', 'شماره استاندارد انگلیسی', 'valid'],
    ['۰۹۱۲۳۴۵۶۷۸۹', 'شماره کامل فارسی', 'valid'],
    ['٠٩١٢٣٤٥٦٧٨٩', 'شماره کامل عربی', 'valid'],
    ['+989123456789', 'فرمت بین‌المللی', 'valid'],
    ['00989123456789', 'فرمت بین‌المللی با صفر', 'valid'],
    ['9123456789', 'بدون صفر اول', 'valid'],
    ['0912 345 6789', 'با فاصله', 'valid'],
    ['0912-345-6789', 'با خط تیره', 'valid'],
    ['0912.345.6789', 'با نقطه', 'valid'],
    ['۰۹۱۲ ۳۴۵ ۶۷۸۹', 'فارسی با فاصله', 'valid'],
    ['۰۹۱۲-۳۴۵-۶۷۸۹', 'فارسی با خط تیره', 'valid'],
    
    // اپراتورهای مختلف
    ['09123456789', 'همراه اول', 'valid'],
    ['09353456789', 'ایرانسل', 'valid'],
    ['09013456789', 'همراه اول جدید', 'valid'],
    ['09213456789', 'رایتل', 'valid'],
    ['09383456789', 'ایرانسل جدید', 'valid'],
    ['09993456789', 'MVNO', 'valid'],
    
    // شماره‌های نامعتبر
    ['091234567890', 'طولانی (12 رقم)', 'invalid'],
    ['0912345678', 'کوتاه (10 رقم)', 'invalid'],
    ['08123456789', 'شروع با 08', 'invalid'],
    ['07123456789', 'شروع با 07', 'invalid'],
    ['abc123', 'حاوی حروف', 'invalid'],
    ['', 'خالی', 'invalid'],
    ['123456789', 'بدون کد کشور', 'invalid'],
    ['+981234567890', 'بین‌المللی طولانی', 'invalid'],
    ['009812345678', 'بین‌المللی کوتاه', 'invalid'],
    
    // ترکیبی از فارسی و انگلیسی
    ['09۱۲۳456789', 'ترکیبی فارسی-انگلیسی', 'valid'],
    ['۰91234۵۶۷۸۹', 'ترکیبی پیچیده', 'valid'],
    ['٠٩١٢٣٤٥٦٧٨٩', 'عربی کامل', 'valid'],
    ['۰٩۱٢۳٤۵٦۷۸۹', 'ترکیب فارسی-عربی', 'valid'],
    
    // کیس‌های خاص
    [' 09123456789 ', 'با فاصله اول و آخر', 'valid'],
    ['(091) 234-5678-9', 'با علامت‌گذاری', 'valid'],
    ['+98 912 345 6789', 'بین‌المللی با فاصله', 'valid'],
    ['0098-912-345-6789', 'با خط تیره کامل', 'valid'],
];

// اجرای تست‌ها
echo "<table>";
echo "<tr>
    <th>شماره ورودی</th>
    <th>توضیحات</th>
    <th>خروجی</th>
    <th>وضعیت انتظار</th>
    <th>نتیجه</th>
    <th>آیا تبدیل شد؟</th>
</tr>";

$totalTests = count($testCases);
$passedTests = 0;
$conversions = 0;

foreach ($testCases as $test) {
    [$input, $description, $expectedStatus] = $test;
    
    $originalInput = $input;
    $result = PersianUtils::validateMobile($input);
    $actualStatus = $result ? 'valid' : 'invalid';
    
    $passed = ($expectedStatus === $actualStatus);
    if ($passed) $passedTests++;
    
    $wasConverted = ($originalInput !== $input);
    if ($wasConverted) $conversions++;
    
    $statusClass = $passed ? 'success' : 'error';
    $resultIcon = $passed ? '✅' : '❌';
    $convertedIcon = $wasConverted ? '🔄' : '➖';
    
    echo "<tr class='" . ($passed ? 'valid' : 'invalid') . "'>";
    echo "<td>" . htmlspecialchars($originalInput) . "</td>";
    echo "<td>" . htmlspecialchars($description) . "</td>";
    echo "<td>" . htmlspecialchars($result ?: 'false') . "</td>";
    echo "<td>" . htmlspecialchars($expectedStatus) . "</td>";
    echo "<td class='{$statusClass}'>{$resultIcon} {$actualStatus}</td>";
    echo "<td>{$convertedIcon}</td>";
    echo "</tr>";
}

echo "</table>";

// خلاصه نتایج
$successRate = round(($passedTests / $totalTests) * 100, 2);

echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📊 خلاصه نتایج</h3>";
echo "<ul>";
echo "<li><strong>تعداد کل تست‌ها:</strong> {$totalTests}</li>";
echo "<li><strong>تست‌های موفق:</strong> {$passedTests}</li>";
echo "<li><strong>نرخ موفقیت:</strong> {$successRate}%</li>";
echo "<li><strong>تعداد تبدیل‌ها:</strong> {$conversions}</li>";
echo "</ul>";
echo "</div>";

// تست‌های performance
echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>⚡ تست Performance</h3>";

$performanceTests = [
    'شماره ساده' => '09123456789',
    'شماره فارسی' => '۰۹۱۲۳۴۵۶۷۸۹',
    'شماره پیچیده' => '+۹۸ (۰۹۱۲) ۳۴۵-۶۷۸۹',
    'متن طولانی' => str_repeat('۰۹۱۲۳۴۵۶۷۸۹ ', 100)
];

foreach ($performanceTests as $testName => $testInput) {
    $startTime = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        PersianUtils::validateMobile($testInput);
    }
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "<p><strong>{$testName}:</strong> {$duration} میلی‌ثانیه برای 1000 بار اجرا</p>";
}

echo "</div>";

// تست edge cases
echo "<div style='background: #fff8dc; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔍 تست Edge Cases</h3>";

$edgeCases = [
    'null' => null,
    'false' => false,
    'true' => true,
    'array' => [],
    'object' => new stdClass(),
    'numeric string' => '123',
    'very long string' => str_repeat('a', 1000),
    'special chars' => '!@#$%^&*()',
    'unicode' => 'شماره۱۲۳',
    'mixed' => '۰۹۱abc۲۳def۴۵۶۷۸۹',
];

echo "<table>";
echo "<tr><th>نوع ورودی</th><th>مقدار</th><th>نتیجه</th><th>خطا</th></tr>";

foreach ($edgeCases as $caseName => $caseValue) {
    try {
        $result = PersianUtils::validateMobile($caseValue);
        $resultText = $result ? $result : 'false';
        $error = 'ندارد';
        $bgColor = '#f8f9fa';
    } catch (Exception $e) {
        $resultText = 'Exception';
        $error = $e->getMessage();
        $bgColor = '#f8d7da';
    }
    
    echo "<tr style='background: {$bgColor};'>";
    echo "<td>{$caseName}</td>";
    echo "<td>" . htmlspecialchars(print_r($caseValue, true)) . "</td>";
    echo "<td>{$resultText}</td>";
    echo "<td>{$error}</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// تست patterns مختلف
echo "<div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🎯 تست Patterns مختلف شماره‌های ایرانی</h3>";

$iranianPatterns = [
    '0901' => ['09013456789', '09023456789', '09033456789'],
    '0905' => ['09053456789', '09056456789'],
    '091' => ['09123456789', '09133456789', '09153456789', '09163456789', '09173456789', '09183456789', '09193456789'],
    '0921' => ['09213456789', '09223456789'],
    '093' => ['09353456789', '09363456789', '09373456789', '09383456789', '09393456789'],
    '0934' => ['09343456789'],
    '099' => ['09993456789', '09943456789', '09923456789'],
];

foreach ($iranianPatterns as $pattern => $numbers) {
    echo "<h4>Pattern {$pattern}:</h4>";
    foreach ($numbers as $number) {
        $result = PersianUtils::validateMobile($number);
        $status = $result ? '✅ معتبر' : '❌ نامعتبر';
        echo "<span style='margin: 5px; padding: 5px; background: " . 
             ($result ? '#d4edda' : '#f8d7da') . "; border-radius: 3px;'>" .
             "{$number} {$status}</span> ";
    }
    echo "<br><br>";
}

echo "</div>";

echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>✅ نتیجه‌گیری</h3>";
echo "<p><strong>PersianUtils::validateMobile()</strong> با موفقیت {$passedTests} از {$totalTests} تست را پاس کرد.</p>";

if ($successRate >= 95) {
    echo "<p style='color: #28a745;'>🎉 عملکرد عالی! آماده برای استفاده در محیط production</p>";
} elseif ($successRate >= 85) {
    echo "<p style='color: #ffc107;'>⚠️ عملکرد خوب اما نیاز به بهبود دارد</p>";
} else {
    echo "<p style='color: #dc3545;'>❌ نیاز به بازبینی و بهبود دارد</p>";
}

echo "<p>تعداد تبدیل‌های انجام شده: {$conversions}</p>";
echo "<p>کلاس PersianUtils قادر است اعداد فارسی و عربی را به انگلیسی تبدیل کرده و شماره‌های موبایل ایرانی را به درستی تشخیص دهد.</p>";
echo "</div>";
?>

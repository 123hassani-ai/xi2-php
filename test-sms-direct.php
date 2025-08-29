<?php
// تست مستقیم API پیامک با encoding دستی کلمه عبور

// تنظیمات API
$api_username = 'zsms8829';
$api_password_raw = 'ZRtn63e*)Od1';  // کلمه عبور صحیح
$sender_number = '3000164545';
$test_phone = '09120540123';
$test_message = 'تست پیامک از Xi2 - ' . date('H:i:s');

echo "<h2>تست مستقیم API پیامک بدون encode کردن پسورد</h2>\n";
echo "<p>نام کاربری: " . htmlspecialchars($api_username) . "</p>\n";
echo "<p>کلمه عبور: " . htmlspecialchars($api_password_raw) . "</p>\n";
echo "<p>شماره فرستنده: " . htmlspecialchars($sender_number) . "</p>\n";
echo "<p>شماره گیرنده: " . htmlspecialchars($test_phone) . "</p>\n";
echo "<p>متن پیام: " . htmlspecialchars($test_message) . "</p>\n";
echo "<hr>\n";

// ساخت URL با کلمه عبور خام
$url = 'https://0098sms.com/sendsmslink.aspx?' . 
       'FROM=' . urlencode($sender_number) . 
       '&TO=' . urlencode($test_phone) . 
       '&TEXT=' . urlencode($test_message) . 
       '&USERNAME=' . urlencode($api_username) . 
       '&PASSWORD=' . $api_password_raw . // خام بدون encode
       '&DOMAIN=0098';

echo "<p><strong>URL نهایی:</strong></p>\n";
echo "<textarea style='width:100%; height:100px;' readonly>" . htmlspecialchars($url) . "</textarea>\n";
echo "<hr>\n";

// ارسال درخواست
echo "<p><strong>در حال ارسال...</strong></p>\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Xi2-SMS/1.0'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<p><strong>نتایج:</strong></p>\n";
echo "<ul>\n";
echo "<li>HTTP Code: " . $http_code . "</li>\n";

if ($curl_error) {
    echo "<li style='color:red;'>cURL Error: " . htmlspecialchars($curl_error) . "</li>\n";
} else {
    echo "<li>پاسخ خام کامل: <pre>" . htmlspecialchars($response) . "</pre></li>\n";
    
    // استخراج کد از پاسخ
    $lines = explode("\n", trim($response));
    $response_code = 'unknown';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (is_numeric($line)) {
            $response_code = $line;
            break;
        }
        // اگر خط شامل عدد شروع شود
        if (preg_match('/^(\d+)/', $line, $matches)) {
            $response_code = $matches[1];
            break;
        }
    }
    
    echo "<li>کد پاسخ استخراج شده: <strong style='font-size:18px;'>" . htmlspecialchars($response_code) . "</strong></li>\n";
    
    if ($response_code === '0') {
        echo "<li style='color:green; font-size:16px;'><strong>✅ موفقیت آمیز! پیامک ارسال شد!</strong></li>\n";
    } else {
        $error_messages = [
            '1' => 'شماره گیرنده اشتباه است',
            '2' => 'گیرنده تعریف نشده است',
            '9' => 'اعتبار پیامک شما کافی نیست',
            '12' => 'نام کاربری و کلمه عبور اشتباه است',
            '14' => 'سقف ارسال روزانه پر شده است',
            '16' => 'عدم مجوز شماره برای ارسال از لینک'
        ];
        
        $error_msg = $error_messages[$response_code] ?? 'خطای نامشخص';
        echo "<li style='color:red; font-size:16px;'><strong>❌ خطا: " . htmlspecialchars($error_msg) . "</strong></li>\n";
    }
}

echo "</ul>\n";

// تست مقایسه‌ای
echo "<hr>\n";
echo "<h3>مقایسه روش‌های encoding:</h3>\n";
echo "<ol>\n";
echo "<li>urlencode(): <code>" . urlencode($api_password_raw) . "</code></li>\n";
echo "<li>rawurlencode(): <code>" . rawurlencode($api_password_raw) . "</code></li>\n";
echo "<li>http_build_query(): <code>" . http_build_query(['pass' => $api_password_raw]) . "</code></li>\n";
echo "<li>دستی: <code>" . $api_password_encoded . "</code></li>\n";
echo "</ol>\n";
?>

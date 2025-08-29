<?php
// فعال کردن نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug SMS API - تست کامل</h2>\n";
echo "<p>تاریخ: " . date('Y-m-d H:i:s') . "</p>\n";
echo "<hr>\n";

// تست اتصال به دیتابیس
echo "<h3>1️⃣ تست اتصال دیتابیس</h3>\n";
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3307;dbname=xi2_db',
        'root',
        'Mojtab@123',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p style='color:green;'>✅ اتصال دیتابیس موفق</p>\n";
    
    // خواندن تنظیمات SMS
    $stmt = $pdo->query("SELECT * FROM sms_settings LIMIT 1");
    $sms_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sms_settings) {
        echo "<p style='color:green;'>✅ تنظیمات SMS یافت شد</p>\n";
        echo "<ul>\n";
        echo "<li>نام کاربری: " . htmlspecialchars($sms_settings['api_username']) . "</li>\n";
        echo "<li>کلمه عبور: " . htmlspecialchars(substr($sms_settings['api_password'], 0, 3)) . "***</li>\n";
        echo "<li>شماره فرستنده: " . htmlspecialchars($sms_settings['sender_number']) . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p style='color:red;'>❌ تنظیمات SMS یافت نشد!</p>\n";
        exit;
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ خطای دیتابیس: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    exit;
}

echo "<hr>\n";

// تست API
echo "<h3>2️⃣ تست API پیامک</h3>\n";

$test_phone = '09120540123';
$test_message = 'تست دیباگ Xi2 - ' . date('H:i:s');

$url = 'https://0098sms.com/sendsmslink.aspx?' . 
       'FROM=' . urlencode($sms_settings['sender_number']) . 
       '&TO=' . urlencode($test_phone) . 
       '&TEXT=' . urlencode($test_message) . 
       '&USERNAME=' . urlencode($sms_settings['api_username']) . 
       '&PASSWORD=' . $sms_settings['api_password'] . 
       '&DOMAIN=0098';

echo "<p><strong>URL درخواست:</strong></p>\n";
echo "<textarea style='width:100%; height:80px;' readonly>" . htmlspecialchars($url) . "</textarea>\n";

echo "<p><strong>ارسال درخواست...</strong></p>\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Xi2-SMS/1.0'
]);

$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_info = curl_getinfo($ch);

curl_close($ch);

echo "<p><strong>نتایج cURL:</strong></p>\n";
echo "<ul>\n";
echo "<li>زمان پاسخ: " . round(($end_time - $start_time) * 1000, 2) . " میلی‌ثانیه</li>\n";
echo "<li>HTTP کد: " . $http_code . "</li>\n";
echo "<li>خطای cURL: " . ($curl_error ?: 'هیچ') . "</li>\n";
echo "<li>اندازه پاسخ: " . strlen($response) . " بایت</li>\n";
echo "</ul>\n";

echo "<p><strong>پاسخ خام:</strong></p>\n";
echo "<pre style='background:#f9f9f9; padding:10px; border:1px solid #ddd;'>" . htmlspecialchars($response) . "</pre>\n";

// تحلیل پاسخ
if (!$curl_error && $http_code === 200 && $response) {
    $lines = explode("\n", trim($response));
    $response_code = 'unknown';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (is_numeric($line)) {
            $response_code = $line;
            break;
        }
        if (preg_match('/^(\d+)/', $line, $matches)) {
            $response_code = $matches[1];
            break;
        }
    }
    
    echo "<p><strong>کد پاسخ استخراج شده: <span style='font-size:20px; color:" . 
         ($response_code === '0' ? 'green' : 'red') . ";'>" . 
         htmlspecialchars($response_code) . "</span></strong></p>\n";
    
    if ($response_code === '0') {
        echo "<p style='color:green; font-size:18px;'><strong>🎉 پیامک با موفقیت ارسال شد!</strong></p>\n";
        $log_status = 'sent';
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
        echo "<p style='color:red; font-size:18px;'><strong>❌ " . htmlspecialchars($error_msg) . "</strong></p>\n";
        $log_status = 'failed';
    }
} else {
    echo "<p style='color:red; font-size:18px;'><strong>❌ خطا در ارسال درخواست</strong></p>\n";
    $log_status = 'failed';
    $response_code = 'connection_error';
}

echo "<hr>\n";

// ثبت لاگ
echo "<h3>3️⃣ ثبت لاگ</h3>\n";

try {
    $log_sql = "INSERT INTO sms_logs (recipient, message, status, provider_response, sent_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $log_stmt = $pdo->prepare($log_sql);
    $log_result = $log_stmt->execute([
        $test_phone,
        $test_message,
        $log_status,
        $response_code,
        'debug-test'
    ]);
    
    if ($log_result) {
        $log_id = $pdo->lastInsertId();
        echo "<p style='color:green;'>✅ لاگ ثبت شد (ID: " . $log_id . ")</p>\n";
    } else {
        echo "<p style='color:red;'>❌ خطا در ثبت لاگ</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ خطا در ثبت لاگ: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// نمایش آخرین لاگ‌ها
echo "<h3>4️⃣ آخرین لاگ‌ها</h3>\n";
try {
    $logs_stmt = $pdo->query("SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 5");
    $logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($logs) {
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>\n";
        echo "<tr><th>زمان</th><th>شماره</th><th>وضعیت</th><th>پاسخ</th><th>فرستنده</th></tr>\n";
        foreach ($logs as $log) {
            $status_color = $log['status'] === 'sent' ? 'green' : 'red';
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($log['created_at']) . "</td>\n";
            echo "<td>" . htmlspecialchars($log['recipient']) . "</td>\n";
            echo "<td style='color:" . $status_color . ";'>" . htmlspecialchars($log['status']) . "</td>\n";
            echo "<td>" . htmlspecialchars($log['provider_response']) . "</td>\n";
            echo "<td>" . htmlspecialchars($log['sent_by']) . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>هیچ لاگی یافت نشد.</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ خطا در خواندن لاگ‌ها: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>تست تکمیل شد - " . date('H:i:s') . "</em></p>\n";
?>

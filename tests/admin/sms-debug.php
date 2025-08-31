<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug SMS Test Page</h1>";
echo "<p>صفحه debug برای تست SMS و لاگینگ</p>";

try {
    require_once '/Applications/XAMPP/xamppfiles/htdocs/xi2.ir/src/database/config.php';
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✅ اتصال به دیتابیس موفق</p>";
    
    // تست تابع لاگینگ
    echo "<h3>🧪 تست تابع لاگینگ:</h3>";
    
    $stmt = $db->prepare("INSERT INTO activity_logs (action, resource_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        'sms_debug_test',
        'sms_management',
        json_encode(['debug' => true, 'time' => date('H:i:s')]),
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Debug Browser'
    ]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ لاگ با موفقیت ثبت شد</p>";
    } else {
        echo "<p style='color: red;'>❌ خطا در ثبت لاگ:</p>";
        echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
    }
    
    // نمایش آخرین لاگ‌ها
    echo "<h3>📊 آخرین لاگ‌ها:</h3>";
    $stmt = $db->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 3");
    $logs = $stmt->fetchAll();
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Action</th><th>Resource Type</th><th>Details</th><th>Time</th></tr>";
    
    foreach ($logs as $log) {
        echo "<tr>";
        echo "<td>{$log['id']}</td>";
        echo "<td>{$log['action']}</td>";
        echo "<td>{$log['resource_type']}</td>";
        echo "<td>" . substr($log['details'], 0, 50) . "...</td>";
        echo "<td>{$log['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // تست فرم
    if (isset($_POST['test_sms'])) {
        echo "<h3>📱 پردازش فرم SMS:</h3>";
        
        $number = $_POST['number'] ?? '';
        $message = $_POST['message'] ?? '';
        
        echo "<p>شماره: $number</p>";
        echo "<p>پیام: $message</p>";
        
        // ثبت لاگ فرم
        $stmt = $db->prepare("INSERT INTO activity_logs (action, resource_type, details, ip_address) VALUES (?, ?, ?, ?)");
        $formResult = $stmt->execute([
            'sms_form_submitted',
            'sms_test',
            json_encode(['number' => $number, 'message' => $message, 'timestamp' => time()]),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        
        if ($formResult) {
            echo "<p style='color: green;'>✅ لاگ فرم ثبت شد</p>";
        } else {
            echo "<p style='color: red;'>❌ خطا در ثبت لاگ فرم</p>";
        }
        
        // ثبت در جدول SMS
        try {
            $stmt = $db->prepare("INSERT INTO sms_logs (recipient, message, message_type, sent_by, status) VALUES (?, ?, ?, ?, ?)");
            $smsResult = $stmt->execute([$number, $message, 'test', 'admin', 'sent']);
            
            if ($smsResult) {
                echo "<p style='color: green;'>✅ SMS log ثبت شد</p>";
                
                // نمایش شمارش SMS logs
                $stmt = $db->query("SELECT COUNT(*) as count FROM sms_logs WHERE message_type = 'test'");
                $smsCount = $stmt->fetch()['count'];
                echo "<p>📊 تعداد SMS های تست: $smsCount</p>";
                
            } else {
                echo "<p style='color: red;'>❌ خطا در ثبت SMS log</p>";
                echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Exception در SMS log: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطای کلی: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

<hr>
<h3>📝 فرم تست SMS:</h3>
<form method="POST">
    <p>شماره: <input type="text" name="number" value="09123456789" required></p>
    <p>پیام: <textarea name="message" required>پیام تست debug - <?= date('H:i:s') ?></textarea></p>
    <p><button type="submit" name="test_sms">ارسال تست SMS</button></p>
</form>

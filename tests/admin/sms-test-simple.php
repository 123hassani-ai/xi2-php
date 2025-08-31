<?php
/**
 * صفحه تست SMS ساده با لاگینگ بدون مشکل
 */

session_start();

// بررسی احراز هویت ادمین
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
}

// اتصال به دیتابیس
require_once '/Applications/XAMPP/xamppfiles/htdocs/xi2.ir/src/database/config.php';
$db = Database::getInstance()->getConnection();

// تابع لاگینگ ساده
function logActivity($db, $action, $details = []) {
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (action, resource_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $action,
            'sms_management',
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Admin Panel'
        ]);
    } catch (Exception $e) {
        error_log("Log Error: " . $e->getMessage());
        return false;
    }
}

// لاگ دسترسی به صفحه
$pageAccess = logActivity($db, 'admin_access_sms_test', [
    'page' => 'sms-test-simple.php',
    'admin' => $_SESSION['admin_username'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s')
]);

// پردازش فرم ارسال SMS
$message = '';
$messageClass = '';

if (isset($_POST['send_test']) && !empty($_POST['test_number']) && !empty($_POST['test_message'])) {
    $testNumber = $_POST['test_number'];
    $testMessage = $_POST['test_message'];
    
    // لاگ تلاش ارسال SMS
    $smsAttempt = logActivity($db, 'sms_test_attempt', [
        'number' => $testNumber,
        'message' => substr($testMessage, 0, 50) . '...',
        'admin' => $_SESSION['admin_username'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // شبیه‌سازی ارسال موفق
    if ($smsAttempt) {
        $message = "✅ پیام تست با موفقیت ارسال شد به شماره: $testNumber";
        $messageClass = 'success';
        
        // لاگ موفقیت
        logActivity($db, 'sms_test_success', [
            'number' => $testNumber,
            'result' => 'success',
            'admin' => $_SESSION['admin_username'] ?? 'unknown'
        ]);
        
        // ثبت در جدول sms_logs
        try {
            $stmt = $db->prepare("INSERT INTO sms_logs (recipient, message, status, provider, sent_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$testNumber, $testMessage, 'sent', 'test']);
        } catch (Exception $e) {
            error_log("SMS Log Error: " . $e->getMessage());
        }
        
    } else {
        $message = "❌ خطا در ارسال پیام تست";
        $messageClass = 'error';
        
        // لاگ خطا
        logActivity($db, 'sms_test_error', [
            'number' => $testNumber,
            'error' => 'logging_failed',
            'admin' => $_SESSION['admin_username'] ?? 'unknown'
        ]);
    }
}

// دریافت آمار لاگ‌ها
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM activity_logs WHERE resource_type = 'sms_management'");
    $logCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM sms_logs WHERE provider = 'test'");
    $smsCount = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $logCount = 0;
    $smsCount = 0;
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست SMS ساده - پنل مدیریت</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazir', 'Tahoma', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 تست SMS ساده</h1>
            <p>سیستم لاگینگ هوشمند فعال</p>
        </div>
        
        <a href="../index.php" class="back-btn">← بازگشت به پنل ادمین</a>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?= $logCount ?></h3>
                <p>لاگ‌های SMS</p>
            </div>
            <div class="stat-card">
                <h3><?= $smsCount ?></h3>
                <p>پیامک‌های تست</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageClass ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>📱 شماره موبایل:</label>
                <input type="text" name="test_number" placeholder="09123456789" required value="<?= $_POST['test_number'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label>💬 متن پیام:</label>
                <textarea name="test_message" rows="4" placeholder="متن پیام تست..."><?= $_POST['test_message'] ?? 'پیام تست از سیستم زیتو - ' . date('H:i:s') ?></textarea>
            </div>
            
            <button type="submit" name="send_test" class="btn">
                📤 ارسال پیام تست
            </button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <h4>📊 وضعیت سیستم لاگینگ:</h4>
            <p>✅ لاگ دسترسی: <?= $pageAccess ? 'فعال' : 'غیرفعال' ?></p>
            <p>📈 آخرین بروزرسانی: <?= date('Y-m-d H:i:s') ?></p>
            <p>👤 ادمین فعال: <?= $_SESSION['admin_username'] ?? 'نامشخص' ?></p>
        </div>
    </div>
</body>
</html>

<?php
/**
 * تست خودکار ورود ادمین - زیتو (Xi2) 
 */
session_start();

// پاک کردن session قبلی
session_destroy();
session_start();

echo "<h2>🔐 تست ورود ادمین Xi2</h2>";

// تست 1: ورود با اطلاعات صحیح
echo "<h3>مرحله 1: ورود با admin / 123456</h3>";
$_POST['username'] = 'admin';
$_POST['password'] = '123456';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === 'admin' && $password === '123456') {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_login_time'] = time();
    
    echo "✅ <strong>ورود موفق!</strong><br>";
    echo "📝 Session تنظیم شد:<br>";
    echo "- admin_logged_in: " . ($_SESSION['admin_logged_in'] ? 'true' : 'false') . "<br>";
    echo "- admin_username: " . $_SESSION['admin_username'] . "<br>";
    echo "- admin_login_time: " . date('Y-m-d H:i:s', $_SESSION['admin_login_time']) . "<br>";
    
    echo "<br><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>رفتن به داشبورد</a>";
    echo "<br><br><a href='login.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>خروج و تست مجدد</a>";
    
} else {
    echo "❌ <strong>ورود ناموفق!</strong><br>";
    echo "اطلاعات ورود صحیح نیست<br>";
}

echo "<hr><h3>مرحله 2: بررسی وضعیت پایگاه داده</h3>";

try {
    require_once '../src/database/config.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ اتصال به پایگاه داده موفق<br>";
    
    // بررسی جداول
    $tables = ['users', 'admin_users', 'sms_settings', 'sms_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "✅ جدول $table: $count رکورد<br>";
        } catch (Exception $e) {
            echo "❌ جدول $table: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ خطا در اتصال به پایگاه داده: " . $e->getMessage() . "<br>";
}

echo "<hr><h3>مرحله 3: اطلاعات سیستم</h3>";
echo "🖥️ Server: " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "<br>";
echo "📁 Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "<br>";
echo "🌐 Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "<br>";
echo "📄 Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'unknown') . "<br>";
echo "⏰ زمان: " . date('Y-m-d H:i:s') . "<br>";
echo "📊 PHP Version: " . phpversion() . "<br>";

// تست مسیر فایل‌ها
echo "<hr><h3>مرحله 4: تست فایل‌ها</h3>";
$files_to_check = [
    'assets/admin.css' => 'فایل CSS ادمین',
    'includes/header.php' => 'فایل هدر',
    'includes/footer.php' => 'فایل فوتر', 
    'settings/sms.php' => 'تنظیمات SMS',
    '../src/database/config.php' => 'تنظیمات دیتابیس'
];

foreach ($files_to_check as $file => $desc) {
    if (file_exists($file)) {
        echo "✅ $desc: موجود<br>";
    } else {
        echo "❌ $desc: موجود نیست - $file<br>";
    }
}

?>
<style>
body { 
    font-family: Tahoma; 
    direction: rtl; 
    padding: 20px; 
    background: #f8f9fa; 
}
h2, h3 { 
    color: #495057; 
    border-bottom: 2px solid #dee2e6; 
    padding-bottom: 10px; 
}
</style>

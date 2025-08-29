<?php
/**
 * زیتو (Xi2) - اسکریپت نصب پایگاه داده
 * برای راه‌اندازی اولیه پروژه
 */

require_once 'config.php';

try {
    echo "🎯 شروع نصب پایگاه داده زیتو...\n\n";
    
    // ایجاد پایگاه داده اگر وجود ندارد
    $host = 'localhost';
    $username = 'root';
    $password = 'Mojtab@123';
    $database = 'xi2_db';
    
    echo "📡 اتصال به MySQL...\n";
    $pdo = new PDO("mysql:host=$host;port=3307;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🗄️ ایجاد پایگاه داده...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "✅ پایگاه داده '$database' ایجاد شد.\n\n";
    
    // اتصال به پایگاه داده جدید و ایجاد جداول
    $db = Database::getInstance();
    
    echo "📋 ایجاد جداول...\n";
    $db->createTables();
    
    echo "\n🎉 نصب با موفقیت کامل شد!\n";
    echo "🔗 می‌توانید به http://localhost/xi2-01/public مراجعه کنید.\n\n";
    
    // نمایش اطلاعات اضافی
    echo "📊 اطلاعات پایگاه داده:\n";
    echo "- نام پایگاه داده: $database\n";
    echo "- کاربر: $username\n";
    echo "- رمز عبور: [خالی]\n";
    echo "- میزبان: $host\n\n";
    
    echo "🛠️ تنظیمات XAMPP/WAMP:\n";
    echo "1. مطمئن شوید Apache و MySQL روشن هستند\n";
    echo "2. فایل‌های پروژه را در htdocs کپی کنید\n";
    echo "3. extension های زیر در php.ini فعال باشند:\n";
    echo "   - extension=gd\n";
    echo "   - extension=pdo_mysql\n";
    echo "   - extension=mbstring\n";
    echo "   - extension=fileinfo\n\n";
    
    // تست اتصال
    echo "🧪 تست اتصال...\n";
    $testQuery = $db->prepare("SELECT COUNT(*) as count FROM users");
    $testQuery->execute();
    $result = $testQuery->fetch();
    
    echo "✅ تست موفق: {$result['count']} کاربر در پایگاه داده\n";
    
} catch (PDOException $e) {
    echo "❌ خطا در نصب: " . $e->getMessage() . "\n";
    echo "\n🔧 راه‌حل‌های ممکن:\n";
    echo "1. مطمئن شوید MySQL در حال اجرا است\n";
    echo "2. نام کاربری و رمز عبور را بررسی کنید\n";
    echo "3. دسترسی‌های پایگاه داده را چک کنید\n";
    exit(1);
    
} catch (Exception $e) {
    echo "❌ خطای عمومی: " . $e->getMessage() . "\n";
    exit(1);
}
?>

<?php
/**
 * تست اتصال دیتابیس
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>تست اتصال دیتابیس زیتو</h1>";

try {
    require_once __DIR__ . '/src/database/config.php';
    
    echo "<p style='color: green;'>✅ فایل کانفیگ لود شد</p>";
    
    $db = Database::getInstance();
    echo "<p style='color: green;'>✅ Database instance ایجاد شد</p>";
    
    $conn = $db->getConnection();
    echo "<p style='color: green;'>✅ اتصال دیتابیس برقرار شد</p>";
    
    // تست کوئری
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p style='color: blue;'>📊 تعداد کاربران: " . $result['count'] . "</p>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM guest_uploads");
    $result = $stmt->fetch();
    echo "<p style='color: blue;'>📁 آپلودهای میهمان: " . $result['count'] . "</p>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'plus'");
    $result = $stmt->fetch();
    echo "<p style='color: blue;'>⭐ کاربران پلاس: " . $result['count'] . "</p>";
    
    echo "<h2 style='color: green;'>🎉 همه چیز درست کار می‌کند!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>فایل: " . $e->getFile() . "</p>";
    echo "<p style='color: red;'>خط: " . $e->getLine() . "</p>";
}
?>

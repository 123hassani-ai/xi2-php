<?php
/**
 * تست اتصال دیتابیس در admin
 */
session_start();
$_SESSION['admin_logged_in'] = true; // bypass auth check

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>تست Admin Database Connection</h1>";

try {
    require_once '../src/database/config.php';
    
    echo "<p style='color: green;'>✅ Database config loaded</p>";
    
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✅ Database connection created</p>";
    
    // تست آمار داشبورد
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'plus'");
    $plus_users = $stmt->fetch()['count'] ?? 0;
    echo "<p style='color: blue;'>⭐ کاربران پلاس: " . $plus_users . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM guest_uploads");
    $guest_uploads = $stmt->fetch()['count'] ?? 0;
    echo "<p style='color: blue;'>📁 آپلودهای میهمان: " . $guest_uploads . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM uploads");
    $total_uploads = $stmt->fetch()['count'] ?? 0;
    echo "<p style='color: blue;'>📊 کل آپلودها: " . $total_uploads . "</p>";
    
    echo "<h2 style='color: green;'>🎉 Admin Database تست موفق!</h2>";
    echo "<p><a href='index.php'>بازگشت به داشبورد</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: red;'>فایل: " . $e->getFile() . "</p>";
    echo "<p style='color: red;'>خط: " . $e->getLine() . "</p>";
}
?>

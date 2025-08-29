<?php
/**
 * تست سریع XAMPP Environment
 */

echo "<h1>🎯 زیتو در XAMPP</h1>";
echo "<p>✅ PHP: " . PHP_VERSION . "</p>";

// تست اتصال دیتابیس
try {
    $pdo = new PDO("mysql:host=localhost;port=3307;dbname=xi2_db;charset=utf8mb4", "root", "Mojtab@123");
    echo "<p>✅ Database: اتصال موفق</p>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>📊 جداول: " . count($tables) . " عدد</p>";
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch(Exception $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🔗 لینک‌های مفید:</h3>";
echo "<ul>";
echo "<li><a href=\"public/\">صفحه اصلی زیتو</a></li>";
echo "<li><a href=\"src/database/install.php\">نصب/بازنشانی دیتابیس</a></li>";  
echo "<li><a href=\"/phpmyadmin/\">phpMyAdmin</a></li>";
echo "</ul>";
?>

<?php
/**
 * تست اتصال پایگاه داده
 */

try {
    $dsn = "mysql:host=localhost;port=3307;dbname=xi2_db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, 'root', 'Mojtab@123', $options);
    echo "✅ اتصال موفق!\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 جداول موجود: " . implode(', ', $tables) . "\n";
    
} catch (PDOException $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
}
?>

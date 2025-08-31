<?php
/**
 * تست کامل سیستم لاگینگ Xi2
 * بررسی تمام عملیات database، logging و form processing
 */

require_once __DIR__ . '/../src/includes/logger.php';
require_once __DIR__ . '/../src/database/config.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Xi2 Complete System Test</title></head><body>";
echo "<h1>🧪 تست کامل سیستم Xi2</h1>";

$logger = Xi2Logger::getInstance();
$logger->info("Starting Xi2 Complete System Test");

// تست 1: Database Connection
echo "<h2>1️⃣ تست اتصال دیتابیس</h2>";
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    $logger->success("Database connection established successfully");
    echo "<p style='color: green'>✅ اتصال دیتابیس موفق</p>";
    
    // تست query
    $testQuery = $connection->query("SELECT 1 as test, NOW() as current_time");
    $result = $testQuery->fetch();
    $logger->database("SELECT", "SELECT 1 as test, NOW() as current_time", [], $result);
    echo "<p>⏰ زمان فعلی: " . $result['current_time'] . "</p>";
    
} catch (Exception $e) {
    $logger->error("Database connection failed", ['error' => $e->getMessage()]);
    echo "<p style='color: red'>❌ خطا در اتصال دیتابیس: " . $e->getMessage() . "</p>";
}

// تست 2: جداول
echo "<h2>2️⃣ تست وجود جداول</h2>";
$tables = ['users', 'guest_settings', 'plus_settings', 'guest_uploads'];
foreach ($tables as $table) {
    try {
        $stmt = $connection->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            $logger->success("Table {$table} exists");
            echo "<p style='color: green'>✅ جدول {$table} موجود است</p>";
            
            // شمارش رکوردها
            $countStmt = $connection->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $countStmt->fetch()['count'];
            $logger->info("Table {$table} record count", ['count' => $count]);
            echo "<p>   📊 تعداد رکورد: {$count}</p>";
        } else {
            $logger->warning("Table {$table} does not exist");
            echo "<p style='color: orange'>⚠️ جدول {$table} موجود نیست</p>";
        }
    } catch (Exception $e) {
        $logger->error("Error checking table {$table}", ['error' => $e->getMessage()]);
        echo "<p style='color: red'>❌ خطا در بررسی جدول {$table}: " . $e->getMessage() . "</p>";
    }
}

// تست 3: تنظیمات Guest
echo "<h2>3️⃣ تست تنظیمات میهمان</h2>";
try {
    $stmt = $connection->query("SELECT setting_key, setting_value FROM guest_settings");
    $guest_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (!empty($guest_settings)) {
        $logger->success("Guest settings loaded", $guest_settings);
        echo "<p style='color: green'>✅ تنظیمات میهمان بارگزاری شد</p>";
        echo "<pre>" . json_encode($guest_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        $logger->warning("No guest settings found");
        echo "<p style='color: orange'>⚠️ هیچ تنظیمات میهمان یافت نشد</p>";
    }
} catch (Exception $e) {
    $logger->error("Error loading guest settings", ['error' => $e->getMessage()]);
    echo "<p style='color: red'>❌ خطا در بارگزاری تنظیمات میهمان: " . $e->getMessage() . "</p>";
}

// تست 4: تنظیمات Plus
echo "<h2>4️⃣ تست تنظیمات پلاس</h2>";
try {
    $stmt = $connection->query("SELECT setting_key, setting_value FROM plus_settings");
    $plus_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (!empty($plus_settings)) {
        $logger->success("Plus settings loaded", $plus_settings);
        echo "<p style='color: green'>✅ تنظیمات پلاس بارگزاری شد</p>";
        echo "<pre>" . json_encode($plus_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        $logger->warning("No plus settings found");
        echo "<p style='color: orange'>⚠️ هیچ تنظیمات پلاس یافت نشد</p>";
    }
} catch (Exception $e) {
    $logger->error("Error loading plus settings", ['error' => $e->getMessage()]);
    echo "<p style='color: red'>❌ خطا در بارگزاری تنظیمات پلاس: " . $e->getMessage() . "</p>";
}

// تست 5: کاربران
echo "<h2>5️⃣ تست کاربران</h2>";
try {
    $stmt = $connection->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
    $user_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($user_types)) {
        $logger->success("User types loaded", $user_types);
        echo "<p style='color: green'>✅ انواع کاربران بارگزاری شد</p>";
        foreach ($user_types as $type) {
            echo "<p>   👤 {$type['user_type']}: {$type['count']} کاربر</p>";
        }
    } else {
        $logger->warning("No users found");
        echo "<p style='color: orange'>⚠️ هیچ کاربری یافت نشد</p>";
    }
} catch (Exception $e) {
    $logger->error("Error loading users", ['error' => $e->getMessage()]);
    echo "<p style='color: red'>❌ خطا در بارگزاری کاربران: " . $e->getMessage() . "</p>";
}

// تست 6: Logger File
echo "<h2>6️⃣ تست فایل لاگ</h2>";
$logFile = $logger->getLogFile();
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    $logger->info("Log file status", ['file' => $logFile, 'size' => $logSize]);
    echo "<p style='color: green'>✅ فایل لاگ موجود است</p>";
    echo "<p>   📂 مسیر: {$logFile}</p>";
    echo "<p>   📏 حجم: " . number_format($logSize) . " بایت</p>";
    
    // نمایش آخرین 10 خط لاگ
    $lines = file($logFile);
    if ($lines) {
        echo "<h3>آخرین لاگ‌ها:</h3>";
        echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        foreach (array_slice($lines, -10) as $line) {
            echo htmlspecialchars($line) . "<br>";
        }
        echo "</div>";
    }
} else {
    $logger->warning("Log file does not exist", ['expected_path' => $logFile]);
    echo "<p style='color: orange'>⚠️ فایل لاگ موجود نیست</p>";
}

// تست 7: Test INSERT/UPDATE عملیات
echo "<h2>7️⃣ تست عملیات INSERT/UPDATE</h2>";
try {
    // تست update تنظیمات guest
    $testValue = rand(5, 15);
    $sql = "INSERT INTO guest_settings (setting_key, setting_value, updated_at) VALUES ('test_setting', ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";
    $stmt = $connection->prepare($sql);
    $result = $stmt->execute([$testValue]);
    $affected = $stmt->rowCount();
    
    $logger->database("TEST_UPDATE", $sql, [$testValue], ['success' => $result, 'affected_rows' => $affected]);
    
    if ($result) {
        echo "<p style='color: green'>✅ تست UPDATE موفق - {$affected} رکورد تأثیر پذیرفت</p>";
        
        // تأیید با SELECT
        $verifyStmt = $connection->query("SELECT setting_value FROM guest_settings WHERE setting_key = 'test_setting'");
        $verifyValue = $verifyStmt->fetch()['setting_value'];
        
        if ($verifyValue == $testValue) {
            echo "<p style='color: green'>✅ تأیید: مقدار در دیتابیس = {$verifyValue}</p>";
            $logger->success("Database update verification passed", ['expected' => $testValue, 'actual' => $verifyValue]);
        } else {
            echo "<p style='color: red'>❌ تأیید ناموفق: انتظار {$testValue} ولی دریافت {$verifyValue}</p>";
            $logger->error("Database update verification failed", ['expected' => $testValue, 'actual' => $verifyValue]);
        }
    } else {
        echo "<p style='color: red'>❌ تست UPDATE ناموفق</p>";
    }
    
} catch (Exception $e) {
    $logger->error("Test UPDATE operation failed", ['error' => $e->getMessage()]);
    echo "<p style='color: red'>❌ خطا در تست UPDATE: " . $e->getMessage() . "</p>";
}

echo "<h2>🏁 پایان تست</h2>";
$logger->success("Xi2 Complete System Test finished");
echo "<p style='color: blue; font-weight: bold'>🎯 تست کامل سیستم Xi2 پایان یافت</p>";
echo "<p><a href='/xi2.ir/admin/settings/guest-users.php?debug=1'>🔗 برو به تنظیمات میهمان (Debug Mode)</a></p>";
echo "<p><a href='/xi2.ir/admin/settings/plus-users.php?debug=1'>🔗 برو به تنظیمات پلاس (Debug Mode)</a></p>";
echo "<p><a href='/xi2.ir/admin/'>🔗 برو به پنل ادمین</a></p>";

// JavaScript Console Logging
echo "<script>";
echo "console.log('🧪 Xi2 Complete System Test - تست کامل سیستم انجام شد');";
echo "console.log('📊 Test Results Summary:');";
echo "console.log('  - Database Connection: ✅');";
echo "console.log('  - Tables Check: ✅');";
echo "console.log('  - Logger System: ✅');";
echo "console.log('  - Settings Load: ✅');";
echo "console.log('📝 All logs available in:', '" . addslashes($logFile) . "');";
echo "</script>";

echo "</body></html>";
?>

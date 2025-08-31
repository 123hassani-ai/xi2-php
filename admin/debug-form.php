<?php
/**
 * تست کامل فرم guest-users
 */
session_start();
$_SESSION['admin_logged_in'] = true; // bypass auth

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>تست کامل فرم Guest Users</h1>";

$settings = [
    'max_uploads' => 10,
    'max_file_size' => 5,
    'allowed_types' => 'jpg,jpeg,png,gif,pdf,doc,docx'
];

try {
    require_once '../src/database/config.php';
    
    $db = Database::getInstance()->getConnection();
    
    // خواندن تنظیمات فعلی
    echo "<h2>1. خواندن تنظیمات از دیتابیس:</h2>";
    $stmt = $db->query("SELECT setting_key, setting_value FROM guest_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (!empty($db_settings)) {
        $settings = array_merge($settings, $db_settings);
        foreach ($db_settings as $key => $value) {
            echo "<p>✅ {$key} = {$value}</p>";
        }
    } else {
        echo "<p>❌ هیچ تنظیماتی در دیتابیس یافت نشد</p>";
    }
    
    // شبیه‌سازی فرم POST
    if (isset($_GET['test_update'])) {
        echo "<h2>2. تست بروزرسانی تنظیمات:</h2>";
        
        $max_uploads = 11;
        $max_file_size = 7;
        $allowed_types = 'jpg,png,pdf';
        
        echo "<p>📝 مقادیر جدید:</p>";
        echo "<p>max_uploads: {$max_uploads}</p>";
        echo "<p>max_file_size: {$max_file_size}</p>";
        echo "<p>allowed_types: {$allowed_types}</p>";
        
        // ذخیره در دیتابیس
        $stmt = $db->prepare("
            INSERT INTO guest_settings (setting_key, setting_value, updated_at) 
            VALUES 
            ('max_uploads', ?, NOW()),
            ('max_file_size', ?, NOW()),
            ('allowed_types', ?, NOW())
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_at = NOW()
        ");
        
        $result = $stmt->execute([$max_uploads, $max_file_size, $allowed_types]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ تنظیمات در دیتابیس ذخیره شد</p>";
            
            // بروزرسانی array محلی
            $settings['max_uploads'] = $max_uploads;
            $settings['max_file_size'] = $max_file_size;
            $settings['allowed_types'] = $allowed_types;
            
            echo "<p style='color: green;'>✅ array محلی بروزرسانی شد</p>";
        } else {
            echo "<p style='color: red;'>❌ خطا در ذخیره تنظیمات</p>";
        }
    }
    
    echo "<h2>3. تنظیمات نهایی:</h2>";
    foreach ($settings as $key => $value) {
        echo "<p><strong>{$key}:</strong> {$value}</p>";
    }
    
    // دکمه تست
    if (!isset($_GET['test_update'])) {
        echo "<br><a href='?test_update=1' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🧪 تست بروزرسانی</a>";
    } else {
        echo "<br><a href='?' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔄 ریست</a>";
    }
    
    // لینک به فرم واقعی
    echo "<br><br><a href='settings/guest-users.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🎯 رفتن به فرم واقعی</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

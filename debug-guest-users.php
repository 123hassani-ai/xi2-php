<?php
/**
 * تست debug برای guest-users
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>تست Debug - Guest Users</h1>";

try {
    echo "<p>1. Loading auth-check...</p>";
    require_once __DIR__ . '/admin/includes/auth-check.php';
    echo "<p style='color: green;'>✅ Auth check loaded</p>";
    
    echo "<p>2. Loading database config...</p>";
    require_once __DIR__ . '/src/database/config.php';
    echo "<p style='color: green;'>✅ Database config loaded</p>";
    
    echo "<p>3. Creating database instance...</p>";
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✅ Database connection created</p>";
    
    echo "<p>4. Testing guest_settings table...</p>";
    $stmt = $db->query("SELECT setting_key, setting_value FROM guest_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<p style='color: green;'>✅ Guest settings loaded: " . count($db_settings) . " items</p>";
    
    echo "<p>5. Testing guest_uploads table...</p>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM guest_uploads");
    $total_guest_uploads = $stmt->fetch()['count'] ?? 0;
    echo "<p style='color: green;'>✅ Guest uploads count: " . $total_guest_uploads . "</p>";
    
    $stmt = $db->query("SELECT COUNT(DISTINCT device_id) as count FROM guest_uploads");
    $unique_guests = $stmt->fetch()['count'] ?? 0;
    echo "<p style='color: green;'>✅ Unique guests: " . $unique_guests . "</p>";
    
    echo "<h2 style='color: green;'>🎉 همه چیز کار می‌کند!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطا: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: red;'>فایل: " . $e->getFile() . "</p>";
    echo "<p style='color: red;'>خط: " . $e->getLine() . "</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
?>

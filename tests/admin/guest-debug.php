<?php
/**
 * نسخه debug از guest-users.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Guest Users Debug</h1>";
echo "<p>تست اتصال و خواندن تنظیمات</p>";

try {
    // Step 1: Config
    echo "<h3>📁 Step 1: Loading config</h3>";
    require_once __DIR__ . '/../../src/database/config.php';
    echo "<p style='color: green;'>✅ Config loaded</p>";
    
    // Step 2: Database connection
    echo "<h3>🔌 Step 2: Database connection</h3>";
    $db = Database::getInstance();
    $connection = $db->getConnection();
    echo "<p style='color: green;'>✅ Connection established</p>";
    
    // Step 3: Test query
    echo "<h3>🧪 Step 3: Test query</h3>";
    $stmt = $connection->query("SELECT DATABASE() as db_name, CONNECTION_ID() as conn_id");
    $info = $stmt->fetch();
    echo "<p>Database: {$info['db_name']}, Connection ID: {$info['conn_id']}</p>";
    
    // Step 4: Guest settings query
    echo "<h3>⚙️ Step 4: Guest settings query</h3>";
    try {
        $stmt = $connection->prepare("SELECT setting_key, setting_value FROM guest_settings");
        echo "<p>✅ Query prepared</p>";
        
        $result = $stmt->execute();
        echo "<p>Execute result: " . ($result ? 'true' : 'false') . "</p>";
        
        $rows = $stmt->fetchAll();
        echo "<p>Rows fetched: " . count($rows) . "</p>";
        
        if (!empty($rows)) {
            echo "<table border='1'><tr><th>Key</th><th>Value</th></tr>";
            foreach ($rows as $row) {
                echo "<tr><td>{$row['setting_key']}</td><td>{$row['setting_value']}</td></tr>";
            }
            echo "</table>";
        }
        
        // Test FETCH_KEY_PAIR
        $stmt->execute();
        $key_pairs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo "<p>Key-value pairs: " . count($key_pairs) . "</p>";
        echo "<pre>" . print_r($key_pairs, true) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error in guest_settings query: " . $e->getMessage() . "</p>";
        echo "<p>Error code: " . $e->getCode() . "</p>";
        echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
    }
    
    // Step 5: Guest uploads query  
    echo "<h3>📊 Step 5: Guest uploads query</h3>";
    try {
        $stmt = $connection->query("SELECT COUNT(*) as total FROM guest_uploads");
        $count = $stmt->fetch()['total'];
        echo "<p>Total guest uploads: $count</p>";
        
        $stmt = $connection->query("SELECT COUNT(DISTINCT device_id) as unique_devices FROM guest_uploads");
        $unique = $stmt->fetch()['unique_devices'];
        echo "<p>Unique devices: $unique</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error in guest_uploads query: " . $e->getMessage() . "</p>";
    }
    
    // Step 6: Show table structure
    echo "<h3>🏗️ Step 6: Table structures</h3>";
    
    try {
        echo "<h4>guest_settings structure:</h4>";
        $stmt = $connection->query("DESCRIBE guest_settings");
        $columns = $stmt->fetchAll();
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
        
        echo "<h4>guest_uploads structure:</h4>";
        $stmt = $connection->query("DESCRIBE guest_uploads");
        $columns = $stmt->fetchAll();
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error getting table structure: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Critical Error</h3>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<p>Code: " . $e->getCode() . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>🎯 Debug Complete</h3>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
?>

<style>
    body { font-family: Tahoma, Arial; direction: rtl; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { padding: 8px; text-align: right; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .success { color: green; }
    .error { color: red; }
</style>

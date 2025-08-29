<?php
// تست Path Configuration
require_once 'admin/includes/path-config.php';

$pathConfig = PathConfig::getInstance();

echo "<h2>🔍 تست Path Configuration</h2>";
echo "<h3>Debug Info:</h3>";
echo "<pre>";
print_r($pathConfig->debug());
echo "</pre>";

echo "<h3>Generated URLs:</h3>";
echo "<ul>";
echo "<li><strong>Admin Base:</strong> " . admin_url() . "</li>";
echo "<li><strong>Admin Index:</strong> " . admin_url('index.php') . "</li>";
echo "<li><strong>SMS Settings:</strong> " . admin_url('settings/sms.php') . "</li>";
echo "<li><strong>SMS Logs:</strong> " . admin_url('logs/sms-logs.php') . "</li>";
echo "<li><strong>Test SMS:</strong> " . admin_url('settings/test-sms.php') . "</li>";
echo "<li><strong>Assets CSS:</strong> " . admin_url('assets/admin.css') . "</li>";
echo "</ul>";

echo "<h3>Test Links:</h3>";
echo '<p><a href="' . admin_url() . '">Admin Panel</a></p>';
echo '<p><a href="' . admin_url('settings/sms.php') . '">SMS Settings</a></p>';
echo '<p><a href="' . admin_url('logs/sms-logs.php') . '">SMS Logs</a></p>';
?>

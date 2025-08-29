<?php
// Debug Path Info در Admin Panel
require_once 'includes/path-config.php';

$pathConfig = PathConfig::getInstance();
?>
<!DOCTYPE html>
<html>
<head><title>Debug Paths</title></head>
<body>
<h2>Debug Path Info - Admin Panel</h2>
<pre><?php print_r($pathConfig->debug()); ?></pre>

<h3>Generated URLs:</h3>
<ul>
<li>admin_url(): <?php echo admin_url(); ?></li>
<li>admin_url('index.php'): <?php echo admin_url('index.php'); ?></li>
<li>admin_url('settings/sms.php'): <?php echo admin_url('settings/sms.php'); ?></li>
</ul>

<h3>Test Navigation:</h3>
<p><a href="<?php echo admin_url('index.php'); ?>">Dashboard</a></p>
<p><a href="<?php echo admin_url('settings/sms.php'); ?>">SMS Settings</a></p>
<p><a href="<?php echo admin_url('logs/sms-logs.php'); ?>">SMS Logs</a></p>
</body>
</html>

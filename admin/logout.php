<?php
/**
 * زیتو (Xi2) - خروج از پنل ادمین
 */
session_start();

// Log logout
if (isset($_SESSION['admin_username'])) {
    error_log('Xi2 Admin: Logout - User: ' . $_SESSION['admin_username'] . ' - Time: ' . date('Y-m-d H:i:s'));
}

// پاک کردن تمام session ها
session_destroy();

// هدایت به صفحه ورود
header('Location: login.php?logout=1');
exit;
<?php
/**
 * زیتو (Xi2) - بررسی احراز هویت ادمین
 */

// شروع session اگر شروع نشده
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تعیین مسیر ریشه ادمین
$admin_root = dirname(dirname(__FILE__));
$login_path = $admin_root . '/login.php';

// بررسی ورود ادمین
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log('Xi2 Admin: Unauthorized access attempt - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' - URL: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    
    // تعیین مسیر صحیح برای redirect
    $relative_path = '../login.php';
    if (basename(dirname($_SERVER['PHP_SELF'])) === 'settings') {
        $relative_path = '../login.php';
    }
    
    header('Location: ' . $relative_path);
    exit;
}

// بررسی timeout session (2 ساعت)
$timeout = 2 * 60 * 60; // 2 hours in seconds
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $timeout) {
    session_destroy();
    
    error_log('Xi2 Admin: Session timeout - User: ' . ($_SESSION['admin_username'] ?? 'unknown'));
    
    // تعیین مسیر صحیح برای timeout redirect
    $relative_path = '../login.php?timeout=1';
    if (basename(dirname($_SERVER['PHP_SELF'])) === 'settings') {
        $relative_path = '../login.php?timeout=1';
    }
    
    header('Location: ' . $relative_path);
    exit;
}

// تمدید زمان session
$_SESSION['admin_login_time'] = time();

/**
 * تابع بررسی سطح دسترسی (برای آینده)
 */
function check_admin_permission($permission = 'basic') {
    // فعلاً همه دسترسی‌ها مجاز هستند
    return true;
}

/**
 * دریافت نام کاربری ادمین
 */
function get_admin_username() {
    return $_SESSION['admin_username'] ?? 'نامشخص';
}

/**
 * دریافت زمان ورود
 */
function get_login_time() {
    return $_SESSION['admin_login_time'] ?? time();
}

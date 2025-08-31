<?php
/**
 * زیتو (Xi2) - Header مشترک ادمین
 */
if (!defined('ADMIN_PANEL')) {
    define('ADMIN_PANEL', true);
}

// Load path configuration
require_once __DIR__ . '/path-config.php';

$pathConfig = PathConfig::getInstance();
$current_page = basename($_SERVER['PHP_SELF'], '.php');

?>

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'پنل مدیریت زیتو'; ?></title>
    <link rel="stylesheet" href="<?php echo admin_url('assets/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-cog" style="margin-left: 8px;"></i>
                    پنل مدیریت زیتو
                </h3>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo admin_url('index.php'); ?>" class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        داشبورد
                    </a>
                </li>
                
                <!-- منوی مدیریت کاربران -->
                <li class="nav-section">
                    <div class="nav-section-title">
                        <i class="fas fa-users"></i>
                        مدیریت کاربران
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo admin_url('settings/guest-users.php'); ?>" class="nav-link <?php echo $current_page === 'guest-users' ? 'active' : ''; ?>">
                        <i class="fas fa-user-clock"></i>
                        کاربران میهمان
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo admin_url('settings/plus-users.php'); ?>" class="nav-link <?php echo $current_page === 'plus-users' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        کاربران پلاس
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo admin_url('settings/premium-users.php'); ?>" class="nav-link <?php echo $current_page === 'premium-users' ? 'active' : ''; ?>">
                        <i class="fas fa-crown"></i>
                        کاربران پریمیوم
                    </a>
                </li>
                
                <!-- منوی تنظیمات پیامک -->
                <li class="nav-section" style="margin-top: 20px;">
                    <div class="nav-section-title">
                        <i class="fas fa-cog"></i>
                        تنظیمات سیستم
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo admin_url('settings/sms.php'); ?>" class="nav-link <?php echo $current_page === 'sms' ? 'active' : ''; ?>">
                        <i class="fas fa-sms"></i>
                        تنظیمات پیامک
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo admin_url('logs/sms-logs.php'); ?>" class="nav-link <?php echo $current_page === 'sms-logs' ? 'active' : ''; ?>">
                        <i class="fas fa-list-alt"></i>
                        گزارش پیامک‌ها
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo admin_url('settings/test-sms-simple.php'); ?>" class="nav-link <?php echo $current_page === 'test-sms-simple' ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane"></i>
                        تست پیامک
                    </a>
                </li>
                
                <li class="nav-item" style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <a href="<?php echo admin_url('logout.php'); ?>" class="nav-link" onclick="return confirm('آیا مطمئن هستید؟')">
                        <i class="fas fa-sign-out-alt"></i>
                        خروج
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1 class="page-title">
                    <?php echo $page_title ?? 'پنل مدیریت'; ?>
                </h1>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-shield" style="margin-left: 5px;"></i>
                        <?php echo get_admin_username(); ?>
                    </div>
                </div>
            </div>
            
            <div class="content-area">

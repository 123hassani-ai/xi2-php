<?php
/**
 * زیتو (Xi2) - صفحه ورود ادمین
 */
session_start();

// اگر قبلاً وارد شده است، به داشبورد برود
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// پردازش ورود
if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // احراز هویت ساده (hardcode اولیه)
    if ($username === 'admin' && $password === '123456') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        
        // Log successful login
        error_log('Xi2 Admin: Login successful - User: ' . $username . ' - Time: ' . date('Y-m-d H:i:s'));
        
        header('Location: index.php');
        exit;
    } else {
        $error = 'نام کاربری یا رمز عبور اشتباه است';
        error_log('Xi2 Admin: Login failed - Username: ' . $username . ' - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل مدیریت - زیتو</title>
    <link rel="stylesheet" href="assets/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <form method="POST" class="login-form">
            <h2>
                <i class="fas fa-shield-alt" style="color: #6366f1; margin-left: 10px;"></i>
                ورود به پنل مدیریت
            </h2>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle" style="margin-left: 8px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user" style="margin-left: 8px;"></i>
                    نام کاربری
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autocomplete="username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    placeholder="admin"
                >
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock" style="margin-left: 8px;"></i>
                    رمز عبور
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="رمز عبور خود را وارد کنید"
                >
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt" style="margin-left: 8px;"></i>
                ورود
            </button>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #6b7280; font-size: 12px;">
                <i class="fas fa-info-circle" style="margin-left: 5px;"></i>
                برای تست: admin / 123456
            </div>
        </form>
    </div>

    <script>
        // Focus on username field on load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
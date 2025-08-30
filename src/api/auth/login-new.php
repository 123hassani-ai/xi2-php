<?php
/**
 * Xi2 API - User Login
 * ورود کاربر با شماره موبایل و رمز عبور - با پشتیبانی اعداد فارسی
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    $input = ApiManager::getJsonInput();
    
    // تبدیل و پاک‌سازی داده‌های ورودی
    $mobile = PersianUtils::sanitizeInput($input['mobile'] ?? '');
    $password = trim($input['password'] ?? '');
    
    // اعتبارسنجی ورودی
    if (empty($mobile)) {
        ApiManager::sendError('شماره موبایل الزامی است');
    }
    
    if (empty($password)) {
        ApiManager::sendError('رمز عبور الزامی است');
    }
    
    // اعتبارسنجی شماره موبایل با PersianUtils
    $validatedMobile = PersianUtils::validateMobile($mobile);
    if (!$validatedMobile) {
        PersianUtils::logConversion('login_mobile_validation_failed', $mobile, 'false', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        ApiManager::sendError('شماره موبایل نامعتبر است');
    }
    
    // جستجوی کاربر
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, full_name, mobile, password_hash, status, created_at FROM users WHERE mobile = ?");
    $stmt->execute([$validatedMobile]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // لاگ تلاش ورود ناموفق
        error_log("Xi2 Auth: Login Failed - Mobile not found: {$validatedMobile}");
        ApiManager::sendError('شماره موبایل یا رمز عبور اشتباه است');
    }
    
    // بررسی وضعیت کاربر
    if ($user['status'] === 'banned') {
        error_log("Xi2 Auth: Login Blocked - User banned: {$user['id']}");
        ApiManager::sendError('حساب شما مسدود شده است. با پشتیبانی تماس بگیرید');
    }
    
    if ($user['status'] === 'pending') {
        error_log("Xi2 Auth: Login Blocked - User pending: {$user['id']}");
        ApiManager::sendError('حساب شما هنوز تایید نشده است. لطفاً کد تایید را وارد کنید');
    }
    
    // بررسی رمز عبور
    if (!ApiManager::verifyPassword($password, $user['password_hash'])) {
        // لاگ تلاش ورود ناموفق
        error_log("Xi2 Auth: Login Failed - Wrong password for user: {$user['id']}");
        
        // ثبت تلاش ناموفق (محدودیت تلاش)
        recordFailedLoginAttempt($validatedMobile);
        
        ApiManager::sendError('شماره موبایل یا رمز عبور اشتباه است');
    }
    
    // بررسی محدودیت تلاش‌های ناموفق
    if (isUserBlockedDueToFailedAttempts($validatedMobile)) {
        ApiManager::sendError('به دلیل تلاش‌های ناموفق متعدد، حساب شما موقتاً مسدود شده است. 15 دقیقه بعد تلاش کنید');
    }
    
    // شروع Transaction برای ورود موفق
    $db->beginTransaction();
    
    try {
        // تولید توکن session
        $sessionToken = ApiManager::generateToken($user['id']);
        
        // اطلاعات دستگاه
        $deviceInfo = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        
        // ایجاد session جدید
        $apiManager = new ApiManager();
        $sessionCreated = $apiManager->createSession($user['id'], $sessionToken, json_encode($deviceInfo));
        
        if (!$sessionCreated) {
            throw new Exception('خطا در ایجاد جلسه کاری');
        }
        
        // به‌روزرسانی آخرین ورود
        $stmt = $db->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // پاک کردن تلاش‌های ناموفق
        clearFailedLoginAttempts($validatedMobile);
        
        // آمارگیری ورود
        $stmt = $db->prepare("
            INSERT INTO user_activity_logs 
            (user_id, activity_type, ip_address, user_agent, created_at) 
            VALUES (?, 'login', ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user['id'], 
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        $db->commit();
        
        // لاگ موفقیت‌آمیز
        error_log("Xi2 Auth: Login Success - User: {$user['id']} - Mobile: {$validatedMobile}");
        
        PersianUtils::logConversion('login_success', $mobile, $validatedMobile, [
            'user_id' => $user['id'],
            'full_name' => $user['full_name']
        ]);
        
        // پاسخ موفق
        ApiManager::sendResponse(true, [
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'mobile' => PersianUtils::formatMobile($validatedMobile, 'dots'),
                'status' => $user['status'],
                'member_since' => date('Y/m/d', strtotime($user['created_at']))
            ],
            'session' => [
                'token' => $sessionToken,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]
        ], 'ورود با موفقیت انجام شد');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Xi2 Auth: Login Error - " . $e->getMessage());
    ApiManager::sendError('خطا در انجام ورود. لطفاً مجدداً تلاش کنید', 500);
}

/**
 * ثبت تلاش ورود ناموفق
 */
function recordFailedLoginAttempt($mobile) {
    try {
        $db = Database::getInstance();
        
        // حذف تلاش‌های قدیمی (بالای 1 ساعت)
        $stmt = $db->prepare("DELETE FROM failed_login_attempts WHERE mobile = ? AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$mobile]);
        
        // ثبت تلاش جدید
        $stmt = $db->prepare("
            INSERT INTO failed_login_attempts 
            (mobile, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $mobile,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
    } catch (Exception $e) {
        error_log("Xi2 Auth: Error recording failed attempt - " . $e->getMessage());
    }
}

/**
 * بررسی مسدود بودن کاربر به دلیل تلاش‌های ناموفق
 */
function isUserBlockedDueToFailedAttempts($mobile) {
    try {
        $db = Database::getInstance();
        
        // شمارش تلاش‌های ناموفق در 15 دقیقه اخیر
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM failed_login_attempts 
            WHERE mobile = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        
        $stmt->execute([$mobile]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // اگر بیش از 5 تلاش ناموفق باشد
        return ($result['attempt_count'] >= 5);
        
    } catch (Exception $e) {
        error_log("Xi2 Auth: Error checking failed attempts - " . $e->getMessage());
        return false;
    }
}

/**
 * پاک کردن تلاش‌های ناموفق بعد از ورود موفق
 */
function clearFailedLoginAttempts($mobile) {
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM failed_login_attempts WHERE mobile = ?");
        $stmt->execute([$mobile]);
    } catch (Exception $e) {
        error_log("Xi2 Auth: Error clearing failed attempts - " . $e->getMessage());
    }
}
?>

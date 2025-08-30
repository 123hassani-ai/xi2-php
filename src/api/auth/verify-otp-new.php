<?php
/**
 * Xi2 API - Verify OTP
 * تایید کد OTP برای فعال‌سازی حساب کاربری - با پشتیبانی اعداد فارسی
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    $input = ApiManager::getJsonInput();
    
    // تبدیل و پاک‌سازی داده‌های ورودی
    $mobile = PersianUtils::sanitizeInput($input['mobile'] ?? '');
    $otpCode = PersianUtils::sanitizeInput($input['otpCode'] ?? $input['otp'] ?? '');
    
    // اعتبارسنجی ورودی
    if (empty($mobile)) {
        ApiManager::sendError('شماره موبایل الزامی است');
    }
    
    if (empty($otpCode)) {
        ApiManager::sendError('کد تایید الزامی است');
    }
    
    // اعتبارسنجی شماره موبایل با PersianUtils
    $validatedMobile = PersianUtils::validateMobile($mobile);
    if (!$validatedMobile) {
        PersianUtils::logConversion('verify_otp_mobile_validation_failed', $mobile, 'false');
        ApiManager::sendError('شماره موبایل نامعتبر است');
    }
    
    // اعتبارسنجی کد OTP با PersianUtils
    $validatedOTP = PersianUtils::validateOTP($otpCode);
    if (!$validatedOTP) {
        PersianUtils::logConversion('verify_otp_code_validation_failed', $otpCode, 'false');
        ApiManager::sendError('کد تایید نامعتبر است. کد باید ۶ رقم باشد');
    }
    
    $db = Database::getInstance();
    
    // جستجوی کاربر
    $stmt = $db->prepare("SELECT id, full_name, mobile, status FROM users WHERE mobile = ?");
    $stmt->execute([$validatedMobile]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        error_log("Xi2 Auth: OTP Verify Failed - User not found: {$validatedMobile}");
        ApiManager::sendError('کاربری با این شماره موبایل یافت نشد');
    }
    
    // بررسی وضعیت کاربر
    if ($user['status'] === 'banned') {
        ApiManager::sendError('حساب شما مسدود شده است');
    }
    
    if ($user['status'] === 'active') {
        ApiManager::sendError('حساب شما قبلاً فعال شده است');
    }
    
    // جستجوی کد OTP معتبر
    $stmt = $db->prepare("
        SELECT id, code, expires_at, type, attempts 
        FROM otp_codes 
        WHERE user_id = ? AND mobile = ? AND used_at IS NULL 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$user['id'], $validatedMobile]);
    $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        error_log("Xi2 Auth: OTP Verify Failed - No valid OTP for user: {$user['id']}");
        ApiManager::sendError('کد تایید یافت نشد یا قبلاً استفاده شده است');
    }
    
    // بررسی انقضا
    if (strtotime($otpRecord['expires_at']) < time()) {
        error_log("Xi2 Auth: OTP Expired - User: {$user['id']} - Code: {$otpRecord['id']}");
        ApiManager::sendError('کد تایید منقضی شده است. کد جدید درخواست کنید');
    }
    
    // بررسی تعداد تلاش‌ها
    if ($otpRecord['attempts'] >= 5) {
        error_log("Xi2 Auth: OTP Max Attempts - User: {$user['id']} - Code: {$otpRecord['id']}");
        ApiManager::sendError('تعداد تلاش‌های مجاز تمام شده است. کد جدید درخواست کنید');
    }
    
    // مقایسه کد OTP
    if ($otpRecord['code'] !== $validatedOTP) {
        // افزایش تعداد تلاش‌های ناموفق
        $stmt = $db->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        $remainingAttempts = 5 - ($otpRecord['attempts'] + 1);
        
        error_log("Xi2 Auth: OTP Wrong Code - User: {$user['id']} - Remaining: {$remainingAttempts}");
        
        if ($remainingAttempts > 0) {
            ApiManager::sendError("کد تایید اشتباه است. {$remainingAttempts} تلاش باقیمانده");
        } else {
            ApiManager::sendError('کد تایید اشتباه است. تعداد تلاش‌های مجاز تمام شده');
        }
    }
    
    // شروع Transaction برای فعال‌سازی
    $db->beginTransaction();
    
    try {
        // فعال‌سازی حساب کاربر
        $stmt = $db->prepare("
            UPDATE users 
            SET status = 'active', verified_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        // علامت‌گذاری استفاده از کد OTP
        $stmt = $db->prepare("
            UPDATE otp_codes 
            SET used_at = NOW(), verified = 1 
            WHERE id = ?
        ");
        $stmt->execute([$otpRecord['id']]);
        
        // تولید session token برای ورود خودکار
        $sessionToken = ApiManager::generateToken($user['id']);
        
        // ایجاد session
        $apiManager = new ApiManager();
        $deviceInfo = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        
        $sessionCreated = $apiManager->createSession($user['id'], $sessionToken, json_encode($deviceInfo));
        
        if (!$sessionCreated) {
            throw new Exception('خطا در ایجاد جلسه کاری');
        }
        
        // ثبت فعالیت
        $stmt = $db->prepare("
            INSERT INTO user_activity_logs 
            (user_id, activity_type, details, ip_address, user_agent, created_at) 
            VALUES (?, 'account_verified', ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user['id'],
            "OTP verified for type: {$otpRecord['type']}",
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // به‌روزرسانی آخرین ورود
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $db->commit();
        
        // لاگ موفقیت‌آمیز
        error_log("Xi2 Auth: OTP Verify Success - User: {$user['id']} - Mobile: {$validatedMobile}");
        
        PersianUtils::logConversion('verify_otp_success', $otpCode, $validatedOTP, [
            'user_id' => $user['id'],
            'mobile' => $validatedMobile,
            'type' => $otpRecord['type']
        ]);
        
        // پاسخ موفق
        ApiManager::sendResponse(true, [
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'mobile' => PersianUtils::formatMobile($validatedMobile, 'dots'),
                'status' => 'active'
            ],
            'session' => [
                'token' => $sessionToken,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]
        ], 'حساب شما با موفقیت فعال شد و وارد سیستم شدید');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Xi2 Auth: OTP Verify Error - " . $e->getMessage());
    ApiManager::sendError('خطا در تایید کد. لطفاً مجدداً تلاش کنید', 500);
}

/**
 * ارسال مجدد کد OTP
 */
function resendOTP($userId, $mobile, $type = 'register') {
    try {
        $db = Database::getInstance();
        
        // بررسی آخرین ارسال (محدودیت زمانی)
        $stmt = $db->prepare("
            SELECT created_at 
            FROM otp_codes 
            WHERE user_id = ? AND mobile = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute([$userId, $mobile]);
        $lastOTP = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastOTP) {
            $timeDiff = time() - strtotime($lastOTP['created_at']);
            if ($timeDiff < 120) { // 2 دقیقه
                $waitTime = 120 - $timeDiff;
                return [
                    'success' => false, 
                    'message' => "لطفاً {$waitTime} ثانیه صبر کنید"
                ];
            }
        }
        
        // تولید کد جدید
        $newOTPCode = ApiManager::generateOTP();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // ذخیره کد جدید
        $stmt = $db->prepare("
            INSERT INTO otp_codes 
            (user_id, mobile, code, expires_at, type, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $mobile, $newOTPCode, $expiresAt, $type]);
        
        // ارسال پیامک
        $smsResult = sendOTPSMS($mobile, $newOTPCode, $type);
        
        return $smsResult;
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'خطای سیستمی: ' . $e->getMessage()];
    }
}
?>

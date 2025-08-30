<?php
/**
 * Xi2 API - User Registration
 * ثبت‌نام کاربر با پشتیبانی کامل از اعداد فارسی
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    $input = ApiManager::getJsonInput();
    
    // تبدیل و پاک‌سازی داده‌های ورودی
    $fullName = PersianUtils::sanitizeInput($input['name'] ?? $input['fullName'] ?? '');
    $mobile = PersianUtils::sanitizeInput($input['mobile'] ?? '');
    $password = trim($input['password'] ?? '');
    
    // اعتبارسنجی نام
    if (empty($fullName)) {
        ApiManager::sendError('نام کامل الزامی است');
    }
    
    if (strlen($fullName) < 2) {
        ApiManager::sendError('نام باید حداقل 2 کاراکتر باشد');
    }
    
    if (strlen($fullName) > 50) {
        ApiManager::sendError('نام نمی‌تواند بیش از 50 کاراکتر باشد');
    }
    
    // اعتبارسنجی شماره موبایل با PersianUtils
    if (empty($mobile)) {
        ApiManager::sendError('شماره موبایل الزامی است');
    }
    
    $validatedMobile = PersianUtils::validateMobile($mobile);
    if (!$validatedMobile) {
        PersianUtils::logConversion('register_mobile_validation_failed', $mobile, 'false', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        ApiManager::sendError('شماره موبایل نامعتبر است. فرمت صحیح: 09123456789');
    }
    
    // اعتبارسنجی رمز عبور
    if (empty($password)) {
        ApiManager::sendError('رمز عبور الزامی است');
    }
    
    if (strlen($password) < 6) {
        ApiManager::sendError('رمز عبور باید حداقل 6 کاراکتر باشد');
    }
    
    if (strlen($password) > 100) {
        ApiManager::sendError('رمز عبور نمی‌تواند بیش از 100 کاراکتر باشد');
    }
    
    // بررسی وجود کاربر قبلی
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, status FROM users WHERE mobile = ?");
    $stmt->execute([$validatedMobile]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        if ($existingUser['status'] === 'active') {
            ApiManager::sendError('کاربری با این شماره موبایل قبلاً ثبت‌نام کرده است');
        } elseif ($existingUser['status'] === 'pending') {
            ApiManager::sendError('ثبت‌نام شما ناتمام است. لطفاً کد تایید را وارد کنید');
        } elseif ($existingUser['status'] === 'banned') {
            ApiManager::sendError('حساب شما مسدود شده است');
        }
    }
    
    // شروع Transaction
    $db->beginTransaction();
    
    try {
        // رمزگذاری پسورد
        $hashedPassword = ApiManager::hashPassword($password);
        
        // ایجاد کاربر جدید
        $stmt = $db->prepare("
            INSERT INTO users 
            (full_name, mobile, password_hash, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([$fullName, $validatedMobile, $hashedPassword]);
        $userId = $db->lastInsertId();
        
        // تولید کد OTP
        $otpCode = ApiManager::generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // ذخیره کد OTP
        $stmt = $db->prepare("
            INSERT INTO otp_codes 
            (user_id, mobile, code, expires_at, type, created_at) 
            VALUES (?, ?, ?, ?, 'register', NOW())
        ");
        
        $stmt->execute([$userId, $validatedMobile, $otpCode, $otpExpiry]);
        
        // ارسال پیامک (اتصال به سیستم SMS)
        $smsResult = sendOTPSMS($validatedMobile, $otpCode, 'register');
        
        if (!$smsResult['success']) {
            throw new Exception('خطا در ارسال پیامک: ' . $smsResult['message']);
        }
        
        // ثبت در لاگ
        $logStmt = $db->prepare("
            INSERT INTO sms_logs 
            (user_id, mobile, message, type, status, sent_at) 
            VALUES (?, ?, ?, 'register_otp', 'sent', NOW())
        ");
        
        $logStmt->execute([
            $userId, 
            $validatedMobile, 
            "کد تایید ثبت‌نام: {$otpCode}"
        ]);
        
        $db->commit();
        
        // لاگ موفقیت‌آمیز
        error_log("Xi2 Auth: Register Success - User: {$userId} - Mobile: {$validatedMobile}");
        
        PersianUtils::logConversion('register_success', $mobile, $validatedMobile, [
            'user_id' => $userId,
            'full_name' => $fullName
        ]);
        
        ApiManager::sendResponse(true, [
            'user_id' => $userId,
            'mobile' => PersianUtils::formatMobile($validatedMobile, 'dots'),
            'otp_expires_at' => $otpExpiry,
            'next_step' => 'verify_otp'
        ], 'ثبت‌نام با موفقیت انجام شد. کد تایید برای شما ارسال شد');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Xi2 Auth: Register Error - " . $e->getMessage());
    
    // در محیط development جزئیات خطا را نمایش دهیم
    if (defined('XI2_DEBUG') && XI2_DEBUG) {
        ApiManager::sendError('خطا در ثبت‌نام: ' . $e->getMessage(), 500);
    } else {
        ApiManager::sendError('خطا در انجام ثبت‌نام. لطفاً مجدداً تلاش کنید', 500);
    }
}

/**
 * ارسال پیامک کد OTP
 */
function sendOTPSMS($mobile, $code, $type = 'register') {
    try {
        // بارگیری تنظیمات SMS
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM sms_settings WHERE provider = '0098' AND is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            return ['success' => false, 'message' => 'تنظیمات SMS یافت نشد'];
        }
        
        // متن پیامک بر اساس نوع
        $messages = [
            'register' => "کد تایید ثبت‌نام زیتو:\n{$code}\nاین کد تا 10 دقیقه معتبر است.",
            'login' => "کد ورود زیتو:\n{$code}\nاین کد تا 5 دقیقه معتبر است.",
            'reset' => "کد بازیابی رمز عبور:\n{$code}\nاین کد تا 15 دقیقه معتبر است."
        ];
        
        $message = $messages[$type] ?? $messages['register'];
        
        // ارسال با API
        $url = 'https://0098sms.com/sendsmslink.aspx?' . 
               'FROM=' . urlencode($settings['sender_number']) . 
               '&TO=' . urlencode($mobile) . 
               '&TEXT=' . urlencode($message) . 
               '&USERNAME=' . urlencode($settings['api_username']) . 
               '&PASSWORD=' . $settings['api_password'] . // بدون encode
               '&DOMAIN=0098';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Xi2-SMS/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => 'خطا در اتصال: ' . $error];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'کد خطای HTTP: ' . $httpCode];
        }
        
        // بررسی پاسخ
        if (strpos($response, 'OK') !== false) {
            return ['success' => true, 'message' => 'پیامک با موفقیت ارسال شد'];
        } else {
            return ['success' => false, 'message' => 'خطا در ارسال: ' . $response];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'خطای سیستمی: ' . $e->getMessage()];
    }
}
?>

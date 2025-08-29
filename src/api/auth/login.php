<?php
/**
 * Xi2 API - Login User
 * ورود کاربر با شماره موبایل و رمز عبور
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    $input = ApiManager::getJsonInput();
    
    // اعتبارسنجی داده‌های ورودی
    $mobile = trim($input['mobile'] ?? '');
    $password = trim($input['password'] ?? '');
    
    // بررسی خالی نبودن فیلدها
    if (empty($mobile)) {
        ApiManager::sendError('شماره موبایل الزامی است');
    }
    
    if (empty($password)) {
        ApiManager::sendError('رمز عبور الزامی است');
    }
    
    // اعتبارسنجی شماره موبایل
    $mobile = ApiManager::validateMobile($mobile);
    if (!$mobile) {
        ApiManager::sendError('شماره موبایل نامعتبر است');
    }
    
    // جستجوی کاربر
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, full_name, mobile, password_hash, status FROM users WHERE mobile = ?");
    $stmt->execute([$mobile]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        ApiManager::sendError('کاربری با این شماره موبایل یافت نشد');
    }
    
    // بررسی وضعیت کاربر
    if ($user['status'] === 'banned') {
        ApiManager::sendError('حساب شما مسدود شده است. با پشتیبانی تماس بگیرید');
    }
    
    if ($user['status'] === 'pending') {
        // اگر کاربر هنوز تایید نشده، کد OTP جدید ارسال کنیم
        $otpCode = ApiManager::generateOTP();
        $otpExpires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $stmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?");
        $stmt->execute([$otpCode, $otpExpires, $user['id']]);
        
        // TODO: ارسال SMS با کد OTP
        error_log("OTP Code for {$mobile}: {$otpCode}");
        
        ApiManager::sendResponse(true, [
            'userId' => $user['id'],
            'mobile' => $mobile,
            'needsVerification' => true,
            'otpExpires' => $otpExpires
        ], 'حساب شما هنوز تایید نشده. کد تایید جدید ارسال شد');
    }
    
    // بررسی رمز عبور
    if (!ApiManager::verifyPassword($password, $user['password_hash'])) {
        ApiManager::sendError('رمز عبور اشتباه است');
    }
    
    // تولید توکن session
    $sessionToken = ApiManager::generateToken($user['id']);
    
    // ایجاد session جدید
    $apiManager = new ApiManager();
    $apiManager->createSession($user['id'], $sessionToken);
    
    // به‌روزرسانی last_login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // دریافت آمار کاربر
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_uploads,
            COALESCE(SUM(file_size), 0) as total_size,
            COALESCE(SUM(view_count), 0) as total_views
        FROM uploads 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // پاسخ موفقیت‌آمیز
    ApiManager::sendResponse(true, [
        'user' => [
            'id' => $user['id'],
            'fullName' => $user['full_name'],
            'mobile' => $user['mobile'],
            'status' => $user['status']
        ],
        'token' => $sessionToken,
        'stats' => [
            'totalUploads' => (int)$stats['total_uploads'],
            'totalSize' => (int)$stats['total_size'],
            'totalViews' => (int)$stats['total_views']
        ]
    ], 'ورود موفقیت‌آمیز');
    
} catch (Exception $e) {
    error_log("Login API Error: " . $e->getMessage());
    ApiManager::sendError('خطای داخلی سرور', 500);
}

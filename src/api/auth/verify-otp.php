<?php
/**
 * Xi2 API - Verify OTP
 * تایید کد OTP برای فعال‌سازی حساب کاربری
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    $input = ApiManager::getJsonInput();
    
    // اعتبارسنجی داده‌های ورودی
    $mobile = trim($input['mobile'] ?? '');
    $otpCode = trim($input['otpCode'] ?? '');
    
    // بررسی خالی نبودن فیلدها
    if (empty($mobile)) {
        ApiManager::sendError('شماره موبایل الزامی است');
    }
    
    if (empty($otpCode)) {
        ApiManager::sendError('کد تایید الزامی است');
    }
    
    // اعتبارسنجی شماره موبایل
    $mobile = ApiManager::validateMobile($mobile);
    if (!$mobile) {
        ApiManager::sendError('شماره موبایل نامعتبر است');
    }
    
    // بررسی فرمت کد OTP
    if (!preg_match('/^\d{6}$/', $otpCode)) {
        ApiManager::sendError('کد تایید باید ۶ رقم باشد');
    }
    
    // جستجوی کاربر
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT id, full_name, mobile, otp_code, otp_expires, status 
        FROM users 
        WHERE mobile = ?
    ");
    $stmt->execute([$mobile]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        ApiManager::sendError('کاربری با این شماره موبایل یافت نشد');
    }
    
    // بررسی وضعیت کاربر
    if ($user['status'] === 'banned') {
        ApiManager::sendError('حساب شما مسدود شده است');
    }
    
    if ($user['status'] === 'active') {
        ApiManager::sendError('حساب شما قبلاً تایید شده است');
    }
    
    // بررسی انقضای کد OTP
    if (strtotime($user['otp_expires']) < time()) {
        ApiManager::sendError('کد تایید منقضی شده است. درخواست کد جدید دهید');
    }
    
    // بررسی صحت کد OTP
    if ($user['otp_code'] !== $otpCode) {
        ApiManager::sendError('کد تایید اشتباه است');
    }
    
    // فعال‌سازی حساب کاربری
    $sessionToken = ApiManager::generateToken($user['id']);
    
    $stmt = $db->prepare("
        UPDATE users 
        SET status = 'active', 
            otp_code = NULL, 
            otp_expires = NULL, 
            last_login = NOW(),
            login_count = login_count + 1
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$user['id']]);
    
    if (!$result) {
        ApiManager::sendError('خطا در تایید حساب', 500);
    }
    
    // ایجاد session
    $apiManager = new ApiManager();
    $apiManager->createSession($user['id'], $sessionToken);
    
    // ثبت رکورد تنظیمات پیش‌فرض کاربر در جدول settings
    $stmt = $db->prepare("
        INSERT IGNORE INTO settings (key_name, value, category) 
        VALUES 
            (CONCAT('user_', ?, '_max_file_size'), '10485760', 'user'),
            (CONCAT('user_', ?, '_compression_quality'), '85', 'user'),
            (CONCAT('user_', ?, '_auto_delete_days'), '0', 'user')
    ");
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    
    // دریافت آمار اولیه (که باید صفر باشد)
    $stats = [
        'totalUploads' => 0,
        'totalSize' => 0,
        'totalViews' => 0
    ];
    
    // پاسخ موفقیت‌آمیز
    ApiManager::sendResponse(true, [
        'user' => [
            'id' => $user['id'],
            'fullName' => $user['full_name'],
            'mobile' => $user['mobile'],
            'status' => 'active'
        ],
        'token' => $sessionToken,
        'stats' => $stats,
        'isNewUser' => true
    ], 'حساب شما با موفقیت تایید شد. خوش آمدید!', 200);
    
} catch (Exception $e) {
    error_log("Verify OTP API Error: " . $e->getMessage());
    ApiManager::sendError('خطای داخلی سرور', 500);
}

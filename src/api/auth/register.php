<?php
/**
 * API ثبت‌نام
 */

require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    $input = ApiManager::getJsonInput();
    
    // اعتبارسنجی داده‌های ورودی
    $fullName = trim($input['name'] ?? $input['fullName'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $password = trim($input['password'] ?? '');
    
    // بررسی خالی نبودن فیلدها
    if (empty($fullName)) {
        ApiManager::sendError('نام کامل الزامی است');
    }
    
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
    
    // بررسی طول رمز عبور
    if (strlen($password) < 6) {
        ApiManager::sendError('رمز عبور باید حداقل ۶ کاراکتر باشد');
    }
    
    // بررسی تکراری نبودن شماره موبایل
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
    $stmt->execute([$mobile]);
    
    if ($stmt->fetch()) {
        ApiManager::sendError('این شماره موبایل قبلاً ثبت شده است');
    }
    
    // تولید کد OTP
    $otpCode = ApiManager::generateOTP();
    $otpExpires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // رمزگذاری پسورد
    $hashedPassword = ApiManager::hashPassword($password);
    
    // ثبت کاربر جدید (فعلاً بدون نیاز به OTP)
    $stmt = $db->prepare("
        INSERT INTO users (full_name, mobile, password_hash, otp_code, otp_expires, status, level, created_at) 
        VALUES (?, ?, ?, ?, ?, 'active', 1, NOW())
    ");
    
    $result = $stmt->execute([
        $fullName,
        $mobile, 
        $hashedPassword,
        $otpCode,
        $otpExpires
    ]);
    
    if (!$result) {
        ApiManager::sendError('خطا در ثبت کاربر', 500);
    }
    
    $userId = $db->lastInsertId();
    
    // TODO: ارسال SMS با کد OTP (فعلاً در لاگ ذخیره می‌شود)
    error_log("OTP Code for {$mobile}: {$otpCode}");
    
    // پاسخ موفقیت‌آمیز
    ApiManager::sendResponse(true, [
        'userId' => $userId,
        'mobile' => $mobile,
        'needsVerification' => true,
        'otpExpires' => $otpExpires
    ], 'ثبت‌نام موفقیت‌آمیز بود. کد تایید به شماره شما ارسال شد', 201);
    
} catch (Exception $e) {
    error_log("Register API Error: " . $e->getMessage());
    ApiManager::sendError('خطای داخلی سرور', 500);
}

<?php
/**
 * Xi2 API - User Registration - نسخه ساده
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'روش درخواست نامعتبر']);
    exit();
}

try {
    // Load database
    require_once __DIR__ . '/../../database/config.php';
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'داده‌های ورودی نامعتبر']);
        exit();
    }
    
    $fullName = trim($input['name'] ?? $input['fullName'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $password = trim($input['password'] ?? '');
    
    // Validation
    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'نام کامل الزامی است']);
        exit();
    }
    
    if (empty($mobile)) {
        echo json_encode(['success' => false, 'message' => 'شماره موبایل الزامی است']);
        exit();
    }
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور الزامی است']);
        exit();
    }
    
    // Validate mobile format
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        echo json_encode(['success' => false, 'message' => 'فرمت شماره موبایل نادرست است']);
        exit();
    }
    
    // Database connection
    $db = Database::getInstance()->getConnection();
    
    // Check if mobile exists
    $stmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
    $stmt->execute([$mobile]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'این شماره موبایل قبلاً ثبت شده است']);
        exit();
    }
    
    // Generate OTP
    $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpExpires = date('Y-m-d H:i:s', time() + 300); // 5 minutes
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (full_name, mobile, password_hash, otp_code, otp_expires, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'inactive', NOW())
    ");
    
    $result = $stmt->execute([$fullName, $mobile, $passwordHash, $otpCode, $otpExpires]);
    
    if ($result) {
        // Log OTP for testing (در production باید SMS ارسال شود)
        error_log("OTP Code for {$mobile}: {$otpCode}");
        
        echo json_encode([
            'success' => true,
            'message' => 'ثبت‌نام موفق بود. کد تایید ارسال شد',
            'mobile' => $mobile,
            'needsVerification' => true,
            'otpExpires' => $otpExpires
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ثبت‌نام']);
    }
    
} catch (Exception $e) {
    error_log("Register API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای داخلی سرور']);
}
?>

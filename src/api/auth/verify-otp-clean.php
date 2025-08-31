<?php
/**
 * زیتو (Xi2) - API تایید OTP
 * بازنویسی کامل طبق پرامپت شماره 3 - Clean Architecture
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// فقط POST مجاز
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'فقط درخواست POST پذیرفته می‌شود'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // بارگذاری کلاس‌های مورد نیاز
    require_once __DIR__ . '/../../includes/auth-manager.php';
    require_once __DIR__ . '/../../includes/session-handler.php';
    
    // دریافت داده‌های JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('داده‌های ورودی نامعتبر');
    }
    
    // اعتبارسنجی فیلدهای اجباری
    $requiredFields = ['mobile', 'otp_code'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("فیلد {$field} الزامی است");
        }
    }
    
    // بررسی CSRF Token (در صورت وجود)
    $sessionHandler = SessionHandler::getInstance();
    if (isset($input['csrf_token'])) {
        if (!$sessionHandler->verifyCSRFToken($input['csrf_token'])) {
            throw new Exception('توکن امنیتی نامعتبر');
        }
    }
    
    // تایید OTP از طریق AuthManager
    $authManager = AuthManager::getInstance();
    $result = $authManager->verifyOTP(
        trim($input['mobile']),
        trim($input['otp_code'])
    );
    
    if ($result['success']) {
        // تایید موفق
        $sessionHandler->setFlashMessage('success', $result['message']);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'user' => $result['user'],
                'authenticated' => true,
                'auto_login' => true
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // تایید ناموفق
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log("Verify OTP API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی در تایید کد',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

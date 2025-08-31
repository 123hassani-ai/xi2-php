<?php
/**
 * زیتو (Xi2) - API ثبت‌نام کاربر پلاس
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
    $requiredFields = ['full_name', 'mobile', 'password'];
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
    
    // ثبت‌نام از طریق AuthManager
    $authManager = AuthManager::getInstance();
    $result = $authManager->registerPlusUser([
        'full_name' => trim($input['full_name']),
        'mobile' => trim($input['mobile']),
        'password' => $input['password']
    ]);
    
    if ($result['success']) {
        // ثبت‌نام موفق
        $sessionHandler->setFlashMessage('success', $result['message']);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'user_id' => $result['user_id'],
                'mobile' => $result['mobile'],
                'requires_otp' => true
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // ثبت‌نام ناموفق
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log("Register API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی در ثبت‌نام',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

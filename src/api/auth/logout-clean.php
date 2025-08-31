<?php
/**
 * زیتو (Xi2) - API خروج از حساب
 * خروج ایمن کاربر و پاک‌سازی session
 * طراحی شده طبق پرامپت شماره 3 - Clean Architecture
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // بارگذاری کلاس‌های مورد نیاز
    require_once __DIR__ . '/../../includes/auth-manager.php';
    require_once __DIR__ . '/../../includes/session-handler.php';
    
    $authManager = AuthManager::getInstance();
    $sessionHandler = SessionHandler::getInstance();
    
    // خروج از حساب
    $result = $authManager->logout();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'redirect' => '/' // هدایت به صفحه اصلی
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Logout API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی در خروج',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

<?php
/**
 * زیتو (Xi2) - API وضعیت کاربر
 * بررسی وضعیت فعلی کاربر (میهمان، پلاس، پریمیوم)
 * طراحی شده طبق پرامپت شماره 3 - Clean Architecture
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // بارگذاری کلاس‌های مورد نیاز
    require_once __DIR__ . '/../../includes/auth-manager.php';
    require_once __DIR__ . '/../../includes/guest-manager.php';
    require_once __DIR__ . '/../../includes/session-handler.php';
    
    // تشخیص نوع کاربر
    $authManager = AuthManager::getInstance();
    $sessionHandler = SessionHandler::getInstance();
    
    $userType = $authManager->detectUserType();
    $sessionStatus = $sessionHandler->getUserStatus();
    
    $response = [
        'success' => true,
        'user_type' => $userType['type'],
        'authenticated' => $userType['authenticated'],
        'session' => $sessionStatus
    ];
    
    // اطلاعات اضافی بر اساس نوع کاربر
    switch ($userType['type']) {
        case 'guest':
            $guestManager = new GuestManager($userType['data']['device_id']);
            $limitations = $guestManager->checkUploadLimit();
            $upgradeMessage = $guestManager->getUpgradeMessage();
            
            $response['guest_data'] = [
                'device_id' => $userType['data']['device_id'],
                'limitations' => $limitations,
                'upgrade_message' => $upgradeMessage
            ];
            break;
            
        case 'plus':
        case 'premium':
            if ($userType['authenticated']) {
                $response['user_data'] = $userType['data'];
                unset($response['user_data']['password_hash']); // حذف رمز عبور
                
                // آمار کاربر (آپلودها، فضای استفاده شده و...)
                // TODO: پیاده‌سازی آمار کاربر
            }
            break;
    }
    
    // تولید CSRF Token
    $response['csrf_token'] = $sessionHandler->generateCSRFToken();
    
    // پیام‌های Flash
    $flashMessages = $sessionHandler->getAndClearFlashMessages();
    if (!empty($flashMessages)) {
        $response['flash_messages'] = $flashMessages;
    }
    
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("User Status API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی در دریافت وضعیت کاربر',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

<?php
/**
 * Xi2 API - Logout User
 * خروج کاربر و نابودی session
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    $input = ApiManager::getJsonInput();
    $headers = getallheaders();
    
    // دریافت توکن از header یا body
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? $input['token'] ?? '';
    
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        ApiManager::sendError('توکن الزامی است');
    }
    
    // اعتبارسنجی و دریافت اطلاعات کاربر
    $apiManager = new ApiManager();
    $user = $apiManager->validateToken($token);
    
    if (!$user) {
        ApiManager::sendError('توکن نامعتبر است', 401);
    }
    
    // غیرفعال کردن session
    $apiManager = new ApiManager();
    $result = $apiManager->deleteSession($token);
    
    if (!$result) {
        ApiManager::sendError('خطا در خروج از سیستم', 500);
    }
    
    // پاسخ موفقیت‌آمیز
    ApiManager::sendResponse(true, [], 'خروج موفقیت‌آمیز');
    
} catch (Exception $e) {
    error_log("Logout API Error: " . $e->getMessage());
    ApiManager::sendError('خطای داخلی سرور', 500);
}

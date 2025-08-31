<?php
/**
 * زیتو (Xi2) - API آپلود میهمان
 * آپلود فایل برای کاربران میهمان با محدودیت
 * طراحی شده طبق پرامپت شماره 3 - Clean Architecture
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
    require_once __DIR__ . '/../../includes/guest-manager.php';
    require_once __DIR__ . '/../../includes/session-handler.php';
    
    // بررسی وجود فایل
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('فایلی انتخاب نشده یا خطا در آپلود');
    }
    
    $uploadedFile = $_FILES['file'];
    
    // اطلاعات فایل
    $originalName = $uploadedFile['name'];
    $tempPath = $uploadedFile['tmp_name'];
    $fileSize = $uploadedFile['size'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // ایجاد GuestManager
    $guestManager = new GuestManager();
    
    // بررسی محدودیت‌ها
    $limitCheck = $guestManager->checkUploadLimit($fileSize, $extension);
    
    if (!$limitCheck['allowed']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => $limitCheck['message'],
            'reason' => $limitCheck['reason'] ?? 'unknown',
            'upgrade_message' => $guestManager->getUpgradeMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // تولید نام فایل منحصربفرد
    $fileName = uniqid('guest_') . '.' . $extension;
    $uploadDir = __DIR__ . '/../../../storage/uploads/guests/';
    
    // ایجاد دایرکتوری در صورت عدم وجود
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $fileName;
    $relativeFilePath = 'storage/uploads/guests/' . $fileName;
    
    // انتقال فایل
    if (!move_uploaded_file($tempPath, $filePath)) {
        throw new Exception('خطا در ذخیره فایل');
    }
    
    // ثبت در دیتابیس
    $recordResult = $guestManager->recordGuestUpload([
        'file_name' => $fileName,
        'original_name' => $originalName,
        'file_path' => $relativeFilePath,
        'file_size' => $fileSize
    ]);
    
    if ($recordResult['success']) {
        // تولید لینک اشتراک‌گذاری
        $shareLink = $_SERVER['HTTP_HOST'] . '/view/' . $fileName;
        
        // آپدیت محدودیت‌ها برای پاسخ
        $updatedLimits = $guestManager->checkUploadLimit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'فایل با موفقیت آپلود شد',
            'data' => [
                'file_name' => $fileName,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'share_link' => $shareLink,
                'expires_at' => $recordResult['expires_at'],
                'remaining_uploads' => $updatedLimits['remaining_uploads']
            ],
            'upgrade_message' => $guestManager->getUpgradeMessage()
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // حذف فایل فیزیکی در صورت خطا در دیتابیس
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        throw new Exception($recordResult['message']);
    }
    
} catch (Exception $e) {
    error_log("Guest Upload API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

<?php
/**
 * Xi2 API - Delete Upload
 * حذف فایل آپلود شده
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    // بررسی احراز هویت
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        ApiManager::sendError('توکن الزامی است', 401);
    }
    
    $apiManager = new ApiManager();
    $user = $apiManager->validateToken($token);
    
    if (!$user) {
        ApiManager::sendError('توکن نامعتبر است', 401);
    }
    
    // دریافت ID فایل از URL یا body
    $uploadId = $_GET['id'] ?? null;
    
    if (!$uploadId) {
        $input = ApiManager::getJsonInput();
        $uploadId = $input['id'] ?? null;
    }
    
    if (!$uploadId || !is_numeric($uploadId)) {
        ApiManager::sendError('شناسه فایل نامعتبر است');
    }
    
    $db = Database::getInstance();
    
    // جستجوی فایل و بررسی مالکیت
    $stmt = $db->prepare("
        SELECT id, file_name, file_path, user_id 
        FROM uploads 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$uploadId, $user['id']]);
    $upload = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$upload) {
        ApiManager::sendError('فایل یافت نشد یا شما مالک آن نیستید', 404);
    }
    
    // حذف فایل فیزیکی
    $filePath = __DIR__ . '/../../' . $upload['file_path'];
    
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            error_log("Failed to delete file: " . $filePath);
            // ادامه دهیم چون رکورد دیتابیس باید حذف شود
        }
    }
    
    // شروع transaction
    $db->beginTransaction();
    
    try {
        // حذف آمار مربوط به فایل
        $stmt = $db->prepare("DELETE FROM upload_stats WHERE upload_id = ?");
        $stmt->execute([$uploadId]);
        
        // حذف رکورد اصلی
        $stmt = $db->prepare("DELETE FROM uploads WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$uploadId, $user['id']]);
        
        if (!$result || $stmt->rowCount() === 0) {
            throw new Exception('خطا در حذف رکورد از دیتابیس');
        }
        
        // ثبت لاگ فعالیت
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, resource_type, resource_id, details, ip_address, created_at) 
            VALUES (?, 'delete', 'upload', ?, ?, ?, NOW())
        ");
        
        $details = json_encode([
            'file_name' => $upload['file_name'],
            'file_path' => $upload['file_path']
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt->execute([
            $user['id'],
            $uploadId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        
        $db->commit();
        
        // محاسبه آمار جدید کاربر
        $statsQuery = "
            SELECT 
                COUNT(*) as totalUploads,
                COALESCE(SUM(file_size), 0) as totalSize,
                COALESCE(SUM(view_count), 0) as totalViews,
                COALESCE(SUM(download_count), 0) as totalDownloads
            FROM uploads 
            WHERE user_id = ?
        ";
        
        $statsStmt = $db->prepare($statsQuery);
        $statsStmt->execute([$user['id']]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // پاسخ موفقیت‌آمیز
        ApiManager::sendResponse(true, [
            'deletedId' => (int)$uploadId,
            'stats' => [
                'totalUploads' => (int)$stats['totalUploads'],
                'totalSize' => (int)$stats['totalSize'],
                'totalSizeFormatted' => formatBytes($stats['totalSize']),
                'totalViews' => (int)$stats['totalViews'],
                'totalDownloads' => (int)$stats['totalDownloads']
            ]
        ], 'فایل با موفقیت حذف شد');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete Upload API Error: " . $e->getMessage());
    ApiManager::sendError('خطای داخلی سرور', 500);
}

/**
 * فرمت کردن حجم فایل
 */
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

<?php
/**
 * Xi2 API - Get User Uploads
 * دریافت لیست آپلودهای کاربر
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // پارامترهای pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // فیلتر جستجو
    $search = trim($_GET['search'] ?? '');
    $orderBy = $_GET['order'] ?? 'created_at';
    $orderDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    
    // اعتبارسنجی order by
    $allowedOrderBy = ['created_at', 'original_name', 'file_size', 'view_count', 'download_count'];
    if (!in_array($orderBy, $allowedOrderBy)) {
        $orderBy = 'created_at';
    }
    
    $db = Database::getInstance();
    
    // شرط جستجو
    $searchCondition = '';
    $searchParams = [$user['id']];
    
    if (!empty($search)) {
        $searchCondition = ' AND (original_name LIKE ? OR description LIKE ?)';
        $searchParams[] = "%{$search}%";
        $searchParams[] = "%{$search}%";
    }
    
    // شمارش کل رکوردها
    $countQuery = "SELECT COUNT(*) as total FROM uploads WHERE user_id = ? {$searchCondition}";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($searchParams);
    $totalCount = $countStmt->fetch()['total'];
    
    // دریافت آپلودها
    $query = "
        SELECT 
            id, file_name, original_name, file_path, file_size, mime_type, 
            short_link, description, view_count, download_count, 
            compression_level, metadata, is_public, expires_at, created_at
        FROM uploads 
        WHERE user_id = ? {$searchCondition}
        ORDER BY {$orderBy} {$orderDir}
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($searchParams);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // URL پایه برای فایل‌ها
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . str_replace('/src/api/upload/list.php', '', $_SERVER['REQUEST_URI']);
    
    // پردازش داده‌ها
    $processedUploads = array_map(function($upload) use ($baseUrl) {
        return [
            'id' => (int)$upload['id'],
            'fileName' => $upload['file_name'],
            'originalName' => $upload['original_name'],
            'size' => (int)$upload['file_size'],
            'sizeFormatted' => formatBytes($upload['file_size']),
            'type' => $upload['mime_type'],
            'shortLink' => $upload['short_link'],
            'description' => $upload['description'],
            'viewCount' => (int)$upload['view_count'],
            'downloadCount' => (int)$upload['download_count'],
            'compressionLevel' => $upload['compression_level'],
            'metadata' => json_decode($upload['metadata'], true),
            'isPublic' => (bool)$upload['is_public'],
            'expiresAt' => $upload['expires_at'],
            'createdAt' => $upload['created_at'],
            'fileUrl' => $baseUrl . '/' . $upload['file_path'],
            'shareUrl' => $baseUrl . '/view/' . $upload['short_link'],
            'thumbnail' => $baseUrl . '/thumb/' . $upload['short_link']
        ];
    }, $uploads);
    
    // محاسبه اطلاعات pagination
    $totalPages = ceil($totalCount / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    // آمار کلی کاربر
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
    
    // پاسخ نهایی
    ApiManager::sendResponse(true, [
        'uploads' => $processedUploads,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => (int)$totalCount,
            'limit' => $limit,
            'hasNextPage' => $hasNextPage,
            'hasPrevPage' => $hasPrevPage
        ],
        'stats' => [
            'totalUploads' => (int)$stats['totalUploads'],
            'totalSize' => (int)$stats['totalSize'],
            'totalSizeFormatted' => formatBytes($stats['totalSize']),
            'totalViews' => (int)$stats['totalViews'],
            'totalDownloads' => (int)$stats['totalDownloads']
        ],
        'filters' => [
            'search' => $search,
            'orderBy' => $orderBy,
            'orderDir' => $orderDir
        ]
    ], 'لیست آپلودها با موفقیت دریافت شد');
    
} catch (Exception $e) {
    error_log("List Uploads API Error: " . $e->getMessage());
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

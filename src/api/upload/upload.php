<?php
/**
 * Xi2 API - Upload File
 * آپلود فایل تصویری با پردازش و بهینه‌سازی
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiManager::sendError('روش درخواست نامعتبر', 405);
}

try {
    // بررسی احراز هویت (اختیاری برای مهمان)
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    $user = null;
    $userId = null;
    
    if (!empty($token)) {
        $apiManager = new ApiManager();
        $user = $apiManager->validateToken($token);
        if ($user) {
            $userId = $user['id'];
        }
    }
    
    // بررسی آپلود مهمان
    $db = Database::getInstance();
    $guestUploadEnabled = $db->getSetting('enable_guest_upload', '1');
    
    if (!$userId && $guestUploadEnabled !== '1') {
        ApiManager::sendError('برای آپلود ابتدا وارد شوید', 401);
    }
    
    // بررسی وجود فایل
    if (!isset($_FILES['file'])) {
        ApiManager::sendError('فایلی انتخاب نشده است');
    }
    
    $file = $_FILES['file'];
    
    // بررسی خطاهای آپلود
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'فایل بزرگتر از حد مجاز است',
            UPLOAD_ERR_FORM_SIZE => 'فایل بزرگتر از حد مجاز فرم است',
            UPLOAD_ERR_PARTIAL => 'فایل به صورت ناقص آپلود شد',
            UPLOAD_ERR_NO_FILE => 'فایلی انتخاب نشده',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت موجود نیست',
            UPLOAD_ERR_CANT_WRITE => 'خطا در نوشتن فایل',
            UPLOAD_ERR_EXTENSION => 'آپلود توسط extension متوقف شد'
        ];
        
        $errorMessage = $errors[$file['error']] ?? 'خطای نامشخص در آپلود';
        ApiManager::sendError($errorMessage);
    }
    
    // دریافت تنظیمات
    $maxFileSize = (int)$db->getSetting('max_file_size', '10485760'); // 10MB
    $allowedExtensions = explode(',', $db->getSetting('allowed_extensions', 'jpg,jpeg,png,gif,webp'));
    $compressionQuality = (int)$db->getSetting('compression_quality', '85');
    
    // بررسی حجم فایل
    if ($file['size'] > $maxFileSize) {
        $maxSizeMB = round($maxFileSize / 1048576, 1);
        ApiManager::sendError("حجم فایل نباید از {$maxSizeMB} مگابایت بیشتر باشد");
    }
    
    // بررسی نوع فایل
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedExtensions)) {
        ApiManager::sendError('فرمت فایل مجاز نیست. فرمت‌های مجاز: ' . implode(', ', $allowedExtensions));
    }
    
    // بررسی MIME type
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    $expectedMime = $allowedMimes[$extension] ?? '';
    if ($file['type'] !== $expectedMime) {
        ApiManager::sendError('نوع فایل با پسوند سازگار نیست');
    }
    
    // تولید نام فایل یکتا
    $fileName = uniqid('xi2_') . '_' . time() . '.' . $extension;
    $shortLink = generateShortLink();
    
    // مسیر ذخیره‌سازی
    $storagePath = __DIR__ . '/../../storage/uploads/';
    $uploadPath = $storagePath . date('Y/m/d/');
    
    // ایجاد پوشه در صورت عدم وجود
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $filePath = $uploadPath . $fileName;
    $relativePath = 'storage/uploads/' . date('Y/m/d/') . $fileName;
    
    // انتقال فایل
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        ApiManager::sendError('خطا در ذخیره فایل', 500);
    }
    
    // پردازش تصویر (فشرده‌سازی)
    $processedFile = processImage($filePath, $compressionQuality);
    $finalSize = filesize($processedFile);
    
    // دریافت metadata تصویر
    $metadata = getImageMetadata($processedFile);
    
    // ذخیره در دیتابیس
    $stmt = $db->prepare("
        INSERT INTO uploads 
        (user_id, file_name, original_name, file_path, file_size, mime_type, short_link, metadata, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $userId,
        $fileName,
        $file['name'],
        $relativePath,
        $finalSize,
        $file['type'],
        $shortLink,
        json_encode($metadata, JSON_UNESCAPED_UNICODE)
    ]);
    
    if (!$result) {
        // حذف فایل در صورت خطا در دیتابیس
        unlink($processedFile);
        ApiManager::sendError('خطا در ذخیره اطلاعات فایل', 500);
    }
    
    $uploadId = $db->lastInsertId();
    
    // URL دسترسی
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . str_replace('/src/api/upload/upload.php', '', $_SERVER['REQUEST_URI']);
    
    $fileUrl = $baseUrl . '/' . $relativePath;
    $shareUrl = $baseUrl . '/view/' . $shortLink;
    
    // پاسخ موفقیت‌آمیز
    ApiManager::sendResponse(true, [
        'upload' => [
            'id' => $uploadId,
            'fileName' => $fileName,
            'originalName' => $file['name'],
            'size' => $finalSize,
            'sizeFormatted' => formatBytes($finalSize),
            'type' => $file['type'],
            'shortLink' => $shortLink,
            'fileUrl' => $fileUrl,
            'shareUrl' => $shareUrl,
            'metadata' => $metadata
        ],
        'user' => $user ? [
            'id' => $user['id'],
            'fullName' => $user['full_name']
        ] : null
    ], 'فایل با موفقیت آپلود شد', 201);
    
} catch (Exception $e) {
    error_log("Upload API Error: " . $e->getMessage());
    ApiManager::sendError('خطای داخلی سرور', 500);
}

/**
 * تولید لینک کوتاه یکتا
 */
function generateShortLink($length = 6) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $db = Database::getInstance();
    
    do {
        $shortLink = '';
        for ($i = 0; $i < $length; $i++) {
            $shortLink .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // بررسی تکراری نبودن
        $stmt = $db->prepare("SELECT id FROM uploads WHERE short_link = ?");
        $stmt->execute([$shortLink]);
    } while ($stmt->fetch());
    
    return $shortLink;
}

/**
 * پردازش و فشرده‌سازی تصویر
 */
function processImage($filePath, $quality = 85) {
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return $filePath; // اگر تصویر نیست، همان فایل را برگردان
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // اگر تصویر کوچک است، پردازش نکن
    if ($width <= 1920 && $height <= 1080 && filesize($filePath) <= 2097152) { // 2MB
        return $filePath;
    }
    
    // ایجاد resource تصویر
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($filePath);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($filePath);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($filePath);
            break;
        default:
            return $filePath;
    }
    
    if (!$image) {
        return $filePath;
    }
    
    // محاسبه ابعاد جدید (حداکثر 1920x1080)
    $maxWidth = 1920;
    $maxHeight = 1080;
    
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // حفظ شفافیت برای PNG و GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    
    // ذخیره تصویر پردازش شده
    $processedPath = $filePath;
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($image, $processedPath, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($image, $processedPath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($image, $processedPath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($image, $processedPath, $quality);
            break;
    }
    
    imagedestroy($image);
    return $processedPath;
}

/**
 * استخراج metadata تصویر
 */
function getImageMetadata($filePath) {
    $metadata = [];
    
    // اطلاعات پایه
    $imageInfo = getimagesize($filePath);
    if ($imageInfo) {
        $metadata['width'] = $imageInfo[0];
        $metadata['height'] = $imageInfo[1];
        $metadata['type'] = $imageInfo[2];
        $metadata['mime'] = $imageInfo['mime'];
    }
    
    // اطلاعات EXIF (برای JPEG)
    if (function_exists('exif_read_data') && $imageInfo[2] == IMAGETYPE_JPEG) {
        $exif = @exif_read_data($filePath);
        if ($exif) {
            $metadata['exif'] = [
                'camera' => $exif['Model'] ?? '',
                'software' => $exif['Software'] ?? '',
                'datetime' => $exif['DateTime'] ?? '',
                'orientation' => $exif['Orientation'] ?? 1
            ];
        }
    }
    
    return $metadata;
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

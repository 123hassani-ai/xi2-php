<?php
/**
 * زیتو (Xi2) - مدیر کاربران میهمان (Guest Manager)
 * مدیریت آپلود و محدودیت‌های کاربران میهمان
 * طراحی شده طبق پرامپت شماره 3 - Clean Architecture
 */

require_once __DIR__ . '/../database/config.php';

class GuestManager {
    
    private $db;
    private $deviceId;
    private $ipAddress;
    
    /**
     * سازنده
     * @param string|null $deviceId شناسه دستگاه
     */
    public function __construct($deviceId = null) {
        $this->db = Database::getInstance();
        $this->deviceId = $deviceId ?: $this->generateDeviceId();
        $this->ipAddress = $this->getUserIP();
    }
    
    /**
     * ایجاد device ID منحصربفرد
     * @return string شناسه دستگاه
     */
    public function generateDeviceId() {
        session_start();
        
        if (isset($_SESSION['device_id'])) {
            return $_SESSION['device_id'];
        }
        
        // ترکیب عوامل مختلف برای ایجاد شناسه منحصربفرد
        $factors = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $this->getUserIP(),
            microtime(true)
        ];
        
        $deviceId = 'guest_' . substr(md5(implode('|', $factors)), 0, 12) . '_' . time();
        $_SESSION['device_id'] = $deviceId;
        
        return $deviceId;
    }
    
    /**
     * بررسی محدودیت آپلود میهمان
     * @param int $fileSize حجم فایل (بایت)
     * @param string $extension پسوند فایل
     * @return array نتیجه بررسی
     */
    public function checkUploadLimit($fileSize = 0, $extension = '') {
        $settings = $this->getGuestSettings();
        
        // شمارش آپلودهای فعلی
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as upload_count, 
                   COALESCE(SUM(file_size), 0) as total_size,
                   MAX(created_at) as last_upload
            FROM guest_uploads 
            WHERE device_id = ? 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$this->deviceId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $result = [
            'allowed' => true,
            'device_id' => $this->deviceId,
            'current_uploads' => (int)$current['upload_count'],
            'max_uploads' => $settings['max_uploads'],
            'remaining_uploads' => max(0, $settings['max_uploads'] - (int)$current['upload_count']),
            'current_size' => (int)$current['total_size'],
            'max_file_size' => $settings['max_file_size'],
            'allowed_extensions' => explode(',', $settings['allowed_extensions']),
            'expires_days' => $settings['expires_days'],
            'last_upload' => $current['last_upload']
        ];
        
        // بررسی تعداد آپلود
        if ($current['upload_count'] >= $settings['max_uploads']) {
            $result['allowed'] = false;
            $result['reason'] = 'upload_limit_exceeded';
            $result['message'] = "شما به حداکثر تعداد آپلود ({$settings['max_uploads']}) رسیده‌اید. برای آپلود نامحدود ثبت‌نام کنید.";
            return $result;
        }
        
        // بررسی حجم فایل
        if ($fileSize > 0 && $fileSize > $settings['max_file_size']) {
            $maxSizeMB = round($settings['max_file_size'] / 1024 / 1024, 1);
            $result['allowed'] = false;
            $result['reason'] = 'file_size_exceeded';
            $result['message'] = "حجم فایل بیش از حد مجاز ({$maxSizeMB}MB) است.";
            return $result;
        }
        
        // بررسی پسوند فایل
        if ($extension && !in_array(strtolower($extension), $result['allowed_extensions'])) {
            $result['allowed'] = false;
            $result['reason'] = 'invalid_extension';
            $result['message'] = "فرمت فایل مجاز نیست. فرمت‌های مجاز: " . implode(', ', $result['allowed_extensions']);
            return $result;
        }
        
        return $result;
    }
    
    /**
     * ثبت آپلود میهمان در دیتابیس
     * @param array $fileData اطلاعات فایل
     * @return array نتیجه ثبت
     */
    public function recordGuestUpload($fileData) {
        try {
            $settings = $this->getGuestSettings();
            
            // محاسبه زمان انقضا
            $expiresAt = null;
            if ($settings['expires_days'] > 0) {
                $expiresAt = date('Y-m-d H:i:s', time() + ($settings['expires_days'] * 24 * 60 * 60));
            }
            
            // درج رکورد آپلود
            $stmt = $this->db->prepare("
                INSERT INTO guest_uploads 
                (device_id, ip_address, file_name, original_name, file_path, file_size, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $this->deviceId,
                $this->ipAddress,
                $fileData['file_name'],
                $fileData['original_name'],
                $fileData['file_path'],
                $fileData['file_size'],
                $expiresAt
            ]);
            
            if ($result) {
                $uploadId = $this->db->lastInsertId();
                
                // بروزرسانی شمارنده آپلود
                $this->updateUploadCount();
                
                return [
                    'success' => true,
                    'upload_id' => $uploadId,
                    'expires_at' => $expiresAt,
                    'message' => 'فایل با موفقیت آپلود شد'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'خطا در ثبت اطلاعات آپلود'
            ];
            
        } catch (Exception $e) {
            error_log("GuestManager Record Upload Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطای سیستمی در ثبت آپلود'
            ];
        }
    }
    
    /**
     * دریافت تنظیمات میهمان از پنل ادمین
     * @return array تنظیمات
     */
    public function getGuestSettings() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM guest_settings WHERE id = 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings) {
                return $settings;
            }
            
            // تنظیمات پیش‌فرض در صورت عدم وجود در دیتابیس
            return [
                'max_uploads' => 10,
                'max_file_size' => 10485760, // 10MB
                'allowed_extensions' => 'jpg,jpeg,png,gif,webp',
                'expires_days' => 30
            ];
            
        } catch (Exception $e) {
            error_log("GuestManager Get Settings Error: " . $e->getMessage());
            return [
                'max_uploads' => 10,
                'max_file_size' => 10485760,
                'allowed_extensions' => 'jpg,jpeg,png,gif,webp',
                'expires_days' => 30
            ];
        }
    }
    
    /**
     * دریافت آمار آپلودهای میهمان
     * @return array آمار
     */
    public function getGuestStats() {
        try {
            // آمار کلی
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_uploads,
                    COUNT(DISTINCT device_id) as unique_devices,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COALESCE(SUM(file_size), 0) as total_size,
                    COALESCE(AVG(file_size), 0) as avg_file_size
                FROM guest_uploads
                WHERE expires_at IS NULL OR expires_at > NOW()
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // آمار روزانه (7 روز گذشته)
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as daily_uploads,
                    COUNT(DISTINCT device_id) as daily_devices
                FROM guest_uploads
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute();
            $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'total' => $stats,
                'daily' => $dailyStats
            ];
            
        } catch (Exception $e) {
            error_log("GuestManager Get Stats Error: " . $e->getMessage());
            return [
                'total' => [],
                'daily' => []
            ];
        }
    }
    
    /**
     * دریافت لیست آپلودهای میهمان خاص
     * @param int $limit حداکثر تعداد
     * @return array لیست آپلودها
     */
    public function getGuestUploads($limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT *,
                       CASE 
                           WHEN expires_at IS NULL THEN 'permanent'
                           WHEN expires_at > NOW() THEN 'active'
                           ELSE 'expired'
                       END as status
                FROM guest_uploads 
                WHERE device_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$this->deviceId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("GuestManager Get Uploads Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * حذف فایل‌های منقضی شده
     * @return array اطلاعات حذف
     */
    public function cleanExpiredUploads() {
        try {
            // دریافت فایل‌های منقضی شده
            $stmt = $this->db->prepare("
                SELECT file_path FROM guest_uploads 
                WHERE expires_at IS NOT NULL AND expires_at < NOW()
            ");
            $stmt->execute();
            $expiredFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // حذف فایل‌های فیزیکی
            $deletedCount = 0;
            foreach ($expiredFiles as $filePath) {
                $fullPath = __DIR__ . '/../../' . $filePath;
                if (file_exists($fullPath) && unlink($fullPath)) {
                    $deletedCount++;
                }
            }
            
            // حذف رکوردها از دیتابیس
            $stmt = $this->db->prepare("
                DELETE FROM guest_uploads 
                WHERE expires_at IS NOT NULL AND expires_at < NOW()
            ");
            $stmt->execute();
            $deletedRecords = $stmt->rowCount();
            
            return [
                'deleted_files' => $deletedCount,
                'deleted_records' => $deletedRecords
            ];
            
        } catch (Exception $e) {
            error_log("GuestManager Clean Expired Error: " . $e->getMessage());
            return [
                'deleted_files' => 0,
                'deleted_records' => 0
            ];
        }
    }
    
    /**
     * تشویق کاربر میهمان به ثبت‌نام
     * @return array پیام تشویقی
     */
    public function getUpgradeMessage() {
        $settings = $this->getGuestSettings();
        $stats = $this->checkUploadLimit();
        
        $remaining = $stats['remaining_uploads'];
        
        if ($remaining <= 0) {
            return [
                'type' => 'critical',
                'title' => '🚀 آپلود نامحدود!',
                'message' => 'شما به حداکثر آپلود رسیده‌اید. با ثبت‌نام رایگان از آپلود نامحدود لذت ببرید!',
                'cta' => 'ثبت‌نام رایگان',
                'benefits' => [
                    'آپلود نامحدود تصاویر',
                    'داشبورد مدیریت فایل‌ها', 
                    'حجم فایل بیشتر (50MB)',
                    'پشتیبانی از PDF',
                    'بدون انقضا'
                ]
            ];
        } elseif ($remaining <= 3) {
            return [
                'type' => 'warning',
                'title' => '⚠️ آپلودهای باقی‌مانده: ' . $remaining,
                'message' => 'با ثبت‌نام رایگان از آپلود نامحدود استفاده کنید',
                'cta' => 'عضو شوید',
                'benefits' => [
                    'آپلود نامحدود',
                    'مدیریت فایل‌ها',
                    'فایل‌های بزرگ‌تر'
                ]
            ];
        }
        
        return [
            'type' => 'info',
            'title' => '💡 آیا می‌دانستید؟',
            'message' => 'با عضویت رایگان از امکانات ویژه استفاده کنید',
            'cta' => 'ثبت‌نام',
            'benefits' => [
                'آپلود نامحدود',
                'داشبورد شخصی'
            ]
        ];
    }
    
    /**
     * دریافت IP کاربر
     * @return string آدرس IP
     */
    private function getUserIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP']; // CloudFlare
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * بروزرسانی شمارنده آپلود
     */
    private function updateUploadCount() {
        try {
            $stmt = $this->db->prepare("
                UPDATE guest_uploads 
                SET upload_count = (
                    SELECT COUNT(*) FROM guest_uploads g2 
                    WHERE g2.device_id = guest_uploads.device_id
                )
                WHERE device_id = ?
            ");
            $stmt->execute([$this->deviceId]);
        } catch (Exception $e) {
            error_log("GuestManager Update Count Error: " . $e->getMessage());
        }
    }
}

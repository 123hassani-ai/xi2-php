<?php
/**
 * زیتو (Xi2) - کانفیگوریشن پایگاه داده
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $username;
    private $password;
    private $database;
    private $charset;
    
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    private function loadConfig() {
        // برای development همیشه از config محلی استفاده کن
        $this->host = 'localhost';
        $this->username = 'root';
        $this->password = 'Mojtab@123';
        $this->database = 'xi2_db';
        $this->charset = 'utf8mb4';
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log('خطا در اتصال پایگاه داده: ' . $e->getMessage());
            throw new Exception('خطا در اتصال به پایگاه داده: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * ایجاد جداول پایگاه داده
     */
    public function createTables() {
        $tables = $this->getTableSchemas();
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->connection->exec($sql);
                echo "جدول {$tableName} با موفقیت ایجاد شد.\n";
            } catch (PDOException $e) {
                if ($e->getCode() != 42000) { // Table already exists
                    error_log("خطا در ایجاد جدول {$tableName}: " . $e->getMessage());
                }
            }
        }
        
        $this->insertDefaultData();
    }
    
    private function getTableSchemas() {
        return [
            'users' => "
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `full_name` varchar(100) NOT NULL,
                    `mobile` varchar(11) NOT NULL UNIQUE,
                    `password_hash` varchar(255) NOT NULL,
                    `status` enum('active','inactive','banned') DEFAULT 'inactive',
                    `level` tinyint(1) DEFAULT 1 COMMENT '1-5: سطح کاربری',
                    `otp_code` varchar(6) DEFAULT NULL,
                    `otp_expires` datetime DEFAULT NULL,
                    `email_verified_at` timestamp NULL DEFAULT NULL,
                    `remember_token` varchar(100) DEFAULT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `last_login` timestamp NULL DEFAULT NULL,
                    `login_count` int(11) DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY `idx_mobile` (`mobile`),
                    KEY `idx_status` (`status`),
                    KEY `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'uploads' => "
                CREATE TABLE IF NOT EXISTS `uploads` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `file_name` varchar(255) NOT NULL,
                    `original_name` varchar(255) NOT NULL,
                    `file_path` varchar(500) NOT NULL,
                    `file_size` bigint(20) NOT NULL,
                    `mime_type` varchar(100) NOT NULL,
                    `short_link` varchar(8) NOT NULL UNIQUE,
                    `description` text DEFAULT NULL,
                    `view_count` int(11) DEFAULT 0,
                    `download_count` int(11) DEFAULT 0,
                    `compression_level` enum('low','medium','high') DEFAULT 'medium',
                    `metadata` json DEFAULT NULL COMMENT 'اطلاعات اضافی تصویر',
                    `is_public` tinyint(1) DEFAULT 1,
                    `expires_at` datetime DEFAULT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    UNIQUE KEY `idx_short_link` (`short_link`),
                    KEY `idx_user_id` (`user_id`),
                    KEY `idx_created_at` (`created_at`),
                    KEY `idx_is_public` (`is_public`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'settings' => "
                CREATE TABLE IF NOT EXISTS `settings` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `key_name` varchar(100) NOT NULL UNIQUE,
                    `value` text NOT NULL,
                    `category` varchar(50) DEFAULT 'general',
                    `description` varchar(255) DEFAULT NULL,
                    `is_public` tinyint(1) DEFAULT 0 COMMENT 'آیا در کلاینت قابل دسترسی باشد',
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_key_name` (`key_name`),
                    KEY `idx_category` (`category`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'user_sessions' => "
                CREATE TABLE IF NOT EXISTS `user_sessions` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `session_token` varchar(255) NOT NULL,
                    `device_info` text DEFAULT NULL,
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_agent` varchar(500) DEFAULT NULL,
                    `is_active` tinyint(1) DEFAULT 1,
                    `expires_at` datetime NOT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    UNIQUE KEY `idx_session_token` (`session_token`),
                    KEY `idx_user_id` (`user_id`),
                    KEY `idx_expires_at` (`expires_at`),
                    KEY `idx_is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'activity_logs' => "
                CREATE TABLE IF NOT EXISTS `activity_logs` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) DEFAULT NULL,
                    `action` varchar(100) NOT NULL,
                    `resource_type` varchar(50) DEFAULT NULL,
                    `resource_id` int(11) DEFAULT NULL,
                    `details` json DEFAULT NULL,
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_agent` varchar(500) DEFAULT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                    KEY `idx_user_id` (`user_id`),
                    KEY `idx_action` (`action`),
                    KEY `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'upload_stats' => "
                CREATE TABLE IF NOT EXISTS `upload_stats` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `upload_id` int(11) NOT NULL,
                    `event_type` enum('view','download','share') NOT NULL,
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_agent` varchar(500) DEFAULT NULL,
                    `referrer` varchar(500) DEFAULT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (`upload_id`) REFERENCES `uploads`(`id`) ON DELETE CASCADE,
                    KEY `idx_upload_id` (`upload_id`),
                    KEY `idx_event_type` (`event_type`),
                    KEY `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
    }
    
    private function insertDefaultData() {
        $defaultSettings = [
            ['site_name', 'زیتو - Xi2', 'general', 'نام سایت'],
            ['site_description', 'پلتفرم هوشمند اشتراک‌گذاری تصاویر', 'general', 'توضیحات سایت'],
            ['max_file_size', '10485760', 'upload', 'حداکثر حجم فایل (بایت)'],
            ['allowed_extensions', 'jpg,jpeg,png,gif,webp', 'upload', 'فرمت‌های مجاز'],
            ['compression_quality', '85', 'upload', 'کیفیت فشرده‌سازی'],
            ['enable_registration', '1', 'auth', 'فعال بودن ثبت‌نام'],
            ['otp_length', '6', 'auth', 'طول کد تایید'],
            ['otp_expiry_minutes', '5', 'auth', 'مدت اعتبار کد تایید (دقیقه)'],
            ['session_lifetime_days', '7', 'auth', 'مدت اعتبار نشست (روز)'],
            ['enable_guest_upload', '1', 'upload', 'آپلود برای مهمان'],
            ['storage_path', '../storage/uploads/', 'upload', 'مسیر ذخیره فایل‌ها'],
            ['cdn_url', '', 'upload', 'URL سرویس CDN'],
            ['watermark_enabled', '0', 'upload', 'فعال بودن واترمارک'],
            ['analytics_enabled', '1', 'general', 'فعال بودن آمارگیری'],
        ];
        
        $stmt = $this->prepare("INSERT IGNORE INTO settings (key_name, value, category, description) VALUES (?, ?, ?, ?)");
        
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
        }
    }
    
    /**
     * دریافت تنظیمات
     */
    public function getSetting($key, $default = null) {
        $stmt = $this->prepare("SELECT value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['value'] : $default;
    }
    
    /**
     * ذخیره تنظیمات
     */
    public function setSetting($key, $value, $category = 'general') {
        $stmt = $this->prepare("
            INSERT INTO settings (key_name, value, category) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE value = ?, updated_at = NOW()
        ");
        
        return $stmt->execute([$key, $value, $category, $value]);
    }
    
    public function __destruct() {
        $this->connection = null;
    }
}

// اتصال سراسری
function getDB() {
    return Database::getInstance();
}
?>

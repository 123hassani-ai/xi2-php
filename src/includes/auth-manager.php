<?php
/**
 * زیتو (Xi2) - مدیر احراز هویت (Auth Manager)
 * مدیریت متمرکز انواع کاربران: میهمان، پلاس، پریمیوم
 * طراحی شده طبق پرامپت شماره 3 - Clean Architecture
 */

require_once __DIR__ . '/persian-utils.php';
require_once __DIR__ . '/../database/config.php';

class AuthManager {
    
    private static $instance = null;
    private $db;
    
    /**
     * Singleton Pattern - تنها یک نمونه از کلاس
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * سازنده - اتصال به دیتابیس
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * تشخیص نوع کاربر بر اساس session و device
     * @return array نوع کاربر و اطلاعات مرتبط
     */
    public function detectUserType() {
        session_start();
        
        // بررسی session کاربر لاگین شده
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
            $user = $this->getUserById($_SESSION['user_id']);
            if ($user) {
                return [
                    'type' => $user['user_type'],
                    'data' => $user,
                    'authenticated' => true
                ];
            }
        }
        
        // کاربر میهمان
        return [
            'type' => 'guest',
            'data' => [
                'device_id' => $this->getOrCreateDeviceId(),
                'ip_address' => $this->getUserIP()
            ],
            'authenticated' => false
        ];
    }
    
    /**
     * مدیریت session کاربران مختلف
     * @param string $userType نوع کاربر
     * @param array|null $userData اطلاعات کاربر
     */
    public function manageUserSession($userType, $userData = null) {
        session_start();
        
        switch ($userType) {
            case 'guest':
                $_SESSION['user_type'] = 'guest';
                $_SESSION['device_id'] = $userData['device_id'] ?? $this->getOrCreateDeviceId();
                $_SESSION['ip_address'] = $this->getUserIP();
                break;
                
            case 'plus':
            case 'premium':
                if ($userData && isset($userData['id'])) {
                    $_SESSION['user_id'] = $userData['id'];
                    $_SESSION['user_type'] = $userData['user_type'];
                    $_SESSION['full_name'] = $userData['full_name'];
                    $_SESSION['mobile'] = $userData['mobile'];
                    $_SESSION['authenticated'] = true;
                    
                    // بروزرسانی آخرین ورود
                    $this->updateLastLogin($userData['id']);
                }
                break;
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * بررسی محدودیت‌های کاربر میهمان
     * @param string $deviceId شناسه دستگاه
     * @return array نتیجه بررسی محدودیت‌ها
     */
    public function checkGuestLimitations($deviceId) {
        // دریافت تنظیمات میهمان
        $settings = $this->getGuestSettings();
        
        // شمارش آپلودهای موجود
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as upload_count, 
                   COALESCE(SUM(file_size), 0) as total_size 
            FROM guest_uploads 
            WHERE device_id = ? 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$deviceId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $result = [
            'allowed' => true,
            'current_uploads' => (int)$current['upload_count'],
            'max_uploads' => $settings['max_uploads'],
            'remaining_uploads' => max(0, $settings['max_uploads'] - (int)$current['upload_count']),
            'max_file_size' => $settings['max_file_size'],
            'allowed_extensions' => explode(',', $settings['allowed_extensions']),
            'expires_days' => $settings['expires_days']
        ];
        
        // بررسی محدودیت تعداد
        if ($current['upload_count'] >= $settings['max_uploads']) {
            $result['allowed'] = false;
            $result['message'] = 'شما به حداکثر تعداد آپلود رسیده‌اید. برای آپلود نامحدود ثبت‌نام کنید.';
        }
        
        return $result;
    }
    
    /**
     * ثبت‌نام کاربر پلاس
     * @param array $userData اطلاعات کاربر
     * @return array نتیجه ثبت‌نام
     */
    public function registerPlusUser($userData) {
        try {
            // اعتبارسنجی داده‌ها
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return $validation;
            }
            
            // تبدیل شماره موبایل با PersianUtils
            $mobile = PersianUtils::convertToEnglishNumbers($userData['mobile']);
            $mobile = PersianUtils::validateMobile($mobile);
            
            if (!$mobile) {
                return ['success' => false, 'message' => 'شماره موبایل نامعتبر است'];
            }
            
            // بررسی عدم وجود کاربر
            if ($this->userExists($mobile)) {
                return ['success' => false, 'message' => 'کاربری با این شماره موبایل قبلاً ثبت‌نام کرده است'];
            }
            
            // تولید OTP
            $otpCode = $this->generateOTP();
            $otpExpires = date('Y-m-d H:i:s', time() + 300); // 5 دقیقه
            
            // هش کردن رمز عبور
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // درج کاربر جدید
            $stmt = $this->db->prepare("
                INSERT INTO users (full_name, mobile, password_hash, user_type, otp_code, otp_expires, status, ip_address)
                VALUES (?, ?, ?, 'plus', ?, ?, 'inactive', ?)
            ");
            
            $result = $stmt->execute([
                trim($userData['full_name']),
                $mobile,
                $passwordHash,
                $otpCode,
                $otpExpires,
                $this->getUserIP()
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                
                // ارسال SMS (در صورت فعال بودن)
                $this->sendOTPSMS($mobile, $otpCode);
                
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'mobile' => $mobile,
                    'message' => 'ثبت‌نام موفق. کد تایید برای شما ارسال شد.'
                ];
            }
            
            return ['success' => false, 'message' => 'خطا در ثبت‌نام کاربر'];
            
        } catch (Exception $e) {
            error_log("AuthManager Register Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطای سیستمی در ثبت‌نام'];
        }
    }
    
    /**
     * ورود کاربر پلاس
     * @param array $credentials اعتبارنامه‌ها
     * @return array نتیجه ورود
     */
    public function loginPlusUser($credentials) {
        try {
            // تبدیل شماره موبایل
            $mobile = PersianUtils::convertToEnglishNumbers($credentials['mobile']);
            $mobile = PersianUtils::validateMobile($mobile);
            
            if (!$mobile) {
                return ['success' => false, 'message' => 'شماره موبایل نامعتبر است'];
            }
            
            // جستجوی کاربر
            $user = $this->getUserByMobile($mobile);
            if (!$user) {
                return ['success' => false, 'message' => 'کاربری با این مشخصات یافت نشد'];
            }
            
            // بررسی رمز عبور
            if (!password_verify($credentials['password'], $user['password_hash'])) {
                return ['success' => false, 'message' => 'رمز عبور اشتباه است'];
            }
            
            // بررسی وضعیت حساب
            if ($user['status'] === 'banned') {
                return ['success' => false, 'message' => 'حساب کاربری شما مسدود شده است'];
            }
            
            if ($user['status'] === 'inactive') {
                return ['success' => false, 'message' => 'حساب کاربری فعال نشده. لطفا ابتدا شماره موبایل را تایید کنید'];
            }
            
            // ورود موفق
            $this->manageUserSession('plus', $user);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'mobile' => $user['mobile'],
                    'user_type' => $user['user_type']
                ],
                'message' => 'ورود موفقیت‌آمیز'
            ];
            
        } catch (Exception $e) {
            error_log("AuthManager Login Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطای سیستمی در ورود'];
        }
    }
    
    /**
     * تایید کد OTP
     * @param string $mobile شماره موبایل
     * @param string $otpCode کد OTP
     * @return array نتیجه تایید
     */
    public function verifyOTP($mobile, $otpCode) {
        try {
            // تبدیل شماره موبایل و کد OTP
            $mobile = PersianUtils::convertToEnglishNumbers($mobile);
            $otpCode = PersianUtils::convertToEnglishNumbers($otpCode);
            
            // جستجوی کاربر
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE mobile = ? AND otp_code = ? 
                AND otp_expires > NOW()
                AND status = 'inactive'
            ");
            $stmt->execute([$mobile, $otpCode]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'کد تایید نامعتبر یا منقضی شده است'];
            }
            
            // فعال‌سازی حساب
            $stmt = $this->db->prepare("
                UPDATE users 
                SET status = 'active', otp_code = NULL, otp_expires = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // اتوماتیک لاگین
            $user['status'] = 'active';
            $this->manageUserSession('plus', $user);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'mobile' => $user['mobile'],
                    'user_type' => $user['user_type']
                ],
                'message' => 'حساب شما با موفقیت فعال شد'
            ];
            
        } catch (Exception $e) {
            error_log("AuthManager OTP Verify Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطای سیستمی در تایید کد'];
        }
    }
    
    /**
     * خروج از حساب
     */
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'با موفقیت خارج شدید'];
    }
    
    /**
     * دریافت تنظیمات کاربر میهمان
     * @return array تنظیمات
     */
    private function getGuestSettings() {
        $stmt = $this->db->prepare("SELECT * FROM guest_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $settings ?: [
            'max_uploads' => 10,
            'max_file_size' => 10485760,
            'allowed_extensions' => 'jpg,jpeg,png,gif,webp',
            'expires_days' => 30
        ];
    }
    
    /**
     * تولید یا دریافت Device ID
     * @return string شناسه دستگاه
     */
    private function getOrCreateDeviceId() {
        session_start();
        
        if (isset($_SESSION['device_id'])) {
            return $_SESSION['device_id'];
        }
        
        // تولید Device ID منحصربفرد
        $deviceId = 'xi2_' . uniqid() . '_' . substr(md5($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 8);
        $_SESSION['device_id'] = $deviceId;
        
        return $deviceId;
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
     * دریافت کاربر با شناسه
     * @param int $userId شناسه کاربر
     * @return array|false اطلاعات کاربر
     */
    private function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * دریافت کاربر با شماره موبایل
     * @param string $mobile شماره موبایل
     * @return array|false اطلاعات کاربر
     */
    private function getUserByMobile($mobile) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE mobile = ?");
        $stmt->execute([$mobile]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * بررسی وجود کاربر
     * @param string $mobile شماره موبایل
     * @return bool وجود کاربر
     */
    private function userExists($mobile) {
        return (bool)$this->getUserByMobile($mobile);
    }
    
    /**
     * اعتبارسنجی داده‌های ثبت‌نام
     * @param array $userData اطلاعات کاربر
     * @return array نتیجه اعتبارسنجی
     */
    private function validateRegistrationData($userData) {
        if (empty($userData['full_name'])) {
            return ['valid' => false, 'message' => 'نام و نام خانوادگی الزامی است'];
        }
        
        if (empty($userData['mobile'])) {
            return ['valid' => false, 'message' => 'شماره موبایل الزامی است'];
        }
        
        if (empty($userData['password']) || strlen($userData['password']) < 6) {
            return ['valid' => false, 'message' => 'رمز عبور باید حداقل ۶ کاراکتر باشد'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * تولید کد OTP
     * @return string کد 6 رقمی
     */
    private function generateOTP() {
        return str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * ارسال SMS کد OTP
     * @param string $mobile شماره موبایل
     * @param string $otpCode کد OTP
     */
    private function sendOTPSMS($mobile, $otpCode) {
        // TODO: پیاده‌سازی ارسال SMS با استفاده از SMS Helper موجود
        error_log("OTP Code for {$mobile}: {$otpCode}");
    }
    
    /**
     * بروزرسانی آخرین ورود کاربر
     * @param int $userId شناسه کاربر
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login = NOW(), login_count = login_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
}

<?php
/**
 * زیتو (Xi2) - مدیر جلسات (Session Handler)
 * مدیریت session های مختلف برای انواع کاربران
 * طراحی شده طبق پرامپت شماره 3 - Clean Architecture
 */

class SessionHandler {
    
    private static $instance = null;
    private $sessionStarted = false;
    
    /**
     * Singleton Pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * شروع session در صورت عدم وجود
     */
    public function startSession() {
        if (!$this->sessionStarted && session_status() === PHP_SESSION_NONE) {
            session_start();
            $this->sessionStarted = true;
        }
    }
    
    /**
     * بررسی وضعیت احراز هویت کاربر
     * @return array وضعیت فعلی کاربر
     */
    public function getUserStatus() {
        $this->startSession();
        
        return [
            'authenticated' => isset($_SESSION['user_id']),
            'user_type' => $_SESSION['user_type'] ?? 'guest',
            'user_id' => $_SESSION['user_id'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'mobile' => $_SESSION['mobile'] ?? null,
            'device_id' => $_SESSION['device_id'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null
        ];
    }
    
    /**
     * ست کردن session کاربر احراز هویت شده
     * @param array $userData اطلاعات کاربر
     */
    public function setAuthenticatedUser($userData) {
        $this->startSession();
        
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_type'] = $userData['user_type'] ?? 'plus';
        $_SESSION['full_name'] = $userData['full_name'];
        $_SESSION['mobile'] = $userData['mobile'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * ست کردن session کاربر میهمان
     * @param string $deviceId شناسه دستگاه
     */
    public function setGuestUser($deviceId) {
        $this->startSession();
        
        $_SESSION['user_type'] = 'guest';
        $_SESSION['device_id'] = $deviceId;
        $_SESSION['authenticated'] = false;
        $_SESSION['guest_start_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * بروزرسانی فعالیت کاربر
     */
    public function updateActivity() {
        $this->startSession();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * بررسی انقضای session
     * @param int $timeout مدت انقضا (ثانیه) - پیش‌فرض: 30 دقیقه
     * @return bool آیا session منقضی شده است
     */
    public function isExpired($timeout = 1800) {
        $this->startSession();
        
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        return (time() - $_SESSION['last_activity']) > $timeout;
    }
    
    /**
     * پاک کردن session
     */
    public function clearSession() {
        $this->startSession();
        
        // ذخیره اطلاعات مورد نیاز
        $deviceId = $_SESSION['device_id'] ?? null;
        
        session_unset();
        session_destroy();
        
        // شروع session جدید برای کاربر میهمان
        session_start();
        if ($deviceId) {
            $_SESSION['device_id'] = $deviceId;
            $_SESSION['user_type'] = 'guest';
        }
    }
    
    /**
     * دریافت اطلاعات session به صورت JSON
     * @return string JSON اطلاعات session
     */
    public function getSessionJSON() {
        $status = $this->getUserStatus();
        return json_encode($status, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * تولید CSRF Token
     * @return string توکن امنیتی
     */
    public function generateCSRFToken() {
        $this->startSession();
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * بررسی CSRF Token
     * @param string $token توکن دریافتی
     * @return bool صحت توکن
     */
    public function verifyCSRFToken($token) {
        $this->startSession();
        
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * ست کردن پیام flash
     * @param string $type نوع پیام (success, error, warning, info)
     * @param string $message متن پیام
     */
    public function setFlashMessage($type, $message) {
        $this->startSession();
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => time()
        ];
    }
    
    /**
     * دریافت و پاک کردن پیام‌های flash
     * @return array پیام‌های flash
     */
    public function getAndClearFlashMessages() {
        $this->startSession();
        
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        
        return $messages;
    }
    
    /**
     * دریافت پیام‌های flash بدون پاک کردن
     * @return array پیام‌های flash
     */
    public function getFlashMessages() {
        $this->startSession();
        return $_SESSION['flash_messages'] ?? [];
    }
}

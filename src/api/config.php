<?php
/**
 * Xi2 API Configuration
 * مدیریت تنظیمات API و header های مشترک
 */

// Headers for API responses
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load database configuration
require_once __DIR__ . '/../database/config.php';

// Load Persian utilities
require_once __DIR__ . '/../includes/persian-utils.php';

/**
 * کلاس مدیریت API
 */
class ApiManager
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * دریافت کاربر از طریق توکن
     */
    public function getUserByToken($token)
    {
        return $this->validateToken($token);
    }
    
    /**
     * ارسال پاسخ JSON
     */
    public static function sendResponse($success = true, $data = [], $message = '', $code = 200)
    {
        http_response_code($code);
        
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * ارسال پاسخ خطا
     */
    public static function sendError($message, $code = 400, $details = [])
    {
        self::sendResponse(false, $details, $message, $code);
    }
    
    /**
     * دریافت داده‌های JSON از درخواست
     */
    public static function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?: [];
    }
    
    /**
     * اعتبارسنجی توکن JWT (ساده)
     */
    public function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }
        
        // بررسی توکن در جدول user_sessions
        $stmt = $this->db->prepare("
            SELECT u.id, u.full_name, u.mobile, us.expires_at
            FROM user_sessions us
            JOIN users u ON us.user_id = u.id 
            WHERE us.session_token = ? 
            AND us.is_active = 1 
            AND us.expires_at > NOW() 
            AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // به‌روزرسانی آخرین فعالیت
            $updateStmt = $this->db->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
            $updateStmt->execute([$token]);
        }
        
        return $result;
    }
    
    /**
     * تولید توکن ساده
     */
    public static function generateToken($userId)
    {
        return hash('sha256', $userId . time() . rand(1000, 9999));
    }
    
    /**
     * ایجاد session جدید
     */
    public function createSession($userId, $token, $deviceInfo = null)
    {
        // اطلاعات درخواست
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_token, device_info, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$userId, $token, $deviceInfo, $ipAddress, $userAgent, $expiresAt]);
    }
    
    /**
     * حذف session
     */
    public function deleteSession($token)
    {
        $stmt = $this->db->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_token = ?");
        return $stmt->execute([$token]);
    }
    
    /**
     * تولید کد OTP
     */
    public static function generateOTP()
    {
        return sprintf('%06d', rand(100000, 999999));
    }
    
    /**
     * اعتبارسنجی شماره موبایل با پشتیبانی اعداد فارسی
     */
    public static function validateMobile($mobile)
    {
        return PersianUtils::validateMobile($mobile);
    }
    
    /**
     * رمزگذاری پسورد
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    /**
     * بررسی پسورد
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}

// Global error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        ApiManager::sendError('خطای سرور: ' . $message, 500);
    }
});

set_exception_handler(function($exception) {
    ApiManager::sendError('خطای سرور: ' . $exception->getMessage(), 500);
});

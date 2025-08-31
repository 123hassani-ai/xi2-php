<?php
/**
 * Xi2 Smart Logging System - Main Logger Class
 * 
 * @description کلاس اصلی سیستم لاگ‌گیری هوشمند زیتو
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 * 
 * این کلاس قلب سیستم لاگ‌گیری Xi2 است که:
 * - همه eventها را capture می‌کند
 * - تحلیل realtime انجام می‌دهد  
 * - مشکلات را پیش‌بینی می‌کند
 * - راه‌حل‌های خودکار ارائه می‌دهد
 * - با GitHub Copilot ارتباط برقرار می‌کند
 */

require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/interfaces/LoggerInterface.php';
require_once __DIR__ . '/Xi2SessionManager.php';
require_once __DIR__ . '/Xi2AIAnalyzer.php';
require_once __DIR__ . '/Xi2AutoFixer.php';

class Xi2SmartLogger implements LoggerInterface
{
    private $db;
    private $sessionManager;
    private $aiAnalyzer;
    private $autoFixer;
    private $currentSession;
    private $isEnabled;
    
    // Configuration
    private const LOG_LEVELS = [
        'debug' => 1,
        'info' => 2, 
        'warning' => 3,
        'error' => 4,
        'critical' => 5
    ];
    
    private const EVENT_TYPES = [
        'click', 'form_submit', 'api_call', 'page_load', 
        'error', 'performance', 'user_journey', 'upload',
        'login', 'logout', 'register', 'view', 'download'
    ];
    
    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->sessionManager = new Xi2SessionManager();
            $this->aiAnalyzer = new Xi2AIAnalyzer();
            $this->autoFixer = new Xi2AutoFixer();
            $this->isEnabled = $this->getLoggerStatus();
            
            $this->initializeLogger();
            
        } catch (Exception $e) {
            error_log("Xi2SmartLogger Initialization Error: " . $e->getMessage());
            $this->isEnabled = false;
        }
    }
    
    /**
     * راه‌اندازی اولیه logger
     */
    private function initializeLogger(): void
    {
        // ایجاد session جدید یا بازیابی موجود
        $this->currentSession = $this->sessionManager->initializeSession();
        
        // پاک‌سازی لاگ‌های قدیمی
        $this->cleanupOldLogs();
        
        // راه‌اندازی monitoring سیستم
        $this->startSystemMonitoring();
    }
    
    /**
     * ثبت event جدید با تحلیل هوشمند
     */
    public function logEvent(array $eventData): bool
    {
        if (!$this->isEnabled) return false;
        
        try {
            // Validation داده‌های ورودی
            $validatedEvent = $this->validateEventData($eventData);
            
            // غنی‌سازی داده‌ها
            $enrichedEvent = $this->enrichEventData($validatedEvent);
            
            // ذخیره در پایگاه داده
            $eventId = $this->storeEventInDatabase($enrichedEvent);
            
            // ذخیره در فایل session
            $this->storeEventInFile($enrichedEvent);
            
            // تحلیل realtime
            $analysis = $this->aiAnalyzer->analyzeEvent($enrichedEvent);
            
            // اعمال راه‌حل‌های خودکار در صورت نیاز
            if (!empty($analysis['issues'])) {
                $this->handleAutomaticFixes($analysis['issues'], $enrichedEvent);
            }
            
            // ارسال به کوپایلوت در صورت نیاز
            if ($this->shouldNotifyCopilot($analysis)) {
                $this->notifyGitHubCopilot($enrichedEvent, $analysis);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Xi2SmartLogger Event Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ثبت خطا با تحلیل کامل
     */
    public function logError(string $message, array $context = [], string $level = 'error'): bool
    {
        $errorData = [
            'timestamp' => microtime(true),
            'session_id' => $this->currentSession['id'],
            'event_type' => 'error',
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $this->getRealUserIP(),
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'user_id' => $this->getCurrentUserId()
        ];
        
        return $this->logEvent($errorData);
    }
    
    /**
     * ثبت فعالیت کاربر
     */
    public function logUserActivity($userId, string $action, array $details = []): bool
    {
        $activityData = [
            'timestamp' => microtime(true),
            'session_id' => $this->currentSession['id'],
            'event_type' => 'user_activity',
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $this->getRealUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null
        ];
        
        return $this->logEvent($activityData);
    }
    
    /**
     * ثبت اطلاعات performance
     */
    public function logPerformance(array $performanceData): bool
    {
        $perfData = [
            'timestamp' => microtime(true),
            'session_id' => $this->currentSession['id'],
            'event_type' => 'performance',
            'performance_metrics' => $performanceData,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'user_id' => $this->getCurrentUserId()
        ];
        
        return $this->logEvent($perfData);
    }
    
    /**
     * دریافت لاگ‌ها بر اساس فیلتر
     */
    public function getLogs(array $filters = [], int $limit = 100): array
    {
        try {
            $sql = "SELECT * FROM activity_logs WHERE 1=1";
            $params = [];
            
            // اعمال فیلترها
            if (!empty($filters['user_id'])) {
                $sql .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['action'])) {
                $sql .= " AND action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Xi2SmartLogger Get Logs Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validation داده‌های event
     */
    private function validateEventData(array $eventData): array
    {
        // بررسی فیلدهای اجباری
        $required = ['timestamp', 'event_type'];
        foreach ($required as $field) {
            if (!isset($eventData[$field])) {
                throw new InvalidArgumentException("Field {$field} is required");
            }
        }
        
        // بررسی نوع event
        if (!in_array($eventData['event_type'], self::EVENT_TYPES)) {
            throw new InvalidArgumentException("Invalid event type: " . $eventData['event_type']);
        }
        
        return $eventData;
    }
    
    /**
     * غنی‌سازی داده‌های event
     */
    private function enrichEventData(array $eventData): array
    {
        // اضافه کردن اطلاعات سیستمی
        $eventData['server_time'] = date('Y-m-d H:i:s');
        $eventData['microtime'] = microtime(true);
        $eventData['php_memory_usage'] = memory_get_usage(true);
        $eventData['session_id'] = $this->currentSession['id'];
        
        // اطلاعات درخواست HTTP
        if (!isset($eventData['ip_address'])) {
            $eventData['ip_address'] = $this->getRealUserIP();
        }
        
        if (!isset($eventData['user_agent'])) {
            $eventData['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
        }
        
        if (!isset($eventData['user_id'])) {
            $eventData['user_id'] = $this->getCurrentUserId();
        }
        
        // تحلیل اطلاعات مرورگر
        $eventData['browser_info'] = $this->parseBrowserInfo($eventData['user_agent']);
        
        return $eventData;
    }
    
    /**
     * ذخیره event در پایگاه داده
     */
    private function storeEventInDatabase(array $eventData): int
    {
        $sql = "INSERT INTO activity_logs 
                (user_id, action, resource_type, resource_id, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $eventData['user_id'],
            $eventData['event_type'],
            $eventData['resource_type'] ?? null,
            $eventData['resource_id'] ?? null,
            json_encode($eventData, JSON_UNESCAPED_UNICODE),
            $eventData['ip_address'],
            $eventData['user_agent']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * ذخیره event در فایل session
     */
    private function storeEventInFile(array $eventData): void
    {
        $this->sessionManager->appendEventToSession($this->currentSession['id'], $eventData);
    }
    
    /**
     * مدیریت راه‌حل‌های خودکار
     */
    private function handleAutomaticFixes(array $issues, array $eventData): void
    {
        foreach ($issues as $issue) {
            if ($this->autoFixer->canAutoFix($issue['type'])) {
                $fixResult = $this->autoFixer->applyFix($issue['type'], [
                    'issue' => $issue,
                    'event' => $eventData,
                    'session' => $this->currentSession
                ]);
                
                // ثبت نتیجه راه‌حل
                $this->autoFixer->logFixResult($issue['type'], $fixResult);
            }
        }
    }
    
    /**
     * بررسی نیاز به اطلاع‌رسانی کوپایلوت
     */
    private function shouldNotifyCopilot(array $analysis): bool
    {
        // شرایطی که نیاز به اطلاع‌رسانی کوپایلوت دارند
        return !empty($analysis['critical_issues']) || 
               !empty($analysis['code_improvement_needed']) ||
               $analysis['confidence_score'] < 0.5;
    }
    
    /**
     * ارسال اطلاعات به GitHub Copilot
     */
    private function notifyGitHubCopilot(array $eventData, array $analysis): void
    {
        // این کار در کلاس Xi2CopilotSync انجام می‌شود
        require_once __DIR__ . '/Xi2CopilotSync.php';
        $copilotSync = new Xi2CopilotSync();
        $copilotSync->syncContextWithCopilot($eventData, $analysis);
    }
    
    // Helper Methods
    
    private function getLoggerStatus(): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT value FROM settings WHERE key_name = 'smart_logging_enabled'");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? (bool)$result['value'] : true;
        } catch (Exception $e) {
            return true; // default enabled
        }
    }
    
    private function getRealUserIP(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = trim($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function getCurrentUserId(): ?int
    {
        // بررسی session یا token کاربر
        if (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        
        // بررسی Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            return $this->getUserIdFromToken($token);
        }
        
        return null;
    }
    
    private function getUserIdFromToken(string $token): ?int
    {
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND is_active = 1 AND expires_at > NOW()");
            $stmt->execute([$token]);
            $result = $stmt->fetch();
            return $result ? (int)$result['user_id'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function parseBrowserInfo(?string $userAgent): array
    {
        if (!$userAgent) return [];
        
        // تشخیص مرورگر و OS
        $browserInfo = [];
        
        // Browser Detection
        if (preg_match('/Chrome/i', $userAgent)) {
            $browserInfo['browser'] = 'Chrome';
            preg_match('/Chrome\/([0-9\.]+)/i', $userAgent, $matches);
            $browserInfo['version'] = $matches[1] ?? 'unknown';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browserInfo['browser'] = 'Firefox';
            preg_match('/Firefox\/([0-9\.]+)/i', $userAgent, $matches);
            $browserInfo['version'] = $matches[1] ?? 'unknown';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browserInfo['browser'] = 'Safari';
            preg_match('/Version\/([0-9\.]+)/i', $userAgent, $matches);
            $browserInfo['version'] = $matches[1] ?? 'unknown';
        }
        
        // OS Detection
        if (preg_match('/Windows NT/i', $userAgent)) {
            $browserInfo['os'] = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $browserInfo['os'] = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $browserInfo['os'] = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $browserInfo['os'] = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $browserInfo['os'] = 'iOS';
        }
        
        return $browserInfo;
    }
    
    private function cleanupOldLogs(): void
    {
        try {
            // حذف لاگ‌های قدیمی‌تر از 30 روز
            $stmt = $this->db->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            
            // پاک‌سازی فایل‌های قدیمی
            $this->sessionManager->cleanupOldSessions(30);
            
        } catch (Exception $e) {
            error_log("Xi2SmartLogger Cleanup Error: " . $e->getMessage());
        }
    }
    
    private function startSystemMonitoring(): void
    {
        // شروع monitoring سیستم در background
        // این کار در آینده با cron job یا background process انجام می‌شود
    }
    
    /**
     * Singleton pattern برای logger
     */
    private static $instance = null;
    
    public static function getInstance(): Xi2SmartLogger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

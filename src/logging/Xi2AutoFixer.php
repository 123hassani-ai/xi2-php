<?php
/**
 * Xi2 Smart Logging System - Auto Fixer
 * 
 * @description رفع خودکار مسائل شناسایی شده
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

require_once __DIR__ . '/interfaces/FixerInterface.php';

class Xi2AutoFixer implements FixerInterface
{
    private $db;
    private $sessionManager;
    private $availableFixes;
    private $fixHistory;
    
    // راه‌حل‌های قابل اعمال خودکار
    private const AUTO_FIXES = [
        'slow_api_response' => [
            'can_auto_fix' => true,
            'priority' => 'high',
            'actions' => ['enable_cache', 'show_loading', 'timeout_handler']
        ],
        'form_validation_error' => [
            'can_auto_fix' => true,
            'priority' => 'medium', 
            'actions' => ['highlight_field', 'show_persian_message', 'scroll_to_error']
        ],
        'upload_failure' => [
            'can_auto_fix' => true,
            'priority' => 'high',
            'actions' => ['retry_with_chunks', 'compress_image', 'fallback_upload']
        ],
        'session_about_to_expire' => [
            'can_auto_fix' => true,
            'priority' => 'medium',
            'actions' => ['refresh_session', 'show_warning', 'auto_save_data']
        ],
        'memory_leak_detected' => [
            'can_auto_fix' => true,
            'priority' => 'critical',
            'actions' => ['garbage_collect', 'optimize_images', 'clear_cache']
        ],
        'user_frustration_detected' => [
            'can_auto_fix' => true,
            'priority' => 'medium',
            'actions' => ['show_help', 'simplify_ui', 'offer_assistance']
        ],
        'database_connection_issue' => [
            'can_auto_fix' => false,
            'priority' => 'critical',
            'actions' => ['retry_connection', 'use_backup_db', 'notify_admin']
        ],
        'performance_degradation' => [
            'can_auto_fix' => true,
            'priority' => 'high', 
            'actions' => ['enable_compression', 'optimize_queries', 'load_balance']
        ]
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->availableFixes = self::AUTO_FIXES;
        $this->fixHistory = [];
        $this->initializeFixer();
    }
    
    /**
     * راه‌اندازی اولیه fixer
     */
    private function initializeFixer(): void
    {
        // بارگذاری تاریخچه راه‌حل‌ها
        $this->loadFixHistory();
        
        // بررسی وضعیت سیستم برای راه‌حل‌های پیشگیرانه
        $this->runPreventiveMaintenance();
    }
    
    /**
     * اعمال راه‌حل خودکار
     */
    public function applyFix(string $issueType, array $context): array
    {
        $result = [
            'issue_type' => $issueType,
            'timestamp' => microtime(true),
            'success' => false,
            'actions_taken' => [],
            'response_time' => 0,
            'error_message' => null,
            'confidence' => 0,
            'user_notification' => null
        ];
        
        $startTime = microtime(true);
        
        try {
            if (!$this->canAutoFix($issueType)) {
                $result['error_message'] = "راه‌حل خودکار برای {$issueType} موجود نیست";
                return $result;
            }
            
            $fixConfig = $this->availableFixes[$issueType];
            $actionsSuccess = [];
            
            foreach ($fixConfig['actions'] as $action) {
                $actionResult = $this->executeFixAction($action, $context, $issueType);
                $actionsSuccess[] = $actionResult;
                $result['actions_taken'][] = [
                    'action' => $action,
                    'success' => $actionResult['success'],
                    'details' => $actionResult['details'] ?? null
                ];
            }
            
            // محاسبه نرخ موفقیت کلی
            $successCount = count(array_filter($actionsSuccess, function($a) { return $a['success']; }));
            $result['success'] = $successCount > 0;
            $result['confidence'] = $successCount / count($actionsSuccess);
            
            // تولید پیغام برای کاربر
            $result['user_notification'] = $this->generateUserNotification($issueType, $result);
            
            // ثبت در تاریخچه
            $this->recordFixAttempt($issueType, $result);
            
        } catch (Exception $e) {
            $result['error_message'] = $e->getMessage();
            error_log("Xi2AutoFixer Error: " . $e->getMessage());
        }
        
        $result['response_time'] = microtime(true) - $startTime;
        
        return $result;
    }
    
    /**
     * بررسی امکان رفع خودکار
     */
    public function canAutoFix(string $issueType): bool
    {
        return isset($this->availableFixes[$issueType]) && 
               $this->availableFixes[$issueType]['can_auto_fix'] === true;
    }
    
    /**
     * دریافت لیست راه‌حل‌های موجود
     */
    public function getAvailableFixes(): array
    {
        return array_keys(array_filter($this->availableFixes, function($fix) {
            return $fix['can_auto_fix'] === true;
        }));
    }
    
    /**
     * ثبت نتیجه اعمال راه‌حل
     */
    public function logFixResult(string $issueType, array $fixResult): bool
    {
        try {
            $sql = "INSERT INTO activity_logs 
                    (user_id, action, resource_type, details, ip_address, created_at) 
                    VALUES (?, 'auto_fix_applied', 'system', ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fixResult['user_id'] ?? null,
                json_encode([
                    'issue_type' => $issueType,
                    'fix_result' => $fixResult,
                    'system_context' => [
                        'memory_usage' => memory_get_usage(true),
                        'timestamp' => time()
                    ]
                ], JSON_UNESCAPED_UNICODE),
                $this->getRealUserIP()
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Xi2AutoFixer logFixResult Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * محاسبه نرخ موفقیت راه‌حل‌ها
     */
    public function getSuccessRate(?string $issueType = null): float
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_attempts,
                        SUM(CASE WHEN JSON_EXTRACT(details, '$.fix_result.success') = true THEN 1 ELSE 0 END) as successful_attempts
                    FROM activity_logs 
                    WHERE action = 'auto_fix_applied'";
            
            $params = [];
            
            if ($issueType) {
                $sql .= " AND JSON_EXTRACT(details, '$.issue_type') = ?";
                $params[] = $issueType;
            }
            
            $sql .= " AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"; // آخرین 7 روز
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if ($result['total_attempts'] > 0) {
                return round($result['successful_attempts'] / $result['total_attempts'], 2);
            }
            
            return 0.0;
            
        } catch (Exception $e) {
            error_log("Xi2AutoFixer getSuccessRate Error: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * اجرای عمل رفع مشکل
     */
    private function executeFixAction(string $action, array $context, string $issueType): array
    {
        $result = [
            'action' => $action,
            'success' => false,
            'details' => null,
            'execution_time' => 0
        ];
        
        $startTime = microtime(true);
        
        try {
            switch ($action) {
                case 'enable_cache':
                    $result = $this->enableCaching($context);
                    break;
                    
                case 'show_loading':
                    $result = $this->showLoadingIndicator($context);
                    break;
                    
                case 'highlight_field':
                    $result = $this->highlightProblemField($context);
                    break;
                    
                case 'show_persian_message':
                    $result = $this->showPersianErrorMessage($context);
                    break;
                    
                case 'retry_with_chunks':
                    $result = $this->enableChunkedUpload($context);
                    break;
                    
                case 'compress_image':
                    $result = $this->compressImageBeforeUpload($context);
                    break;
                    
                case 'refresh_session':
                    $result = $this->refreshUserSession($context);
                    break;
                    
                case 'show_warning':
                    $result = $this->showSessionWarning($context);
                    break;
                    
                case 'garbage_collect':
                    $result = $this->performGarbageCollection($context);
                    break;
                    
                case 'show_help':
                    $result = $this->showContextualHelp($context);
                    break;
                    
                case 'simplify_ui':
                    $result = $this->simplifyUserInterface($context);
                    break;
                    
                default:
                    $result['details'] = "عمل {$action} پیاده‌سازی نشده است";
            }
            
        } catch (Exception $e) {
            $result['details'] = "خطا در اجرای {$action}: " . $e->getMessage();
        }
        
        $result['execution_time'] = microtime(true) - $startTime;
        
        return $result;
    }
    
    // راه‌حل‌های خاص
    
    /**
     * فعال کردن cache
     */
    private function enableCaching(array $context): array
    {
        // فعال‌سازی cache برای response های کند
        $headers = [
            'Cache-Control: public, max-age=3600',
            'Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            'ETag: "' . md5(json_encode($context)) . '"'
        ];
        
        foreach ($headers as $header) {
            if (!headers_sent()) {
                header($header);
            }
        }
        
        return [
            'success' => true,
            'details' => 'Cache headers فعال شد',
            'cache_duration' => 3600
        ];
    }
    
    /**
     * نمایش loading indicator
     */
    private function showLoadingIndicator(array $context): array
    {
        // تولید JavaScript برای نمایش loading
        $jsCode = "
            if (typeof Xi2LoadingManager !== 'undefined') {
                Xi2LoadingManager.showIntelligentLoading({
                    message: 'در حال پردازش درخواست شما...',
                    type: 'api_slowness_detected',
                    estimatedTime: 5000
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => 'Loading indicator فعال شد',
            'js_code' => $jsCode
        ];
    }
    
    /**
     * هایلایت کردن فیلد مشکل‌دار
     */
    private function highlightProblemField(array $context): array
    {
        $fieldName = $context['field_name'] ?? 'unknown';
        
        $jsCode = "
            if (typeof Xi2FieldHelper !== 'undefined') {
                Xi2FieldHelper.highlightField('{$fieldName}', {
                    style: 'error-highlight',
                    message: 'لطفاً این فیلد را بررسی کنید',
                    duration: 3000
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => "فیلد {$fieldName} هایلایت شد",
            'js_code' => $jsCode
        ];
    }
    
    /**
     * نمایش پیغام خطای فارسی
     */
    private function showPersianErrorMessage(array $context): array
    {
        $error = $context['error'] ?? 'خطایی رخ داده است';
        $persianMessage = $this->translateErrorToPersian($error);
        
        $jsCode = "
            if (typeof Xi2NotificationManager !== 'undefined') {
                Xi2NotificationManager.showError('{$persianMessage}', {
                    duration: 5000,
                    position: 'top-center',
                    rtl: true
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => 'پیغام فارسی نمایش داده شد',
            'persian_message' => $persianMessage,
            'js_code' => $jsCode
        ];
    }
    
    /**
     * فعال کردن آپلود چندقسمتی
     */
    private function enableChunkedUpload(array $context): array
    {
        $jsCode = "
            if (typeof Xi2UploadManager !== 'undefined') {
                Xi2UploadManager.switchToChunkedMode({
                    chunkSize: 1024 * 1024, // 1MB chunks
                    maxRetries: 3,
                    autoRetry: true
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => 'حالت آپلود چندقسمتی فعال شد',
            'js_code' => $jsCode
        ];
    }
    
    /**
     * فشرده‌سازی تصویر
     */
    private function compressImageBeforeUpload(array $context): array
    {
        $jsCode = "
            if (typeof Xi2ImageCompressor !== 'undefined') {
                Xi2ImageCompressor.enableAutoCompression({
                    quality: 0.8,
                    maxWidth: 1920,
                    maxHeight: 1080,
                    format: 'jpeg'
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => 'فشرده‌سازی خودکار تصاویر فعال شد',
            'js_code' => $jsCode
        ];
    }
    
    /**
     * تمدید session کاربر
     */
    private function refreshUserSession(array $context): array
    {
        try {
            $userId = $context['event']['user_id'] ?? null;
            if (!$userId) {
                return ['success' => false, 'details' => 'کاربر شناسایی نشد'];
            }
            
            // به‌روزرسانی زمان انقضای session
            $sql = "UPDATE user_sessions 
                    SET expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR),
                        last_activity = NOW()
                    WHERE user_id = ? AND is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'details' => 'Session کاربر تمدید شد',
                'new_expiry' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'details' => 'خطا در تمدید session: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * نمایش هشدار session
     */
    private function showSessionWarning(array $context): array
    {
        $jsCode = "
            if (typeof Xi2SessionManager !== 'undefined') {
                Xi2SessionManager.showExpiryWarning({
                    message: 'نشست شما به زودی منقضی می‌شود',
                    timeRemaining: 300000, // 5 minutes
                    autoExtend: true
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => 'هشدار انقضای session نمایش داده شد',
            'js_code' => $jsCode
        ];
    }
    
    /**
     * انجام garbage collection
     */
    private function performGarbageCollection(array $context): array
    {
        $beforeMemory = memory_get_usage(true);
        
        // اجرای garbage collector
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
        } else {
            $collected = 0;
        }
        
        $afterMemory = memory_get_usage(true);
        $freedMemory = $beforeMemory - $afterMemory;
        
        return [
            'success' => true,
            'details' => 'Garbage collection انجام شد',
            'cycles_collected' => $collected,
            'memory_freed' => $freedMemory,
            'memory_before' => $beforeMemory,
            'memory_after' => $afterMemory
        ];
    }
    
    /**
     * نمایش کمک contextual
     */
    private function showContextualHelp(array $context): array
    {
        $helpMessage = $this->generateHelpMessage($context);
        
        $jsCode = "
            if (typeof Xi2HelpSystem !== 'undefined') {
                Xi2HelpSystem.showContextualHelp({
                    message: '{$helpMessage}',
                    position: 'right-bottom',
                    type: 'tooltip',
                    persistent: false,
                    duration: 8000
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => 'راهنمای contextual نمایش داده شد',
            'help_message' => $helpMessage,
            'js_code' => $jsCode
        ];
    }
    
    /**
     * ساده‌سازی رابط کاربری
     */
    private function simplifyUserInterface(array $context): array
    {
        $jsCode = "
            if (typeof Xi2UISimplifier !== 'undefined') {
                Xi2UISimplifier.activateSimpleMode({
                    hideAdvancedOptions: true,
                    enlargeButtons: true,
                    addHelpTexts: true,
                    highlightMainActions: true
                });
            }
        ";
        
        return [
            'success' => true,
            'details' => 'رابط کاربری ساده‌تر شد',
            'js_code' => $jsCode
        ];
    }
    
    // Helper Methods
    
    /**
     * ترجمه خطاها به فارسی
     */
    private function translateErrorToPersian(string $error): string
    {
        $translations = [
            'file too large' => 'حجم فایل خیلی زیاد است',
            'invalid file type' => 'نوع فایل مجاز نیست',
            'upload failed' => 'آپلود ناموفق بود',
            'connection timeout' => 'زمان اتصال تمام شد',
            'server error' => 'خطای سرور',
            'validation failed' => 'اعتبارسنجی ناموفق',
            'session expired' => 'نشست شما منقضی شده',
            'permission denied' => 'دسترسی مجاز نیست'
        ];
        
        foreach ($translations as $english => $persian) {
            if (stripos($error, $english) !== false) {
                return $persian;
            }
        }
        
        return 'خطایی رخ داده است. لطفاً دوباره تلاش کنید.';
    }
    
    /**
     * تولید پیغام کمک
     */
    private function generateHelpMessage(array $context): string
    {
        $event = $context['event'] ?? [];
        
        if (isset($event['action']) && $event['action'] === 'upload') {
            return 'برای آپلود موفق، اطمینان حاصل کنید که فایل کمتر از ۱۰ مگابایت باشد.';
        } elseif (isset($event['event_type']) && $event['event_type'] === 'form_submit') {
            return 'لطفاً تمام فیلدهای اجباری را پر کنید و دوباره امتحان کنید.';
        }
        
        return 'اگر مشکل ادامه داشت، با پشتیبانی تماس بگیرید.';
    }
    
    /**
     * تولید اعلان برای کاربر
     */
    private function generateUserNotification(string $issueType, array $result): ?array
    {
        if (!$result['success']) {
            return null;
        }
        
        $messages = [
            'slow_api_response' => 'سرعت سیستم بهبود یافت',
            'upload_failure' => 'روش آپلود بهینه شد',
            'form_validation_error' => 'خطاهای فرم برطرف شد',
            'session_about_to_expire' => 'نشست شما تمدید شد',
            'memory_leak_detected' => 'عملکرد سیستم بهینه شد'
        ];
        
        return [
            'type' => 'success',
            'message' => $messages[$issueType] ?? 'مشکل برطرف شد',
            'should_display' => false, // معمولاً خاموش تا کاربر متوجه نشود
            'duration' => 3000
        ];
    }
    
    /**
     * ثبت تلاش رفع مشکل
     */
    private function recordFixAttempt(string $issueType, array $result): void
    {
        $this->fixHistory[] = [
            'timestamp' => time(),
            'issue_type' => $issueType,
            'success' => $result['success'],
            'actions' => $result['actions_taken'],
            'response_time' => $result['response_time']
        ];
        
        // محدود کردن تاریخچه
        if (count($this->fixHistory) > 100) {
            $this->fixHistory = array_slice($this->fixHistory, -100);
        }
    }
    
    /**
     * بارگذاری تاریخچه راه‌حل‌ها
     */
    private function loadFixHistory(): void
    {
        // در آینده از پایگاه داده یا cache بارگذاری می‌شود
        $this->fixHistory = [];
    }
    
    /**
     * نگهداری پیشگیرانه
     */
    private function runPreventiveMaintenance(): void
    {
        // بررسی وضعیت memory
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        if ($memoryUsage > $memoryLimit * 0.8) { // بیش از 80% استفاده
            $this->performGarbageCollection([]);
        }
    }
    
    /**
     * پارس کردن memory limit
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $memoryLimit = (int) $memoryLimit;
        
        switch ($last) {
            case 'g':
                $memoryLimit *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $memoryLimit *= 1024 * 1024;
                break;
            case 'k':
                $memoryLimit *= 1024;
                break;
        }
        
        return $memoryLimit;
    }
    
    private function getRealUserIP(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        
        return 'unknown';
    }
}

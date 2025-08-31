<?php
/**
 * Xi2 Smart Logging API - Trigger Auto Fix
 * 
 * @description اعمال راه‌حل‌های خودکار
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Xi2-Session-Id');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Load required classes
    require_once __DIR__ . '/../../database/config.php';
    require_once __DIR__ . '/../logging/Xi2AutoFixer.php';
    require_once __DIR__ . '/../logging/Xi2SmartLogger.php';
    
    // Get request data
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    
    if (!$requestData) {
        throw new InvalidArgumentException('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($requestData['issue_type'])) {
        throw new InvalidArgumentException('issue_type is required');
    }
    
    $issueType = $requestData['issue_type'];
    $context = $requestData['context'] ?? [];
    $sessionId = $requestData['session_id'] ?? $_SERVER['HTTP_X_XI2_SESSION_ID'] ?? null;
    $userId = $requestData['user_id'] ?? null;
    $forceApply = $requestData['force_apply'] ?? false;
    
    // Initialize auto fixer
    $autoFixer = new Xi2AutoFixer();
    
    // Check if auto fix is available for this issue type
    if (!$autoFixer->canAutoFix($issueType)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot Auto Fix',
            'message' => "راه‌حل خودکار برای '{$issueType}' موجود نیست",
            'available_fixes' => $autoFixer->getAvailableFixes()
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Add additional context
    $context = enrichFixContext($context, $sessionId, $userId);
    
    // Check rate limiting
    if (!$forceApply && isFixRateLimited($issueType, $userId)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Rate Limited',
            'message' => 'تعداد تلاش‌های رفع مشکل از حد مجاز گذشته است',
            'retry_after' => 300 // 5 minutes
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Apply the fix
    $fixResult = $autoFixer->applyFix($issueType, $context);
    
    // Log the fix attempt
    $logger = Xi2SmartLogger::getInstance();
    $logger->logUserActivity($userId, 'auto_fix_triggered', [
        'issue_type' => $issueType,
        'fix_result' => $fixResult,
        'context' => $context,
        'success' => $fixResult['success']
    ]);
    
    // Prepare response
    $response = [
        'success' => $fixResult['success'],
        'fix_result' => $fixResult,
        'timestamp' => time(),
        'issue_type' => $issueType
    ];
    
    // Add JavaScript code if available
    if (isset($fixResult['js_code'])) {
        $response['execute_js'] = $fixResult['js_code'];
    }
    
    // Add user notification if needed
    if (isset($fixResult['user_notification']) && $fixResult['user_notification']) {
        $response['notification'] = $fixResult['user_notification'];
    }
    
    // Add follow-up recommendations
    $response['recommendations'] = getFollowUpRecommendations($issueType, $fixResult);
    
    // Add success rate info
    $response['fix_reliability'] = [
        'success_rate' => $autoFixer->getSuccessRate($issueType),
        'total_success_rate' => $autoFixer->getSuccessRate()
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Validation Error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server Error',
        'message' => 'Auto fix service temporarily unavailable'
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Xi2 Auto Fix API Error: " . $e->getMessage());
}

/**
 * غنی‌سازی context برای راه‌حل
 */
function enrichFixContext(array $context, ?string $sessionId, ?int $userId): array
{
    // اضافه کردن اطلاعات session
    if ($sessionId) {
        try {
            $sessionManager = new Xi2SessionManager();
            $sessionData = $sessionManager->getSessionData($sessionId);
            if ($sessionData) {
                $context['session_data'] = $sessionData;
                $context['session_duration'] = time() - $sessionData['created_at'];
                $context['session_events_count'] = $sessionData['events_count'] ?? 0;
            }
        } catch (Exception $e) {
            error_log("Error enriching session context: " . $e->getMessage());
        }
    }
    
    // اضافه کردن اطلاعات کاربر
    if ($userId) {
        $context['user_id'] = $userId;
        $context['user_history'] = getUserFixHistory($userId);
    }
    
    // اضافه کردن اطلاعات سیستم
    $context['system_info'] = [
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
        'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        'server_time' => time(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'ip_address' => getRealUserIP()
    ];
    
    return $context;
}

/**
 * بررسی rate limiting برای fix
 */
function isFixRateLimited(string $issueType, ?int $userId): bool
{
    try {
        $db = Database::getInstance()->getConnection();
        
        // بررسی تعداد تلاش‌های اخیر
        $sql = "SELECT COUNT(*) as attempts FROM activity_logs 
                WHERE action = 'auto_fix_triggered' 
                AND JSON_EXTRACT(details, '$.issue_type') = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        $params = [$issueType];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        } else {
            $sql .= " AND ip_address = ?";
            $params[] = getRealUserIP();
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        // حداکثر 3 تلاش در 5 دقیقه
        return $result['attempts'] >= 3;
        
    } catch (Exception $e) {
        error_log("Error checking rate limit: " . $e->getMessage());
        return false; // در صورت خطا، rate limiting را غیرفعال کن
    }
}

/**
 * دریافت تاریخچه fix های کاربر
 */
function getUserFixHistory(int $userId): array
{
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT 
                    JSON_EXTRACT(details, '$.issue_type') as issue_type,
                    JSON_EXTRACT(details, '$.fix_result.success') as success,
                    created_at
                FROM activity_logs 
                WHERE user_id = ? AND action = 'auto_fix_triggered'
                ORDER BY created_at DESC 
                LIMIT 10";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting user fix history: " . $e->getMessage());
        return [];
    }
}

/**
 * تولید توصیه‌های پیگیری
 */
function getFollowUpRecommendations(string $issueType, array $fixResult): array
{
    $recommendations = [];
    
    if ($fixResult['success']) {
        switch ($issueType) {
            case 'slow_api_response':
                $recommendations[] = [
                    'type' => 'monitoring',
                    'message' => 'سرعت API بهبود یافت. در صورت تکرار مشکل، با پشتیبانی تماس بگیرید.',
                    'action' => 'none'
                ];
                break;
                
            case 'upload_failure':
                $recommendations[] = [
                    'type' => 'user_action',
                    'message' => 'حالت آپلود بهینه فعال شد. دوباره فایل خود را آپلود کنید.',
                    'action' => 'retry_upload'
                ];
                break;
                
            case 'form_validation_error':
                $recommendations[] = [
                    'type' => 'user_guidance',
                    'message' => 'خطاهای فرم برطرف شد. فیلدهای نشان‌داده شده را تکمیل کنید.',
                    'action' => 'focus_error_fields'
                ];
                break;
        }
    } else {
        // در صورت عدم موفقیت fix
        $recommendations[] = [
            'type' => 'escalation',
            'message' => 'رفع خودکار مشکل موفق نبود. با پشتیبانی تماس بگیرید.',
            'action' => 'contact_support'
        ];
        
        // پیشنهاد راه‌حل‌های manual
        $manualSolutions = getManualSolutions($issueType);
        if (!empty($manualSolutions)) {
            $recommendations[] = [
                'type' => 'manual_solution',
                'message' => 'راه‌حل‌های دستی پیشنهادی:',
                'solutions' => $manualSolutions
            ];
        }
    }
    
    return $recommendations;
}

/**
 * دریافت راه‌حل‌های دستی
 */
function getManualSolutions(string $issueType): array
{
    $solutions = [
        'slow_api_response' => [
            'صفحه را refresh کنید',
            'اتصال اینترنت خود را بررسی کنید',
            'چند دقیقه بعد دوباره تلاش کنید'
        ],
        'upload_failure' => [
            'اندازه فایل را کاهش دهید (کمتر از 10MB)',
            'فرمت فایل را بررسی کنید (فقط تصاویر مجاز)',
            'مرورگر خود را restart کنید'
        ],
        'form_validation_error' => [
            'تمام فیلدهای اجباری را پر کنید',
            'فرمت داده‌ها را بررسی کنید',
            'صفحه را reload کرده و دوباره امتحان کنید'
        ],
        'session_expired' => [
            'دوباره وارد حساب کاربری خود شوید',
            'اطلاعات ورود خود را بررسی کنید'
        ]
    ];
    
    return $solutions[$issueType] ?? [
        'صفحه را reload کنید',
        'مرورگر خود را restart کنید',
        'با پشتیبانی تماس بگیرید'
    ];
}

/**
 * دریافت IP واقعی کاربر
 */
function getRealUserIP(): string
{
    $ipKeys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR', 
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
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

?>

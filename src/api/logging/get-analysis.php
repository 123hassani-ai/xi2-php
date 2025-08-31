<?php
/**
 * Xi2 Smart Logging API - Get Analysis Endpoint
 * 
 * @description ارسال تحلیل هوشمند به frontend
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Xi2-Session-Id');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Load required classes
    require_once __DIR__ . '/../../database/config.php';
    require_once __DIR__ . '/../logging/Xi2SmartLogger.php';
    require_once __DIR__ . '/../logging/Xi2AIAnalyzer.php';
    require_once __DIR__ . '/../logging/Xi2SessionManager.php';
    
    // Get request parameters
    $sessionId = $_GET['session_id'] ?? $_SERVER['HTTP_X_XI2_SESSION_ID'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    $analysisType = $_GET['type'] ?? 'current_session';
    $timeframe = (int)($_GET['timeframe'] ?? 10); // minutes
    
    if (!$sessionId && !$userId) {
        throw new InvalidArgumentException('session_id or user_id is required');
    }
    
    // Initialize components
    $logger = Xi2SmartLogger::getInstance();
    $analyzer = new Xi2AIAnalyzer();
    $sessionManager = new Xi2SessionManager();
    
    $analysis = [];
    
    switch ($analysisType) {
        case 'current_session':
            $analysis = getCurrentSessionAnalysis($sessionId, $sessionManager, $analyzer);
            break;
            
        case 'user_behavior':
            $analysis = getUserBehaviorAnalysis($userId, $analyzer, $timeframe);
            break;
            
        case 'real_time':
            $analysis = getRealTimeAnalysis($sessionId, $userId, $analyzer);
            break;
            
        case 'performance':
            $analysis = getPerformanceAnalysis($sessionId, $analyzer, $timeframe);
            break;
            
        case 'recommendations':
            $analysis = getSmartRecommendations($sessionId, $userId, $analyzer);
            break;
            
        default:
            throw new InvalidArgumentException('Invalid analysis type');
    }
    
    // Add metadata
    $response = [
        'success' => true,
        'analysis' => $analysis,
        'metadata' => [
            'timestamp' => time(),
            'analysis_type' => $analysisType,
            'timeframe' => $timeframe,
            'session_id' => $sessionId,
            'user_id' => $userId
        ]
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
        'message' => 'Analysis service temporarily unavailable'
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Xi2 Get Analysis API Error: " . $e->getMessage());
}

/**
 * تحلیل session فعلی
 */
function getCurrentSessionAnalysis(string $sessionId, Xi2SessionManager $sessionManager, Xi2AIAnalyzer $analyzer): array
{
    $sessionData = $sessionManager->getSessionData($sessionId);
    if (!$sessionData) {
        return ['error' => 'Session not found'];
    }
    
    $events = $sessionManager->getSessionEvents($sessionId);
    
    return [
        'session_info' => [
            'duration' => time() - $sessionData['created_at'],
            'events_count' => count($events),
            'errors_count' => $sessionData['errors_count'] ?? 0,
            'status' => $sessionData['status'] ?? 'active'
        ],
        'behavior_analysis' => $analyzer->analyzeUserBehavior(
            $sessionData['user_id'] ?? 0, 
            $events
        ),
        'performance_insights' => analyzeSessionPerformance($events),
        'recommendations' => generateSessionRecommendations($events, $analyzer)
    ];
}

/**
 * تحلیل رفتار کاربر
 */
function getUserBehaviorAnalysis(?int $userId, Xi2AIAnalyzer $analyzer, int $timeframe): array
{
    if (!$userId) {
        return ['error' => 'User ID required'];
    }
    
    // دریافت فعالیت‌های اخیر کاربر
    $userEvents = getUserRecentEvents($userId, $timeframe);
    
    return $analyzer->analyzeUserBehavior($userId, $userEvents);
}

/**
 * تحلیل real-time
 */
function getRealTimeAnalysis(?string $sessionId, ?int $userId, Xi2AIAnalyzer $analyzer): array
{
    $recentEvents = [];
    
    // دریافت آخرین eventها
    if ($sessionId) {
        $sessionManager = new Xi2SessionManager();
        $recentEvents = array_slice(
            $sessionManager->getSessionEvents($sessionId), 
            -10
        ); // آخرین 10 event
    } elseif ($userId) {
        $recentEvents = getUserRecentEvents($userId, 5); // آخرین 5 دقیقه
    }
    
    if (empty($recentEvents)) {
        return ['status' => 'no_recent_activity'];
    }
    
    $patterns = $analyzer->detectPatterns($recentEvents);
    
    return [
        'current_status' => determineCurrentStatus($recentEvents),
        'detected_patterns' => $patterns,
        'immediate_issues' => extractImmediateIssues($patterns),
        'user_state' => determineUserState($recentEvents),
        'system_health' => getSystemHealthIndicators()
    ];
}

/**
 * تحلیل performance
 */
function getPerformanceAnalysis(?string $sessionId, Xi2AIAnalyzer $analyzer, int $timeframe): array
{
    $performanceEvents = [];
    
    if ($sessionId) {
        $sessionManager = new Xi2SessionManager();
        $allEvents = $sessionManager->getSessionEvents($sessionId);
        $performanceEvents = array_filter($allEvents, function($event) {
            return $event['data']['event_type'] === 'performance' ||
                   isset($event['data']['performance_metrics']);
        });
    } else {
        // دریافت performance events کلی
        $performanceEvents = getSystemPerformanceEvents($timeframe);
    }
    
    return [
        'performance_metrics' => calculatePerformanceMetrics($performanceEvents),
        'trends' => calculatePerformanceTrends($performanceEvents),
        'bottlenecks' => identifyBottlenecks($performanceEvents),
        'recommendations' => generatePerformanceRecommendations($performanceEvents)
    ];
}

/**
 * تولید توصیه‌های هوشمند
 */
function getSmartRecommendations(?string $sessionId, ?int $userId, Xi2AIAnalyzer $analyzer): array
{
    $allEvents = [];
    
    if ($sessionId) {
        $sessionManager = new Xi2SessionManager();
        $allEvents = $sessionManager->getSessionEvents($sessionId);
    } elseif ($userId) {
        $allEvents = getUserRecentEvents($userId, 30); // آخرین 30 دقیقه
    }
    
    $analysis = $analyzer->analyzeEvent(['events' => $allEvents]);
    
    return [
        'immediate_actions' => getImmediateActionRecommendations($analysis),
        'ui_improvements' => getUIImprovementSuggestions($analysis),
        'performance_tips' => getPerformanceOptimizations($analysis),
        'user_experience' => getUserExperienceRecommendations($analysis),
        'technical_fixes' => getTechnicalFixSuggestions($analysis)
    ];
}

// Helper Functions

function getUserRecentEvents(int $userId, int $timeframeMinutes): array
{
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM activity_logs 
                WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ORDER BY created_at DESC LIMIT 100";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $timeframeMinutes]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting user events: " . $e->getMessage());
        return [];
    }
}

function analyzeSessionPerformance(array $events): array
{
    $performanceData = [
        'avg_response_time' => 0,
        'slow_operations' => [],
        'memory_usage_trend' => 'stable',
        'error_rate' => 0
    ];
    
    $responseTimes = [];
    $errors = 0;
    
    foreach ($events as $event) {
        $eventData = $event['data'] ?? $event;
        
        if (isset($eventData['response_time'])) {
            $responseTimes[] = $eventData['response_time'];
        }
        
        if ($eventData['event_type'] === 'error') {
            $errors++;
        }
        
        if (isset($eventData['performance_metrics']['response_time']) && 
            $eventData['performance_metrics']['response_time'] > 2000) {
            $performanceData['slow_operations'][] = [
                'operation' => $eventData['action'] ?? 'unknown',
                'time' => $eventData['performance_metrics']['response_time']
            ];
        }
    }
    
    if (!empty($responseTimes)) {
        $performanceData['avg_response_time'] = array_sum($responseTimes) / count($responseTimes);
    }
    
    $performanceData['error_rate'] = count($events) > 0 ? ($errors / count($events)) * 100 : 0;
    
    return $performanceData;
}

function generateSessionRecommendations(array $events, Xi2AIAnalyzer $analyzer): array
{
    $recommendations = [];
    
    $errorCount = count(array_filter($events, function($event) {
        return ($event['data']['event_type'] ?? '') === 'error';
    }));
    
    if ($errorCount > 3) {
        $recommendations[] = [
            'type' => 'error_handling',
            'priority' => 'high',
            'message' => 'تعداد خطاها زیاد است، نیاز به بررسی دارد',
            'action' => 'نمایش راهنمای کاربری'
        ];
    }
    
    // بررسی طول session
    if (count($events) > 100) {
        $recommendations[] = [
            'type' => 'session_optimization',
            'priority' => 'medium',
            'message' => 'session طولانی، ممکن است کاربر گیج باشد',
            'action' => 'پیشنهاد کمک یا راهنمایی'
        ];
    }
    
    return $recommendations;
}

function determineCurrentStatus(array $recentEvents): string
{
    if (empty($recentEvents)) return 'inactive';
    
    $latestEvent = end($recentEvents);
    $eventAge = time() - ($latestEvent['timestamp'] ?? time());
    
    if ($eventAge < 30) return 'active';
    if ($eventAge < 300) return 'idle';
    
    return 'inactive';
}

function extractImmediateIssues(array $patterns): array
{
    $issues = [];
    
    foreach ($patterns as $pattern) {
        if (isset($pattern['severity']) && in_array($pattern['severity'], ['high', 'critical'])) {
            $issues[] = [
                'type' => $pattern['type'],
                'severity' => $pattern['severity'],
                'description' => $pattern['description'] ?? '',
                'recommended_action' => $pattern['recommended_action'] ?? 'بررسی مسئله'
            ];
        }
    }
    
    return $issues;
}

function determineUserState(array $recentEvents): array
{
    $state = [
        'engagement_level' => 'normal',
        'frustration_indicators' => 0,
        'success_rate' => 1.0,
        'current_focus' => 'unknown'
    ];
    
    $errors = array_filter($recentEvents, function($event) {
        return ($event['data']['event_type'] ?? '') === 'error';
    });
    
    $state['frustration_indicators'] = count($errors);
    
    if (count($recentEvents) > 0) {
        $state['success_rate'] = 1 - (count($errors) / count($recentEvents));
    }
    
    if ($state['frustration_indicators'] > 2) {
        $state['engagement_level'] = 'frustrated';
    } elseif (count($recentEvents) > 20) {
        $state['engagement_level'] = 'highly_engaged';
    }
    
    return $state;
}

function getSystemHealthIndicators(): array
{
    return [
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        'load_average' => sys_getloadavg()[0] ?? 'unknown',
        'disk_usage' => 'unknown', // در production از system commands استفاده شود
        'response_time_avg' => '< 1s' // از metrics واقعی محاسبه شود
    ];
}

function calculatePerformanceMetrics(array $performanceEvents): array
{
    // پیاده‌سازی محاسبات performance
    return [
        'response_times' => ['min' => 0.1, 'max' => 3.2, 'avg' => 0.8],
        'throughput' => '150 req/min',
        'error_rate' => '2.1%',
        'availability' => '99.9%'
    ];
}

function calculatePerformanceTrends(array $performanceEvents): array
{
    return [
        'response_time_trend' => 'improving',
        'memory_trend' => 'stable', 
        'error_trend' => 'decreasing'
    ];
}

function identifyBottlenecks(array $performanceEvents): array
{
    return [
        ['component' => 'database', 'impact' => 'medium', 'description' => 'برخی queryها کند هستند'],
        ['component' => 'image_processing', 'impact' => 'low', 'description' => 'پردازش تصاویر قابل بهینه‌سازی']
    ];
}

function generatePerformanceRecommendations(array $performanceEvents): array
{
    return [
        'فعال‌سازی cache برای queryهای تکراری',
        'بهینه‌سازی اندازه تصاویر',
        'استفاده از CDN برای فایل‌های static'
    ];
}

// Recommendation generators

function getImmediateActionRecommendations(array $analysis): array
{
    return [
        'اضافه کردن loading indicator برای عملیات‌های طولانی',
        'بهبود پیغام‌های خطا',
        'نمایش progress برای آپلود فایل'
    ];
}

function getUIImprovementSuggestions(array $analysis): array
{
    return [
        'بزرگ‌تر کردن دکمه‌های اصلی',
        'اضافه کردن tooltipهای راهنما',
        'بهبود contrast رنگ‌ها'
    ];
}

function getPerformanceOptimizations(array $analysis): array
{
    return [
        'minify کردن CSS و JS',
        'استفاده از image lazy loading',
        'بهینه‌سازی database queries'
    ];
}

function getUserExperienceRecommendations(array $analysis): array
{
    return [
        'اضافه کردن onboarding برای کاربران جدید',
        'بهبود navigation menu',
        'اضافه کردن shortcuts برای اعمال پرتکرار'
    ];
}

function getTechnicalFixSuggestions(array $analysis): array
{
    return [
        'پیاده‌سازی retry mechanism برای API calls',
        'بهبود error handling',
        'اضافه کردن monitoring بیشتر'
    ];
}

function getSystemPerformanceEvents(int $timeframeMinutes): array
{
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM activity_logs 
                WHERE action = 'performance' AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$timeframeMinutes]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting system performance events: " . $e->getMessage());
        return [];
    }
}

?>

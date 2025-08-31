<?php
/**
 * Xi2 Smart Logging API - Log Event Endpoint
 * 
 * @description دریافت و پردازش لاگ‌های ارسالی از frontend
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
    require_once __DIR__ . '/../logging/Xi2SmartLogger.php';
    
    // Get request data
    $input = file_get_contents('php://input');
    $eventData = json_decode($input, true);
    
    if (!$eventData) {
        throw new InvalidArgumentException('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($eventData['event_type'])) {
        throw new InvalidArgumentException('event_type is required');
    }
    
    // Add server-side information
    $eventData['server_timestamp'] = microtime(true);
    $eventData['server_ip'] = $_SERVER['SERVER_ADDR'] ?? 'unknown';
    $eventData['request_method'] = $_SERVER['REQUEST_METHOD'];
    $eventData['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $eventData['ip_address'] = getRealUserIP();
    
    // Add session information if available
    if (isset($_SERVER['HTTP_X_XI2_SESSION_ID'])) {
        $eventData['session_id'] = $_SERVER['HTTP_X_XI2_SESSION_ID'];
    }
    
    // Initialize logger and log the event
    $logger = Xi2SmartLogger::getInstance();
    $success = $logger->logEvent($eventData);
    
    if ($success) {
        $response = [
            'success' => true,
            'message' => 'Event logged successfully',
            'event_id' => $eventData['server_timestamp'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // اگر تحلیل خاصی نیاز به ارسال به frontend داشت
        if (isset($eventData['request_analysis']) && $eventData['request_analysis']) {
            $response['analysis'] = getQuickAnalysis($eventData);
        }
        
        http_response_code(200);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Failed to log event');
    }
    
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
        'message' => 'Internal server error occurred'
    ], JSON_UNESCAPED_UNICODE);
    
    // Log the error internally
    error_log("Xi2 Log Event API Error: " . $e->getMessage());
}

/**
 * Get real user IP address
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

/**
 * Get quick analysis for frontend
 */
function getQuickAnalysis(array $eventData): array
{
    $analysis = [
        'status' => 'normal',
        'recommendations' => [],
        'should_show_help' => false
    ];
    
    // تحلیل سریع برای تصمیم‌گیری frontend
    if ($eventData['event_type'] === 'error') {
        $analysis['status'] = 'error';
        $analysis['should_show_help'] = true;
        $analysis['recommendations'][] = 'نمایش پیغام خطای دوستانه';
    }
    
    if (isset($eventData['performance_metrics']['page_load_time']) && 
        $eventData['performance_metrics']['page_load_time'] > 3000) {
        $analysis['status'] = 'slow';
        $analysis['recommendations'][] = 'نمایش loading indicator';
    }
    
    return $analysis;
}
?>

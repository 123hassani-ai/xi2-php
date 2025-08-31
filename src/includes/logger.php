<?php
/**
 * زیتو (Xi2) - کلاس Logger
 * سیستم لاگ‌گذاری کامل برای tracking تمام عملیات
 */

class Xi2Logger {
    private static $instance = null;
    private $logFile;
    
    private function __construct() {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/xi2-admin-' . date('Y-m-d') . '.log';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // نوشتن در فایل
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // نوشتن در browser console (اگر در حالت debug باشد)
        if (isset($_GET['debug']) || isset($_SESSION['debug_mode'])) {
            echo "<script>console.log(" . json_encode("XI2-LOG [{$level}] {$message}") . ");</script>";
            
            // همچنین در صفحه نمایش بده
            $color = $this->getLogColor($level);
            echo "<div style='background: {$color}; padding: 0.5rem; margin: 0.25rem; border-radius: 0.25rem; font-family: monospace; font-size: 0.875rem;'>";
            echo "<strong>[{$timestamp}] [{$level}]</strong> {$message}";
            if (!empty($context)) {
                echo "<br><small>Context: " . htmlspecialchars(json_encode($context)) . "</small>";
            }
            echo "</div>";
        }
        
        // همچنین در error_log سیستم بنویس
        error_log("XI2-LOG [{$level}] {$message}" . $contextStr);
    }
    
    private function getLogColor($level) {
        switch (strtolower($level)) {
            case 'error': return '#fee2e2';
            case 'warning': return '#fef3c7';
            case 'info': return '#dbeafe';
            case 'success': return '#dcfce7';
            case 'debug': return '#f3f4f6';
            default: return '#ffffff';
        }
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function success($message, $context = []) {
        $this->log('SUCCESS', $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    public function database($operation, $query, $params = [], $result = null) {
        $this->log('DATABASE', "Operation: {$operation} | Query: {$query}", [
            'params' => $params,
            'result' => $result,
            'timestamp' => microtime(true)
        ]);
    }
    
    public function form($action, $data = []) {
        $this->log('FORM', "Action: {$action}", $data);
    }
    
    public function session($action, $data = []) {
        $this->log('SESSION', "Action: {$action}", $data);
    }
    
    /**
     * Get current log file path
     */
    public function getLogFile() {
        return $this->logFile;
    }
}
?>

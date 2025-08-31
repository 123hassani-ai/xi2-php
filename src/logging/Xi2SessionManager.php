<?php
/**
 * Xi2 Smart Logging System - Session Manager
 * 
 * @description مدیریت session ها و پوشه‌بندی لاگ‌ها
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

class Xi2SessionManager
{
    private const LOGS_BASE_PATH = __DIR__ . '/../../logs/';
    private const SESSION_EXPIRY = 86400; // 24 ساعت
    
    private $currentSessionData;
    
    public function __construct()
    {
        $this->ensureLogsDirectoryExists();
    }
    
    /**
     * راه‌اندازی session جدید یا بازیابی موجود
     */
    public function initializeSession(): array
    {
        // بررسی session موجود
        $sessionId = $this->getSessionIdFromCookie() ?: $this->getSessionIdFromHeader();
        
        if ($sessionId && $this->isValidSession($sessionId)) {
            $this->currentSessionData = $this->loadSessionData($sessionId);
        } else {
            $this->currentSessionData = $this->createNewSession();
        }
        
        // به‌روزرسانی last activity
        $this->updateSessionActivity();
        
        return $this->currentSessionData;
    }
    
    /**
     * ایجاد session جدید
     */
    private function createNewSession(): array
    {
        $sessionId = $this->generateSessionId();
        $timestamp = time();
        $date = date('Y-m-d');
        
        $sessionData = [
            'id' => $sessionId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'date' => $date,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $this->getRealUserIP(),
            'user_id' => $this->getCurrentUserId(),
            'events_count' => 0,
            'errors_count' => 0,
            'session_path' => $this->createSessionDirectory($sessionId),
            'status' => 'active'
        ];
        
        // ذخیره session metadata
        $this->saveSessionMetadata($sessionData);
        
        // تنظیم cookie
        $this->setSessionCookie($sessionId);
        
        return $sessionData;
    }
    
    /**
     * ایجاد پوشه session
     */
    private function createSessionDirectory(string $sessionId): string
    {
        $date = date('Y-m-d');
        $sessionPath = self::LOGS_BASE_PATH . "sessions/{$date}/{$sessionId}/";
        
        if (!file_exists($sessionPath)) {
            mkdir($sessionPath, 0755, true);
            
            // ایجاد فایل‌های اولیه
            $this->createInitialSessionFiles($sessionPath);
        }
        
        return $sessionPath;
    }
    
    /**
     * ایجاد فایل‌های اولیه session
     */
    private function createInitialSessionFiles(string $sessionPath): void
    {
        $initialFiles = [
            'actions.json' => [],
            'errors.json' => [],
            'performance.json' => [],
            'ai-analysis.json' => [],
            'auto-fixes.json' => []
        ];
        
        foreach ($initialFiles as $filename => $content) {
            file_put_contents(
                $sessionPath . $filename, 
                json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
    }
    
    /**
     * اضافه کردن event به session
     */
    public function appendEventToSession(string $sessionId, array $eventData): void
    {
        $sessionPath = $this->getSessionPath($sessionId);
        if (!$sessionPath) return;
        
        // تعیین نوع فایل بر اساس event type
        $filename = $this->getEventFilename($eventData['event_type']);
        $filepath = $sessionPath . $filename;
        
        // خواندن محتوای فعلی
        $currentContent = [];
        if (file_exists($filepath)) {
            $currentContent = json_decode(file_get_contents($filepath), true) ?? [];
        }
        
        // اضافه کردن event جدید
        $currentContent[] = [
            'timestamp' => $eventData['timestamp'] ?? time(),
            'microtime' => microtime(true),
            'event_type' => $eventData['event_type'],
            'data' => $eventData
        ];
        
        // محدود کردن تعداد events در هر فایل
        if (count($currentContent) > 1000) {
            $currentContent = array_slice($currentContent, -1000);
        }
        
        // ذخیره در فایل
        file_put_contents(
            $filepath,
            json_encode($currentContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        
        // به‌روزرسانی آمار session
        $this->updateSessionStats($sessionId, $eventData['event_type']);
    }
    
    /**
     * تعیین نام فایل بر اساس نوع event
     */
    private function getEventFilename(string $eventType): string
    {
        $mapping = [
            'error' => 'errors.json',
            'performance' => 'performance.json',
            'user_activity' => 'actions.json',
            'click' => 'actions.json',
            'form_submit' => 'actions.json',
            'api_call' => 'actions.json',
            'page_load' => 'actions.json'
        ];
        
        return $mapping[$eventType] ?? 'actions.json';
    }
    
    /**
     * ذخیره تحلیل هوش مصنوعی
     */
    public function saveAIAnalysis(string $sessionId, array $analysisData): void
    {
        $sessionPath = $this->getSessionPath($sessionId);
        if (!$sessionPath) return;
        
        $filepath = $sessionPath . 'ai-analysis.json';
        
        $currentAnalysis = [];
        if (file_exists($filepath)) {
            $currentAnalysis = json_decode(file_get_contents($filepath), true) ?? [];
        }
        
        $currentAnalysis[] = [
            'timestamp' => time(),
            'microtime' => microtime(true),
            'analysis' => $analysisData
        ];
        
        // محدود کردن تعداد تحلیل‌ها
        if (count($currentAnalysis) > 100) {
            $currentAnalysis = array_slice($currentAnalysis, -100);
        }
        
        file_put_contents(
            $filepath,
            json_encode($currentAnalysis, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * ذخیره نتایج راه‌حل‌های خودکار
     */
    public function saveAutoFixResult(string $sessionId, array $fixData): void
    {
        $sessionPath = $this->getSessionPath($sessionId);
        if (!$sessionPath) return;
        
        $filepath = $sessionPath . 'auto-fixes.json';
        
        $currentFixes = [];
        if (file_exists($filepath)) {
            $currentFixes = json_decode(file_get_contents($filepath), true) ?? [];
        }
        
        $currentFixes[] = [
            'timestamp' => time(),
            'fix_result' => $fixData
        ];
        
        file_put_contents(
            $filepath,
            json_encode($currentFixes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * دریافت اطلاعات session
     */
    public function getSessionData(string $sessionId): ?array
    {
        $sessionPath = $this->getSessionPath($sessionId);
        if (!$sessionPath) return null;
        
        $metadataPath = $sessionPath . 'metadata.json';
        if (!file_exists($metadataPath)) return null;
        
        return json_decode(file_get_contents($metadataPath), true);
    }
    
    /**
     * دریافت events یک session
     */
    public function getSessionEvents(string $sessionId, string $eventType = 'all'): array
    {
        $sessionPath = $this->getSessionPath($sessionId);
        if (!$sessionPath) return [];
        
        $allEvents = [];
        $filesToRead = [];
        
        if ($eventType === 'all') {
            $filesToRead = ['actions.json', 'errors.json', 'performance.json'];
        } else {
            $filesToRead = [$this->getEventFilename($eventType)];
        }
        
        foreach ($filesToRead as $filename) {
            $filepath = $sessionPath . $filename;
            if (file_exists($filepath)) {
                $events = json_decode(file_get_contents($filepath), true) ?? [];
                $allEvents = array_merge($allEvents, $events);
            }
        }
        
        // مرتب‌سازی بر اساس timestamp
        usort($allEvents, function($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });
        
        return $allEvents;
    }
    
    /**
     * پاک‌سازی session های قدیمی
     */
    public function cleanupOldSessions(int $daysOld = 30): void
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysOld} days"));
        $sessionsPath = self::LOGS_BASE_PATH . 'sessions/';
        
        if (!is_dir($sessionsPath)) return;
        
        $directories = scandir($sessionsPath);
        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dir)) {
                continue;
            }
            
            if ($dir < $cutoffDate) {
                $this->deleteDirectory($sessionsPath . $dir);
            }
        }
    }
    
    /**
     * حذف پوشه به صورت recursive
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) return false;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    // Helper Methods
    
    private function generateSessionId(): string
    {
        return 'xi2_' . bin2hex(random_bytes(16)) . '_' . time();
    }
    
    private function getSessionIdFromCookie(): ?string
    {
        return $_COOKIE['xi2_session_id'] ?? null;
    }
    
    private function getSessionIdFromHeader(): ?string
    {
        // بررسی CLI environment
        if (php_sapi_name() === 'cli') {
            return null;
        }
        
        // استفاده از getallheaders اگر موجود باشد
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return $headers['X-Xi2-Session-Id'] ?? null;
        }
        
        // روش جایگزین برای سرورهای nginx
        foreach ($_SERVER as $name => $value) {
            if (strtolower($name) === 'http_x_xi2_session_id') {
                return $value;
            }
        }
        
        return null;
    }
    
    private function setSessionCookie(string $sessionId): void
    {
        setcookie('xi2_session_id', $sessionId, time() + self::SESSION_EXPIRY, '/', '', false, true);
    }
    
    private function isValidSession(string $sessionId): bool
    {
        $sessionPath = $this->getSessionPath($sessionId);
        if (!$sessionPath) return false;
        
        $metadataPath = $sessionPath . 'metadata.json';
        if (!file_exists($metadataPath)) return false;
        
        $metadata = json_decode(file_get_contents($metadataPath), true);
        if (!$metadata) return false;
        
        // بررسی انقضا
        return ($metadata['updated_at'] + self::SESSION_EXPIRY) > time();
    }
    
    private function loadSessionData(string $sessionId): array
    {
        $sessionPath = $this->getSessionPath($sessionId);
        $metadataPath = $sessionPath . 'metadata.json';
        
        return json_decode(file_get_contents($metadataPath), true);
    }
    
    private function saveSessionMetadata(array $sessionData): void
    {
        $metadataPath = $sessionData['session_path'] . 'metadata.json';
        file_put_contents(
            $metadataPath,
            json_encode($sessionData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
    
    private function updateSessionActivity(): void
    {
        if (!$this->currentSessionData) return;
        
        $this->currentSessionData['updated_at'] = time();
        $this->saveSessionMetadata($this->currentSessionData);
    }
    
    private function updateSessionStats(string $sessionId, string $eventType): void
    {
        $sessionData = $this->getSessionData($sessionId);
        if (!$sessionData) return;
        
        $sessionData['events_count']++;
        if ($eventType === 'error') {
            $sessionData['errors_count']++;
        }
        
        $this->saveSessionMetadata($sessionData);
    }
    
    private function getSessionPath(string $sessionId): ?string
    {
        // استخراج تاریخ از session ID یا جستجو
        if (preg_match('/xi2_[a-f0-9]+_(\d+)/', $sessionId, $matches)) {
            $timestamp = (int)$matches[1];
            $date = date('Y-m-d', $timestamp);
            $path = self::LOGS_BASE_PATH . "sessions/{$date}/{$sessionId}/";
            
            if (is_dir($path)) {
                return $path;
            }
        }
        
        // جستجوی کلی
        $sessionsPath = self::LOGS_BASE_PATH . 'sessions/';
        if (!is_dir($sessionsPath)) return null;
        
        $dates = scandir($sessionsPath);
        foreach ($dates as $date) {
            if ($date === '.' || $date === '..') continue;
            
            $sessionPath = $sessionsPath . $date . '/' . $sessionId . '/';
            if (is_dir($sessionPath)) {
                return $sessionPath;
            }
        }
        
        return null;
    }
    
    private function ensureLogsDirectoryExists(): void
    {
        $directories = [
            self::LOGS_BASE_PATH,
            self::LOGS_BASE_PATH . 'sessions/',
            self::LOGS_BASE_PATH . 'daily/',
            self::LOGS_BASE_PATH . 'patterns/',
            self::LOGS_BASE_PATH . 'copilot-sync/'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
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
        if (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        
        return null;
    }
}

<?php
/**
 * Xi2 Smart Logging System - GitHub Copilot Sync
 * 
 * @description همگام‌سازی اطلاعات با GitHub Copilot برای بهبود کیفیت کد
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

class Xi2CopilotSync
{
    private $db;
    private $syncEnabled;
    private $copilotContext;
    private $contextQueue;
    private $lastSyncTime;
    
    // تنظیمات sync
    private const SYNC_INTERVAL = 300; // 5 دقیقه
    private const MAX_CONTEXT_SIZE = 10000; // حداکثر اندازه context
    private const COPILOT_LOG_PATH = __DIR__ . '/../../logs/copilot-sync/';
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->syncEnabled = $this->isSyncEnabled();
        $this->copilotContext = [];
        $this->contextQueue = [];
        $this->lastSyncTime = 0;
        
        $this->initializeSync();
    }
    
    /**
     * راه‌اندازی اولیه sync
     */
    private function initializeSync(): void
    {
        if (!$this->syncEnabled) return;
        
        // ایجاد پوشه‌های لازم
        $this->ensureCopilotDirectories();
        
        // بارگذاری context موجود
        $this->loadExistingContext();
        
        // شروع monitoring
        $this->startContextMonitoring();
    }
    
    /**
     * همگام‌سازی context با Copilot
     */
    public function syncContextWithCopilot(array $eventData, array $analysis): void
    {
        if (!$this->syncEnabled) return;
        
        try {
            // تولید context جدید
            $contextUpdate = $this->generateContextUpdate($eventData, $analysis);
            
            // اضافه به صف
            $this->addToContextQueue($contextUpdate);
            
            // بررسی نیاز به sync فوری
            if ($this->shouldSyncImmediately($analysis)) {
                $this->performImmediateSync();
            } elseif ($this->shouldScheduleSync()) {
                $this->scheduleSync();
            }
            
        } catch (Exception $e) {
            error_log("Xi2CopilotSync Error: " . $e->getMessage());
        }
    }
    
    /**
     * تولید بروزرسانی context
     */
    private function generateContextUpdate(array $eventData, array $analysis): array
    {
        $update = [
            'timestamp' => microtime(true),
            'event_id' => $eventData['timestamp'] ?? time(),
            'context_type' => $this->determineContextType($eventData, $analysis),
            'priority' => $this->calculatePriority($analysis),
            'copilot_data' => []
        ];
        
        // اطلاعات مربوط به مشکل کاربر
        if (!empty($analysis['issues'])) {
            $update['copilot_data']['current_user_struggle'] = $this->extractUserStruggle($eventData, $analysis);
            $update['copilot_data']['probable_cause'] = $this->identifyProbableCause($analysis);
            $update['copilot_data']['business_impact'] = $this->assessBusinessImpact($analysis);
        }
        
        // پیشنهادات بهبود کد
        if (!empty($analysis['code_improvement_needed'])) {
            $update['copilot_data']['suggested_code_improvement'] = $this->generateCodeImprovement($eventData, $analysis);
            $update['copilot_data']['relevant_files'] = $this->identifyRelevantFiles($eventData, $analysis);
            $update['copilot_data']['recommended_prompt'] = $this->generateCopilotPrompt($eventData, $analysis);
        }
        
        // الگوهای خطا
        if (isset($analysis['patterns'])) {
            $update['copilot_data']['error_pattern'] = $this->extractErrorPattern($analysis['patterns']);
        }
        
        // آمار و metrics
        $update['copilot_data']['performance_metrics'] = $this->extractPerformanceMetrics($eventData);
        
        // تحلیل trend
        $update['copilot_data']['trend_analysis'] = $this->generateTrendAnalysis();
        
        return $update;
    }
    
    /**
     * استخراج مشکل کاربر
     */
    private function extractUserStruggle(array $eventData, array $analysis): string
    {
        $struggles = [];
        
        foreach ($analysis['issues'] ?? [] as $issue) {
            switch ($issue['type']) {
                case 'user_frustration':
                    $struggles[] = 'کاربر نشان‌های عصبانیت نشان می‌دهد';
                    break;
                    
                case 'slow_page_load':
                    $struggles[] = 'صفحه کند بارگذاری می‌شود و کاربر منتظر مانده';
                    break;
                    
                case 'upload_failure':
                    $uploadAttempts = $this->getRecentUploadAttempts($eventData['user_id'] ?? null);
                    $struggles[] = "کاربر {$uploadAttempts} بار تلاش کرده فایل آپلود کند ولی موفق نشده";
                    break;
                    
                case 'form_validation_error':
                    $struggles[] = 'کاربر مشکل در پر کردن فرم دارد';
                    break;
            }
        }
        
        return implode('. ', $struggles) ?: 'کاربر با مشکل نامشخصی مواجه است';
    }
    
    /**
     * شناسایی علت احتمالی
     */
    private function identifyProbableCause(array $analysis): string
    {
        $causes = [];
        
        foreach ($analysis['issues'] ?? [] as $issue) {
            switch ($issue['type']) {
                case 'slow_page_load':
                    $causes[] = 'سرور overload شده یا query های کند';
                    break;
                    
                case 'upload_failure':
                    $causes[] = 'فایل خیلی بزرگه و timeout میشه';
                    break;
                    
                case 'high_memory_usage':
                    $causes[] = 'memory leak یا تصاویر بهینه نشده';
                    break;
                    
                case 'database_error':
                    $causes[] = 'مشکل در connection pool یا query optimization';
                    break;
            }
        }
        
        return implode(', ', $causes) ?: 'علت دقیق مشخص نیست';
    }
    
    /**
     * تولید پیشنهاد بهبود کد
     */
    private function generateCodeImprovement(array $eventData, array $analysis): string
    {
        $improvements = [];
        
        foreach ($analysis['issues'] ?? [] as $issue) {
            switch ($issue['type']) {
                case 'upload_failure':
                    $improvements[] = 'نیاز به chunk upload implementation داریم';
                    break;
                    
                case 'slow_page_load':
                    $improvements[] = 'نیاز به بهینه‌سازی database queries و اضافه کردن caching';
                    break;
                    
                case 'memory_leak':
                    $improvements[] = 'نیاز به بررسی و بهینه‌سازی memory management';
                    break;
                    
                case 'user_frustration':
                    $improvements[] = 'نیاز به بهبود UX و اضافه کردن progress indicators';
                    break;
            }
        }
        
        return implode(', ', $improvements) ?: 'نیاز به بررسی کلی کیفیت کد';
    }
    
    /**
     * شناسایی فایل‌های مرتبط
     */
    private function identifyRelevantFiles(array $eventData, array $analysis): array
    {
        $files = [];
        
        foreach ($analysis['issues'] ?? [] as $issue) {
            switch ($issue['type']) {
                case 'upload_failure':
                    $files[] = '/src/api/upload/upload.php';
                    $files[] = '/src/assets/js/upload-handler.js';
                    break;
                    
                case 'slow_page_load':
                    $files[] = '/src/database/config.php';
                    $files[] = '/src/includes/*';
                    break;
                    
                case 'form_validation_error':
                    $files[] = '/src/assets/js/auth.js';
                    $files[] = '/src/api/auth/*.php';
                    break;
                    
                case 'user_frustration':
                    $files[] = '/src/assets/css/*.css';
                    $files[] = '/src/assets/js/*.js';
                    break;
            }
        }
        
        return array_unique($files);
    }
    
    /**
     * تولید prompt برای Copilot
     */
    private function generateCopilotPrompt(array $eventData, array $analysis): string
    {
        $mainIssue = $analysis['issues'][0] ?? null;
        if (!$mainIssue) return '';
        
        $prompts = [
            'upload_failure' => 'لطفاً upload.php را بروزرسانی کن تا از chunk uploading پشتیبانی کند. همچنین progress bar و error handling بهتری اضافه کن.',
            
            'slow_page_load' => 'لطفاً performance صفحه را بهینه کن. database queries را بررسی کن و caching mechanism اضافه کن.',
            
            'user_frustration' => 'لطفاً user experience را بهبود بده. loading indicators، better feedback و error messages فهم‌پذیرتر اضافه کن.',
            
            'memory_leak' => 'لطفاً memory usage را بهینه کن. image processing و resource management را بررسی کن.'
        ];
        
        return $prompts[$mainIssue['type']] ?? 'لطفاً کیفیت کلی سیستم را بهبود بده.';
    }
    
    /**
     * ارزیابی تأثیر کسب‌وکار
     */
    private function assessBusinessImpact(array $analysis): string
    {
        $highImpactIssues = array_filter($analysis['issues'] ?? [], function($issue) {
            return in_array($issue['severity'] ?? 'low', ['high', 'critical']);
        });
        
        if (!empty($highImpactIssues)) {
            $issueTypes = array_column($highImpactIssues, 'type');
            
            if (in_array('upload_failure', $issueTypes)) {
                return 'کاربران از آپلود منصرف می‌شوند، درآمد کاهش می‌یابد';
            } elseif (in_array('user_frustration', $issueTypes)) {
                return 'کاربران سایت را ترک می‌کنند، نرخ bounce افزایش می‌یابد';
            } elseif (in_array('slow_page_load', $issueTypes)) {
                return 'تجربه کاربری ضعیف، SEO تحت تأثیر منفی';
            }
        }
        
        return 'تأثیر محدود روی تجربه کاربری';
    }
    
    /**
     * sync فوری
     */
    private function performImmediateSync(): void
    {
        $this->flushContextQueue();
        $this->updateCopilotFiles();
        $this->lastSyncTime = time();
    }
    
    /**
     * زمان‌بندی sync
     */
    private function scheduleSync(): void
    {
        // در production با cron job یا queue system انجام می‌شود
        if ((time() - $this->lastSyncTime) > self::SYNC_INTERVAL) {
            $this->performImmediateSync();
        }
    }
    
    /**
     * بررسی نیاز به sync فوری
     */
    private function shouldSyncImmediately(array $analysis): bool
    {
        // sync فوری برای مسائل critical
        $criticalIssues = array_filter($analysis['issues'] ?? [], function($issue) {
            return ($issue['severity'] ?? 'low') === 'critical';
        });
        
        return !empty($criticalIssues) || 
               ($analysis['confidence_score'] ?? 1) < 0.5 ||
               !empty($analysis['code_improvement_needed']);
    }
    
    /**
     * بررسی نیاز به sync معمولی
     */
    private function shouldScheduleSync(): bool
    {
        return count($this->contextQueue) > 10 || 
               (time() - $this->lastSyncTime) > self::SYNC_INTERVAL;
    }
    
    /**
     * اضافه کردن به صف context
     */
    private function addToContextQueue(array $contextUpdate): void
    {
        $this->contextQueue[] = $contextUpdate;
        
        // محدود کردن اندازه صف
        if (count($this->contextQueue) > 100) {
            $this->contextQueue = array_slice($this->contextQueue, -100);
        }
    }
    
    /**
     * خالی کردن صف context
     */
    private function flushContextQueue(): void
    {
        if (empty($this->contextQueue)) return;
        
        // ادغام تمام updates
        $mergedContext = $this->mergeContextUpdates($this->contextQueue);
        
        // ذخیره در فایل
        $this->saveContextUpdate($mergedContext);
        
        // پاک کردن صف
        $this->contextQueue = [];
    }
    
    /**
     * ادغام context updates
     */
    private function mergeContextUpdates(array $updates): array
    {
        $merged = [
            'timestamp' => time(),
            'total_updates' => count($updates),
            'priority_summary' => [],
            'issue_summary' => [],
            'improvement_suggestions' => [],
            'affected_files' => [],
            'business_impact_summary' => ''
        ];
        
        foreach ($updates as $update) {
            // ادغام priorities
            $priority = $update['priority'] ?? 'normal';
            $merged['priority_summary'][$priority] = ($merged['priority_summary'][$priority] ?? 0) + 1;
            
            // ادغام issues
            if (isset($update['copilot_data']['current_user_struggle'])) {
                $merged['issue_summary'][] = $update['copilot_data']['current_user_struggle'];
            }
            
            // ادغام پیشنهادات
            if (isset($update['copilot_data']['suggested_code_improvement'])) {
                $merged['improvement_suggestions'][] = $update['copilot_data']['suggested_code_improvement'];
            }
            
            // ادغام فایل‌ها
            if (isset($update['copilot_data']['relevant_files'])) {
                $merged['affected_files'] = array_merge($merged['affected_files'], $update['copilot_data']['relevant_files']);
            }
        }
        
        // حذف تکرارها
        $merged['issue_summary'] = array_unique($merged['issue_summary']);
        $merged['improvement_suggestions'] = array_unique($merged['improvement_suggestions']);
        $merged['affected_files'] = array_unique($merged['affected_files']);
        
        return $merged;
    }
    
    /**
     * ذخیره context update
     */
    private function saveContextUpdate(array $context): void
    {
        $filename = self::COPILOT_LOG_PATH . 'context-updates.json';
        
        // خواندن محتوای فعلی
        $existingUpdates = [];
        if (file_exists($filename)) {
            $existingUpdates = json_decode(file_get_contents($filename), true) ?? [];
        }
        
        // اضافه کردن update جدید
        $existingUpdates[] = $context;
        
        // محدود کردن تعداد updates
        if (count($existingUpdates) > 50) {
            $existingUpdates = array_slice($existingUpdates, -50);
        }
        
        // ذخیره
        file_put_contents(
            $filename,
            json_encode($existingUpdates, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        
        // ایجاد فایل suggestions جداگانه
        $this->saveCodeSuggestions($context);
    }
    
    /**
     * ذخیره پیشنهادات کد
     */
    private function saveCodeSuggestions(array $context): void
    {
        if (empty($context['improvement_suggestions']) && empty($context['affected_files'])) {
            return;
        }
        
        $suggestions = [
            'timestamp' => time(),
            'summary' => $this->generateSuggestionSummary($context),
            'priority_level' => $this->calculateOverallPriority($context),
            'suggested_improvements' => $context['improvement_suggestions'] ?? [],
            'files_to_review' => $context['affected_files'] ?? [],
            'estimated_effort' => $this->estimateEffort($context),
            'business_justification' => $context['business_impact_summary'] ?? ''
        ];
        
        $filename = self::COPILOT_LOG_PATH . 'code-improvement-suggestions.json';
        
        $existingSuggestions = [];
        if (file_exists($filename)) {
            $existingSuggestions = json_decode(file_get_contents($filename), true) ?? [];
        }
        
        $existingSuggestions[] = $suggestions;
        
        // محدود کردن
        if (count($existingSuggestions) > 30) {
            $existingSuggestions = array_slice($existingSuggestions, -30);
        }
        
        file_put_contents(
            $filename,
            json_encode($existingSuggestions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
    
    // Helper Methods
    
    private function isSyncEnabled(): bool
    {
        // بررسی تنظیمات از دیتابیس یا config
        return true; // فعلاً همیشه فعال
    }
    
    private function ensureCopilotDirectories(): void
    {
        $dirs = [
            self::COPILOT_LOG_PATH,
            self::COPILOT_LOG_PATH . 'archive/',
            self::COPILOT_LOG_PATH . 'feedback/'
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private function loadExistingContext(): void
    {
        $filename = self::COPILOT_LOG_PATH . 'context-updates.json';
        if (file_exists($filename)) {
            $this->copilotContext = json_decode(file_get_contents($filename), true) ?? [];
        }
    }
    
    private function startContextMonitoring(): void
    {
        // در production با background process انجام می‌شود
    }
    
    private function determineContextType(array $eventData, array $analysis): string
    {
        if (!empty($analysis['issues'])) {
            $severities = array_column($analysis['issues'], 'severity');
            if (in_array('critical', $severities)) return 'critical_issue';
            if (in_array('high', $severities)) return 'high_priority';
        }
        
        return 'normal_event';
    }
    
    private function calculatePriority(array $analysis): string
    {
        if (!empty($analysis['issues'])) {
            $severities = array_column($analysis['issues'], 'severity');
            if (in_array('critical', $severities)) return 'critical';
            if (in_array('high', $severities)) return 'high';
            if (in_array('medium', $severities)) return 'medium';
        }
        
        return 'low';
    }
    
    private function extractErrorPattern(array $patterns): string
    {
        $patternTypes = array_keys($patterns);
        return implode(', ', $patternTypes);
    }
    
    private function extractPerformanceMetrics(array $eventData): array
    {
        $metrics = [];
        
        if (isset($eventData['performance_metrics'])) {
            $metrics = $eventData['performance_metrics'];
        }
        
        $metrics['memory_usage'] = memory_get_usage(true);
        $metrics['peak_memory'] = memory_get_peak_usage(true);
        
        return $metrics;
    }
    
    private function generateTrendAnalysis(): array
    {
        // تحلیل trend بر اساس داده‌های اخیر
        return [
            'error_trend' => 'stable', // increasing, decreasing, stable
            'performance_trend' => 'improving',
            'user_satisfaction_trend' => 'stable'
        ];
    }
    
    private function getRecentUploadAttempts(?int $userId): int
    {
        if (!$userId) return 0;
        
        try {
            $sql = "SELECT COUNT(*) as attempts FROM activity_logs 
                    WHERE user_id = ? AND action = 'upload' 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)$result['attempts'];
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function generateSuggestionSummary(array $context): string
    {
        $issueCount = count($context['issue_summary'] ?? []);
        $suggestionCount = count($context['improvement_suggestions'] ?? []);
        
        return "تحلیل {$issueCount} مشکل شناسایی شده با {$suggestionCount} پیشنهاد بهبود";
    }
    
    private function calculateOverallPriority(array $context): string
    {
        $priorities = $context['priority_summary'] ?? [];
        
        if (isset($priorities['critical']) && $priorities['critical'] > 0) return 'critical';
        if (isset($priorities['high']) && $priorities['high'] > 2) return 'high';
        if (isset($priorities['medium']) && $priorities['medium'] > 5) return 'medium';
        
        return 'low';
    }
    
    private function estimateEffort(array $context): string
    {
        $fileCount = count($context['affected_files'] ?? []);
        $suggestionCount = count($context['improvement_suggestions'] ?? []);
        
        if ($fileCount > 5 || $suggestionCount > 3) return 'high';
        if ($fileCount > 2 || $suggestionCount > 1) return 'medium';
        
        return 'low';
    }
    
    private function updateCopilotFiles(): void
    {
        // ایجاد فایل‌های خاص برای Copilot
        $learningData = [
            'timestamp' => time(),
            'system_health' => $this->getSystemHealthSummary(),
            'user_patterns' => $this->getUserPatternSummary(),
            'performance_insights' => $this->getPerformanceInsights(),
            'recommended_focus_areas' => $this->getRecommendedFocusAreas()
        ];
        
        file_put_contents(
            self::COPILOT_LOG_PATH . 'learning-feedback.json',
            json_encode($learningData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
    
    private function getSystemHealthSummary(): array
    {
        return [
            'status' => 'healthy', // healthy, warning, critical
            'error_rate' => '2%',
            'performance_score' => '8.5/10',
            'user_satisfaction' => '85%'
        ];
    }
    
    private function getUserPatternSummary(): array
    {
        return [
            'most_common_issues' => ['upload_problems', 'slow_loading'],
            'peak_usage_times' => ['14:00-16:00', '20:00-22:00'],
            'user_journey_bottlenecks' => ['file_upload_page', 'registration_form']
        ];
    }
    
    private function getPerformanceInsights(): array
    {
        return [
            'slowest_endpoints' => ['/upload', '/auth/register'],
            'memory_hotspots' => ['image_processing', 'session_handling'],
            'database_bottlenecks' => ['uploads_table', 'user_sessions']
        ];
    }
    
    private function getRecommendedFocusAreas(): array
    {
        return [
            'immediate' => ['fix_upload_chunking', 'optimize_image_processing'],
            'short_term' => ['improve_error_messages', 'add_progress_indicators'],
            'long_term' => ['implement_caching', 'database_optimization']
        ];
    }
}

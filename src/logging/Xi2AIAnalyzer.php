<?php
/**
 * Xi2 Smart Logging System - AI Analyzer
 * 
 * @description تحلیل هوشمند real-time لاگ‌ها و رفتار کاربران
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

require_once __DIR__ . '/interfaces/AnalyzerInterface.php';
require_once __DIR__ . '/Xi2PatternDetector.php';

class Xi2AIAnalyzer implements AnalyzerInterface
{
    private $patternDetector;
    private $predictionEngine;
    private $emotionalAnalyzer;
    private $performanceAnalyzer;
    private $db;
    
    // آستانه‌های تشخیص مشکل
    private const THRESHOLDS = [
        'performance' => [
            'page_load_slow' => 3.0,       // بیش از 3 ثانیه
            'api_response_slow' => 2.0,    // بیش از 2 ثانیه
            'memory_high' => 50 * 1024 * 1024, // بیش از 50MB
        ],
        'user_behavior' => [
            'rapid_clicking' => 5,          // بیش از 5 کلیک در ثانیه
            'form_abandonment_time' => 30,  // رها کردن فرم بعد از 30 ثانیه
            'session_timeout_warning' => 300, // هشدار 5 دقیقه قبل از انقضا
            'error_threshold' => 3,         // بیش از 3 خطا در 5 دقیقه
        ],
        'system' => [
            'error_cascade' => 5,           // بیش از 5 خطای متوالی
            'concurrent_failures' => 3,    // بیش از 3 failure همزمان
            'disk_usage_high' => 90,       // بیش از 90 درصد
        ]
    ];
    
    public function __construct()
    {
        $this->patternDetector = new Xi2PatternDetector();
        $this->db = Database::getInstance()->getConnection();
        $this->initializeAnalyzer();
    }
    
    /**
     * راه‌اندازی اولیه تحلیلگر
     */
    private function initializeAnalyzer(): void
    {
        // بارگذاری machine learning models (در آینده)
        // فعلاً از rule-based analysis استفاده می‌کنیم
    }
    
    /**
     * تحلیل realtime یک event
     */
    public function analyzeEvent(array $eventData): array
    {
        $analysis = [
            'timestamp' => microtime(true),
            'event_id' => $eventData['timestamp'] ?? time(),
            'event_type' => $eventData['event_type'] ?? 'unknown',
            'analysis_results' => [],
            'issues' => [],
            'predictions' => [],
            'recommendations' => [],
            'confidence_score' => 1.0,
            'requires_immediate_action' => false,
            'code_improvement_needed' => false
        ];
        
        try {
            // 1. تحلیل نوع event
            $analysis['classification'] = $this->classifyEvent($eventData);
            
            // 2. تحلیل performance
            if (isset($eventData['performance_metrics']) || $eventData['event_type'] === 'performance') {
                $analysis['performance_analysis'] = $this->analyzePerformance($eventData);
                $analysis['issues'] = array_merge($analysis['issues'], $analysis['performance_analysis']['issues'] ?? []);
            }
            
            // 3. تحلیل رفتار کاربر
            if (isset($eventData['user_id']) && $eventData['user_id']) {
                $analysis['user_behavior'] = $this->analyzeUserBehaviorEvent($eventData);
                $analysis['issues'] = array_merge($analysis['issues'], $analysis['user_behavior']['issues'] ?? []);
            }
            
            // 4. تحلیل خطاها
            if ($eventData['event_type'] === 'error') {
                $analysis['error_analysis'] = $this->analyzeError($eventData);
                $analysis['issues'] = array_merge($analysis['issues'], $analysis['error_analysis']['issues'] ?? []);
            }
            
            // 5. تشخیص الگوهای مشکل‌دار
            $patterns = $this->patternDetector->detectEventPatterns($eventData);
            if (!empty($patterns)) {
                $analysis['patterns'] = $patterns;
                $analysis['issues'] = array_merge($analysis['issues'], $patterns['issues'] ?? []);
            }
            
            // 6. پیش‌بینی مشکلات آینده
            $analysis['predictions'] = $this->predictUpcomingIssues($analysis);
            
            // 7. تولید توصیه‌ها
            $analysis['recommendations'] = $this->generateRecommendations($analysis);
            
            // 8. محاسبه confidence score
            $analysis['confidence_score'] = $this->calculateConfidence($analysis);
            
            // 9. تعیین اولویت
            $analysis = $this->setPriority($analysis);
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log("Xi2AIAnalyzer Error: " . $e->getMessage());
            $analysis['analysis_error'] = $e->getMessage();
            $analysis['confidence_score'] = 0.0;
            return $analysis;
        }
    }
    
    /**
     * شناسایی الگوهای مشکل‌دار
     */
    public function detectPatterns(array $events): array
    {
        return $this->patternDetector->analyzeEventSequence($events);
    }
    
    /**
     * پیش‌بینی مشکلات آینده
     */
    public function predictIssues(array $patterns): array
    {
        return $this->predictUpcomingIssues($patterns);
    }

    /**
     * پیش‌بینی مشکلات آینده (متد کمکی)
     */
    public function predictUpcomingIssues(array $patterns): array
    {
        $predictions = [];
        
        // تحلیل trend ها
        foreach ($patterns as $patternType => $patternData) {
            switch ($patternType) {
                case 'performance_degradation':
                    $predictions[] = [
                        'type' => 'system_slowdown',
                        'probability' => 0.8,
                        'timeframe' => '5-10 minutes',
                        'impact' => 'high',
                        'description' => 'سیستم ممکن است در چند دقیقه آینده کند شود'
                    ];
                    break;
                    
                case 'error_cascade':
                    $predictions[] = [
                        'type' => 'system_failure',
                        'probability' => 0.9,
                        'timeframe' => '1-3 minutes',
                        'impact' => 'critical',
                        'description' => 'احتمال خرابی سیستم بسیار بالا است'
                    ];
                    break;
                    
                case 'user_frustration':
                    $predictions[] = [
                        'type' => 'user_abandonment',
                        'probability' => 0.7,
                        'timeframe' => '30-60 seconds',
                        'impact' => 'medium',
                        'description' => 'کاربر ممکن است سایت را ترک کند'
                    ];
                    break;
            }
        }
        
        return $predictions;
    }
    
    /**
     * تحلیل رفتار کاربر
     */
    public function analyzeUserBehavior(int $userId, array $userEvents): array
    {
        $analysis = [
            'user_id' => $userId,
            'session_analysis' => [],
            'behavior_patterns' => [],
            'emotional_state' => [],
            'recommendations' => [],
            'risk_factors' => []
        ];
        
        // تحلیل session فعلی
        $analysis['session_analysis'] = $this->analyzeCurrentSession($userEvents);
        
        // تحلیل الگوهای رفتاری
        $analysis['behavior_patterns'] = $this->detectUserPatterns($userEvents);
        
        // تحلیل احساسات
        $analysis['emotional_state'] = $this->analyzeUserEmotion($userEvents);
        
        // تشخیص ریسک‌ها
        $analysis['risk_factors'] = $this->detectUserRisks($userEvents);
        
        return $analysis;
    }
    
    /**
     * محاسبه سطح اعتماد
     */
    public function calculateConfidence(array $analysisData): float
    {
        $factors = [];
        
        // تعداد داده‌های موجود
        $dataPoints = count($analysisData['analysis_results'] ?? []);
        $factors['data_sufficiency'] = min(1.0, $dataPoints / 10);
        
        // کیفیت الگوهای تشخیص داده شده
        $patternQuality = 0.8; // default
        if (isset($analysisData['patterns']) && !empty($analysisData['patterns'])) {
            $strongPatterns = array_filter($analysisData['patterns'], function($pattern) {
                return ($pattern['confidence'] ?? 0) > 0.7;
            });
            $patternQuality = count($strongPatterns) / count($analysisData['patterns']);
        }
        $factors['pattern_quality'] = $patternQuality;
        
        // تطبیق با تجربیات گذشته
        $factors['historical_match'] = 0.9; // فعلاً ثابت
        
        // محاسبه میانگین وزن‌دار
        $weights = [
            'data_sufficiency' => 0.3,
            'pattern_quality' => 0.5,
            'historical_match' => 0.2
        ];
        
        $confidence = 0;
        foreach ($factors as $factor => $value) {
            $confidence += $value * $weights[$factor];
        }
        
        return round($confidence, 2);
    }
    
    /**
     * تولید گزارش تحلیل
     */
    public function generateReport(array $analysisData): array
    {
        return [
            'report_id' => uniqid('xi2_report_'),
            'timestamp' => time(),
            'summary' => $this->generateSummary($analysisData),
            'detailed_analysis' => $analysisData,
            'action_items' => $this->generateActionItems($analysisData),
            'metrics' => $this->extractMetrics($analysisData),
            'recommendations' => $analysisData['recommendations'] ?? []
        ];
    }
    
    // Private Analysis Methods
    
    /**
     * طبقه‌بندی event
     */
    private function classifyEvent(array $eventData): array
    {
        $classification = [
            'primary_type' => $eventData['event_type'] ?? 'unknown',
            'severity' => 'normal',
            'category' => 'general',
            'requires_attention' => false
        ];
        
        switch ($eventData['event_type'] ?? '') {
            case 'error':
                $classification['severity'] = 'high';
                $classification['category'] = 'system';
                $classification['requires_attention'] = true;
                break;
                
            case 'performance':
                $classification['category'] = 'system';
                if (isset($eventData['page_load_time']) && $eventData['page_load_time'] > 3) {
                    $classification['severity'] = 'medium';
                    $classification['requires_attention'] = true;
                }
                break;
                
            case 'user_activity':
                $classification['category'] = 'user_behavior';
                break;
        }
        
        return $classification;
    }
    
    /**
     * تحلیل performance
     */
    private function analyzePerformance(array $eventData): array
    {
        $analysis = [
            'metrics' => [],
            'issues' => [],
            'recommendations' => []
        ];
        
        // تحلیل زمان بارگذاری صفحه
        if (isset($eventData['page_load_time'])) {
            $loadTime = $eventData['page_load_time'];
            $analysis['metrics']['page_load_time'] = $loadTime;
            
            if ($loadTime > self::THRESHOLDS['performance']['page_load_slow']) {
                $analysis['issues'][] = [
                    'type' => 'slow_page_load',
                    'severity' => 'medium',
                    'value' => $loadTime,
                    'threshold' => self::THRESHOLDS['performance']['page_load_slow'],
                    'description' => "صفحه در {$loadTime} ثانیه بارگذاری شد که بیش از حد مطلوب است"
                ];
            }
        }
        
        // تحلیل استفاده از memory
        if (isset($eventData['memory_usage'])) {
            $memoryUsage = $eventData['memory_usage'];
            $analysis['metrics']['memory_usage'] = $memoryUsage;
            
            if ($memoryUsage > self::THRESHOLDS['performance']['memory_high']) {
                $analysis['issues'][] = [
                    'type' => 'high_memory_usage',
                    'severity' => 'medium',
                    'value' => $memoryUsage,
                    'threshold' => self::THRESHOLDS['performance']['memory_high'],
                    'description' => "استفاده از memory بالا است: " . $this->formatBytes($memoryUsage)
                ];
            }
        }
        
        return $analysis;
    }
    
    /**
     * تحلیل رفتار کاربر در یک event
     */
    private function analyzeUserBehaviorEvent(array $eventData): array
    {
        $analysis = [
            'behavior_type' => 'normal',
            'indicators' => [],
            'issues' => []
        ];
        
        // تحلیل کلیک‌های سریع
        if (isset($eventData['click_frequency']) && $eventData['click_frequency'] > self::THRESHOLDS['user_behavior']['rapid_clicking']) {
            $analysis['behavior_type'] = 'frustrated';
            $analysis['indicators'][] = 'rapid_clicking';
            $analysis['issues'][] = [
                'type' => 'user_frustration',
                'severity' => 'medium',
                'description' => 'کاربر به سرعت کلیک می‌کند که نشان از عصبانیت است'
            ];
        }
        
        return $analysis;
    }
    
    /**
     * تحلیل خطا
     */
    private function analyzeError(array $eventData): array
    {
        $analysis = [
            'error_type' => 'unknown',
            'severity' => 'medium',
            'issues' => [],
            'potential_causes' => []
        ];
        
        $message = $eventData['message'] ?? '';
        $context = $eventData['context'] ?? [];
        
        // طبقه‌بندی نوع خطا
        if (strpos($message, 'database') !== false || strpos($message, 'SQL') !== false) {
            $analysis['error_type'] = 'database';
            $analysis['severity'] = 'high';
            $analysis['potential_causes'][] = 'مشکل در اتصال به پایگاه داده';
        } elseif (strpos($message, 'file') !== false || strpos($message, 'upload') !== false) {
            $analysis['error_type'] = 'file_system';
            $analysis['potential_causes'][] = 'مشکل در سیستم فایل یا آپلود';
        } elseif (strpos($message, 'permission') !== false) {
            $analysis['error_type'] = 'permission';
            $analysis['potential_causes'][] = 'مشکل در دسترسی‌ها';
        }
        
        $analysis['issues'][] = [
            'type' => $analysis['error_type'] . '_error',
            'severity' => $analysis['severity'],
            'description' => $message
        ];
        
        return $analysis;
    }
    
    /**
     * تحلیل session فعلی کاربر
     */
    private function analyzeCurrentSession(array $userEvents): array
    {
        $analysis = [
            'duration' => 0,
            'events_count' => count($userEvents),
            'errors_count' => 0,
            'activity_level' => 'normal',
            'engagement_score' => 0.5
        ];
        
        if (empty($userEvents)) return $analysis;
        
        // محاسبه مدت session
        $firstEvent = min(array_column($userEvents, 'timestamp'));
        $lastEvent = max(array_column($userEvents, 'timestamp'));
        $analysis['duration'] = $lastEvent - $firstEvent;
        
        // شمارش خطاها
        $analysis['errors_count'] = count(array_filter($userEvents, function($event) {
            return $event['event_type'] === 'error';
        }));
        
        // محاسبه سطح فعالیت
        if ($analysis['events_count'] > 50) {
            $analysis['activity_level'] = 'high';
        } elseif ($analysis['events_count'] < 10) {
            $analysis['activity_level'] = 'low';
        }
        
        // محاسبه engagement score
        $analysis['engagement_score'] = min(1.0, $analysis['events_count'] / 100);
        
        return $analysis;
    }
    
    /**
     * تشخیص الگوهای رفتاری کاربر
     */
    private function detectUserPatterns(array $userEvents): array
    {
        // این متد در Xi2PatternDetector پیاده‌سازی می‌شود
        return [];
    }
    
    /**
     * تحلیل احساسات کاربر
     */
    private function analyzeUserEmotion(array $userEvents): array
    {
        $emotions = [
            'frustration' => 0,
            'confusion' => 0,
            'satisfaction' => 0,
            'engagement' => 0
        ];
        
        foreach ($userEvents as $event) {
            // تشخیص frustration
            if (isset($event['rapid_clicking']) && $event['rapid_clicking']) {
                $emotions['frustration'] += 0.3;
            }
            if ($event['event_type'] === 'error') {
                $emotions['frustration'] += 0.2;
            }
            
            // تشخیص confusion
            if (isset($event['long_hover']) && $event['long_hover']) {
                $emotions['confusion'] += 0.2;
            }
            
            // تشخیص satisfaction
            if (isset($event['task_completed']) && $event['task_completed']) {
                $emotions['satisfaction'] += 0.4;
            }
        }
        
        // نرمال‌سازی مقادیر
        foreach ($emotions as &$value) {
            $value = min(1.0, max(0.0, $value));
        }
        
        return $emotions;
    }
    
    /**
     * تشخیص ریسک‌های کاربر
     */
    private function detectUserRisks(array $userEvents): array
    {
        $risks = [];
        
        // ریسک ترک کردن
        $errorCount = count(array_filter($userEvents, function($event) {
            return $event['event_type'] === 'error';
        }));
        
        if ($errorCount > 3) {
            $risks[] = [
                'type' => 'abandonment_risk',
                'probability' => 0.7,
                'description' => 'کاربر ممکن است به دلیل خطاهای زیاد سایت را ترک کند'
            ];
        }
        
        return $risks;
    }
    
    /**
     * تولید توصیه‌ها
     */
    private function generateRecommendations(array $analysisData): array
    {
        $recommendations = [];
        
        foreach ($analysisData['issues'] ?? [] as $issue) {
            switch ($issue['type']) {
                case 'slow_page_load':
                    $recommendations[] = [
                        'type' => 'performance_optimization',
                        'priority' => 'high',
                        'action' => 'بهینه‌سازی سرعت صفحه',
                        'details' => 'فعال کردن cache، بهینه‌سازی تصاویر، کاهش اندازه فایل‌ها'
                    ];
                    break;
                    
                case 'user_frustration':
                    $recommendations[] = [
                        'type' => 'user_experience',
                        'priority' => 'medium', 
                        'action' => 'بهبود تجربه کاربری',
                        'details' => 'اضافه کردن راهنمایی، بهبود feedback، کاهش مراحل'
                    ];
                    break;
            }
        }
        
        return $recommendations;
    }
    
    /**
     * تعیین اولویت تحلیل
     */
    private function setPriority(array $analysis): array
    {
        $criticalIssues = array_filter($analysis['issues'] ?? [], function($issue) {
            return $issue['severity'] === 'critical' || $issue['severity'] === 'high';
        });
        
        if (!empty($criticalIssues)) {
            $analysis['requires_immediate_action'] = true;
            $analysis['priority'] = 'critical';
        } elseif (count($analysis['issues'] ?? []) > 3) {
            $analysis['priority'] = 'high';
        } else {
            $analysis['priority'] = 'normal';
        }
        
        return $analysis;
    }
    
    // Utility Methods
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function generateSummary(array $analysisData): string
    {
        $issuesCount = count($analysisData['issues'] ?? []);
        $confidence = $analysisData['confidence_score'] ?? 0;
        
        if ($issuesCount === 0) {
            return "همه چیز عادی است. هیچ مشکلی شناسایی نشد.";
        } else {
            return "{$issuesCount} مشکل شناسایی شد با اطمینان {$confidence}%.";
        }
    }
    
    private function generateActionItems(array $analysisData): array
    {
        $actionItems = [];
        
        foreach ($analysisData['issues'] ?? [] as $issue) {
            $actionItems[] = [
                'issue' => $issue['type'],
                'action' => "رفع " . $issue['description'],
                'priority' => $issue['severity']
            ];
        }
        
        return $actionItems;
    }
    
    private function extractMetrics(array $analysisData): array
    {
        return [
            'total_issues' => count($analysisData['issues'] ?? []),
            'confidence_score' => $analysisData['confidence_score'] ?? 0,
            'requires_action' => $analysisData['requires_immediate_action'] ?? false
        ];
    }
}

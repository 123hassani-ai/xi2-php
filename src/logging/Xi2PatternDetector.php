<?php
/**
 * Xi2 Smart Logging System - Pattern Detector
 * 
 * @description شناسایی الگوهای مشکل‌دار در رفتار کاربران و سیستم
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

class Xi2PatternDetector
{
    private $db;
    private $sessionManager;
    
    // الگوهای شناسایی‌شده
    private const PATTERNS = [
        'user_frustration' => [
            'indicators' => ['rapid_clicking', 'page_refresh', 'back_button', 'form_restart'],
            'threshold' => 3,
            'timeframe' => 60 // ثانیه
        ],
        'performance_degradation' => [
            'indicators' => ['slow_response', 'high_memory', 'long_queries'],
            'threshold' => 5,
            'timeframe' => 300
        ],
        'error_cascade' => [
            'indicators' => ['consecutive_errors', 'related_failures'],
            'threshold' => 3,
            'timeframe' => 120
        ],
        'abandonment_pattern' => [
            'indicators' => ['form_abandonment', 'quick_exit', 'low_engagement'],
            'threshold' => 2,
            'timeframe' => 180
        ]
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * تشخیص الگو در یک event
     */
    public function detectEventPatterns(array $eventData): array
    {
        $detectedPatterns = [
            'patterns' => [],
            'issues' => [],
            'confidence' => 0.5
        ];
        
        // دریافت eventهای اخیر برای تحلیل الگو
        $recentEvents = $this->getRecentEvents($eventData['user_id'] ?? null, $eventData['session_id'] ?? null);
        
        // تحلیل الگوهای کاربر
        if (!empty($recentEvents)) {
            $userPatterns = $this->analyzeUserPatterns($recentEvents);
            $detectedPatterns['patterns'] = array_merge($detectedPatterns['patterns'], $userPatterns);
        }
        
        // تحلیل الگوهای سیستم
        $systemPatterns = $this->analyzeSystemPatterns($eventData);
        $detectedPatterns['patterns'] = array_merge($detectedPatterns['patterns'], $systemPatterns);
        
        // استخراج مسائل از الگوها
        $detectedPatterns['issues'] = $this->extractIssuesFromPatterns($detectedPatterns['patterns']);
        
        // محاسبه confidence
        $detectedPatterns['confidence'] = $this->calculatePatternConfidence($detectedPatterns['patterns']);
        
        return $detectedPatterns;
    }
    
    /**
     * تحلیل توالی eventها
     */
    public function analyzeEventSequence(array $events): array
    {
        $analysis = [
            'sequence_patterns' => [],
            'behavioral_insights' => [],
            'anomalies' => [],
            'trends' => []
        ];
        
        if (count($events) < 2) {
            return $analysis;
        }
        
        // مرتب‌سازی events بر اساس زمان
        usort($events, function($a, $b) {
            return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
        });
        
        // تحلیل sequence patterns
        $analysis['sequence_patterns'] = $this->detectSequencePatterns($events);
        
        // تحلیل رفتاری
        $analysis['behavioral_insights'] = $this->analyzeBehavioralSequence($events);
        
        // تشخیص anomalies
        $analysis['anomalies'] = $this->detectAnomalies($events);
        
        // تحلیل trend
        $analysis['trends'] = $this->analyzeTrends($events);
        
        return $analysis;
    }
    
    /**
     * تحلیل الگوهای کاربر
     */
    private function analyzeUserPatterns(array $events): array
    {
        $patterns = [];
        
        // الگوی عصبانیت کاربر
        $frustrationPattern = $this->detectFrustrationPattern($events);
        if ($frustrationPattern) {
            $patterns[] = $frustrationPattern;
        }
        
        // الگوی گیجی کاربر
        $confusionPattern = $this->detectConfusionPattern($events);
        if ($confusionPattern) {
            $patterns[] = $confusionPattern;
        }
        
        // الگوی رها کردن کار
        $abandonmentPattern = $this->detectAbandonmentPattern($events);
        if ($abandonmentPattern) {
            $patterns[] = $abandonmentPattern;
        }
        
        return $patterns;
    }
    
    /**
     * تحلیل الگوهای سیستم
     */
    private function analyzeSystemPatterns(array $eventData): array
    {
        $patterns = [];
        
        // الگوی کاهش performance
        if (isset($eventData['performance_metrics'])) {
            $performancePattern = $this->detectPerformancePattern($eventData);
            if ($performancePattern) {
                $patterns[] = $performancePattern;
            }
        }
        
        // الگوی خطاهای متوالی
        if ($eventData['event_type'] === 'error') {
            $errorPattern = $this->detectErrorCascadePattern($eventData);
            if ($errorPattern) {
                $patterns[] = $errorPattern;
            }
        }
        
        return $patterns;
    }
    
    /**
     * تشخیص الگوی عصبانیت کاربر
     */
    private function detectFrustrationPattern(array $events): ?array
    {
        $frustrationIndicators = 0;
        $recentEvents = array_slice($events, -10); // آخرین 10 event
        
        foreach ($recentEvents as $event) {
            $eventData = $event['data'] ?? $event;
            
            // کلیک‌های سریع
            if (isset($eventData['click_frequency']) && $eventData['click_frequency'] > 3) {
                $frustrationIndicators++;
            }
            
            // refresh مکرر
            if ($eventData['event_type'] === 'page_load' && isset($eventData['is_refresh']) && $eventData['is_refresh']) {
                $frustrationIndicators++;
            }
            
            // خطاهای متوالی
            if ($eventData['event_type'] === 'error') {
                $frustrationIndicators++;
            }
            
            // استفاده از دکمه back
            if (isset($eventData['action']) && $eventData['action'] === 'back_button') {
                $frustrationIndicators++;
            }
        }
        
        if ($frustrationIndicators >= self::PATTERNS['user_frustration']['threshold']) {
            return [
                'type' => 'user_frustration',
                'severity' => 'medium',
                'confidence' => min(1.0, $frustrationIndicators / 10),
                'indicators_count' => $frustrationIndicators,
                'description' => 'کاربر نشان‌های عصبانیت از خود بروز می‌دهد',
                'detected_at' => time(),
                'recommendations' => [
                    'نمایش پیغام کمک‌رسان',
                    'ساده‌سازی رابط کاربری',
                    'بررسی مشکلات فنی'
                ]
            ];
        }
        
        return null;
    }
    
    /**
     * تشخیص الگوی گیجی کاربر
     */
    private function detectConfusionPattern(array $events): ?array
    {
        $confusionIndicators = 0;
        $recentEvents = array_slice($events, -15);
        
        foreach ($recentEvents as $event) {
            $eventData = $event['data'] ?? $event;
            
            // hover طولانی روی المان‌ها
            if (isset($eventData['hover_duration']) && $eventData['hover_duration'] > 3000) {
                $confusionIndicators++;
            }
            
            // کلیک‌های تصادفی
            if (isset($eventData['random_clicking']) && $eventData['random_clicking']) {
                $confusionIndicators++;
            }
            
            // رفت و برگشت بین صفحات
            if (isset($eventData['page_switching_frequency']) && $eventData['page_switching_frequency'] > 5) {
                $confusionIndicators++;
            }
            
            // مراجعه به بخش راهنما
            if (isset($eventData['help_section_visited']) && $eventData['help_section_visited']) {
                $confusionIndicators++;
            }
        }
        
        if ($confusionIndicators >= 2) {
            return [
                'type' => 'user_confusion',
                'severity' => 'medium',
                'confidence' => min(1.0, $confusionIndicators / 5),
                'indicators_count' => $confusionIndicators,
                'description' => 'کاربر گیج به نظر می‌رسد و نمی‌داند چه کار کند',
                'detected_at' => time(),
                'recommendations' => [
                    'نمایش راهنمای contextual',
                    'highlighting قسمت‌های مهم',
                    'ساده‌سازی مراحل'
                ]
            ];
        }
        
        return null;
    }
    
    /**
     * تشخیص الگوی رها کردن کار
     */
    private function detectAbandonmentPattern(array $events): ?array
    {
        $lastEvent = end($events);
        $eventData = $lastEvent['data'] ?? $lastEvent;
        
        // بررسی فرم نیمه‌تمام
        if (isset($eventData['form_started']) && !isset($eventData['form_completed'])) {
            $formStartTime = $eventData['form_start_time'] ?? 0;
            $currentTime = time();
            
            if (($currentTime - $formStartTime) > 30 && ($currentTime - $formStartTime) < 300) {
                return [
                    'type' => 'form_abandonment',
                    'severity' => 'high',
                    'confidence' => 0.8,
                    'description' => 'کاربر فرم را نیمه‌تمام رها کرده است',
                    'detected_at' => time(),
                    'time_spent' => $currentTime - $formStartTime,
                    'recommendations' => [
                        'ارسال یادآوری ملایم',
                        'بررسی پیچیدگی فرم',
                        'اضافه کردن auto-save'
                    ]
                ];
            }
        }
        
        return null;
    }
    
    /**
     * تشخیص الگوی کاهش performance
     */
    private function detectPerformancePattern(array $eventData): ?array
    {
        $performanceMetrics = $eventData['performance_metrics'] ?? [];
        
        // بررسی زمان response
        if (isset($performanceMetrics['response_time']) && $performanceMetrics['response_time'] > 2000) {
            return [
                'type' => 'performance_degradation',
                'severity' => 'medium',
                'confidence' => 0.9,
                'metric' => 'response_time',
                'value' => $performanceMetrics['response_time'],
                'threshold' => 2000,
                'description' => 'زمان پاسخ سرور بیش از حد طبیعی است',
                'detected_at' => time()
            ];
        }
        
        return null;
    }
    
    /**
     * تشخیص الگوی خطاهای متوالی
     */
    private function detectErrorCascadePattern(array $eventData): ?array
    {
        // دریافت خطاهای اخیر
        $recentErrors = $this->getRecentErrors($eventData['session_id'] ?? null, 300); // 5 دقیقه اخیر
        
        if (count($recentErrors) >= self::PATTERNS['error_cascade']['threshold']) {
            return [
                'type' => 'error_cascade',
                'severity' => 'high',
                'confidence' => 0.95,
                'errors_count' => count($recentErrors),
                'timeframe' => 300,
                'description' => 'خطاهای متوالی در سیستم رخ داده است',
                'detected_at' => time(),
                'latest_errors' => array_slice($recentErrors, -3) // آخرین 3 خطا
            ];
        }
        
        return null;
    }
    
    /**
     * تحلیل sequence patterns
     */
    private function detectSequencePatterns(array $events): array
    {
        $patterns = [];
        
        // الگوی مسیر کاربر
        $userJourney = $this->analyzeUserJourney($events);
        if (!empty($userJourney['unusual_patterns'])) {
            $patterns['user_journey_anomaly'] = $userJourney;
        }
        
        // الگوی تکرار اعمال
        $repeatPattern = $this->detectRepetitiveActions($events);
        if ($repeatPattern) {
            $patterns['repetitive_actions'] = $repeatPattern;
        }
        
        return $patterns;
    }
    
    /**
     * تحلیل مسیر کاربر
     */
    private function analyzeUserJourney(array $events): array
    {
        $journey = [
            'path' => [],
            'unusual_patterns' => [],
            'efficiency_score' => 1.0
        ];
        
        foreach ($events as $event) {
            if (isset($event['data']['url'])) {
                $journey['path'][] = $event['data']['url'];
            }
        }
        
        // تشخیص رفت و برگشت غیرمعمول
        if (count($journey['path']) > 10) {
            $uniquePages = count(array_unique($journey['path']));
            $totalVisits = count($journey['path']);
            
            if ($uniquePages / $totalVisits < 0.5) { // بیش از 50% تکرار
                $journey['unusual_patterns'][] = [
                    'type' => 'excessive_back_forth',
                    'description' => 'کاربر بیش از حد بین صفحات رفت و برگشت می‌کند'
                ];
            }
        }
        
        return $journey;
    }
    
    /**
     * تشخیص اعمال تکراری
     */
    private function detectRepetitiveActions(array $events): ?array
    {
        $actionCounts = [];
        
        foreach ($events as $event) {
            $action = $event['data']['action'] ?? $event['data']['event_type'] ?? 'unknown';
            $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;
        }
        
        foreach ($actionCounts as $action => $count) {
            if ($count > 10) { // بیش از 10 بار تکرار
                return [
                    'action' => $action,
                    'count' => $count,
                    'description' => "عمل {$action} به صورت غیرعادی تکرار شده است"
                ];
            }
        }
        
        return null;
    }
    
    /**
     * استخراج مسائل از الگوها
     */
    private function extractIssuesFromPatterns(array $patterns): array
    {
        $issues = [];
        
        foreach ($patterns as $pattern) {
            $issues[] = [
                'type' => $pattern['type'],
                'severity' => $pattern['severity'] ?? 'medium',
                'description' => $pattern['description'] ?? '',
                'confidence' => $pattern['confidence'] ?? 0.5,
                'recommendations' => $pattern['recommendations'] ?? []
            ];
        }
        
        return $issues;
    }
    
    /**
     * محاسبه confidence الگوها
     */
    private function calculatePatternConfidence(array $patterns): float
    {
        if (empty($patterns)) return 0.0;
        
        $totalConfidence = array_sum(array_column($patterns, 'confidence'));
        return min(1.0, $totalConfidence / count($patterns));
    }
    
    // Helper Methods
    
    /**
     * دریافت eventهای اخیر
     */
    private function getRecentEvents(?int $userId, ?string $sessionId, int $minutes = 10): array
    {
        try {
            $sql = "SELECT * FROM activity_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
            $params = [$minutes];
            
            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            if ($sessionId) {
                $sql .= " AND JSON_EXTRACT(details, '$.session_id') = ?";
                $params[] = $sessionId;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 50";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Xi2PatternDetector Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * دریافت خطاهای اخیر
     */
    private function getRecentErrors(?string $sessionId, int $seconds = 300): array
    {
        try {
            $sql = "SELECT * FROM activity_logs WHERE action = 'error' AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
            $params = [$seconds];
            
            if ($sessionId) {
                $sql .= " AND JSON_EXTRACT(details, '$.session_id') = ?";
                $params[] = $sessionId;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Xi2PatternDetector getRecentErrors Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * تشخیص anomalies در events
     */
    private function detectAnomalies(array $events): array
    {
        $anomalies = [];
        
        // تشخیص spike در فعالیت
        $eventCounts = [];
        foreach ($events as $event) {
            $minute = floor(($event['timestamp'] ?? time()) / 60) * 60;
            $eventCounts[$minute] = ($eventCounts[$minute] ?? 0) + 1;
        }
        
        $avgEventsPerMinute = array_sum($eventCounts) / max(1, count($eventCounts));
        
        foreach ($eventCounts as $minute => $count) {
            if ($count > $avgEventsPerMinute * 3) { // 3 برابر میانگین
                $anomalies[] = [
                    'type' => 'activity_spike',
                    'timestamp' => $minute,
                    'value' => $count,
                    'expected' => round($avgEventsPerMinute),
                    'description' => 'فعالیت غیرعادی در این دقیقه'
                ];
            }
        }
        
        return $anomalies;
    }
    
    /**
     * تحلیل trend ها
     */
    private function analyzeTrends(array $events): array
    {
        $trends = [];
        
        if (count($events) < 10) return $trends;
        
        // تحلیل trend زمان response
        $responseTimes = [];
        foreach ($events as $event) {
            if (isset($event['data']['response_time'])) {
                $responseTimes[] = $event['data']['response_time'];
            }
        }
        
        if (count($responseTimes) >= 5) {
            $first_half = array_slice($responseTimes, 0, floor(count($responseTimes) / 2));
            $second_half = array_slice($responseTimes, floor(count($responseTimes) / 2));
            
            $avg_first = array_sum($first_half) / count($first_half);
            $avg_second = array_sum($second_half) / count($second_half);
            
            if ($avg_second > $avg_first * 1.5) {
                $trends['performance_degradation'] = [
                    'type' => 'performance_declining',
                    'severity' => 'medium',
                    'change_percentage' => round((($avg_second - $avg_first) / $avg_first) * 100),
                    'description' => 'کارایی سیستم در حال کاهش است'
                ];
            }
        }
        
        return $trends;
    }
    
    /**
     * تحلیل توالی رفتاری
     */
    private function analyzeBehavioralSequence(array $events): array
    {
        $insights = [];
        
        // تحلیل الگوی موفقیت کاربر
        $successEvents = array_filter($events, function($event) {
            return isset($event['data']['success']) && $event['data']['success'];
        });
        
        $failureEvents = array_filter($events, function($event) {
            return $event['data']['event_type'] === 'error' || 
                   (isset($event['data']['success']) && !$event['data']['success']);
        });
        
        $successRate = count($events) > 0 ? count($successEvents) / count($events) : 0;
        
        $insights['success_rate'] = [
            'value' => round($successRate, 2),
            'total_events' => count($events),
            'successful_events' => count($successEvents),
            'failed_events' => count($failureEvents)
        ];
        
        if ($successRate < 0.5) {
            $insights['low_success_rate'] = [
                'type' => 'low_success_rate',
                'severity' => 'high',
                'description' => 'نرخ موفقیت کاربر پایین است'
            ];
        }
        
        return $insights;
    }
}

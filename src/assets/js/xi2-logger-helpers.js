/**
 * Performance Monitor - مانیتور عملکرد پیشرفته
 */
class PerformanceMonitor {
    constructor() {
        this.metrics = {
            loadTime: null,
            renderTime: null,
            interactionDelays: [],
            resourceLoadTimes: new Map(),
            memoryUsage: [],
            fpsData: []
        };
        
        this.observer = null;
        this.isMonitoring = false;
    }
    
    /**
     * شروع monitoring
     */
    start() {
        this.isMonitoring = true;
        this.measurePageLoad();
        this.monitorInteractions();
        this.trackResourcePerformance();
        this.monitorMemory();
        this.trackFPS();
    }
    
    /**
     * اندازه‌گیری زمان لود صفحه
     */
    measurePageLoad() {
        if (performance.timing) {
            window.addEventListener('load', () => {
                const timing = performance.timing;
                this.metrics.loadTime = {
                    total: timing.loadEventEnd - timing.navigationStart,
                    dns: timing.domainLookupEnd - timing.domainLookupStart,
                    connect: timing.connectEnd - timing.connectStart,
                    request: timing.responseStart - timing.requestStart,
                    response: timing.responseEnd - timing.responseStart,
                    dom: timing.domContentLoadedEventEnd - timing.domLoading,
                    render: timing.loadEventEnd - timing.domContentLoadedEventEnd
                };
            });
        }
    }
    
    /**
     * نظارت بر تعاملات کاربر
     */
    monitorInteractions() {
        ['click', 'keydown', 'touchstart'].forEach(eventType => {
            document.addEventListener(eventType, (event) => {
                const startTime = performance.now();
                
                // استفاده از requestIdleCallback یا setTimeout
                const measureDelay = () => {
                    const delay = performance.now() - startTime;
                    this.metrics.interactionDelays.push({
                        type: eventType,
                        delay: delay,
                        timestamp: Date.now()
                    });
                    
                    // محدود کردن آرایه
                    if (this.metrics.interactionDelays.length > 100) {
                        this.metrics.interactionDelays.shift();
                    }
                };
                
                if (window.requestIdleCallback) {
                    requestIdleCallback(measureDelay);
                } else {
                    setTimeout(measureDelay, 0);
                }
            });
        });
    }
    
    /**
     * ردیابی عملکرد منابع
     */
    trackResourcePerformance() {
        if (PerformanceObserver) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'resource') {
                        this.metrics.resourceLoadTimes.set(entry.name, {
                            duration: entry.duration,
                            size: entry.transferSize,
                            type: this.getResourceType(entry.name)
                        });
                    }
                });
            });
            
            observer.observe({ entryTypes: ['resource'] });
        }
    }
    
    /**
     * نظارت بر مصرف حافظه
     */
    monitorMemory() {
        if ('memory' in performance) {
            setInterval(() => {
                this.metrics.memoryUsage.push({
                    used: performance.memory.usedJSHeapSize,
                    total: performance.memory.totalJSHeapSize,
                    limit: performance.memory.jsHeapSizeLimit,
                    timestamp: Date.now()
                });
                
                // محدود کردن داده‌ها
                if (this.metrics.memoryUsage.length > 60) {
                    this.metrics.memoryUsage.shift();
                }
            }, 5000);
        }
    }
    
    /**
     * اندازه‌گیری FPS
     */
    trackFPS() {
        let lastTime = performance.now();
        let frames = 0;
        
        const measureFPS = () => {
            if (!this.isMonitoring) return;
            
            frames++;
            const currentTime = performance.now();
            
            if (currentTime >= lastTime + 1000) {
                const fps = Math.round((frames * 1000) / (currentTime - lastTime));
                this.metrics.fpsData.push({
                    fps: fps,
                    timestamp: Date.now()
                });
                
                // محدود کردن داده‌ها
                if (this.metrics.fpsData.length > 60) {
                    this.metrics.fpsData.shift();
                }
                
                frames = 0;
                lastTime = currentTime;
            }
            
            requestAnimationFrame(measureFPS);
        };
        
        requestAnimationFrame(measureFPS);
    }
    
    /**
     * تشخیص نوع منبع
     */
    getResourceType(url) {
        if (url.match(/\.(css)$/)) return 'css';
        if (url.match(/\.(js)$/)) return 'javascript';
        if (url.match(/\.(png|jpg|jpeg|gif|svg|webp)$/)) return 'image';
        if (url.match(/\.(woff|woff2|ttf|eot)$/)) return 'font';
        return 'other';
    }
    
    /**
     * دریافت خلاصه metrics
     */
    getMetricsSummary() {
        return {
            loadTime: this.metrics.loadTime,
            averageInteractionDelay: this.getAverageInteractionDelay(),
            slowResources: this.getSlowResources(),
            memoryTrend: this.getMemoryTrend(),
            averageFPS: this.getAverageFPS(),
            performanceScore: this.calculatePerformanceScore()
        };
    }
    
    getAverageInteractionDelay() {
        if (this.metrics.interactionDelays.length === 0) return 0;
        const sum = this.metrics.interactionDelays.reduce((acc, item) => acc + item.delay, 0);
        return sum / this.metrics.interactionDelays.length;
    }
    
    getSlowResources() {
        const slowResources = [];
        this.metrics.resourceLoadTimes.forEach((data, url) => {
            if (data.duration > 1000) { // بیشتر از 1 ثانیه
                slowResources.push({ url, ...data });
            }
        });
        return slowResources;
    }
    
    getMemoryTrend() {
        if (this.metrics.memoryUsage.length < 2) return 'stable';
        
        const recent = this.metrics.memoryUsage.slice(-5);
        const increasing = recent.every((curr, index) => 
            index === 0 || curr.used > recent[index - 1].used
        );
        
        return increasing ? 'increasing' : 'stable';
    }
    
    getAverageFPS() {
        if (this.metrics.fpsData.length === 0) return 60;
        const sum = this.metrics.fpsData.reduce((acc, item) => acc + item.fps, 0);
        return Math.round(sum / this.metrics.fpsData.length);
    }
    
    calculatePerformanceScore() {
        let score = 100;
        
        // کسر امتیاز بر اساس load time
        if (this.metrics.loadTime && this.metrics.loadTime.total > 3000) {
            score -= 20;
        }
        
        // کسر امتیاز بر اساس interaction delay
        const avgDelay = this.getAverageInteractionDelay();
        if (avgDelay > 100) score -= 15;
        if (avgDelay > 300) score -= 25;
        
        // کسر امتیاز بر اساس FPS
        const avgFPS = this.getAverageFPS();
        if (avgFPS < 30) score -= 30;
        if (avgFPS < 60) score -= 15;
        
        return Math.max(0, score);
    }
}

/**
 * Error Handler - مدیریت پیشرفته خطاها
 */
class ErrorHandler {
    constructor() {
        this.errorCategories = new Map();
        this.errorPatterns = [];
        this.autoFixEnabled = true;
    }
    
    /**
     * تحلیل خطا و دسته‌بندی
     */
    analyzeError(error) {
        const category = this.categorizeError(error);
        const severity = this.assessSeverity(error);
        const suggestion = this.generateSuggestion(error, category);
        
        // ثبت در آمار
        this.recordErrorStats(category);
        
        return {
            category,
            severity,
            suggestion,
            timestamp: Date.now(),
            canAutoFix: this.canAutoFix(error, category)
        };
    }
    
    /**
     * دسته‌بندی خطا
     */
    categorizeError(error) {
        const message = error.message ? error.message.toLowerCase() : '';
        
        if (message.includes('network') || message.includes('fetch')) {
            return 'network';
        }
        
        if (message.includes('permission') || message.includes('denied')) {
            return 'permission';
        }
        
        if (message.includes('syntax') || message.includes('unexpected')) {
            return 'syntax';
        }
        
        if (message.includes('null') || message.includes('undefined')) {
            return 'reference';
        }
        
        return 'generic';
    }
    
    /**
     * ارزیابی شدت خطا
     */
    assessSeverity(error) {
        const criticalKeywords = ['critical', 'fatal', 'security'];
        const warningKeywords = ['warning', 'deprecated'];
        
        const message = (error.message || '').toLowerCase();
        
        if (criticalKeywords.some(keyword => message.includes(keyword))) {
            return 'critical';
        }
        
        if (warningKeywords.some(keyword => message.includes(keyword))) {
            return 'warning';
        }
        
        return 'error';
    }
    
    /**
     * تولید پیشنهاد رفع خطا
     */
    generateSuggestion(error, category) {
        switch (category) {
            case 'network':
                return 'بررسی کنید که اتصال اینترنت برقرار باشد و سرور در دسترس باشد.';
            
            case 'permission':
                return 'اجازه‌های لازم را بررسی کنید یا از کاربر درخواست کنید.';
            
            case 'syntax':
                return 'کد JavaScript را بررسی کنید و syntax errors را رفع کنید.';
            
            case 'reference':
                return 'مطمئن شوید که متغیرها و objects مقداردهی شده‌اند.';
            
            default:
                return 'خطا را بررسی و لاگ‌های کامل را مطالعه کنید.';
        }
    }
    
    /**
     * بررسی امکان رفع خودکار
     */
    canAutoFix(error, category) {
        const autoFixableCategories = ['network', 'reference'];
        return this.autoFixEnabled && autoFixableCategories.includes(category);
    }
    
    /**
     * ثبت آمار خطا
     */
    recordErrorStats(category) {
        const current = this.errorCategories.get(category) || 0;
        this.errorCategories.set(category, current + 1);
    }
    
    /**
     * دریافت آمار خطاها
     */
    getErrorStats() {
        const stats = {};
        this.errorCategories.forEach((count, category) => {
            stats[category] = count;
        });
        return stats;
    }
}

/**
 * Realtime Analyzer - تحلیلگر real-time
 */
class RealtimeAnalyzer {
    constructor() {
        this.analysisRules = new Map();
        this.setupDefaultRules();
    }
    
    /**
     * تنظیم قوانین پیشفرض تحلیل
     */
    setupDefaultRules() {
        // قانون تشخیص کند بودن
        this.analysisRules.set('slow_interaction', {
            condition: (data) => {
                return data.performanceMetrics && 
                       data.performanceMetrics.interactions > 0 && 
                       Date.now() - data.performanceMetrics.pageLoadStart > 5000;
            },
            action: 'show_loading_optimization'
        });
        
        // قانون تشخیص سردرگمی کاربر
        this.analysisRules.set('user_confusion', {
            condition: (data) => {
                return data.userBehavior && 
                       data.userBehavior.hoverDuration.some(duration => duration > 3000);
            },
            action: 'offer_help'
        });
        
        // قانون تشخیص خطاهای مکرر
        this.analysisRules.set('repeated_errors', {
            condition: (data) => {
                return data.performanceMetrics && data.performanceMetrics.errors > 3;
            },
            action: 'debug_assistance'
        });
    }
    
    /**
     * تحلیل event فردی
     */
    analyzeEvent(eventData) {
        const analysis = {
            requiresImmediateAction: false,
            immediateActions: [],
            insights: []
        };
        
        // تحلیل بر اساس نوع event
        switch (eventData.event_type) {
            case 'click':
                if (eventData.click_frequency > 5) {
                    analysis.requiresImmediateAction = true;
                    analysis.immediateActions.push({
                        type: 'show_help',
                        message: 'به نظر می‌رسد در حال تلاش برای انجام کاری هستید. نیاز به کمک دارید؟'
                    });
                }
                break;
                
            case 'error':
                analysis.requiresImmediateAction = true;
                analysis.immediateActions.push({
                    type: 'log_error',
                    error: eventData.error_info
                });
                break;
                
            case 'form_input':
                if (eventData.input_duration > 30000) { // 30 ثانیه
                    analysis.immediateActions.push({
                        type: 'show_help',
                        message: 'اگر در تکمیل فرم مشکل دارید، می‌توانید از راهنما استفاده کنید.'
                    });
                }
                break;
        }
        
        return analysis;
    }
    
    /**
     * تحلیل وضعیت فعلی
     */
    analyzeCurrentState(stateData) {
        const issues = [];
        const recommendations = [];
        
        // اجرای قوانین تحلیل
        this.analysisRules.forEach((rule, ruleName) => {
            if (rule.condition(stateData)) {
                issues.push({
                    type: ruleName,
                    action: rule.action,
                    severity: this.getIssueSeverity(ruleName),
                    timestamp: Date.now()
                });
                
                recommendations.push(this.getRecommendation(rule.action));
            }
        });
        
        return {
            issues,
            recommendations,
            overallHealth: this.calculateOverallHealth(stateData),
            timestamp: Date.now()
        };
    }
    
    /**
     * محاسبه سلامت کلی
     */
    calculateOverallHealth(stateData) {
        let score = 100;
        
        // کسر امتیاز بر اساس خطاها
        if (stateData.performanceMetrics) {
            score -= stateData.performanceMetrics.errors * 5;
            score -= stateData.performanceMetrics.frustrationIndicators * 10;
        }
        
        // کسر امتیاز بر اساس عملکرد
        if (stateData.userBehavior && stateData.userBehavior.frustrationIndicators > 0) {
            score -= stateData.userBehavior.frustrationIndicators * 15;
        }
        
        return Math.max(0, Math.min(100, score));
    }
    
    /**
     * تعیین شدت مسئله
     */
    getIssueSeverity(ruleName) {
        const severityMap = {
            'slow_interaction': 'medium',
            'user_confusion': 'high',
            'repeated_errors': 'critical'
        };
        
        return severityMap[ruleName] || 'low';
    }
    
    /**
     * دریافت توصیه
     */
    getRecommendation(action) {
        const recommendations = {
            'show_loading_optimization': 'نمایش loading indicators برای بهبود تجربه کاربر',
            'offer_help': 'ارائه کمک contextual به کاربر',
            'debug_assistance': 'فعال‌سازی حالت debug برای رفع خطاها'
        };
        
        return recommendations[action] || 'بررسی و بهینه‌سازی عملکرد';
    }
}

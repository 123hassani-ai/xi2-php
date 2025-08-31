/**
 * Xi2 Smart Logging System - Frontend Logger
 * 
 * @description هوشمندترین سیستم لاگ‌گیری frontend برای Xi2
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 * 
 * این کلاس قلب سیستم لاگ‌گیری frontend است که:
 * - خودکار همه رویدادها را capture می‌کند
 * - رفتار کاربر را تحلیل می‌کند
 * - مشکلات را پیش‌بینی می‌کند
 * - راه‌حل‌های خودکار ارائه می‌دهد
 */

class Xi2SmartLogger {
    constructor() {
        // Core properties
        this.sessionId = this.generateSessionId();
        this.userId = this.getUserId();
        this.apiBaseUrl = '/xi2.ir/src/api/logging/';
        this.isEnabled = true;
        this.debugMode = false;
        
        // Event tracking
        this.eventBuffer = [];
        this.bufferSize = 50;
        this.flushInterval = 5000; // 5 seconds
        
        // Performance monitoring
        this.performanceMetrics = {
            pageLoadStart: performance.now(),
            interactions: 0,
            errors: 0,
            apiCalls: 0
        };
        
        // User behavior analysis
        this.userBehavior = {
            clickFrequency: 0,
            hoverDuration: [],
            scrollBehavior: [],
            formInteractions: {},
            frustrationIndicators: 0
        };
        
        // Real-time analyzers
        this.performanceMonitor = new PerformanceMonitor();
        this.errorHandler = new ErrorHandler();
        this.realtimeAnalyzer = new RealtimeAnalyzer();
        
        // Initialize
        this.initialize();
    }
    
    /**
     * راه‌اندازی اولیه logger
     */
    initialize() {
        try {
            // ثبت event های سیستمی
            this.setupEventListeners();
            
            // شروع performance monitoring
            this.startPerformanceMonitoring();
            
            // تنظیم error boundaries
            this.setupErrorBoundaries();
            
            // شروع real-time analysis
            this.startRealtimeAnalysis();
            
            // شروع auto-flush
            this.startAutoFlush();
            
            // ثبت شروع session
            this.logEvent({
                event_type: 'session_start',
                timestamp: Date.now(),
                user_agent: navigator.userAgent,
                screen_resolution: `${screen.width}x${screen.height}`,
                viewport_size: `${window.innerWidth}x${window.innerHeight}`,
                connection_type: this.getConnectionType(),
                performance_timing: this.getInitialPerformanceTiming()
            });
            
            this.log('Xi2SmartLogger initialized successfully');
            
        } catch (error) {
            console.error('Xi2SmartLogger initialization failed:', error);
            this.isEnabled = false;
        }
    }
    
    /**
     * ثبت event اصلی
     */
    logEvent(eventData) {
        if (!this.isEnabled) return;
        
        try {
            // غنی‌سازی داده‌های event
            const enrichedEvent = this.enrichEventData(eventData);
            
            // اضافه به buffer
            this.addToBuffer(enrichedEvent);
            
            // تحلیل real-time
            const analysis = this.realtimeAnalyzer.analyzeEvent(enrichedEvent);
            
            // اعمال راه‌حل‌های فوری در صورت نیاز
            if (analysis.requiresImmediateAction) {
                this.handleImmediateAction(analysis);
            }
            
            // به‌روزرسانی metrics
            this.updateMetrics(enrichedEvent);
            
            // log کردن در console (فقط در debug mode)
            if (this.debugMode) {
                this.log('Event logged:', enrichedEvent);
            }
            
        } catch (error) {
            console.error('Error logging event:', error);
        }
    }
    
    /**
     * تنظیم event listeners خودکار
     */
    setupEventListeners() {
        // Click tracking با تحلیل frequency
        document.addEventListener('click', (event) => {
            this.userBehavior.clickFrequency++;
            
            // Reset counter after 1 second
            setTimeout(() => {
                this.userBehavior.clickFrequency = Math.max(0, this.userBehavior.clickFrequency - 1);
            }, 1000);
            
            // Log click event
            this.logEvent({
                event_type: 'click',
                timestamp: Date.now(),
                element_info: this.getElementInfo(event.target),
                coordinates: [event.clientX, event.clientY],
                click_frequency: this.userBehavior.clickFrequency,
                page_context: this.getPageContext()
            });
            
            // تشخیص rapid clicking (علامت frustration)
            if (this.userBehavior.clickFrequency > 5) {
                this.detectFrustration('rapid_clicking');
            }
        });
        
        // Form interactions
        document.addEventListener('input', (event) => {
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                this.trackFormInteraction(event);
            }
        });
        
        document.addEventListener('submit', (event) => {
            this.logEvent({
                event_type: 'form_submit',
                timestamp: Date.now(),
                form_info: this.getFormInfo(event.target),
                form_completion_time: this.getFormCompletionTime(event.target)
            });
        });
        
        // Page navigation
        window.addEventListener('beforeunload', () => {
            this.logEvent({
                event_type: 'page_unload',
                timestamp: Date.now(),
                session_duration: Date.now() - this.performanceMetrics.pageLoadStart,
                total_interactions: this.performanceMetrics.interactions
            });
            
            // Flush buffer فوری
            this.flushBuffer(true);
        });
        
        // Scroll behavior
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.userBehavior.scrollBehavior.push({
                    timestamp: Date.now(),
                    scrollTop: window.pageYOffset,
                    scrollPercent: (window.pageYOffset / (document.body.scrollHeight - window.innerHeight)) * 100
                });
                
                // محدود کردن آرایه
                if (this.userBehavior.scrollBehavior.length > 20) {
                    this.userBehavior.scrollBehavior.shift();
                }
            }, 150);
        });
        
        // Mouse hover tracking
        document.addEventListener('mouseover', (event) => {
            const hoverStart = Date.now();
            
            const hoverEnd = () => {
                const duration = Date.now() - hoverStart;
                this.userBehavior.hoverDuration.push(duration);
                
                // تشخیص hover طولانی (علامت confusion)
                if (duration > 3000) {
                    this.detectFrustration('long_hover', {
                        element: this.getElementInfo(event.target),
                        duration: duration
                    });
                }
                
                event.target.removeEventListener('mouseleave', hoverEnd);
            };
            
            event.target.addEventListener('mouseleave', hoverEnd, { once: true });
        });
        
        // API calls monitoring
        this.interceptFetchAndXHR();
        
        // Page visibility changes
        document.addEventListener('visibilitychange', () => {
            this.logEvent({
                event_type: 'visibility_change',
                timestamp: Date.now(),
                visibility_state: document.visibilityState,
                hidden: document.hidden
            });
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            this.logEvent({
                event_type: 'window_resize',
                timestamp: Date.now(),
                new_viewport: `${window.innerWidth}x${window.innerHeight}`
            });
        });
    }
    
    /**
     * شروع performance monitoring
     */
    startPerformanceMonitoring() {
        // Navigation timing
        window.addEventListener('load', () => {
            setTimeout(() => {
                const perfData = this.gatherPerformanceData();
                
                this.logEvent({
                    event_type: 'performance',
                    timestamp: Date.now(),
                    performance_metrics: perfData
                });
            }, 0);
        });
        
        // Resource timing
        this.monitorResourceLoading();
        
        // Memory monitoring (اگر API موجود باشد)
        if ('memory' in performance) {
            setInterval(() => {
                this.logEvent({
                    event_type: 'memory_usage',
                    timestamp: Date.now(),
                    memory_metrics: {
                        used: performance.memory.usedJSHeapSize,
                        total: performance.memory.totalJSHeapSize,
                        limit: performance.memory.jsHeapSizeLimit
                    }
                });
            }, 30000); // هر 30 ثانیه
        }
    }
    
    /**
     * تنظیم error boundaries
     */
    setupErrorBoundaries() {
        // Global error handler
        window.addEventListener('error', (event) => {
            this.logEvent({
                event_type: 'error',
                timestamp: Date.now(),
                error_info: {
                    message: event.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    stack: event.error ? event.error.stack : null
                },
                page_context: this.getPageContext(),
                user_context: this.getUserContext()
            });
            
            this.performanceMetrics.errors++;
        });
        
        // Promise rejection handler
        window.addEventListener('unhandledrejection', (event) => {
            this.logEvent({
                event_type: 'promise_rejection',
                timestamp: Date.now(),
                error_info: {
                    reason: event.reason,
                    promise: event.promise
                },
                page_context: this.getPageContext()
            });
        });
        
        // Console error interceptor
        const originalConsoleError = console.error;
        console.error = (...args) => {
            this.logEvent({
                event_type: 'console_error',
                timestamp: Date.now(),
                error_messages: args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
                )
            });
            
            originalConsoleError.apply(console, args);
        };
    }
    
    /**
     * شروع real-time analysis
     */
    startRealtimeAnalysis() {
        setInterval(() => {
            const analysis = this.realtimeAnalyzer.analyzeCurrentState({
                userBehavior: this.userBehavior,
                performanceMetrics: this.performanceMetrics,
                recentEvents: this.eventBuffer.slice(-10)
            });
            
            if (analysis.issues && analysis.issues.length > 0) {
                this.handleAnalysisResults(analysis);
            }
        }, 10000); // هر 10 ثانیه
    }
    
    /**
     * شروع auto flush
     */
    startAutoFlush() {
        setInterval(() => {
            if (this.eventBuffer.length > 0) {
                this.flushBuffer();
            }
        }, this.flushInterval);
    }
    
    /**
     * غنی‌سازی داده‌های event
     */
    enrichEventData(eventData) {
        return {
            ...eventData,
            session_id: this.sessionId,
            user_id: this.userId,
            timestamp: eventData.timestamp || Date.now(),
            page_url: window.location.href,
            referrer: document.referrer,
            user_agent: navigator.userAgent,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            screen_resolution: `${screen.width}x${screen.height}`,
            color_depth: screen.colorDepth,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            online_status: navigator.onLine,
            battery_level: this.getBatteryLevel(),
            performance_now: performance.now()
        };
    }
    
    /**
     * اضافه کردن event به buffer
     */
    addToBuffer(eventData) {
        this.eventBuffer.push(eventData);
        
        // محدود کردن اندازه buffer
        if (this.eventBuffer.length > this.bufferSize) {
            this.eventBuffer.shift();
        }
        
        // Flush فوری برای event های critical
        if (this.isCriticalEvent(eventData)) {
            this.flushBuffer();
        }
    }
    
    /**
     * ارسال buffer به سرور
     */
    async flushBuffer(isUrgent = false) {
        if (this.eventBuffer.length === 0) return;
        
        const eventsToSend = [...this.eventBuffer];
        this.eventBuffer = [];
        
        try {
            // ارسال همه events یکجا
            for (const event of eventsToSend) {
                await this.sendEventToServer(event, isUrgent);
            }
            
        } catch (error) {
            console.error('Failed to flush event buffer:', error);
            // برگرداندن events به buffer
            this.eventBuffer = [...eventsToSend, ...this.eventBuffer];
        }
    }
    
    /**
     * ارسال event به سرور
     */
    async sendEventToServer(eventData, isUrgent = false) {
        try {
            const response = await fetch(`${this.apiBaseUrl}log-event.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Xi2-Session-Id': this.sessionId
                },
                body: JSON.stringify(eventData),
                ...(isUrgent && { keepalive: true })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            // پردازش پاسخ سرور
            if (result.analysis) {
                this.handleServerAnalysis(result.analysis);
            }
            
        } catch (error) {
            console.error('Failed to send event to server:', error);
            throw error;
        }
    }
    
    /**
     * مدیریت اقدامات فوری
     */
    handleImmediateAction(analysis) {
        for (const action of analysis.immediateActions) {
            switch (action.type) {
                case 'show_loading':
                    this.showIntelligentLoading(action.config);
                    break;
                    
                case 'highlight_field':
                    this.highlightProblemField(action.fieldName);
                    break;
                    
                case 'show_help':
                    this.showContextualHelp(action.message);
                    break;
                    
                case 'optimize_performance':
                    this.optimizePerformance(action.optimizations);
                    break;
            }
        }
    }
    
    /**
     * مدیریت نتایج تحلیل سرور
     */
    handleServerAnalysis(analysis) {
        if (analysis.should_show_help) {
            this.showContextualHelp('در صورت نیاز به کمک، از دکمه راهنما استفاده کنید.');
        }
        
        if (analysis.status === 'slow') {
            this.showIntelligentLoading({
                message: 'در حال بهینه‌سازی عملکرد...',
                duration: 3000
            });
        }
        
        // اعمال توصیه‌های سرور
        if (analysis.recommendations) {
            analysis.recommendations.forEach(rec => {
                this.applyRecommendation(rec);
            });
        }
    }
    
    /**
     * اعمال توصیه
     */
    applyRecommendation(recommendation) {
        switch (recommendation) {
            case 'نمایش loading indicator':
                this.ensureLoadingIndicators();
                break;
                
            case 'نمایش پیغام خطای دوستانه':
                this.improveErrorMessages();
                break;
        }
    }
    
    /**
     * تشخیص frustration کاربر
     */
    detectFrustration(type, data = {}) {
        this.userBehavior.frustrationIndicators++;
        
        this.logEvent({
            event_type: 'user_frustration',
            timestamp: Date.now(),
            frustration_type: type,
            frustration_data: data,
            total_frustration_indicators: this.userBehavior.frustrationIndicators
        });
        
        // ارائه کمک خودکار
        if (this.userBehavior.frustrationIndicators >= 3) {
            this.offerHelp();
        }
    }
    
    /**
     * ارائه کمک خودکار
     */
    offerHelp() {
        // نمایش tooltip کمک‌رسان
        const helpTooltip = document.createElement('div');
        helpTooltip.className = 'xi2-help-tooltip';
        helpTooltip.innerHTML = `
            <div class="help-content">
                <p>به نظر می‌رسد مشکلی داشته باشید. می‌تونم کمکتون کنم؟</p>
                <button onclick="this.parentElement.parentElement.remove()">بستن</button>
            </div>
        `;
        
        helpTooltip.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            max-width: 300px;
            animation: slideInUp 0.3s ease-out;
        `;
        
        document.body.appendChild(helpTooltip);
        
        // حذف خودکار بعد از 10 ثانیه
        setTimeout(() => {
            if (helpTooltip.parentElement) {
                helpTooltip.remove();
            }
        }, 10000);
    }
    
    /**
     * نمایش loading هوشمند
     */
    showIntelligentLoading(config) {
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'xi2-intelligent-loading';
        loadingDiv.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p>${config.message || 'در حال پردازش...'}</p>
            </div>
        `;
        
        loadingDiv.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;
        
        document.body.appendChild(loadingDiv);
        
        // حذف خودکار
        setTimeout(() => {
            if (loadingDiv.parentElement) {
                loadingDiv.remove();
            }
        }, config.duration || 5000);
    }
    
    // Helper Methods
    
    generateSessionId() {
        return 'xi2_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }
    
    getUserId() {
        // دریافت از localStorage یا session
        return localStorage.getItem('xi2_user_id') || null;
    }
    
    getElementInfo(element) {
        return {
            tag: element.tagName.toLowerCase(),
            id: element.id,
            className: element.className,
            text: element.textContent ? element.textContent.substr(0, 100) : '',
            type: element.type,
            name: element.name,
            value: element.type === 'password' ? '[HIDDEN]' : element.value
        };
    }
    
    getPageContext() {
        return {
            url: window.location.href,
            title: document.title,
            referrer: document.referrer,
            pathname: window.location.pathname,
            search: window.location.search,
            hash: window.location.hash
        };
    }
    
    getUserContext() {
        return {
            user_id: this.userId,
            session_duration: Date.now() - this.performanceMetrics.pageLoadStart,
            interactions: this.performanceMetrics.interactions,
            errors: this.performanceMetrics.errors,
            frustration_level: this.userBehavior.frustrationIndicators
        };
    }
    
    getConnectionType() {
        if ('connection' in navigator) {
            return navigator.connection.effectiveType || navigator.connection.type;
        }
        return 'unknown';
    }
    
    getBatteryLevel() {
        if ('getBattery' in navigator) {
            navigator.getBattery().then(battery => {
                return Math.round(battery.level * 100) + '%';
            });
        }
        return null;
    }
    
    isCriticalEvent(eventData) {
        const criticalTypes = ['error', 'promise_rejection', 'user_frustration'];
        return criticalTypes.includes(eventData.event_type);
    }
    
    updateMetrics(eventData) {
        this.performanceMetrics.interactions++;
        
        if (eventData.event_type === 'error') {
            this.performanceMetrics.errors++;
        }
        
        if (eventData.event_type === 'api_call') {
            this.performanceMetrics.apiCalls++;
        }
    }
    
    log(message, data = null) {
        if (this.debugMode) {
            console.log(`[Xi2SmartLogger] ${message}`, data);
        }
    }
    
    // API Methods برای استفاده خارجی
    
    /**
     * فعال/غیرفعال کردن debug mode
     */
    setDebugMode(enabled) {
        this.debugMode = enabled;
        this.log(`Debug mode ${enabled ? 'enabled' : 'disabled'}`);
    }
    
    /**
     * تنظیم user ID
     */
    setUserId(userId) {
        this.userId = userId;
        localStorage.setItem('xi2_user_id', userId);
    }
    
    /**
     * ثبت event سفارشی
     */
    track(eventName, properties = {}) {
        this.logEvent({
            event_type: 'custom',
            custom_event_name: eventName,
            properties: properties,
            timestamp: Date.now()
        });
    }
    
    /**
     * ثبت خطای سفارشی
     */
    trackError(error, context = {}) {
        this.logEvent({
            event_type: 'error',
            timestamp: Date.now(),
            error_info: {
                message: error.message,
                stack: error.stack,
                name: error.name
            },
            context: context
        });
    }
    
    /**
     * دریافت تحلیل فعلی
     */
    async getCurrentAnalysis() {
        try {
            const response = await fetch(`${this.apiBaseUrl}get-analysis.php?session_id=${this.sessionId}&type=real_time`);
            return await response.json();
        } catch (error) {
            console.error('Failed to get analysis:', error);
            return null;
        }
    }
}

// Export برای استفاده در modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Xi2SmartLogger;
}

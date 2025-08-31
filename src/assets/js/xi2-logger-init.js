/**
 * Xi2 Smart Logger Auto-Initializer
 * راه‌اندازی خودکار سیستم لاگ‌گیری هوشمند
 */

(function() {
    'use strict';
    
    // تنظیمات پیشفرض
    const DEFAULT_CONFIG = {
        enabled: true,
        debugMode: false,
        autoStart: true,
        bufferSize: 50,
        flushInterval: 5000,
        enablePerformanceMonitoring: true,
        enableErrorHandling: true,
        enableRealtimeAnalysis: true,
        apiBaseUrl: '/xi2.ir/src/api/logging/'
    };
    
    /**
     * مدیریت تنظیمات
     */
    class Xi2LoggerConfig {
        constructor() {
            this.config = { ...DEFAULT_CONFIG };
            this.loadConfig();
        }
        
        /**
         * بارگذاری تنظیمات از localStorage
         */
        loadConfig() {
            try {
                const savedConfig = localStorage.getItem('xi2_logger_config');
                if (savedConfig) {
                    this.config = { ...this.config, ...JSON.parse(savedConfig) };
                }
            } catch (error) {
                console.warn('Failed to load Xi2Logger config:', error);
            }
        }
        
        /**
         * ذخیره تنظیمات
         */
        saveConfig() {
            try {
                localStorage.setItem('xi2_logger_config', JSON.stringify(this.config));
            } catch (error) {
                console.warn('Failed to save Xi2Logger config:', error);
            }
        }
        
        /**
         * دریافت تنظیم
         */
        get(key) {
            return this.config[key];
        }
        
        /**
         * تنظیم مقدار
         */
        set(key, value) {
            this.config[key] = value;
            this.saveConfig();
        }
        
        /**
         * دریافت همه تنظیمات
         */
        getAll() {
            return { ...this.config };
        }
    }
    
    /**
     * راه‌اندازی خودکار
     */
    class Xi2LoggerBootstrap {
        constructor() {
            this.config = new Xi2LoggerConfig();
            this.logger = null;
            this.isInitialized = false;
            
            // شروع خودکار در صورت فعال بودن
            if (this.config.get('autoStart')) {
                this.init();
            }
        }
        
        /**
         * راه‌اندازی logger
         */
        async init() {
            if (this.isInitialized) {
                console.warn('Xi2SmartLogger is already initialized');
                return this.logger;
            }
            
            try {
                // صبر برای بارگذاری کامل DOM
                await this.waitForDOM();
                
                // بررسی دسترسی‌های لازم
                this.checkRequirements();
                
                // ایجاد instance logger
                this.logger = new Xi2SmartLogger();
                
                // تنظیم config
                this.applyConfig();
                
                // ثبت global handlers
                this.setupGlobalHandlers();
                
                // اعلان آماده بودن
                this.notifyReady();
                
                this.isInitialized = true;
                
                console.log('🚀 Xi2SmartLogger initialized successfully!');
                return this.logger;
                
            } catch (error) {
                console.error('Failed to initialize Xi2SmartLogger:', error);
                throw error;
            }
        }
        
        /**
         * انتظار برای آماده شدن DOM
         */
        waitForDOM() {
            return new Promise((resolve) => {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', resolve);
                } else {
                    resolve();
                }
            });
        }
        
        /**
         * بررسی دسترسی‌های لازم
         */
        checkRequirements() {
            const requirements = [
                { feature: 'fetch', available: typeof fetch !== 'undefined' },
                { feature: 'localStorage', available: typeof Storage !== 'undefined' },
                { feature: 'JSON', available: typeof JSON !== 'undefined' },
                { feature: 'performance', available: typeof performance !== 'undefined' }
            ];
            
            const missing = requirements.filter(req => !req.available);
            
            if (missing.length > 0) {
                const missingFeatures = missing.map(req => req.feature).join(', ');
                console.warn(`Xi2SmartLogger: Missing features: ${missingFeatures}`);
            }
        }
        
        /**
         * اعمال تنظیمات
         */
        applyConfig() {
            if (!this.logger) return;
            
            // تنظیم debug mode
            this.logger.setDebugMode(this.config.get('debugMode'));
            
            // تنظیم user ID اگر موجود باشد
            const userId = localStorage.getItem('xi2_user_id');
            if (userId) {
                this.logger.setUserId(userId);
            }
            
            // سایر تنظیمات
            this.logger.bufferSize = this.config.get('bufferSize');
            this.logger.flushInterval = this.config.get('flushInterval');
            this.logger.apiBaseUrl = this.config.get('apiBaseUrl');
        }
        
        /**
         * تنظیم global handlers
         */
        setupGlobalHandlers() {
            // Global logger برای استفاده آسان
            window.xi2Logger = this.logger;
            
            // Helper functions
            window.xi2Track = (eventName, properties) => {
                this.logger.track(eventName, properties);
            };
            
            window.xi2TrackError = (error, context) => {
                this.logger.trackError(error, context);
            };
            
            // Config manager
            window.xi2LoggerConfig = this.config;
            
            // Page visibility handler
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.logger.flushBuffer(true);
                }
            });
            
            // Before unload handler
            window.addEventListener('beforeunload', () => {
                this.logger.flushBuffer(true);
            });
        }
        
        /**
         * اعلان آماده بودن
         */
        notifyReady() {
            // Custom event برای اطلاع سایر کامپوننت‌ها
            const readyEvent = new CustomEvent('xi2LoggerReady', {
                detail: {
                    logger: this.logger,
                    config: this.config.getAll()
                }
            });
            
            document.dispatchEvent(readyEvent);
            
            // Promise برای async operations
            if (typeof window.xi2LoggerPromise !== 'undefined') {
                window.xi2LoggerResolve(this.logger);
            }
        }
        
        /**
         * دریافت logger instance
         */
        getLogger() {
            return this.logger;
        }
        
        /**
         * restart logger
         */
        restart() {
            if (this.logger) {
                this.logger.flushBuffer(true);
            }
            
            this.isInitialized = false;
            this.logger = null;
            
            return this.init();
        }
    }
    
    // تنظیم Promise برای async access
    let xi2LoggerResolve;
    window.xi2LoggerPromise = new Promise((resolve) => {
        window.xi2LoggerResolve = resolve;
    });
    
    // راه‌اندازی bootstrap
    const bootstrap = new Xi2LoggerBootstrap();
    
    // Export bootstrap برای دسترسی خارجی
    window.xi2LoggerBootstrap = bootstrap;
    
    // Auto-initialization message
    console.log('📊 Xi2SmartLogger Bootstrap loaded - Initializing intelligent logging system...');
    
})();

/**
 * نمونه استفاده:
 * 
 * // استفاده ساده
 * xi2Track('button_clicked', { button_id: 'login' });
 * 
 * // ثبت خطا
 * xi2TrackError(new Error('Something went wrong'), { page: 'login' });
 * 
 * // تغییر تنظیمات
 * xi2LoggerConfig.set('debugMode', true);
 * 
 * // دسترسی به logger کامل
 * window.xi2Logger.logEvent({ event_type: 'custom', data: {...} });
 * 
 * // انتظار برای آماده شدن
 * xi2LoggerPromise.then(logger => {
 *     logger.setDebugMode(true);
 * });
 * 
 * // گوش دادن به ready event
 * document.addEventListener('xi2LoggerReady', (event) => {
 *     console.log('Logger ready!', event.detail.logger);
 * });
 */

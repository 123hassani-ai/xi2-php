/**
 * زیتو (Xi2) - مدیریت مسیرها v2.0
 * برای حل مشکلات مسیردهی در کل پروژه
 * نسخه بهبود یافته با مدیریت کامل خطاها
 */

// مدیریت مسیرها در کل پروژه
(function() {
    'use strict';
    
    // جلوگیری از اجرای مکرر
    if (window.Xi2PathResolver) {
        return;
    }
    window.Xi2PathResolver = true;
    
    // تنظیم مسیر اصلی برحسب محیط اجرا
    var baseUrl;
    
    // تشخیص محیط اجرا
    if (location.hostname === 'localhost' || location.hostname === '127.0.0.1') {
        // محیط محلی
        baseUrl = '/xi2.ir';
    } else {
        // محیط آنلاین - اگر نیاز بود اینجا تغییر کنید
        baseUrl = '';
    }
    
    // ذخیره در متغیر گلوبال
    window.XI_BASE_URL = baseUrl;
    
    // تولید تگ‌های مورد نیاز
    var cssFiles = [
        '/src/assets/css/fonts.css',
        '/src/assets/css/main.css',
        '/src/assets/css/components.css'
    ];
    
    var jsFiles = [
        '/src/assets/js/main.js',
        '/src/assets/js/auth.js',
        '/src/assets/js/upload.js'
    ];
    
    // تابع کمکی برای تشخیص فایل‌های موجود
    function isFileIncluded(url) {
        try {
            var links = document.getElementsByTagName('link');
            var scripts = document.getElementsByTagName('script');
            
            // بررسی تمام لینک‌ها
            for (var i = 0; i < links.length; i++) {
                if (links[i].href && links[i].href.indexOf(url) !== -1) {
                    return true;
                }
            }
            
            // بررسی تمام اسکریپت‌ها
            for (var j = 0; j < scripts.length; j++) {
                if (scripts[j].src && scripts[j].src.indexOf(url) !== -1) {
                    return true;
                }
            }
            
            return false;
        } catch (e) {
            console.warn('Xi2 Path Resolver: خطا در بررسی فایل‌های موجود:', e);
            return false;
        }
    }
    
    // تابع امن برای اضافه کردن المنت
    function safeAppendChild(parent, child) {
        try {
            if (parent && child && typeof parent.appendChild === 'function') {
                parent.appendChild(child);
                return true;
            }
        } catch (e) {
            console.warn('Xi2 Path Resolver: خطا در appendChild:', e);
        }
        return false;
    }
    
    // تابع برای اضافه کردن فایل‌های CSS
    function addCSSFiles() {
        cssFiles.forEach(function(file) {
            try {
                if (!isFileIncluded(file)) {
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = baseUrl + file;
                    link.onerror = function() {
                        console.warn('Xi2 Path Resolver: خطا در بارگذاری CSS:', this.href);
                    };
                    
                    if (document.head) {
                        safeAppendChild(document.head, link);
                    }
                }
            } catch (e) {
                console.warn('Xi2 Path Resolver: خطا در اضافه کردن CSS:', file, e);
            }
        });
    }
    
    // تابع برای اضافه کردن فایل‌های JS
    function addJSFiles() {
        jsFiles.forEach(function(file) {
            try {
                if (!isFileIncluded(file)) {
                    var script = document.createElement('script');
                    script.src = baseUrl + file;
                    script.defer = true;
                    script.onerror = function() {
                        console.warn('Xi2 Path Resolver: خطا در بارگذاری JS:', this.src);
                    };
                    
                    // ترجیح با body، در صورت عدم وجود با head
                    var target = document.body || document.head;
                    if (target) {
                        safeAppendChild(target, script);
                    }
                }
            } catch (e) {
                console.warn('Xi2 Path Resolver: خطا در اضافه کردن JS:', file, e);
            }
        });
    }
    
    // تابع برای اضافه کردن استایل‌های فونت
    function addFontStyles() {
        try {
            var pathResolver = document.createElement('style');
            pathResolver.id = 'xi2-path-resolver-fonts';
            pathResolver.textContent = `
                @font-face {
                    font-family: 'Vazirmatn';
                    src: url('${baseUrl}/src/assets/fonts/Vazirmatn-Regular.woff2') format('woff2');
                    font-weight: 400;
                    font-style: normal;
                    font-display: swap;
                }
                
                @font-face {
                    font-family: 'Vazirmatn';
                    src: url('${baseUrl}/src/assets/fonts/Vazirmatn-Light.woff2') format('woff2');
                    font-weight: 300;
                    font-style: normal;
                    font-display: swap;
                }
                
                @font-face {
                    font-family: 'Vazirmatn';
                    src: url('${baseUrl}/src/assets/fonts/Vazirmatn-Bold.woff2') format('woff2');
                    font-weight: 700;
                    font-style: normal;
                    font-display: swap;
                }
                
                @font-face {
                    font-family: 'Vazirmatn Variable';
                    src: url('${baseUrl}/src/assets/fonts/Vazirmatn-Variable.woff2') format('woff2-variations');
                    font-weight: 100 900;
                    font-style: normal;
                    font-display: swap;
                }
            `;
            
            if (document.head) {
                safeAppendChild(document.head, pathResolver);
            }
        } catch (e) {
            console.warn('Xi2 Path Resolver: خطا در اضافه کردن فونت‌ها:', e);
        }
    }
    
    // تابع برای اضافه کردن متا تگ
    function addMetaTag() {
        try {
            // بررسی اینکه قبلاً اضافه نشده باشد
            if (!document.querySelector('meta[name="xi-path-resolver"]')) {
                var meta = document.createElement('meta');
                meta.name = 'xi-path-resolver';
                meta.content = 'v2.0';
                
                if (document.head) {
                    safeAppendChild(document.head, meta);
                }
            }
        } catch (e) {
            console.warn('Xi2 Path Resolver: خطا در اضافه کردن متا تگ:', e);
        }
    }
    
    // تابع اصلی برای اضافه کردن همه فایل‌ها
    function initializePathResolver() {
        try {
            addCSSFiles();
            addJSFiles();
            addFontStyles();
            addMetaTag();
            
            console.log('✅ مسیردهی خودکار زیتو v2.0 فعال شد - مسیر پایه:', baseUrl);
        } catch (e) {
            console.error('Xi2 Path Resolver: خطا در مقداردهی اولیه:', e);
        }
    }
    
    // بررسی وضعیت DOM و اجرای مناسب
    function checkDOMAndInitialize() {
        if (document.readyState === 'loading') {
            // DOM هنوز در حال بارگذاری است
            document.addEventListener('DOMContentLoaded', initializePathResolver);
        } else {
            // DOM آماده است یا کامل بارگذاری شده
            initializePathResolver();
        }
    }
    
    // شروع فرآیند
    checkDOMAndInitialize();
    
})();

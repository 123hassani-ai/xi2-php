/**
 * زیتو (Xi2) - مدیریت مسیرها
 * برای حل مشکلات مسیردهی در کل پروژه
 */

// مدیریت مسیرها در کل پروژه
(function() {
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
        var links = document.getElementsByTagName('link');
        var scripts = document.getElementsByTagName('script');
        
        // بررسی تمام لینک‌ها
        for (var i = 0; i < links.length; i++) {
            if (links[i].href.indexOf(url) !== -1) {
                return true;
            }
        }
        
        // بررسی تمام اسکریپت‌ها
        for (var j = 0; j < scripts.length; j++) {
            if (scripts[j].src.indexOf(url) !== -1) {
                return true;
            }
        }
        
        return false;
    }
    
    // اضافه کردن فایل‌های CSS
    cssFiles.forEach(function(file) {
        if (!isFileIncluded(file)) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = baseUrl + file;
            document.head.appendChild(link);
        }
    });
    
    // اضافه کردن فایل‌های JS
    jsFiles.forEach(function(file) {
        if (!isFileIncluded(file)) {
            var script = document.createElement('script');
            script.src = baseUrl + file;
            script.defer = true;
            document.body.appendChild(script);
        }
    });
    
    // تزریق استایل برای مدیریت مسیرها
    var pathResolver = document.createElement('style');
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
    document.head.appendChild(pathResolver);
    
    // تزریق متا تگ
    var meta = document.createElement('meta');
    meta.name = 'xi-path-resolver';
    meta.content = 'v1.0';
    document.head.appendChild(meta);
    
    console.log('✅ مسیردهی خودکار زیتو فعال شد - مسیر پایه:', baseUrl);
})();

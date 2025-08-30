// تست console ساده
console.log('🧪 شروع تست Path Resolver Debug');
console.log('Document Ready State:', document.readyState);
console.log('Document Head:', document.head);
console.log('Document Body:', document.body);

// تست بعد از 1 ثانیه
setTimeout(() => {
    console.log('🔍 تست پس از 1 ثانیه:');
    console.log('XI_BASE_URL:', window.XI_BASE_URL);
    console.log('Xi2PathResolver:', window.Xi2PathResolver);
    
    // شمارش فایل‌ها
    const cssFiles = document.querySelectorAll('link[rel="stylesheet"]');
    const jsFiles = document.querySelectorAll('script[src]');
    
    console.log('CSS Files:', cssFiles.length);
    console.log('JS Files:', jsFiles.length);
    
    // لیست CSS Files
    cssFiles.forEach((css, index) => {
        console.log(`CSS ${index + 1}:`, css.href);
    });
    
    // لیست JS Files
    jsFiles.forEach((js, index) => {
        console.log(`JS ${index + 1}:`, js.src);
    });
    
}, 1000);

// تست خطاها
window.addEventListener('error', (e) => {
    console.error('❌ JavaScript Error:', e.message, e.filename, e.lineno);
});

console.log('✅ Debug script loaded');

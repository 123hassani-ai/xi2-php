<?php
/**
 * زیتو (Xi2) - Router اصلی
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get requested URI
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$requestUri = strtok($requestUri, '?');

// API routing
if (strpos($requestUri, '/api/') === 0) {
    // Remove /api/ prefix  
    $apiPath = substr($requestUri, 5);
    $apiFile = __DIR__ . '/../src/api/' . $apiPath;
    
    if (file_exists($apiFile) && pathinfo($apiFile, PATHINFO_EXTENSION) === 'php') {
        // Execute API file
        include $apiFile;
        exit();
    } else {
        // API not found
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit();
    }
}

// Static files (CSS, JS, images)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|webp|woff|woff2|ttf|eot)$/', $requestUri)) {
    $filePath = __DIR__ . $requestUri;
    if (file_exists($filePath)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject'
        ];
        
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        header("Content-Type: $mimeType");
        header("Cache-Control: public, max-age=3600");
        readfile($filePath);
        exit();
    }
}

// All other requests serve the main HTML file
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="زیتو - پلتفرم هوشمند اشتراک‌گذاری و مدیریت تصاویر">
    <meta name="keywords" content="آپلود تصویر، اشتراک گذاری، تصویر، زیتو، xi2">
    <meta name="author" content="Xi2 Team">
    <meta name="theme-color" content="#6366F1">
    
    <!-- مدیریت خودکار مسیرها -->
    <script src="/xi2.ir/src/assets/js/path-resolver.js"></script>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="/xi2.ir/src/assets/images/icon-192.png">
    
    <style>
        /* اصلاحات سریع UI */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #6366F1 0%, #EC4899 100%);
            color: #1F2937;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* اعمال فونت فارسی به همه عناصر */
        *, *::before, *::after {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        
        /* فونت خاص برای input ها و textarea ها */
        input, textarea, button, select {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif !important;
            font-feature-settings: 'kern' 1;
            -webkit-font-feature-settings: 'kern' 1;
            -moz-font-feature-settings: 'kern' 1;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo h1 {
            color: #6366F1;
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .logo span {
            color: #EC4899;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif !important;
        }
        
        .btn-primary {
            background: #6366F1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4F46E5;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
        }
        
        .btn-outline {
            border: 2px solid #6366F1;
            color: #6366F1;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #6366F1;
            color: white;
        }
        
        .hero {
            padding: 4rem 0;
            text-align: center;
            color: white;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 3rem;
            opacity: 0.9;
        }
        
        .upload-zone {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            padding: 3rem;
            margin: 2rem 0;
            border: 3px dashed #D1D5DB;
            transition: all 0.3s ease;
            color: #1F2937;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .upload-zone:hover {
            border-color: #6366F1;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .upload-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .upload-content h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #1F2937;
        }
        
        .upload-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .btn-secondary {
            background: #EC4899;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #DB2777;
        }
        
        .features {
            background: rgba(255, 255, 255, 0.95);
            padding: 4rem 0;
            margin-top: 2rem;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: #1F2937;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1F2937;
        }
        
        .feature-card p {
            color: #6B7280;
        }
        
        .footer {
            background: rgba(31, 41, 55, 0.95);
            color: white;
            padding: 2rem 0 1rem;
            margin-top: 2rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 400px;
            width: 90%;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-family: 'Vazirmatn', sans-serif !important;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: 'Vazirmatn', sans-serif !important;
            direction: rtl;
            text-align: right;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-group input::placeholder {
            color: #9CA3AF;
            font-family: 'Vazirmatn', sans-serif !important;
        }
        
        .btn-full {
            width: 100%;
        }
        
        .auth-switch {
            text-align: center;
            margin-top: 1rem;
        }
        
        .auth-switch a {
            color: #6366F1;
            text-decoration: none;
        }
        
        .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #9CA3AF;
        }
        
        /* پاسخگو */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .upload-zone {
                margin: 1rem;
                padding: 2rem;
            }
            
            .upload-actions {
                flex-direction: column;
            }
            
            .nav {
                display: none;
            }
            
            .result-actions {
                flex-direction: column;
            }
        }
        
        /* اضافات برای UI بهتر */
        .upload-progress,
        .upload-result {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 600px;
            text-align: center;
            color: #1F2937;
        }
        
        .progress-bar {
            background: #E5E7EB;
            border-radius: 1rem;
            height: 8px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #6366F1, #EC4899);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .result-success {
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .result-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            align-items: center;
        }
        
        .result-actions input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            background: #F9FAFB;
            font-family: 'Vazirmatn', sans-serif !important;
            direction: ltr;
            text-align: left;
        }
    </style>
    
    <title>زیتو | تصاویرتان را رها کنید</title>
</head>
<body class="landing">
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>🎯 زیتو</h1>
                    <span>Xi2</span>
                </div>
                <nav class="nav">
                    <a href="#features" class="nav-link">ویژگی‌ها</a>
                    <a href="#login" class="nav-link btn btn-outline">ورود</a>
                    <a href="#register" class="nav-link btn btn-primary">شروع رایگان</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="hero">
        <div class="container">
            <div class="hero-content">
                <h2 class="hero-title">تصاویرتان را رها کنید</h2>
                <p class="hero-subtitle">
                    ساده‌ترین و سریع‌ترین راه برای آپلود، مدیریت و اشتراک‌گذاری تصاویر
                </p>
                
                <!-- Upload Zone -->
                <div class="upload-zone" id="uploadZone">
                    <div class="upload-content">
                        <div class="upload-icon">📸</div>
                        <h3>منطقه آپلود</h3>
                        <p>تصویر را اینجا رها کنید یا کلیک کنید</p>
                        <input type="file" id="fileInput" accept="image/*" multiple hidden>
                        <div class="upload-actions">
                            <button class="btn btn-primary" id="selectFiles">انتخاب فایل</button>
                            <button class="btn btn-secondary" id="takePhoto">📷 عکس بگیرید</button>
                        </div>
                    </div>
                </div>

                <!-- Progress -->
                <div class="upload-progress" id="uploadProgress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <p id="progressText">در حال آپلود...</p>
                </div>

                <!-- Result -->
                <div class="upload-result" id="uploadResult" style="display: none;">
                    <div class="result-success">
                        <div class="success-icon">✅</div>
                        <h3>عالی! تصویرتان آماده است</h3>
                        <div class="result-actions">
                            <input type="text" id="shareLink" readonly>
                            <button class="btn btn-primary" id="copyLink">کپی لینک</button>
                            <button class="btn btn-secondary" id="shareWhatsApp">واتساپ</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">چرا زیتو؟</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>سرعت بالا</h3>
                    <p>آپلود فوری با CDN بومی</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>امنیت کامل</h3>
                    <p>رمزنگاری و محافظت از حریم خصوصی</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>موبایل فریندلی</h3>
                    <p>PWA و قابلیت آفلاین</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>ویرایش آسان</h3>
                    <p>برش، فشرده‌سازی و بهینه‌سازی</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Auth Modal -->
    <div class="modal" id="authModal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            
            <!-- Login Form -->
            <div class="auth-form" id="loginForm">
                <h2>ورود به زیتو</h2>
                <form>
                    <div class="form-group">
                        <label>شماره موبایل</label>
                        <input type="tel" id="loginMobile" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required>
                    </div>
                    <div class="form-group">
                        <label>رمز عبور</label>
                        <input type="password" id="loginPassword" placeholder="رمز عبور خود را وارد کنید" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">ورود</button>
                </form>
                <p class="auth-switch">
                    حساب ندارید؟ <a href="#" id="showRegister">ثبت‌نام کنید</a>
                </p>
            </div>

            <!-- Register Form -->
            <div class="auth-form" id="registerForm" style="display: none;">
                <h2>ثبت‌نام در زیتو</h2>
                <form>
                    <div class="form-group">
                        <label>نام و نام خانوادگی</label>
                        <input type="text" id="registerName" placeholder="نام کامل خود را وارد کنید" required>
                    </div>
                    <div class="form-group">
                        <label>شماره موبایل</label>
                        <input type="tel" id="registerMobile" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required>
                    </div>
                    <div class="form-group">
                        <label>رمز عبور</label>
                        <input type="password" id="registerPassword" placeholder="حداقل ۶ کاراکتر" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">ثبت‌نام</button>
                </form>
                <p class="auth-switch">
                    حساب دارید؟ <a href="#" id="showLogin">وارد شوید</a>
                </p>
            </div>

            <!-- OTP Verification -->
            <div class="auth-form" id="otpForm" style="display: none;">
                <h2>تایید شماره موبایل</h2>
                <p>کد تایید به شماره <span id="otpMobile"></span> ارسال شد</p>
                <form>
                    <div class="form-group">
                        <label>کد تایید</label>
                        <input type="text" id="otpCode" placeholder="کد ۶ رقمی را وارد کنید" maxlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">تایید</button>
                </form>
                <p class="auth-switch">
                    <a href="#" id="resendOTP">ارسال مجدد کد</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>🎯 زیتو</h3>
                    <p>پلتفرم هوشمند اشتراک‌گذاری تصاویر</p>
                </div>
                <div class="footer-section">
                    <h4>لینک‌های مفید</h4>
                    <ul>
                        <li><a href="#features">ویژگی‌ها</a></li>
                        <li><a href="#pricing">قیمت‌ها</a></li>
                        <li><a href="#support">پشتیبانی</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>قوانین</h4>
                    <ul>
                        <li><a href="#privacy">حریم خصوصی</a></li>
                        <li><a href="#terms">شرایط استفاده</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 زیتو (Xi2). تمامی حقوق محفوظ است.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('service-worker.js')
                    .then((registration) => {
                        console.log('SW registered: ', registration);
                    })
                    .catch((registrationError) => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for auth modals
            const loginLink = document.querySelector('a[href="#login"]');
            const registerLink = document.querySelector('a[href="#register"]');
            const modal = document.getElementById('authModal');
            const closeModal = document.getElementById('closeModal');
            const showRegister = document.getElementById('showRegister');
            const showLogin = document.getElementById('showLogin');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            // Show login modal
            if (loginLink) {
                loginLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    modal.style.display = 'block';
                    loginForm.style.display = 'block';
                    registerForm.style.display = 'none';
                });
            }
            
            // Show register modal  
            if (registerLink) {
                registerLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    modal.style.display = 'block';
                    registerForm.style.display = 'block';
                    loginForm.style.display = 'none';
                });
            }
            
            // Close modal
            if (closeModal) {
                closeModal.addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            }
            
            // Switch between forms
            if (showRegister) {
                showRegister.addEventListener('click', (e) => {
                    e.preventDefault();
                    loginForm.style.display = 'none';
                    registerForm.style.display = 'block';
                });
            }
            
            if (showLogin) {
                showLogin.addEventListener('click', (e) => {
                    e.preventDefault();
                    registerForm.style.display = 'none';
                    loginForm.style.display = 'block';
                });
            }
            
            // Upload zone functionality
            const uploadZone = document.getElementById('uploadZone');
            const fileInput = document.getElementById('fileInput');
            const selectFiles = document.getElementById('selectFiles');
            
            if (selectFiles) {
                selectFiles.addEventListener('click', () => {
                    fileInput.click();
                });
            }
            
            if (uploadZone) {
                uploadZone.addEventListener('click', () => {
                    fileInput.click();
                });
                
                // Drag and drop
                uploadZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadZone.style.borderColor = '#6366F1';
                    uploadZone.style.backgroundColor = 'rgba(99, 102, 241, 0.05)';
                });
                
                uploadZone.addEventListener('dragleave', () => {
                    uploadZone.style.borderColor = '#D1D5DB';
                    uploadZone.style.backgroundColor = '';
                });
                
                uploadZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadZone.style.borderColor = '#D1D5DB';
                    uploadZone.style.backgroundColor = '';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFileUpload(files[0]);
                    }
                });
            }
            
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        handleFileUpload(e.target.files[0]);
                    }
                });
            }
            
            // Form submissions
            const registerFormEl = document.querySelector('#registerForm form');
            const loginFormEl = document.querySelector('#loginForm form');
            
            if (registerFormEl) {
                registerFormEl.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const data = {
                        name: document.getElementById('registerName').value,
                        mobile: document.getElementById('registerMobile').value,
                        password: document.getElementById('registerPassword').value
                    };
                    
                    try {
                        const response = await fetch('/api/auth/register.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data)
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('✅ ثبت‌نام موفقیت‌آمیز بود!');
                            modal.style.display = 'none';
                        } else {
                            alert('❌ خطا: ' + result.message);
                        }
                    } catch (error) {
                        alert('❌ خطا در اتصال به سرور');
                    }
                });
            }
            
            if (loginFormEl) {
                loginFormEl.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const data = {
                        mobile: document.getElementById('loginMobile').value,
                        password: document.getElementById('loginPassword').value
                    };
                    
                    try {
                        const response = await fetch('/api/auth/login.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data)
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('✅ ورود موفقیت‌آمیز بود!');
                            localStorage.setItem('xi2_token', result.data.token);
                            modal.style.display = 'none';
                        } else {
                            alert('❌ خطا: ' + result.message);
                        }
                    } catch (error) {
                        alert('❌ خطا در اتصال به سرور');
                    }
                });
            }
            
            function handleFileUpload(file) {
                // Show progress
                const progressDiv = document.getElementById('uploadProgress');
                const resultDiv = document.getElementById('uploadResult');
                
                if (progressDiv) {
                    progressDiv.style.display = 'block';
                    resultDiv.style.display = 'none';
                    
                    // Simulate upload progress
                    let progress = 0;
                    const progressBar = document.getElementById('progressFill');
                    const progressText = document.getElementById('progressText');
                    
                    const interval = setInterval(() => {
                        progress += Math.random() * 30;
                        if (progress > 100) progress = 100;
                        
                        progressBar.style.width = progress + '%';
                        progressText.textContent = `در حال آپلود... ${Math.round(progress)}%`;
                        
                        if (progress === 100) {
                            clearInterval(interval);
                            setTimeout(() => {
                                progressDiv.style.display = 'none';
                                resultDiv.style.display = 'block';
                                
                                const shareLink = document.getElementById('shareLink');
                                if (shareLink) {
                                    shareLink.value = `https://xi2.app/view/${Date.now()}`;
                                }
                            }, 500);
                        }
                    }, 100);
                }
            }
            
            // Copy link functionality
            const copyLink = document.getElementById('copyLink');
            if (copyLink) {
                copyLink.addEventListener('click', () => {
                    const shareLink = document.getElementById('shareLink');
                    if (shareLink) {
                        shareLink.select();
                        document.execCommand('copy');
                        copyLink.textContent = '✅ کپی شد';
                        setTimeout(() => {
                            copyLink.textContent = 'کپی لینک';
                        }, 2000);
                    }
                });
            }
        });
    </script>
</body>
</html>

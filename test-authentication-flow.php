<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست Authentication Flow - زیتو</title>
    <style>
        body {
            font-family: 'Vazirmatn', Arial, sans-serif;
            direction: rtl;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
        }
        
        .test-section h3 {
            margin-top: 0;
            color: #1f2937;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #374151;
        }
        
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        input.valid {
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        
        input.invalid {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        
        .btn {
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #2563eb;
        }
        
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            white-space: pre-wrap;
        }
        
        .result.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .result.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .result.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        
        .conversion-demo {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .step {
            display: none;
        }
        
        .step.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 تست Authentication Flow با پشتیبانی اعداد فارسی</h1>
        
        <!-- Demo تبدیل اعداد فارسی -->
        <div class="test-section">
            <h3>🔢 دمو تبدیل اعداد فارسی به انگلیسی</h3>
            
            <div class="form-group">
                <label>متن ورودی (با اعداد فارسی یا عربی)</label>
                <input type="text" id="persianInput" placeholder="مثال: شماره ۰۹۱۲۳۴۵۶۷۸۹" />
            </div>
            
            <div class="conversion-demo">
                <strong>نتیجه تبدیل:</strong> <span id="convertedOutput">---</span>
            </div>
        </div>
        
        <!-- Authentication Steps -->
        <div class="test-section">
            <h3>🔐 فرآیند کامل احراز هویت</h3>
            
            <!-- Step 1: Register -->
            <div id="registerStep" class="step active">
                <h4>مرحله 1: ثبت‌نام</h4>
                
                <form id="registerForm">
                    <div class="form-group">
                        <label>نام کامل</label>
                        <input type="text" name="fullName" placeholder="نام و نام خانوادگی" required />
                    </div>
                    
                    <div class="form-group">
                        <label>شماره موبایل (می‌تواند فارسی باشد)</label>
                        <input type="tel" name="mobile" placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹" required />
                        <small>شماره خود را با اعداد فارسی یا انگلیسی وارد کنید</small>
                    </div>
                    
                    <div class="form-group">
                        <label>رمز عبور</label>
                        <input type="password" name="password" placeholder="حداقل 6 کاراکتر" required />
                    </div>
                    
                    <button type="submit" class="btn">ثبت‌نام</button>
                </form>
                
                <div id="registerResult" class="result" style="display:none;"></div>
            </div>
            
            <!-- Step 2: Verify OTP -->
            <div id="otpStep" class="step">
                <h4>مرحله 2: تایید کد OTP</h4>
                
                <p>کد تایید برای شماره <strong id="otpMobile">---</strong> ارسال شد</p>
                
                <form id="otpForm">
                    <div class="form-group">
                        <label>کد تایید 6 رقمی (می‌تواند فارسی باشد)</label>
                        <input type="text" name="otpCode" id="otpCode" placeholder="مثال: ۱۲۳۴۵۶" maxlength="6" required />
                        <small>کد را با اعداد فارسی یا انگلیسی وارد کنید</small>
                    </div>
                    
                    <button type="submit" class="btn">تایید کد</button>
                    <button type="button" class="btn" id="resendOtp" style="margin-right: 10px;">ارسال مجدد</button>
                </form>
                
                <div id="otpResult" class="result" style="display:none;"></div>
            </div>
            
            <!-- Step 3: Login -->
            <div id="loginStep" class="step">
                <h4>مرحله 3: ورود مجدد</h4>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label>شماره موبایل</label>
                        <input type="tel" name="mobile" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required />
                    </div>
                    
                    <div class="form-group">
                        <label>رمز عبور</label>
                        <input type="password" name="password" required />
                    </div>
                    
                    <button type="submit" class="btn">ورود</button>
                </form>
                
                <div id="loginResult" class="result" style="display:none;"></div>
            </div>
            
            <!-- Step 4: Success -->
            <div id="successStep" class="step">
                <h4>✅ موفقیت‌آمیز</h4>
                <div class="result success">
                    احراز هویت کامل شد! کاربر با موفقیت وارد شد
                </div>
                
                <div id="userInfo"></div>
                
                <button type="button" class="btn" onclick="resetTest()">تست مجدد</button>
            </div>
        </div>
        
        <!-- آمار تست -->
        <div class="test-section">
            <h3>📊 آمار تست</h3>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="statConversions">0</div>
                    <div class="stat-label">تبدیل اعداد</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" id="statValidations">0</div>
                    <div class="stat-label">اعتبارسنجی</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" id="statApiCalls">0</div>
                    <div class="stat-label">فراخوانی API</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number" id="statSuccessRate">0%</div>
                    <div class="stat-label">نرخ موفقیت</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Statistics
        let stats = {
            conversions: 0,
            validations: 0,
            apiCalls: 0,
            successfulCalls: 0
        };
        
        let currentMobile = '';
        let currentUserId = '';
        
        // تبدیل اعداد فارسی/عربی
        function convertPersianToEnglish(input) {
            if (!input) return input;
            
            const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            
            let result = input.toString();
            let hasConversion = false;
            
            // تبدیل اعداد فارسی
            for (let i = 0; i < 10; i++) {
                const oldResult = result;
                result = result.replace(new RegExp(persianNumbers[i], 'g'), englishNumbers[i]);
                result = result.replace(new RegExp(arabicNumbers[i], 'g'), englishNumbers[i]);
                if (oldResult !== result) hasConversion = true;
            }
            
            if (hasConversion) {
                stats.conversions++;
                updateStats();
            }
            
            return result;
        }
        
        // اعتبارسنجی شماره موبایل
        function validateMobile(mobile) {
            stats.validations++;
            updateStats();
            
            const cleanMobile = convertPersianToEnglish(mobile);
            return /^09\d{9}$/.test(cleanMobile);
        }
        
        // اعتبارسنجی OTP
        function validateOTP(otp) {
            stats.validations++;
            updateStats();
            
            const cleanOTP = convertPersianToEnglish(otp);
            return /^\d{6}$/.test(cleanOTP);
        }
        
        // API Call
        async function apiCall(endpoint, data) {
            stats.apiCalls++;
            updateStats();
            
            try {
                const response = await fetch(`/xi2.ir/src/api/auth/${endpoint}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    stats.successfulCalls++;
                }
                
                updateStats();
                return result;
                
            } catch (error) {
                updateStats();
                return {
                    success: false,
                    message: 'خطا در اتصال: ' + error.message
                };
            }
        }
        
        // نمایش نتیجه
        function showResult(elementId, result, type = null) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = `result ${type || (result.success ? 'success' : 'error')}`;
            element.textContent = JSON.stringify(result, null, 2);
        }
        
        // نمایش مرحله
        function showStep(stepId) {
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
            });
            document.getElementById(stepId).classList.add('active');
        }
        
        // بروزرسانی آمار
        function updateStats() {
            document.getElementById('statConversions').textContent = stats.conversions;
            document.getElementById('statValidations').textContent = stats.validations;
            document.getElementById('statApiCalls').textContent = stats.apiCalls;
            
            const successRate = stats.apiCalls > 0 ? 
                Math.round((stats.successfulCalls / stats.apiCalls) * 100) : 0;
            document.getElementById('statSuccessRate').textContent = successRate + '%';
        }
        
        // ریست تست
        function resetTest() {
            stats = { conversions: 0, validations: 0, apiCalls: 0, successfulCalls: 0 };
            updateStats();
            
            document.querySelectorAll('form').forEach(form => form.reset());
            document.querySelectorAll('.result').forEach(result => result.style.display = 'none');
            
            showStep('registerStep');
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Demo تبدیل
            const persianInput = document.getElementById('persianInput');
            const convertedOutput = document.getElementById('convertedOutput');
            
            persianInput.addEventListener('input', function() {
                const converted = convertPersianToEnglish(this.value);
                convertedOutput.textContent = converted;
                
                if (this.value !== converted) {
                    convertedOutput.style.color = '#059669';
                    convertedOutput.style.fontWeight = 'bold';
                } else {
                    convertedOutput.style.color = '#6b7280';
                    convertedOutput.style.fontWeight = 'normal';
                }
            });
            
            // Real-time validation برای موبایل
            document.querySelectorAll('input[name="mobile"]').forEach(input => {
                input.addEventListener('input', function() {
                    const converted = convertPersianToEnglish(this.value);
                    this.value = converted.replace(/[^\d]/g, '');
                    
                    const isValid = validateMobile(this.value);
                    this.classList.toggle('valid', isValid);
                    this.classList.toggle('invalid', !isValid && this.value.length > 0);
                });
            });
            
            // Real-time validation برای OTP
            document.getElementById('otpCode').addEventListener('input', function() {
                const converted = convertPersianToEnglish(this.value);
                this.value = converted.replace(/\D/g, '').substring(0, 6);
                
                const isValid = validateOTP(this.value);
                this.classList.toggle('valid', isValid);
                this.classList.toggle('invalid', !isValid && this.value.length > 0);
            });
            
            // Form handlers
            document.getElementById('registerForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    fullName: formData.get('fullName'),
                    mobile: convertPersianToEnglish(formData.get('mobile')),
                    password: formData.get('password')
                };
                
                const result = await apiCall('register-new.php', data);
                showResult('registerResult', result);
                
                if (result.success) {
                    currentMobile = data.mobile;
                    currentUserId = result.data.user_id;
                    document.getElementById('otpMobile').textContent = data.mobile;
                    
                    setTimeout(() => {
                        showStep('otpStep');
                    }, 2000);
                }
            });
            
            document.getElementById('otpForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    mobile: currentMobile,
                    otpCode: convertPersianToEnglish(formData.get('otpCode'))
                };
                
                const result = await apiCall('verify-otp-new.php', data);
                showResult('otpResult', result);
                
                if (result.success) {
                    document.getElementById('userInfo').innerHTML = `
                        <div class="result info">
                            <strong>اطلاعات کاربر:</strong>
                            نام: ${result.data.user.full_name}
                            موبایل: ${result.data.user.mobile}
                            وضعیت: ${result.data.user.status}
                        </div>
                    `;
                    
                    setTimeout(() => {
                        showStep('successStep');
                    }, 2000);
                }
            });
            
            document.getElementById('loginForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    mobile: convertPersianToEnglish(formData.get('mobile')),
                    password: formData.get('password')
                };
                
                const result = await apiCall('login-new.php', data);
                showResult('loginResult', result);
                
                if (result.success) {
                    setTimeout(() => {
                        showStep('successStep');
                    }, 2000);
                }
            });
            
            // Resend OTP
            document.getElementById('resendOtp').addEventListener('click', function() {
                alert('قابلیت ارسال مجدد در API پیاده‌سازی نشده است');
            });
        });
    </script>
</body>
</html>

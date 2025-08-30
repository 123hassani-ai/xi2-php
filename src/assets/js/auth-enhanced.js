/**
 * زیتو (Xi2) - مدیریت احراز هویت بهبود یافته
 * مدیریت ورود، ثبت‌نام، OTP و session با پشتیبانی کامل اعداد فارسی
 */
class Xi2AuthEnhanced {
    constructor() {
        this.API_BASE = '/xi2.ir/src/api/auth/';
        this.currentMobile = null;
        this.otpTimer = null;
        this.sessionCheckInterval = null;
        this.retryCount = 0;
        this.maxRetries = 3;
        
        // وضعیت‌های مختلف
        this.isSubmitting = false;
        this.otpTimeLeft = 0;
        this.lastOtpSentAt = null;
        
        this.init();
    }

    init() {
        this.setupInputHandlers();
        this.setupFormHandlers();
        this.setupEventListeners();
        this.checkStoredAuth();
        this.startSessionMonitoring();
        
        // بارگیری اولیه
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeUI();
        });
        
        if (document.readyState !== 'loading') {
            this.initializeUI();
        }
    }

    // ===================== تبدیل اعداد فارسی =====================
    convertPersianToEnglish(input) {
        if (!input) return input;
        
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        let result = input.toString();
        
        // تبدیل اعداد فارسی
        for (let i = 0; i < 10; i++) {
            result = result.replace(new RegExp(persianNumbers[i], 'g'), englishNumbers[i]);
            result = result.replace(new RegExp(arabicNumbers[i], 'g'), englishNumbers[i]);
        }
        
        return result;
    }

    // ===================== مدیریت Input ها =====================
    setupInputHandlers() {
        this.setupMobileInputHandlers();
        this.setupOTPInputHandlers();
        this.setupPasswordHandlers();
    }

    setupMobileInputHandlers() {
        const mobileInputs = document.querySelectorAll(
            'input[type="tel"], #loginMobile, #registerMobile, #mobile, .mobile-input'
        );
        
        mobileInputs.forEach(input => {
            // Real-time conversion
            input.addEventListener('input', (e) => {
                let value = this.convertPersianToEnglish(e.target.value);
                
                // فقط اعداد و + مجاز
                value = value.replace(/[^\d+]/g, '');
                
                // محدودیت طول
                if (!value.includes('+')) {
                    if (value.length > 11) {
                        value = value.substring(0, 11);
                    }
                }
                
                e.target.value = value;
                
                // Real-time validation
                this.validateMobileInput(e.target);
            });
            
            // Paste handling
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                let cleanNumber = this.convertPersianToEnglish(pastedText);
                cleanNumber = cleanNumber.replace(/[^\d+]/g, '');
                
                if (!cleanNumber.includes('+') && cleanNumber.length > 11) {
                    cleanNumber = cleanNumber.substring(0, 11);
                }
                
                e.target.value = cleanNumber;
                this.validateMobileInput(e.target);
            });
            
            // Focus/Blur events
            input.addEventListener('focus', (e) => {
                e.target.classList.add('focused');
            });
            
            input.addEventListener('blur', (e) => {
                e.target.classList.remove('focused');
                this.validateMobileInput(e.target, true); // Final validation
            });
        });
    }

    setupOTPInputHandlers() {
        const otpInputs = document.querySelectorAll(
            '#otpCode, .otp-input, input[maxlength="6"]'
        );
        
        otpInputs.forEach(input => {
            // Real-time conversion و validation
            input.addEventListener('input', (e) => {
                let value = this.convertPersianToEnglish(e.target.value);
                value = value.replace(/\D/g, '').substring(0, 6);
                e.target.value = value;
                
                // Auto-submit when 6 digits
                if (value.length === 6) {
                    setTimeout(() => {
                        this.handleAutoSubmitOTP(e.target);
                    }, 300);
                }
                
                this.updateOTPProgress(value.length);
            });
            
            // Paste handling for OTP
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                let numbers = this.convertPersianToEnglish(pastedText);
                numbers = numbers.replace(/\D/g, '').substring(0, 6);
                
                e.target.value = numbers;
                this.updateOTPProgress(numbers.length);
                
                if (numbers.length === 6) {
                    setTimeout(() => {
                        this.handleAutoSubmitOTP(e.target);
                    }, 300);
                }
            });
        });
    }

    setupPasswordHandlers() {
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        
        passwordInputs.forEach(input => {
            // اضافه کردن toggle button
            this.addPasswordToggle(input);
            
            // Strength indicator
            if (input.name === 'password') {
                this.addPasswordStrengthIndicator(input);
            }
        });
    }

    // ===================== مدیریت فرم‌ها =====================
    setupFormHandlers() {
        // Register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleRegister();
            });
        }
        
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }
        
        // OTP form
        const otpForm = document.getElementById('otpForm');
        if (otpForm) {
            otpForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleVerifyOTP();
            });
        }
    }

    setupEventListeners() {
        // Resend OTP button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.resend-otp-btn, #resendOtp')) {
                e.preventDefault();
                this.handleResendOTP();
            }
            
            if (e.target.matches('.logout-btn')) {
                e.preventDefault();
                this.handleLogout();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Enter key in forms
            if (e.key === 'Enter' && !this.isSubmitting) {
                const activeForm = e.target.closest('form');
                if (activeForm) {
                    e.preventDefault();
                    activeForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    }

    // ===================== Validation =====================
    validateMobileInput(input, showError = false) {
        const value = input.value;
        const isValid = this.validateMobile(value);
        
        // UI feedback
        input.classList.toggle('valid', isValid);
        input.classList.toggle('invalid', !isValid && value.length > 0);
        
        if (showError && !isValid && value.length > 0) {
            this.showFieldError(input, 'شماره موبایل معتبر نیست');
        } else {
            this.clearFieldError(input);
        }
        
        return isValid;
    }

    validateMobile(mobile) {
        if (!mobile) return false;
        const cleanMobile = this.convertPersianToEnglish(mobile);
        return /^09\d{9}$/.test(cleanMobile);
    }

    validateOTP(otp) {
        if (!otp) return false;
        const cleanOTP = this.convertPersianToEnglish(otp);
        return /^\d{6}$/.test(cleanOTP);
    }

    validatePassword(password) {
        return password && password.length >= 6;
    }

    validateFullName(name) {
        return name && name.trim().length >= 2;
    }

    // ===================== Form Handlers =====================
    async handleRegister() {
        if (this.isSubmitting) return;
        
        const form = document.getElementById('registerForm');
        const formData = new FormData(form);
        
        const data = {
            fullName: formData.get('fullName') || formData.get('name'),
            mobile: this.convertPersianToEnglish(formData.get('mobile')),
            password: formData.get('password')
        };
        
        // Client-side validation
        if (!this.validateRegisterForm(data)) {
            return;
        }
        
        this.setSubmitting(true, form);
        
        try {
            const response = await this.apiCall('register-new.php', data);
            
            if (response.success) {
                this.currentMobile = data.mobile;
                this.showSuccess(response.message);
                this.switchToOTPStep(data.mobile);
                this.startOTPTimer(600); // 10 minutes
            } else {
                this.showError(response.message);
            }
            
        } catch (error) {
            console.error('Register error:', error);
            this.showError('خطا در اتصال به سرور');
        } finally {
            this.setSubmitting(false, form);
        }
    }

    async handleLogin() {
        if (this.isSubmitting) return;
        
        const form = document.getElementById('loginForm');
        const formData = new FormData(form);
        
        const data = {
            mobile: this.convertPersianToEnglish(formData.get('mobile')),
            password: formData.get('password')
        };
        
        // Client-side validation
        if (!this.validateLoginForm(data)) {
            return;
        }
        
        this.setSubmitting(true, form);
        
        try {
            const response = await this.apiCall('login-new.php', data);
            
            if (response.success) {
                this.storeAuthData(response.data);
                this.showSuccess(response.message);
                this.onLoginSuccess(response.data.user);
            } else {
                this.showError(response.message);
            }
            
        } catch (error) {
            console.error('Login error:', error);
            this.showError('خطا در اتصال به سرور');
        } finally {
            this.setSubmitting(false, form);
        }
    }

    async handleVerifyOTP() {
        if (this.isSubmitting) return;
        
        const form = document.getElementById('otpForm');
        const otpInput = form.querySelector('#otpCode');
        
        const data = {
            mobile: this.currentMobile,
            otpCode: this.convertPersianToEnglish(otpInput.value)
        };
        
        // Client-side validation
        if (!this.validateOTP(data.otpCode)) {
            this.showError('کد تایید باید ۶ رقم باشد');
            return;
        }
        
        this.setSubmitting(true, form);
        
        try {
            const response = await this.apiCall('verify-otp-new.php', data);
            
            if (response.success) {
                this.storeAuthData(response.data);
                this.showSuccess(response.message);
                this.onVerificationSuccess(response.data.user);
            } else {
                this.showError(response.message);
            }
            
        } catch (error) {
            console.error('OTP verification error:', error);
            this.showError('خطا در اتصال به سرور');
        } finally {
            this.setSubmitting(false, form);
        }
    }

    async handleResendOTP() {
        if (!this.currentMobile) {
            this.showError('شماره موبایل یافت نشد');
            return;
        }
        
        // Check cooldown
        if (this.lastOtpSentAt) {
            const timeDiff = Date.now() - this.lastOtpSentAt;
            if (timeDiff < 120000) { // 2 minutes
                const remaining = Math.ceil((120000 - timeDiff) / 1000);
                this.showError(`لطفاً ${remaining} ثانیه صبر کنید`);
                return;
            }
        }
        
        const button = document.querySelector('.resend-otp-btn');
        this.setButtonLoading(button, true);
        
        try {
            // Since resend is not implemented yet, let's use register again
            const response = await this.apiCall('register-new.php', {
                mobile: this.currentMobile,
                resend: true
            });
            
            if (response.success) {
                this.lastOtpSentAt = Date.now();
                this.showSuccess('کد تایید مجدد ارسال شد');
                this.startOTPTimer(600);
            } else {
                this.showError(response.message);
            }
            
        } catch (error) {
            console.error('Resend OTP error:', error);
            this.showError('خطا در ارسال مجدد کد');
        } finally {
            this.setButtonLoading(button, false);
        }
    }

    // ===================== UI Management =====================
    initializeUI() {
        const user = this.getCurrentUser();
        if (user) {
            this.updateUIForLoggedInUser(user);
        } else {
            this.updateUIForGuestUser();
        }
    }

    switchToOTPStep(mobile) {
        // Hide register/login forms
        document.querySelectorAll('.auth-step').forEach(step => {
            step.style.display = 'none';
        });
        
        // Show OTP form
        const otpStep = document.querySelector('.otp-step, #otpForm');
        if (otpStep) {
            otpStep.style.display = 'block';
            
            // Update mobile display
            const mobileDisplay = otpStep.querySelector('.mobile-display');
            if (mobileDisplay) {
                mobileDisplay.textContent = this.formatMobile(mobile);
            }
            
            // Focus OTP input
            const otpInput = otpStep.querySelector('#otpCode');
            if (otpInput) {
                setTimeout(() => otpInput.focus(), 100);
            }
        }
    }

    startOTPTimer(seconds) {
        this.otpTimeLeft = seconds;
        this.updateOTPTimer();
        
        if (this.otpTimer) {
            clearInterval(this.otpTimer);
        }
        
        this.otpTimer = setInterval(() => {
            this.otpTimeLeft--;
            this.updateOTPTimer();
            
            if (this.otpTimeLeft <= 0) {
                clearInterval(this.otpTimer);
                this.onOTPExpired();
            }
        }, 1000);
    }

    updateOTPTimer() {
        const timerElements = document.querySelectorAll('.otp-timer');
        const minutes = Math.floor(this.otpTimeLeft / 60);
        const seconds = this.otpTimeLeft % 60;
        const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        timerElements.forEach(el => {
            el.textContent = timeString;
        });
        
        // Enable/disable resend button
        const resendButton = document.querySelector('.resend-otp-btn');
        if (resendButton) {
            resendButton.disabled = this.otpTimeLeft > 0;
        }
    }

    updateOTPProgress(length) {
        const progressElements = document.querySelectorAll('.otp-progress');
        progressElements.forEach(el => {
            el.style.width = `${(length / 6) * 100}%`;
        });
    }

    // ===================== Utility Functions =====================
    formatMobile(mobile, format = 'dots') {
        if (!mobile || mobile.length !== 11) return mobile;
        
        switch (format) {
            case 'dots':
                return `${mobile.slice(0, 4)}.${mobile.slice(4, 7)}.${mobile.slice(7)}`;
            case 'spaces':
                return `${mobile.slice(0, 4)} ${mobile.slice(4, 7)} ${mobile.slice(7)}`;
            default:
                return mobile;
        }
    }

    setSubmitting(isSubmitting, form = null) {
        this.isSubmitting = isSubmitting;
        
        if (form) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = isSubmitting;
                submitButton.innerHTML = isSubmitting 
                    ? '<i class="fas fa-spinner fa-spin"></i> در حال پردازش...'
                    : submitButton.getAttribute('data-original-text') || 'ادامه';
            }
        }
    }

    setButtonLoading(button, loading) {
        if (!button) return;
        
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> لطفا صبر کنید...';
        } else {
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text') || 'ارسال مجدد';
        }
    }

    // ===================== API Calls =====================
    async apiCall(endpoint, data = {}) {
        const response = await fetch(this.API_BASE + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
    }

    // ===================== Storage Management =====================
    storeAuthData(data) {
        if (data.user) {
            localStorage.setItem('xi2_user', JSON.stringify(data.user));
        }
        if (data.session) {
            localStorage.setItem('xi2_token', data.session.token);
            localStorage.setItem('xi2_token_expires', data.session.expires_at);
        }
    }

    getCurrentUser() {
        const userData = localStorage.getItem('xi2_user');
        return userData ? JSON.parse(userData) : null;
    }

    getToken() {
        return localStorage.getItem('xi2_token');
    }

    clearAuthData() {
        localStorage.removeItem('xi2_user');
        localStorage.removeItem('xi2_token');
        localStorage.removeItem('xi2_token_expires');
    }

    // ===================== Event Callbacks =====================
    onLoginSuccess(user) {
        this.updateUIForLoggedInUser(user);
        // Redirect یا actions دیگر
        if (typeof window.onUserLogin === 'function') {
            window.onUserLogin(user);
        }
    }

    onVerificationSuccess(user) {
        this.updateUIForLoggedInUser(user);
        if (typeof window.onUserVerification === 'function') {
            window.onUserVerification(user);
        }
    }

    onOTPExpired() {
        this.showError('کد تایید منقضی شده است');
        const resendButton = document.querySelector('.resend-otp-btn');
        if (resendButton) {
            resendButton.disabled = false;
        }
    }

    // ===================== UI Updates =====================
    updateUIForLoggedInUser(user) {
        // Implementation based on your UI structure
        console.log('User logged in:', user);
    }

    updateUIForGuestUser() {
        // Implementation based on your UI structure
        console.log('No user logged in');
    }

    // ===================== Validation Methods =====================
    validateRegisterForm(data) {
        if (!this.validateFullName(data.fullName)) {
            this.showError('نام باید حداقل 2 کاراکتر باشد');
            return false;
        }
        
        if (!this.validateMobile(data.mobile)) {
            this.showError('شماره موبایل معتبر نیست');
            return false;
        }
        
        if (!this.validatePassword(data.password)) {
            this.showError('رمز عبور باید حداقل 6 کاراکتر باشد');
            return false;
        }
        
        return true;
    }

    validateLoginForm(data) {
        if (!this.validateMobile(data.mobile)) {
            this.showError('شماره موبایل معتبر نیست');
            return false;
        }
        
        if (!data.password) {
            this.showError('رمز عبور الزامی است');
            return false;
        }
        
        return true;
    }

    // ===================== UI Helper Methods =====================
    showSuccess(message) {
        // Implementation based on your notification system
        console.log('Success:', message);
    }

    showError(message) {
        // Implementation based on your notification system
        console.error('Error:', message);
    }

    showFieldError(input, message) {
        // Implementation for field-specific errors
        console.log('Field error:', input.name, message);
    }

    clearFieldError(input) {
        // Clear field-specific error
    }

    handleAutoSubmitOTP(input) {
        const form = input.closest('form');
        if (form && !this.isSubmitting) {
            form.dispatchEvent(new Event('submit'));
        }
    }

    addPasswordToggle(input) {
        // Implementation for password visibility toggle
    }

    addPasswordStrengthIndicator(input) {
        // Implementation for password strength indicator
    }

    checkStoredAuth() {
        // Check if user has valid stored authentication
        const token = this.getToken();
        const user = this.getCurrentUser();
        
        if (token && user) {
            // Verify token validity
            this.verifyStoredToken();
        }
    }

    async verifyStoredToken() {
        // Implementation to verify stored token with server
    }

    startSessionMonitoring() {
        // Periodic check of session validity
        this.sessionCheckInterval = setInterval(() => {
            this.checkSessionValidity();
        }, 300000); // 5 minutes
    }

    checkSessionValidity() {
        // Implementation for session validity check
    }

    async handleLogout() {
        // Implementation for logout
        this.clearAuthData();
        this.updateUIForGuestUser();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.xi2Auth = new Xi2AuthEnhanced();
});

// Fallback initialization
if (document.readyState !== 'loading') {
    window.xi2Auth = new Xi2AuthEnhanced();
}

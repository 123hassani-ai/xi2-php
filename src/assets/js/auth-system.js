/**
 * زیتو (Xi2) - سیستم احراز هویت یکپارچه
 * مدیریت کاربران میهمان، پلاس و پریمیوم
 * طراحی شده طبق پرامپت شماره 3 - Clean Architecture
 */

class Xi2AuthSystem {
    constructor() {
        this.userType = null;
        this.userData = null;
        this.deviceId = this.getOrCreateDeviceId();
        this.csrfToken = null;
        this.apiBase = '/api/auth/';
        
        // Initialize system
        this.init();
    }
    
    /**
     * راه‌اندازی اولیه سیستم
     */
    async init() {
        try {
            // تشخیص وضعیت فعلی کاربر
            await this.detectUserType();
            
            // راه‌اندازی UI
            this.updateUIForUserType();
            
            // تنظیم event listeners
            this.setupEventListeners();
            
            console.log('Xi2 Auth System initialized:', this.userType);
            
        } catch (error) {
            console.error('خطا در راه‌اندازی سیستم احراز هویت:', error);
        }
    }
    
    /**
     * تشخیص نوع کاربر از سرور
     */
    async detectUserType() {
        try {
            const response = await fetch(this.apiBase + 'user-status.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.userType = data.user_type;
                this.userData = data.user_data || data.guest_data || null;
                this.csrfToken = data.csrf_token;
                
                // ذخیره در localStorage برای عملکرد بهتر
                localStorage.setItem('xi2_user_type', this.userType);
                localStorage.setItem('xi2_csrf_token', this.csrfToken);
                
                // پیام‌های flash
                if (data.flash_messages && data.flash_messages.length > 0) {
                    this.showFlashMessages(data.flash_messages);
                }
                
                return data;
            }
            
            throw new Error(data.message || 'خطا در تشخیص نوع کاربر');
            
        } catch (error) {
            console.error('خطا در تشخیص کاربر:', error);
            // حالت پیش‌فرض: کاربر میهمان
            this.userType = 'guest';
            this.userData = { device_id: this.deviceId };
        }
    }
    
    /**
     * بروزرسانی UI بر اساس نوع کاربر
     */
    updateUIForUserType() {
        const header = document.querySelector('.responsive-header');
        const heroSection = document.querySelector('.hero-section');
        const authButtons = document.querySelector('.nav');
        
        switch (this.userType) {
            case 'guest':
                this.showGuestInterface();
                break;
                
            case 'plus':
            case 'premium':
                this.showAuthenticatedInterface();
                break;
        }
    }
    
    /**
     * رابط کاربری میهمان
     */
    showGuestInterface() {
        // هدر میهمان
        const header = document.querySelector('.responsive-header');
        if (header) {
            header.innerHTML = `
                <div class="container">
                    <div class="header-content">
                        <div class="logo">
                            <h1>🎯 زیتو</h1>
                            <span>Xi2</span>
                        </div>
                        <nav class="main-nav">
                            <button id="loginBtn" class="btn btn-primary">ورود</button>
                            <button id="registerBtn" class="btn btn-secondary">کاربر پلاس رایگان</button>
                        </nav>
                    </div>
                </div>
            `;
        }
        
        // محتوای اصلی با تبلیغات
        this.showGuestContent();
        
        // نمایش محدودیت‌های میهمان
        if (this.userData && this.userData.limitations) {
            this.showGuestLimitations();
        }
    }
    
    /**
     * رابط کاربری احراز هویت شده
     */
    showAuthenticatedInterface() {
        const header = document.querySelector('.responsive-header');
        if (header) {
            header.classList.add('logged-in');
            header.innerHTML = `
                <div class="container">
                    <div class="header-content">
                        <div class="logo">
                            <h1>🎯 زیتو</h1>
                            <span>Xi2</span>
                        </div>
                        <div class="user-section">
                            <div class="user-avatar" id="userAvatar">
                                <img src="/assets/images/default-avatar.png" alt="آواتار" class="avatar-img">
                                <span class="user-name">${this.userData?.full_name || 'کاربر'}</span>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="user-dropdown" id="userDropdown">
                                <a href="#profile">👤 پروفایل کاربری</a>
                                <a href="#dashboard">📊 محیط کاربری</a>
                                <a href="#uploads">📁 فایل‌های من</a>
                                ${this.userType === 'plus' ? '<a href="#premium">⭐ تبدیل به پریمیوم</a>' : ''}
                                <a href="#logout" id="logoutBtn">🚪 خروج</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // محتوای اصلی بدون تبلیغات
        this.showCleanInterface();
    }
    
    /**
     * مدیریت فرم‌های ورود/ثبت‌نام
     */
    handleAuthForms() {
        // فرم ثبت‌نام
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }
        
        // فرم ورود
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        // فرم OTP
        const otpForm = document.getElementById('otpForm');
        if (otpForm) {
            otpForm.addEventListener('submit', (e) => this.handleOTPVerification(e));
        }
    }
    
    /**
     * مدیریت ثبت‌نام
     */
    async handleRegister(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const data = {
            full_name: formData.get('full_name'),
            mobile: this.convertPersianToEnglish(formData.get('mobile')),
            password: formData.get('password'),
            csrf_token: this.csrfToken
        };
        
        try {
            const response = await fetch(this.apiBase + 'register-clean.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // نمایش فرم OTP
                this.showOTPForm(data.mobile);
                this.showMessage(result.message, 'success');
            } else {
                this.showMessage(result.message, 'error');
            }
            
        } catch (error) {
            console.error('خطا در ثبت‌نام:', error);
            this.showMessage('خطای شبکه در ثبت‌نام', 'error');
        }
    }
    
    /**
     * مدیریت ورود
     */
    async handleLogin(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const data = {
            mobile: this.convertPersianToEnglish(formData.get('mobile')),
            password: formData.get('password'),
            csrf_token: this.csrfToken
        };
        
        try {
            const response = await fetch(this.apiBase + 'login-clean.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // بروزرسانی وضعیت کاربر
                this.userType = result.data.user_type;
                this.userData = result.data.user;
                
                // بستن modal و بروزرسانی UI
                this.closeAuthModal();
                this.updateUIForUserType();
                
                this.showMessage(result.message, 'success');
                
                // بارگذاری مجدد صفحه
                setTimeout(() => location.reload(), 1500);
                
            } else {
                this.showMessage(result.message, 'error');
            }
            
        } catch (error) {
            console.error('خطا در ورود:', error);
            this.showMessage('خطای شبکه در ورود', 'error');
        }
    }
    
    /**
     * مدیریت تایید OTP
     */
    async handleOTPVerification(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const data = {
            mobile: this.pendingMobile,
            otp_code: this.convertPersianToEnglish(formData.get('otp_code')),
            csrf_token: this.csrfToken
        };
        
        try {
            const response = await fetch(this.apiBase + 'verify-otp-clean.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // لاگین خودکار
                this.userType = 'plus';
                this.userData = result.data.user;
                
                this.closeAuthModal();
                this.updateUIForUserType();
                this.showMessage(result.message, 'success');
                
                setTimeout(() => location.reload(), 1500);
                
            } else {
                this.showMessage(result.message, 'error');
            }
            
        } catch (error) {
            console.error('خطا در تایید OTP:', error);
            this.showMessage('خطای شبکه در تایید کد', 'error');
        }
    }
    
    /**
     * مدیریت آپلود میهمان
     */
    async handleGuestUpload(file) {
        // بررسی محدودیت‌های میهمان
        if (this.userData && this.userData.limitations && !this.userData.limitations.allowed) {
            this.showUpgradeModal(this.userData.upgrade_message);
            return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('csrf_token', this.csrfToken);
        
        try {
            const response = await fetch(this.apiBase + 'guest-upload.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showUploadSuccess(result.data);
                
                // بروزرسانی محدودیت‌ها
                this.userData.limitations.remaining_uploads = result.data.remaining_uploads;
                this.updateGuestLimitations();
                
                // نمایش پیام تشویقی
                if (result.upgrade_message) {
                    this.showUpgradeHint(result.upgrade_message);
                }
                
            } else {
                this.showMessage(result.message, 'error');
                
                if (result.upgrade_message) {
                    this.showUpgradeModal(result.upgrade_message);
                }
            }
            
        } catch (error) {
            console.error('خطا در آپلود:', error);
            this.showMessage('خطای شبکه در آپلود', 'error');
        }
    }
    
    /**
     * مدیریت منوی کاربر
     */
    handleUserDropdown() {
        const userAvatar = document.getElementById('userAvatar');
        const userDropdown = document.getElementById('userDropdown');
        
        if (userAvatar && userDropdown) {
            userAvatar.addEventListener('click', () => {
                userDropdown.classList.toggle('show');
            });
            
            // بستن dropdown با کلیک خارج از آن
            document.addEventListener('click', (e) => {
                if (!userAvatar.contains(e.target)) {
                    userDropdown.classList.remove('show');
                }
            });
        }
    }
    
    /**
     * تبدیل اعداد فارسی به انگلیسی (استفاده از PersianUtils موجود)
     */
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
    
    /**
     * تولید یا دریافت Device ID
     */
    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem('xi2_device_id');
        
        if (!deviceId) {
            const factors = [
                navigator.userAgent,
                navigator.language,
                screen.width + 'x' + screen.height,
                Date.now()
            ];
            
            deviceId = 'xi2_' + btoa(factors.join('|')).substr(0, 12) + '_' + Date.now();
            localStorage.setItem('xi2_device_id', deviceId);
        }
        
        return deviceId;
    }
    
    /**
     * تنظیم Event Listeners
     */
    setupEventListeners() {
        // دکمه‌های ورود/ثبت‌نام
        document.addEventListener('click', (e) => {
            if (e.target.id === 'loginBtn' || e.target.matches('a[href="#login"]')) {
                e.preventDefault();
                this.showAuthModal('login');
            }
            
            if (e.target.id === 'registerBtn' || e.target.matches('a[href="#register"]')) {
                e.preventDefault();
                this.showAuthModal('register');
            }
            
            if (e.target.id === 'logoutBtn') {
                e.preventDefault();
                this.handleLogout();
            }
        });
        
        // مدیریت آپلود
        this.setupUploadHandlers();
        
        // فرم‌های احراز هویت
        this.handleAuthForms();
        
        // منوی کاربر
        this.handleUserDropdown();
        
        // ورودی‌های فارسی
        this.setupPersianInputs();
    }
    
    /**
     * راه‌اندازی ورودی‌های فارسی
     */
    setupPersianInputs() {
        // تبدیل خودکار اعداد در input های مشخص
        document.addEventListener('input', (e) => {
            if (e.target.matches('input[name="mobile"], input[name="otp_code"]')) {
                const converted = this.convertPersianToEnglish(e.target.value);
                if (converted !== e.target.value) {
                    e.target.value = converted;
                }
            }
        });
    }
    
    /**
     * نمایش modal احراز هویت
     */
    showAuthModal(type) {
        // TODO: پیاده‌سازی modal
        console.log('Show auth modal:', type);
    }
    
    /**
     * بستن modal احراز هویت
     */
    closeAuthModal() {
        // TODO: پیاده‌سازی بستن modal
        console.log('Close auth modal');
    }
    
    /**
     * نمایش پیام
     */
    showMessage(message, type) {
        console.log(`[${type}] ${message}`);
        // TODO: پیاده‌سازی نمایش پیام
    }
    
    /**
     * سایر متدهای کمکی
     */
    showGuestContent() { /* TODO */ }
    showCleanInterface() { /* TODO */ }
    showGuestLimitations() { /* TODO */ }
    showOTPForm(mobile) { this.pendingMobile = mobile; /* TODO */ }
    showUpgradeModal(message) { /* TODO */ }
    showUploadSuccess(data) { /* TODO */ }
    showUpgradeHint(message) { /* TODO */ }
    updateGuestLimitations() { /* TODO */ }
    setupUploadHandlers() { /* TODO */ }
    showFlashMessages(messages) { console.log('Flash messages:', messages); }
    
    async handleLogout() {
        try {
            const response = await fetch(this.apiBase + 'logout-clean.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            }
            
        } catch (error) {
            console.error('خطا در خروج:', error);
        }
    }
}

// راه‌اندازی سیستم پس از بارگذاری DOM
document.addEventListener('DOMContentLoaded', () => {
    window.xi2Auth = new Xi2AuthSystem();
});

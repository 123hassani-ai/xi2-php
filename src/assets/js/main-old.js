/**
 * زیتو (Xi2) - اسکریپت اصلی
 * مدیریت کلی وب‌اپلیکیشن
 */

class Xi2App {
    constructor() {
        this.API_BASE = '/xi2-01/src/api/';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initPWA();
        this.checkAuthStatus();
    }

    setupEventListeners() {
        // مدیریت مودال
        this.setupModalHandlers();
        
        // مدیریت فرم‌ها
        this.setupAuthForms();
        
        // مدیریت نوتیفیکیشن‌ها
        this.setupNotifications();
        
        // مدیریت تم
        this.setupThemeHandler();
    }

    setupModalHandlers() {
        const modal = document.getElementById('authModal');
        const loginLink = document.querySelector('a[href="#login"]');
        const registerLink = document.querySelector('a[href="#register"]');
        const closeModal = document.getElementById('closeModal');
        const showRegister = document.getElementById('showRegister');
        const showLogin = document.getElementById('showLogin');

        // باز کردن مودال ورود
        loginLink?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showModal('login');
        });

        // باز کردن مودال ثبت‌نام
        registerLink?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showModal('register');
        });

        // بستن مودال
        closeModal?.addEventListener('click', () => {
            this.hideModal();
        });

        // بستن مودال با کلیک روی پس‌زمینه
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.hideModal();
            }
        });

        // جابجایی بین فرم‌ها
        showRegister?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showForm('register');
        });

        showLogin?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showForm('login');
        });

        // بستن مودال با ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal?.classList.contains('active')) {
                this.hideModal();
            }
        });
    }

    setupAuthForms() {
        // فرم ورود
        const loginForm = document.querySelector('#loginForm form');
        loginForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        // فرم ثبت‌نام
        const registerForm = document.querySelector('#registerForm form');
        registerForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });

        // فرم OTP
        const otpForm = document.querySelector('#otpForm form');
        otpForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleOTPVerification();
        });

        // ارسال مجدد OTP
        const resendOTP = document.getElementById('resendOTP');
        resendOTP?.addEventListener('click', (e) => {
            e.preventDefault();
            this.resendOTP();
        });
    }

    setupNotifications() {
        // حذف نوتیفیکیشن‌های موجود بعد از مدتی
        setTimeout(() => {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                this.hideNotification(notification);
            });
        }, 5000);
    }

    setupThemeHandler() {
        // تشخیص تم سیستم
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addListener(this.handleThemeChange.bind(this));
    }

    initPWA() {
        // راه‌اندازی PWA
        if ('serviceWorker' in navigator) {
            this.registerServiceWorker();
        }

        // مدیریت نصب PWA
        this.handlePWAInstall();
    }

    async registerServiceWorker() {
        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('✅ Service Worker ثبت شد:', registration);
        } catch (error) {
            console.error('❌ خطا در ثبت Service Worker:', error);
        }
    }

    handlePWAInstall() {
        let deferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // نمایش دکمه نصب
            this.showInstallButton();
        });

        // مدیریت کلیک نصب
        document.addEventListener('click', async (e) => {
            if (e.target.matches('.install-button')) {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const result = await deferredPrompt.userChoice;
                    if (result.outcome === 'accepted') {
                        this.showNotification('اپلیکیشن با موفقیت نصب شد!', 'success');
                    }
                    deferredPrompt = null;
                }
            }
        });
    }

    showInstallButton() {
        const installButton = document.createElement('button');
        installButton.className = 'btn btn-primary install-button';
        installButton.innerHTML = '📱 نصب اپلیکیشن';
        installButton.style.position = 'fixed';
        installButton.style.bottom = '20px';
        installButton.style.right = '20px';
        installButton.style.zIndex = '1000';
        document.body.appendChild(installButton);
    }

    // مدیریت مودال‌ها
    showModal(type = 'login') {
        const modal = document.getElementById('authModal');
        modal?.classList.add('active');
        this.showForm(type);
    }

    hideModal() {
        const modal = document.getElementById('authModal');
        modal?.classList.remove('active');
    }

    showForm(type) {
        // مخفی کردن همه فرم‌ها
        const forms = ['loginForm', 'registerForm', 'otpForm'];
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) form.style.display = 'none';
        });

        // نمایش فرم مورد نظر
        const targetForm = document.getElementById(`${type}Form`);
        if (targetForm) targetForm.style.display = 'block';
    }

    // احراز هویت
    async handleLogin() {
        const mobile = document.getElementById('loginMobile').value;
        const password = document.getElementById('loginPassword').value;

        if (!this.validateMobile(mobile)) {
            this.showNotification('شماره موبایل معتبر نیست', 'error');
            return;
        }

        const submitButton = document.querySelector('#loginForm button[type="submit"]');
        this.setButtonLoading(submitButton, true);

        try {
            const result = await window.xi2Auth.login(mobile, password);

            if (result.success) {
                this.showNotification('ورود موفقیت‌آمیز بود!', 'success');
                this.hideModal();
                this.redirectToDashboard();
            } else if (result.needsVerification) {
                this.showNotification(result.message, 'info');
                document.getElementById('otpMobile').textContent = result.mobile;
                this.showForm('otp');
            } else {
                this.showNotification(result.message || 'خطا در ورود', 'error');
            }
        } catch (error) {
            console.error('خطا در ورود:', error);
            this.showNotification('خطا در برقراری ارتباط', 'error');
        } finally {
            this.setButtonLoading(submitButton, false);
        }
    }

    async handleRegister() {
        const fullName = document.getElementById('registerName').value;
        const mobile = document.getElementById('registerMobile').value;
        const password = document.getElementById('registerPassword').value;

        if (!this.validateForm({ fullName, mobile, password })) {
            return;
        }

        const submitButton = document.querySelector('#registerForm button[type="submit"]');
        this.setButtonLoading(submitButton, true);

        try {
            const result = await window.xi2Auth.register(fullName, mobile, password);

            if (result.success) {
                this.showNotification(result.message, 'success');
                document.getElementById('otpMobile').textContent = result.mobile;
                this.showForm('otp');
            } else {
                this.showNotification(result.message || 'خطا در ثبت‌نام', 'error');
            }
        } catch (error) {
            console.error('خطا در ثبت‌نام:', error);
            this.showNotification('خطا در برقراری ارتباط', 'error');
        } finally {
            this.setButtonLoading(submitButton, false);
        }
    }

    async handleOTPVerification() {
        const otpCode = document.getElementById('otpCode').value;

        if (!otpCode || otpCode.length !== 6) {
            this.showNotification('کد تایید باید 6 رقمی باشد', 'error');
            return;
        }

        const submitButton = document.querySelector('#otpForm button[type="submit"]');
        this.setButtonLoading(submitButton, true);

        try {
            const result = await window.xi2Auth.verifyOTP(otpCode);

            if (result.success) {
                this.showNotification('تایید موفقیت‌آمیز بود!', 'success');
                this.hideModal();
                this.redirectToDashboard();
            } else {
                this.showNotification(result.message || 'کد تایید نادرست', 'error');
            }
        } catch (error) {
            console.error('خطا در تایید OTP:', error);
            this.showNotification('خطا در برقراری ارتباط', 'error');
        } finally {
            this.setButtonLoading(submitButton, false);
        }
    }
            console.error('خطا در تایید:', error);
            this.showNotification('خطا در برقراری ارتباط', 'error');
        } finally {
            this.setButtonLoading(submitButton, false);
        }
    }

    async resendOTP() {
        try {
            const response = await this.api('auth/resend-otp.php');
            
            if (response.success) {
                this.showNotification('کد تایید مجدداً ارسال شد', 'success');
            } else {
                this.showNotification(response.message || 'خطا در ارسال کد', 'error');
            }
        } catch (error) {
            console.error('خطا در ارسال مجدد:', error);
            this.showNotification('خطا در برقراری ارتباط', 'error');
        }
    }

    // اعتبارسنجی
    validateMobile(mobile) {
        const mobileRegex = /^09\d{9}$/;
        return mobileRegex.test(mobile);
    }

    validateForm({ name, mobile, password }) {
        if (!name || name.length < 2) {
            this.showNotification('نام باید حداقل 2 کاراکتر باشد', 'error');
            return false;
        }

        if (!this.validateMobile(mobile)) {
            this.showNotification('شماره موبایل معتبر نیست', 'error');
            return false;
        }

        if (!password || password.length < 6) {
            this.showNotification('رمز عبور باید حداقل 6 کاراکتر باشد', 'error');
            return false;
        }

        return true;
    }

    // نوتیفیکیشن‌ها
    showNotification(message, type = 'info', title = '') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">${icons[type] || icons.info}</div>
                <div class="notification-text">
                    ${title ? `<div class="notification-title">${title}</div>` : ''}
                    <div class="notification-message">${message}</div>
                </div>
                <div class="notification-close">&times;</div>
            </div>
        `;

        document.body.appendChild(notification);

        // مدیریت بستن
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.hideNotification(notification);
        });

        // حذف خودکار بعد از 5 ثانیه
        setTimeout(() => {
            this.hideNotification(notification);
        }, 5000);

        return notification;
    }

    hideNotification(notification) {
        notification.style.animation = 'notificationSlideOut 0.3s ease forwards';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }

    // ابزارها
    setButtonLoading(button, loading) {
        if (!button) return;

        if (loading) {
            button.disabled = true;
            button.innerHTML = '<div class="loader"></div> در حال پردازش...';
        } else {
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text') || button.textContent;
        }
    }

    async api(endpoint, data = null, method = 'POST') {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(this.API_BASE + endpoint, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async checkAuthStatus() {
        try {
            const response = await this.api('auth/status.php', null, 'GET');
            if (response.authenticated) {
                this.updateUIForLoggedInUser(response.user);
            }
        } catch (error) {
            console.log('کاربر وارد نشده است');
        }
    }

    updateUIForLoggedInUser(user) {
        // به‌روزرسانی UI برای کاربر وارد شده
        const loginLink = document.querySelector('a[href="#login"]');
        const registerLink = document.querySelector('a[href="#register"]');
        
        if (loginLink && registerLink) {
            loginLink.textContent = `سلام ${user.name}`;
            loginLink.href = '/dashboard';
            registerLink.style.display = 'none';
        }
    }

    redirectToDashboard() {
        setTimeout(() => {
            window.location.href = '/dashboard.html';
        }, 1500);
    }

    handleThemeChange(e) {
        if (e.matches) {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
    }
}

// راه‌اندازی اپلیکیشن
document.addEventListener('DOMContentLoaded', () => {
    window.xi2App = new Xi2App();
});

// CSS برای انیمیشن حذف نوتیفیکیشن
const style = document.createElement('style');
style.textContent = `
@keyframes notificationSlideOut {
  to { 
    transform: translateX(100%);
    opacity: 0;
  }
}
`;
document.head.appendChild(style);

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
            const registration = await navigator.serviceWorker.register('/xi2-01/public/service-worker.js');
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

    async resendOTP() {
        try {
            const result = await window.xi2Auth.resendOTP();
            
            if (result.success) {
                this.showNotification(result.message, 'success');
            } else {
                this.showNotification(result.message || 'خطا در ارسال کد', 'error');
            }
        } catch (error) {
            console.error('خطا در ارسال مجدد کد:', error);
            this.showNotification('خطا در برقراری ارتباط', 'error');
        }
    }

    validateMobile(mobile) {
        const mobileRegex = /^09\d{9}$/;
        return mobileRegex.test(mobile);
    }

    validateForm({ fullName, mobile, password }) {
        // نام
        if (!fullName || fullName.trim().length < 2) {
            this.showNotification('نام باید حداقل 2 کاراکتر باشد', 'error');
            return false;
        }

        // موبایل
        if (!this.validateMobile(mobile)) {
            this.showNotification('شماره موبایل معتبر نیست', 'error');
            return false;
        }

        // رمز عبور
        if (!password || password.length < 6) {
            this.showNotification('رمز عبور باید حداقل 6 کاراکتر باشد', 'error');
            return false;
        }

        return true;
    }

    showNotification(message, type = 'info', title = '') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icon = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        }[type] || 'ℹ️';

        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">${icon}</div>
                <div class="notification-text">
                    ${title ? `<strong>${title}</strong><br>` : ''}
                    ${message}
                </div>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        document.body.appendChild(notification);

        // خودکار حذف کردن بعد از 5 ثانیه
        setTimeout(() => {
            this.hideNotification(notification);
        }, 5000);

        // انیمیشن ورود
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
    }

    hideNotification(notification) {
        if (notification && notification.parentElement) {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }

    setButtonLoading(button, loading) {
        if (!button) return;

        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.innerHTML = `<span class="loading-spinner"></span> در حال پردازش...`;
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || 'ارسال';
        }
    }

    async checkAuthStatus() {
        // این کار توسط Xi2Auth انجام می‌شود
        // فقط چک می‌کنیم که آیا کاربر وارد شده یا نه
        if (window.xi2Auth?.isLoggedIn()) {
            this.updateUIForAuthenticatedUser();
        }
    }

    updateUIForAuthenticatedUser() {
        // به‌روزرسانی UI برای کاربر وارد شده
        const user = window.xi2Auth?.getCurrentUser();
        if (user) {
            console.log('کاربر وارد شده:', user);
            // می‌توانید اینجا UI را به‌روزرسانی کنید
        }
    }

    redirectToDashboard() {
        // هدایت به داشبورد یا بخش مدیریت
        // فعلاً صفحه را reload می‌کنیم
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    handleThemeChange(e) {
        if (e.matches) {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
    }

    // متد کمکی برای navigation
    showSection(sectionName) {
        console.log('Showing section:', sectionName);
        // پیاده‌سازی navigation بین بخش‌ها
    }
}

// راه‌اندازی سراسری
document.addEventListener('DOMContentLoaded', () => {
    window.xi2App = new Xi2App();
});

// استایل‌های CSS برای نوتیفیکیشن‌ها
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    max-width: 400px;
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    overflow: hidden;
}

.notification.show {
    transform: translateX(0);
}

.notification-content {
    display: flex;
    align-items: flex-start;
    padding: 16px;
    gap: 12px;
}

.notification-icon {
    font-size: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.notification-text {
    flex: 1;
    font-size: 14px;
    line-height: 1.4;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.6;
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    opacity: 1;
}

.notification-success {
    background: #d1fae5;
    color: #065f46;
}

.notification-error {
    background: #fee2e2;
    color: #991b1b;
}

.notification-warning {
    background: #fef3c7;
    color: #92400e;
}

.notification-info {
    background: #dbeafe;
    color: #1e40af;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .notification {
        right: 10px;
        left: 10px;
        max-width: none;
        transform: translateY(-100%);
    }
    
    .notification.show {
        transform: translateY(0);
    }
}
`;
document.head.appendChild(notificationStyles);

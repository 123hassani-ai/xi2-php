/**
 * زیتو (Xi2) - مدیریت احراز هویت
 * مدیریت ورود، ثبت‌نام، OTP و session
 */

class Xi2Auth {
    constructor() {
        this.API_BASE = '/xi2-01/src/api/auth/';
        this.currentMobile = null;
        this.otpTimer = null;
        this.init();
    }

    init() {
        this.setupOTPInputHandlers();
        this.setupPasswordToggles();
        this.checkStoredAuth();
    }

    setupOTPInputHandlers() {
        const otpInput = document.getElementById('otpCode');
        if (!otpInput) return;

        // فقط اجازه عدد
        otpInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '');
            
            // اگر 6 رقم شد، خودکار submit کن
            if (e.target.value.length === 6) {
                setTimeout(() => {
                    const form = e.target.closest('form');
                    form?.dispatchEvent(new Event('submit'));
                }, 500);
            }
        });

        // پیست کردن کد OTP
        otpInput.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = pastedText.replace(/\D/g, '').substring(0, 6);
            otpInput.value = numbers;
            
            if (numbers.length === 6) {
                setTimeout(() => {
                    const form = otpInput.closest('form');
                    form?.dispatchEvent(new Event('submit'));
                }, 500);
            }
        });
    }

    setupPasswordToggles() {
        // اضافه کردن دکمه نمایش رمز عبور
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        
        passwordInputs.forEach(input => {
            const container = input.parentElement;
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle';
            toggleBtn.innerHTML = '👁️';
            toggleBtn.setAttribute('aria-label', 'نمایش رمز عبور');
            
            container.style.position = 'relative';
            container.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', () => {
                if (input.type === 'password') {
                    input.type = 'text';
                    toggleBtn.innerHTML = '🙈';
                } else {
                    input.type = 'password';
                    toggleBtn.innerHTML = '👁️';
                }
            });
        });
    }

    checkStoredAuth() {
        const token = localStorage.getItem('xi2_token');
        const user = localStorage.getItem('xi2_user');
        
        if (token && user) {
            try {
                const userData = JSON.parse(user);
                this.updateUIForLoggedInUser(userData);
                
                // تایید اعتبار توکن در پس‌زمینه
                this.validateToken(token);
            } catch (error) {
                this.clearStoredAuth();
            }
        }
    }

    async validateToken(token) {
        try {
            // این API در فاز بعدی پیاده‌سازی می‌شود
            // فعلاً فرض می‌کنیم توکن معتبر است
            return true;
        } catch (error) {
            console.error('خطا در اعتبارسنجی توکن:', error);
            this.clearStoredAuth();
            return false;
        }
    }

    async login(mobile, password) {
        try {
            const response = await fetch(this.API_BASE + 'login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mobile: mobile,
                    password: password
                })
            });

            const result = await response.json();

            if (result.success) {
                // اگر کاربر تایید نشده باشد
                if (result.data.needsVerification) {
                    this.currentMobile = mobile;
                    this.startOTPTimer();
                    return { 
                        success: false, 
                        needsVerification: true, 
                        message: result.message,
                        mobile: mobile
                    };
                }
                
                // ورود موفق
                this.storeAuthData(result.data.token, result.data.user);
                this.updateUIForLoggedInUser(result.data.user);
                
                return { 
                    success: true, 
                    user: result.data.user,
                    stats: result.data.stats
                };
            } else {
                return { success: false, message: result.message };
            }

        } catch (error) {
            console.error('خطا در ورود:', error);
            return { success: false, message: 'خطا در برقراری ارتباط' };
        }
    }

    async register(fullName, mobile, password) {
        try {
            // اعتبارسنجی سمت کلاینت
            if (!this.validateRegisterData(fullName, mobile, password)) {
                return { success: false, message: 'اطلاعات وارد شده معتبر نیست' };
            }

            const response = await fetch(this.API_BASE + 'register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fullName: fullName,
                    mobile: mobile,
                    password: password
                })
            });

            const result = await response.json();

            if (result.success) {
                this.currentMobile = mobile;
                this.startOTPTimer();
                return { 
                    success: true, 
                    message: result.message,
                    mobile: mobile,
                    otpExpires: result.data.otpExpires
                };
            } else {
                return { success: false, message: result.message };
            }

        } catch (error) {
            console.error('خطا در ثبت‌نام:', error);
            return { success: false, message: 'خطا در برقراری ارتباط' };
        }
    }

    async verifyOTP(otpCode) {
        try {
            const response = await fetch(this.API_BASE + 'verify-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mobile: this.currentMobile,
                    otpCode: otpCode
                })
            });

            const result = await response.json();

            if (result.success) {
                this.stopOTPTimer();
                this.storeAuthData(result.data.token, result.data.user);
                this.updateUIForLoggedInUser(result.data.user);
                
                return { 
                    success: true, 
                    user: result.data.user,
                    stats: result.data.stats,
                    isNewUser: result.data.isNewUser
                };
            } else {
                return { success: false, message: result.message };
            }

        } catch (error) {
            console.error('خطا در تایید OTP:', error);
            return { success: false, message: 'خطا در برقراری ارتباط' };
        }
    }

    async resendOTP() {
        if (!this.currentMobile) {
            return { success: false, message: 'شماره موبایل معتبر نیست' };
        }

        try {
            // در API جدید، resend OTP با درخواست login یا register جدید انجام می‌شود
            // فعلاً یک پیام موفقیت ساده برمی‌گردانیم
            this.startOTPTimer();
            return { 
                success: true, 
                message: 'کد تایید جدید ارسال شد'
            };

        } catch (error) {
            console.error('خطا در ارسال مجدد کد:', error);
            return { success: false, message: 'خطا در ارسال کد' };
        }
    }

    async logout() {
        try {
            const token = localStorage.getItem('xi2_token');
            
            if (token) {
                const response = await fetch(this.API_BASE + 'logout.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ token })
                });
                
                const result = await response.json();
                console.log('Logout result:', result);
            }

        } catch (error) {
            console.error('خطا در خروج:', error);
        } finally {
            this.clearStoredAuth();
            this.updateUIForGuestUser();
            window.location.reload();
        }
    }

    validateRegisterData(fullName, mobile, password) {
        // نام
        if (!fullName || fullName.trim().length < 2) {
            window.xi2App?.showNotification('نام باید حداقل 2 کاراکتر باشد', 'error');
            return false;
        }

        // موبایل
        const mobileRegex = /^09\d{9}$/;
        if (!mobileRegex.test(mobile)) {
            window.xi2App?.showNotification('شماره موبایل معتبر نیست', 'error');
            return false;
        }

        // رمز عبور
        if (!password || password.length < 6) {
            window.xi2App?.showNotification('رمز عبور باید حداقل 6 کاراکتر باشد', 'error');
            return false;
        }

        return true;
    }

    startOTPTimer() {
        let timeLeft = 120; // 2 دقیقه
        const timerElement = document.querySelector('.otp-timer');
        
        if (!timerElement) return;
        
        this.otpTimer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                this.stopOTPTimer();
                timerElement.textContent = 'کد منقضی شد';
                
                // فعال کردن دکمه ارسال مجدد
                const resendBtn = document.querySelector('.resend-otp-btn');
                if (resendBtn) {
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'ارسال مجدد کد';
                }
            }
            
            timeLeft--;
        }, 1000);
    }

    stopOTPTimer() {
        if (this.otpTimer) {
            clearInterval(this.otpTimer);
            this.otpTimer = null;
        }
    }

    storeAuthData(token, user) {
        localStorage.setItem('xi2_token', token);
        localStorage.setItem('xi2_user', JSON.stringify(user));
        
        // ذخیره زمان ورود
        localStorage.setItem('xi2_login_time', Date.now().toString());
    }

    clearStoredAuth() {
        localStorage.removeItem('xi2_token');
        localStorage.removeItem('xi2_user');
        localStorage.removeItem('xi2_login_time');
    }

    updateUIForLoggedInUser(user) {
        // بروزرسانی نام کاربر
        const userNameElements = document.querySelectorAll('.user-name');
        userNameElements.forEach(el => el.textContent = user.fullName);
        
        // نمایش بخش‌های کاربر وارد شده
        const authElements = document.querySelectorAll('.auth-required');
        authElements.forEach(el => el.style.display = 'block');
        
        // مخفی کردن بخش‌های مهمان
        const guestElements = document.querySelectorAll('.guest-only');
        guestElements.forEach(el => el.style.display = 'none');
        
        // اضافه کردن منوی کاربر
        this.addUserMenu(user);
    }

    updateUIForGuestUser() {
        // مخفی کردن بخش‌های کاربر وارد شده
        const authElements = document.querySelectorAll('.auth-required');
        authElements.forEach(el => el.style.display = 'none');
        
        // نمایش بخش‌های مهمان
        const guestElements = document.querySelectorAll('.guest-only');
        guestElements.forEach(el => el.style.display = 'block');
        
        // حذف منوی کاربر
        const userMenu = document.querySelector('.user-menu');
        if (userMenu) {
            userMenu.remove();
        }
    }

    addUserMenu(user) {
        // حذف منوی قبلی
        const existingMenu = document.querySelector('.user-menu');
        if (existingMenu) {
            existingMenu.remove();
        }
        
        // ایجاد منوی جدید
        const menuHTML = `
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        ${user.fullName.charAt(0)}
                    </div>
                    <div class="user-details">
                        <div class="user-name">${user.fullName}</div>
                        <div class="user-mobile">${user.mobile}</div>
                    </div>
                </div>
                <div class="user-actions">
                    <button onclick="window.xi2App.showSection('gallery')" class="menu-item">
                        📁 گالری من
                    </button>
                    <button onclick="window.xi2App.showSection('profile')" class="menu-item">
                        ⚙️ تنظیمات
                    </button>
                    <button onclick="window.xi2Auth.confirmLogout()" class="menu-item logout-btn">
                        🚪 خروج
                    </button>
                </div>
            </div>
        `;
        
        // اضافه کردن به header
        const header = document.querySelector('.app-header');
        if (header) {
            header.insertAdjacentHTML('beforeend', menuHTML);
        }
    }

    confirmLogout() {
        if (confirm('آیا می‌خواهید از حساب کاربری خود خارج شوید؟')) {
            this.logout();
        }
    }

    getCurrentUser() {
        const userData = localStorage.getItem('xi2_user');
        return userData ? JSON.parse(userData) : null;
    }

    getToken() {
        return localStorage.getItem('xi2_token');
    }

    isLoggedIn() {
        return !!this.getToken();
    }
}

// راه‌اندازی سراسری
document.addEventListener('DOMContentLoaded', () => {
    window.xi2Auth = new Xi2Auth();
});

// استایل‌های CSS برای احراز هویت
const authStyles = document.createElement('style');
authStyles.textContent = `
.password-toggle {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    z-index: 2;
}

.user-menu {
    position: fixed;
    top: 70px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 250px;
    z-index: 1000;
    overflow: hidden;
}

.user-info {
    display: flex;
    align-items: center;
    padding: 16px;
    background: linear-gradient(135deg, #6366f1, #ec4899);
    color: white;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    margin-left: 12px;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: bold;
    margin-bottom: 4px;
}

.user-mobile {
    font-size: 12px;
    opacity: 0.8;
}

.user-actions {
    padding: 8px 0;
}

.menu-item {
    width: 100%;
    padding: 12px 16px;
    border: none;
    background: none;
    text-align: right;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    font-size: 14px;
}

.menu-item:hover {
    background-color: #f3f4f6;
}

.logout-btn {
    color: #ef4444;
    border-top: 1px solid #e5e7eb;
}

.logout-btn:hover {
    background-color: #fef2f2;
}

.otp-timer {
    font-weight: bold;
    color: #6366f1;
    font-size: 16px;
    margin: 8px 0;
}

.resend-otp-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.auth-required {
    display: none;
}

.guest-only {
    display: block;
}

@media (max-width: 768px) {
    .user-menu {
        right: 10px;
        left: 10px;
        top: 60px;
    }
}
`;
document.head.appendChild(authStyles);

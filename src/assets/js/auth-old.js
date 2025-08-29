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
                
                // تایید اعتبار توکن
                this.validateToken(token);
            } catch (error) {
                this.clearStoredAuth();
            }
        }
    }

    async validateToken(token) {
        try {
            const response = await fetch(this.API_BASE + 'validate-token.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();
            
            if (!result.valid) {
                this.clearStoredAuth();
                this.updateUIForGuestUser();
            }
        } catch (error) {
            console.error('خطا در اعتبارسنجی توکن:', error);
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
            const response = await fetch(this.API_BASE + 'resend-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mobile: this.currentMobile
                })
            });

            const result = await response.json();

            if (result.success) {
                this.startOTPTimer();
                return { success: true, message: result.message };
            } else {
                return { success: false, message: result.message };
            }

        } catch (error) {
            console.error('خطا در ارسال مجدد OTP:', error);
            return { success: false, message: 'خطا در برقراری ارتباط' };
        }
    }

    async logout() {
        try {
            const token = localStorage.getItem('xi2_token');
            
            if (token) {
                await fetch(this.API_BASE + 'logout.php', {
                    method: 'POST',
                    headers: {
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

    validateRegisterData(name, mobile, password) {
        // نام
        if (!name || name.trim().length < 2) {
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

        // بررسی قدرت رمز عبور
        if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(password)) {
            window.xi2App?.showNotification('رمز عبور باید شامل حروف و اعداد باشد', 'warning');
        }

        return true;
    }

    startOTPTimer() {
        let timeLeft = 120; // 2 دقیقه
        const resendBtn = document.getElementById('resendOTP');
        
        if (!resendBtn) return;

        resendBtn.disabled = true;
        
        this.otpTimer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            resendBtn.textContent = `ارسال مجدد (${minutes}:${seconds.toString().padStart(2, '0')})`;
            
            timeLeft--;
            
            if (timeLeft < 0) {
                this.stopOTPTimer();
                resendBtn.disabled = false;
                resendBtn.textContent = 'ارسال مجدد کد';
            }
        }, 1000);
    }

    stopOTPTimer() {
        if (this.otpTimer) {
            clearInterval(this.otpTimer);
            this.otpTimer = null;
        }
        
        const resendBtn = document.getElementById('resendOTP');
        if (resendBtn) {
            resendBtn.disabled = false;
            resendBtn.textContent = 'ارسال مجدد کد';
        }
    }

    storeAuthData(token, user) {
        localStorage.setItem('xi2_token', token);
        localStorage.setItem('xi2_user', JSON.stringify(user));
        
        // تنظیم انقضا (7 روز)
        const expiry = new Date();
        expiry.setDate(expiry.getDate() + 7);
        localStorage.setItem('xi2_token_expiry', expiry.getTime().toString());
    }

    clearStoredAuth() {
        localStorage.removeItem('xi2_token');
        localStorage.removeItem('xi2_user');
        localStorage.removeItem('xi2_token_expiry');
    }

    updateUIForLoggedInUser(user) {
        // به‌روزرسانی لینک‌های ناوبری
        const loginLink = document.querySelector('a[href="#login"]');
        const registerLink = document.querySelector('a[href="#register"]');
        
        if (loginLink) {
            loginLink.innerHTML = `👤 ${user.name}`;
            loginLink.href = '/dashboard.html';
            loginLink.onclick = null;
        }
        
        if (registerLink) {
            registerLink.innerHTML = '🚪 خروج';
            registerLink.href = '#logout';
            registerLink.onclick = (e) => {
                e.preventDefault();
                this.confirmLogout();
            };
        }

        // اضافه کردن منوی کاربری
        this.addUserMenu(user);
    }

    updateUIForGuestUser() {
        const loginLink = document.querySelector('a[href="#login"], a[href="/dashboard.html"]');
        const registerLink = document.querySelector('a[href="#logout"], a[href="#register"]');
        
        if (loginLink) {
            loginLink.textContent = 'ورود';
            loginLink.href = '#login';
            loginLink.onclick = null;
        }
        
        if (registerLink) {
            registerLink.textContent = 'شروع رایگان';
            registerLink.href = '#register';
            registerLink.onclick = null;
        }

        // حذف منوی کاربری
        const userMenu = document.querySelector('.user-menu');
        if (userMenu) {
            userMenu.remove();
        }
    }

    addUserMenu(user) {
        const existingMenu = document.querySelector('.user-menu');
        if (existingMenu) return;

        const nav = document.querySelector('.nav');
        if (!nav) return;

        const userMenu = document.createElement('div');
        userMenu.className = 'user-menu';
        userMenu.innerHTML = `
            <div class="user-avatar">
                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff&size=32" 
                     alt="${user.name}" class="avatar-img">
            </div>
            <div class="user-dropdown">
                <div class="dropdown-header">
                    <div class="user-info">
                        <div class="user-name">${user.name}</div>
                        <div class="user-mobile">${user.mobile}</div>
                    </div>
                </div>
                <div class="dropdown-menu">
                    <a href="/dashboard.html" class="dropdown-item">
                        <span>📊</span> داشبورد
                    </a>
                    <a href="/gallery.html" class="dropdown-item">
                        <span>🖼️</span> گالری من
                    </a>
                    <a href="/settings.html" class="dropdown-item">
                        <span>⚙️</span> تنظیمات
                    </a>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item logout-btn" onclick="window.xi2Auth.confirmLogout()">
                        <span>🚪</span> خروج
                    </button>
                </div>
            </div>
        `;

        nav.appendChild(userMenu);

        // مدیریت باز/بسته شدن منو
        const avatar = userMenu.querySelector('.user-avatar');
        const dropdown = userMenu.querySelector('.user-dropdown');
        
        avatar.addEventListener('click', () => {
            dropdown.classList.toggle('active');
        });

        // بستن منو با کلیک بیرون
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }

    confirmLogout() {
        if (confirm('آیا مطمئن هستید که می‌خواهید خارج شوید؟')) {
            this.logout();
        }
    }

    async logLastLogin() {
        try {
            const token = localStorage.getItem('xi2_token');
            await fetch(this.API_BASE + 'log-activity.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'login',
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.error('خطا در ثبت فعالیت:', error);
        }
    }

    getCurrentUser() {
        const userData = localStorage.getItem('xi2_user');
        return userData ? JSON.parse(userData) : null;
    }

    isLoggedIn() {
        const token = localStorage.getItem('xi2_token');
        const expiry = localStorage.getItem('xi2_token_expiry');
        
        if (!token || !expiry) return false;
        
        return new Date().getTime() < parseInt(expiry);
    }
}

// راه‌اندازی احراز هویت
document.addEventListener('DOMContentLoaded', () => {
    window.xi2Auth = new Xi2Auth();
});

// CSS برای منوی کاربری
const authStyles = document.createElement('style');
authStyles.textContent = `
.password-toggle {
    position: absolute;
    left: var(--spacing-sm);
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    padding: var(--spacing-xs);
}

.user-menu {
    position: relative;
}

.user-avatar {
    cursor: pointer;
    border-radius: 50%;
    overflow: hidden;
    width: 32px;
    height: 32px;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    min-width: 200px;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--transition);
}

.user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--border);
}

.user-name {
    font-weight: 600;
    color: var(--text-primary);
}

.user-mobile {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.dropdown-menu {
    padding: var(--spacing-sm) 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-lg);
    color: var(--text-secondary);
    text-decoration: none;
    transition: var(--transition);
    border: none;
    background: none;
    width: 100%;
    text-align: right;
    font-family: inherit;
    cursor: pointer;
}

.dropdown-item:hover {
    background: var(--gray-50);
    color: var(--text-primary);
}

.dropdown-divider {
    height: 1px;
    background: var(--border);
    margin: var(--spacing-sm) 0;
}

@media (max-width: 768px) {
    .user-dropdown {
        right: -50px;
        min-width: 180px;
    }
}
`;
document.head.appendChild(authStyles);

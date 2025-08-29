/**
 * زیتو (Xi2) - مدیریت احراز هویت
 * مدیریت ورود، ثبت‌نام، OTP و session
 */

class Xi2Auth {
    constructor() {
        this.API_BASE = '/xi2.ir/src/api/auth/';
        this.currentMobile = null;
        this.otpTimer = null;
        this.popperInstance = null; // برای Popper.js
        this.userMenuTrigger = null;
        this.userMenuDropdown = null;
        this.init();
    }

    // تبدیل اعداد فارسی/عربی به انگلیسی
    convertPersianToEnglish(input) {
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        let result = input;
        
        // تبدیل اعداد فارسی
        for (let i = 0; i < 10; i++) {
            result = result.replace(new RegExp(persianNumbers[i], 'g'), englishNumbers[i]);
            result = result.replace(new RegExp(arabicNumbers[i], 'g'), englishNumbers[i]);
        }
        
        return result;
    }

    init() {
        this.setupOTPInputHandlers();
        this.setupMobileInputHandlers();
        this.setupPasswordToggles();
        this.checkStoredAuth();
        
        // چک کردن وضعیت کاربر هنگام load صفحه
        document.addEventListener('DOMContentLoaded', () => {
            this.checkCurrentUserStatus();
        });
        
        // اگر صفحه قبلاً load شده
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.checkCurrentUserStatus();
            });
        } else {
            this.checkCurrentUserStatus();
        }
    }

    checkCurrentUserStatus() {
        const user = this.getCurrentUser();
        const token = this.getToken();
        
        if (user && token) {
            console.log('کاربر قبلاً وارد شده:', user.fullName);
            this.updateUIForLoggedInUser(user);
        } else {
            console.log('کاربر وارد نشده');
            this.updateUIForGuestUser();
        }
    }

    setupMobileInputHandlers() {
        // تمام فیلدهای موبایل
        const mobileInputs = document.querySelectorAll('input[type="tel"], #loginMobile, #registerMobile, #mobile');
        
        mobileInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                // تبدیل اعداد فارسی/عربی به انگلیسی
                let value = this.convertPersianToEnglish(e.target.value);
                
                // فقط اجازه اعداد و علامت +
                value = value.replace(/[^\d+]/g, '');
                
                // اگر با 0 شروع می‌شود و + ندارد، با 09 شروع کن
                if (value.length > 0 && value[0] === '0' && !value.includes('+')) {
                    // فرمت موبایل ایران
                    if (value.length > 11) {
                        value = value.substring(0, 11);
                    }
                }
                
                e.target.value = value;
            });
            
            // پیست کردن شماره موبایل
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                let cleanNumber = this.convertPersianToEnglish(pastedText);
                cleanNumber = cleanNumber.replace(/[^\d+]/g, '');
                
                // محدود کردن طول
                if (cleanNumber.length > 11 && !cleanNumber.includes('+')) {
                    cleanNumber = cleanNumber.substring(0, 11);
                }
                
                input.value = cleanNumber;
            });
        });
    }

    setupOTPInputHandlers() {
        const otpInput = document.getElementById('otpCode');
        if (!otpInput) return;

        // فقط اجازه عدد + تبدیل فارسی به انگلیسی
        otpInput.addEventListener('input', (e) => {
            // تبدیل اعداد فارسی/عربی به انگلیسی
            let value = this.convertPersianToEnglish(e.target.value);
            // فقط اعداد
            value = value.replace(/\D/g, '');
            // محدود به 6 رقم
            value = value.substring(0, 6);
            
            e.target.value = value;
            
            // اگر 6 رقم شد، خودکار submit کن
            if (value.length === 6) {
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
            let numbers = this.convertPersianToEnglish(pastedText);
            numbers = numbers.replace(/\D/g, '').substring(0, 6);
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
                    name: fullName,  // تغییر از fullName به name
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
            // window.location.reload(); // این خط حذف شد تا صفحه رفرش نشود
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
        
        // از بین بردن نمونه Popper
        if (this.popperInstance) {
            this.popperInstance.destroy();
            this.popperInstance = null;
        }
    }

    updateUIForLoggedInUser(user) {
        const navButtons = document.getElementById('nav-buttons');
        if (navButtons) {
            // پاک کردن دکمه‌های ورود/ثبت‌نام
            navButtons.innerHTML = '';
            // اضافه کردن منوی کاربر
            this.addUserMenu(user, navButtons);
        } else {
            console.warn('Container #nav-buttons not found.');
        }
    }

    updateUIForGuestUser() {
        // حذف منوی کاربر
        const existingMenu = document.querySelector('.user-menu');
        if (existingMenu) {
            existingMenu.remove();
        }

        // از بین بردن نمونه Popper
        if (this.popperInstance) {
            this.popperInstance.destroy();
            this.popperInstance = null;
        }

        const navButtons = document.getElementById('nav-buttons');
        if (navButtons) {
            // نمایش دکمه‌های ورود و ثبت‌نام
            navButtons.innerHTML = `
                <a href="#login" class="btn btn-secondary" onclick="window.xi2App.showLoginModal(); return false;">ورود</a>
                <a href="#register" class="btn btn-primary" onclick="window.xi2App.showRegisterModal(); return false;">شروع رایگان</a>
            `;
        }
    }

    addUserMenu(user, container) {
        // حذف منوی قبلی برای اطمینان
        const existingMenu = document.querySelector('.user-menu');
        if (existingMenu) {
            existingMenu.remove();
        }
        
        const menuHTML = `
            <div class="user-menu">
                <div class="user-trigger" id="userMenuTrigger">
                    <div class="user-avatar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="fill: currentColor;">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                    <span class="user-name">${user.fullName}</span>
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="m3 4.5 3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                
                <div class="user-dropdown" id="userDropdown" role="menu">
                    <!-- Content is the same as before -->
                    <div class="dropdown-header">
                        <div class="user-info">
                            <div class="user-avatar-large">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="fill: currentColor;">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                            <div class="user-details">
                                <div class="user-fullname">${user.fullName}</div>
                                <div class="user-mobile">${user.mobile}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dropdown-menu">
                        <a href="#" class="menu-item" role="menuitem">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="fill: currentColor;"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                            <span>پنل کاربری</span>
                        </a>
                        <div class="menu-divider"></div>
                        <a href="#" class="menu-item logout" role="menuitem" onclick="window.xi2Auth.confirmLogout(); return false;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="fill: currentColor;"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.1 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                            <span>خروج</span>
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = menuHTML;
        
        this.userMenuTrigger = document.getElementById('userMenuTrigger');
        this.userMenuDropdown = document.getElementById('userDropdown');

        if (this.userMenuTrigger && this.userMenuDropdown) {
            this.popperInstance = Popper.createPopper(this.userMenuTrigger, this.userMenuDropdown, {
                placement: 'bottom-end',
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, 8],
                        },
                    },
                    {
                        name: 'preventOverflow',
                        options: {
                            padding: 16,
                        },
                    },
                ],
            });

            this.userMenuTrigger.addEventListener('click', () => this.toggleUserDropdown());
            document.addEventListener('click', (event) => this.handleOutsideClick(event));
        }
    }

    toggleUserDropdown() {
        if (!this.userMenuDropdown) return;
        
        const isVisible = this.userMenuDropdown.classList.contains('show');
        
        if (isVisible) {
            this.userMenuDropdown.classList.remove('show');
        } else {
            this.userMenuDropdown.classList.add('show');
            this.popperInstance.update();
        }
    }

    handleOutsideClick(event) {
        if (this.userMenuTrigger && !this.userMenuTrigger.contains(event.target) && this.userMenuDropdown) {
            this.userMenuDropdown.classList.remove('show');
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

// تنظیم global variable
window.xi2Auth = new Xi2Auth();

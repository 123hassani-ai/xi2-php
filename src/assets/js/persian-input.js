/**
 * زیتو (Xi2) - مدیریت ورودی‌های فارسی
 * تبدیل اعداد فارسی و مدیریت input های RTL
 * طراحی شده طبق پرامپت شماره 3 - استفاده از PersianUtils موجود
 */

class PersianInputHandler {
    constructor() {
        this.persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        this.arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        this.englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        this.init();
    }
    
    /**
     * راه‌اندازی اولیه
     */
    init() {
        this.setupAutoConversion();
        this.setupValidation();
        this.setupDirectionHandling();
        
        console.log('Persian Input Handler initialized');
    }
    
    /**
     * تبدیل خودکار اعداد فارسی
     */
    setupAutoConversion() {
        // تبدیل در زمان واقعی برای input های مشخص
        document.addEventListener('input', (e) => {
            const target = e.target;
            
            // فقط برای input های مشخص
            if (this.shouldConvertNumbers(target)) {
                const originalValue = target.value;
                const convertedValue = this.convertPersianToEnglish(originalValue);
                
                if (originalValue !== convertedValue) {
                    target.value = convertedValue;
                    this.showConversionHint(target, originalValue, convertedValue);
                }
            }
        });
        
        // تبدیل در هنگام paste
        document.addEventListener('paste', (e) => {
            const target = e.target;
            
            if (this.shouldConvertNumbers(target)) {
                setTimeout(() => {
                    const originalValue = target.value;
                    const convertedValue = this.convertPersianToEnglish(originalValue);
                    
                    if (originalValue !== convertedValue) {
                        target.value = convertedValue;
                        this.showConversionHint(target, originalValue, convertedValue);
                    }
                }, 10);
            }
        });
    }
    
    /**
     * تنظیم اعتبارسنجی
     */
    setupValidation() {
        document.addEventListener('input', (e) => {
            const target = e.target;
            
            if (target.matches('input[name="mobile"]')) {
                this.validateMobile(target);
            }
            
            if (target.matches('input[name="otp_code"]')) {
                this.validateOTP(target);
            }
        });
    }
    
    /**
     * مدیریت جهت متن
     */
    setupDirectionHandling() {
        document.addEventListener('focus', (e) => {
            const target = e.target;
            
            if (target.matches('input[name="mobile"], input[name="otp_code"]')) {
                // input های عددی: چپ به راست
                target.style.direction = 'ltr';
                target.style.textAlign = 'left';
            } else if (target.matches('input[name="full_name"]')) {
                // input های متنی: راست به چپ
                target.style.direction = 'rtl';
                target.style.textAlign = 'right';
            }
        });
    }
    
    /**
     * تبدیل اعداد فارسی/عربی به انگلیسی
     */
    convertPersianToEnglish(input) {
        if (!input) return input;
        
        let result = input.toString();
        
        // تبدیل اعداد فارسی
        for (let i = 0; i < 10; i++) {
            result = result.replace(new RegExp(this.persianNumbers[i], 'g'), this.englishNumbers[i]);
            result = result.replace(new RegExp(this.arabicNumbers[i], 'g'), this.englishNumbers[i]);
        }
        
        return result;
    }
    
    /**
     * بررسی نیاز به تبدیل اعداد
     */
    shouldConvertNumbers(element) {
        return element.matches(`
            input[name="mobile"],
            input[name="otp_code"],
            input[type="tel"],
            input.persian-numbers,
            .numeric-input input
        `);
    }
    
    /**
     * اعتبارسنجی شماره موبایل
     */
    validateMobile(element) {
        const value = this.convertPersianToEnglish(element.value);
        const isValid = this.isValidMobile(value);
        
        element.classList.toggle('valid', isValid && value.length === 11);
        element.classList.toggle('invalid', !isValid && value.length > 0);
        
        // نمایش پیام راهنما
        this.showMobileHint(element, value, isValid);
    }
    
    /**
     * اعتبارسنجی کد OTP
     */
    validateOTP(element) {
        const value = this.convertPersianToEnglish(element.value);
        const isValid = this.isValidOTP(value);
        
        element.classList.toggle('valid', isValid);
        element.classList.toggle('invalid', !isValid && value.length > 0);
        
        // محدود کردن به 6 رقم
        if (value.length > 6) {
            element.value = value.substring(0, 6);
        }
        
        // فقط اعداد
        element.value = value.replace(/[^\d]/g, '');
    }
    
    /**
     * بررسی معتبر بودن شماره موبایل
     */
    isValidMobile(mobile) {
        // حذف فاصله‌ها و علائم
        const cleanMobile = mobile.replace(/[\s\-\(\)]/g, '');
        
        // الگوهای معتبر شماره موبایل ایران
        const patterns = [
            /^09\d{9}$/,           // 09123456789
            /^\+989\d{9}$/,        // +989123456789
            /^00989\d{9}$/,        // 00989123456789
            /^9\d{9}$/             // 9123456789
        ];
        
        return patterns.some(pattern => pattern.test(cleanMobile));
    }
    
    /**
     * بررسی معتبر بودن کد OTP
     */
    isValidOTP(otp) {
        return /^\d{6}$/.test(otp);
    }
    
    /**
     * نمایش راهنمای تبدیل
     */
    showConversionHint(element, original, converted) {
        const hint = document.createElement('div');
        hint.className = 'conversion-hint';
        hint.innerHTML = `
            <small>تبدیل شد: ${original} → ${converted}</small>
        `;
        
        // حذف راهنمای قبلی
        const existingHint = element.parentNode.querySelector('.conversion-hint');
        if (existingHint) {
            existingHint.remove();
        }
        
        // اضافه کردن راهنمای جدید
        element.parentNode.appendChild(hint);
        
        // حذف خودکار بعد از 3 ثانیه
        setTimeout(() => {
            if (hint.parentNode) {
                hint.remove();
            }
        }, 3000);
    }
    
    /**
     * نمایش راهنمای شماره موبایل
     */
    showMobileHint(element, value, isValid) {
        let hintText = '';
        
        if (value.length === 0) {
            hintText = 'شماره موبایل خود را وارد کنید';
        } else if (value.length < 11) {
            hintText = `${11 - value.length} رقم دیگر وارد کنید`;
        } else if (value.length === 11 && isValid) {
            hintText = 'شماره موبایل معتبر است ✓';
        } else if (value.length === 11 && !isValid) {
            hintText = 'شماره موبایل نامعتبر است';
        } else if (value.length > 11) {
            hintText = 'شماره موبایل بیش از حد طولانی است';
        }
        
        this.showHint(element, hintText, isValid ? 'success' : 'warning');
    }
    
    /**
     * نمایش راهنما
     */
    showHint(element, text, type = 'info') {
        let hintElement = element.parentNode.querySelector('.input-hint');
        
        if (!hintElement) {
            hintElement = document.createElement('div');
            hintElement.className = 'input-hint';
            element.parentNode.appendChild(hintElement);
        }
        
        hintElement.textContent = text;
        hintElement.className = `input-hint ${type}`;
    }
    
    /**
     * فرمت کردن شماره موبایل برای نمایش
     */
    formatMobileForDisplay(mobile) {
        const clean = this.convertPersianToEnglish(mobile).replace(/\D/g, '');
        
        if (clean.length === 11 && clean.startsWith('09')) {
            // فرمت: 0912 345 6789
            return clean.replace(/(\d{4})(\d{3})(\d{4})/, '$1 $2 $3');
        }
        
        return mobile;
    }
    
    /**
     * اضافه کردن استایل‌های CSS
     */
    addStyles() {
        if (document.getElementById('persian-input-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'persian-input-styles';
        styles.textContent = `
            .conversion-hint {
                font-size: 0.75rem;
                color: #059669;
                margin-top: 0.25rem;
                opacity: 0.8;
            }
            
            .input-hint {
                font-size: 0.75rem;
                margin-top: 0.25rem;
            }
            
            .input-hint.success {
                color: #059669;
            }
            
            .input-hint.warning {
                color: #d97706;
            }
            
            .input-hint.error {
                color: #dc2626;
            }
            
            .input-hint.info {
                color: #6b7280;
            }
            
            input.valid {
                border-color: #059669;
                box-shadow: 0 0 0 1px rgba(5, 150, 105, 0.1);
            }
            
            input.invalid {
                border-color: #dc2626;
                box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.1);
            }
            
            /* اعداد فارسی در placeholder */
            input[placeholder*="۰"],
            input[placeholder*="۱"],
            input[placeholder*="۲"],
            input[placeholder*="۳"],
            input[placeholder*="۴"],
            input[placeholder*="۵"],
            input[placeholder*="۶"],
            input[placeholder*="۷"],
            input[placeholder*="۸"],
            input[placeholder*="۹"] {
                font-family: 'Vazirmatn', Arial, sans-serif;
            }
        `;
        
        document.head.appendChild(styles);
    }
    
    /**
     * متد عمومی برای استفاده خارجی
     */
    static convert(input) {
        const handler = new PersianInputHandler();
        return handler.convertPersianToEnglish(input);
    }
}

// راه‌اندازی خودکار
document.addEventListener('DOMContentLoaded', () => {
    const persianInputHandler = new PersianInputHandler();
    persianInputHandler.addStyles();
    
    // در دسترس قرار دادن برای استفاده جهانی
    window.PersianInput = persianInputHandler;
    window.convertPersianNumbers = (input) => persianInputHandler.convertPersianToEnglish(input);
});

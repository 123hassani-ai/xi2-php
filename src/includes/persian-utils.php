<?php
/**
 * زیتو (Xi2) - ابزارهای فارسی
 * مدیریت تبدیل اعداد فارسی/عربی و اعتبارسنجی
 */

class PersianUtils {
    
    // آرایه‌های تبدیل اعداد
    private static $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private static $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private static $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    /**
     * تبدیل اعداد فارسی/عربی به انگلیسی
     * @param string $input متن ورودی
     * @return string متن با اعداد انگلیسی
     */
    public static function convertToEnglishNumbers($input) {
        // بررسی نوع ورودی - فقط رشته‌ها پردازش می‌شوند
        if (!is_string($input)) {
            return is_numeric($input) ? (string)$input : '';
        }
        
        if (empty($input)) {
            return $input;
        }
        
        $result = $input;
        
        // تبدیل اعداد فارسی
        for ($i = 0; $i < 10; $i++) {
            $result = str_replace(self::$persianNumbers[$i], self::$englishNumbers[$i], $result);
        }
        
        // تبدیل اعداد عربی
        for ($i = 0; $i < 10; $i++) {
            $result = str_replace(self::$arabicNumbers[$i], self::$englishNumbers[$i], $result);
        }
        
        // لاگ تبدیل در صورت تغییر
        if ($input !== $result) {
            error_log("Xi2 Persian Convert: Input: {$input} -> Output: {$result}");
        }
        
        return $result;
    }
    
    /**
     * تبدیل متن کامل و پاک‌سازی
     * @param string $input متن ورودی
     * @return string متن پاک شده
     */
    public static function sanitizeInput($input) {
        if (empty($input)) {
            return '';
        }
        
        // تبدیل اعداد
        $result = self::convertToEnglishNumbers($input);
        
        // حذف فاصله‌های اضافی
        $result = trim($result);
        $result = preg_replace('/\s+/', ' ', $result);
        
        // حذف کاراکترهای مخرب
        $result = strip_tags($result);
        
        return $result;
    }
    
    /**
     * اعتبارسنجی شماره موبایل ایرانی
     * @param string $mobile شماره موبایل
     * @return string|false شماره استاندارد یا false
     */
    public static function validateMobile($mobile) {
        if (empty($mobile)) {
            return false;
        }
        
        // تبدیل اعداد فارسی/عربی
        $mobile = self::convertToEnglishNumbers($mobile);
        
        // حذف فاصله‌ها و علائم اضافی
        $mobile = preg_replace('/[^\d+]/', '', $mobile);
        
        // حالت‌های مختلف شماره موبایل ایران
        $patterns = [
            '/^09\d{9}$/',           // 09123456789
            '/^\+989\d{9}$/',        // +989123456789
            '/^00989\d{9}$/',        // 00989123456789
            '/^9\d{9}$/',            // 9123456789
        ];
        
        // تبدیل به فرمت استاندارد 09xxxxxxxxx
        if (preg_match('/^\+989(\d{9})$/', $mobile, $matches)) {
            $mobile = '09' . $matches[1];
        } elseif (preg_match('/^00989(\d{9})$/', $mobile, $matches)) {
            $mobile = '09' . $matches[1];
        } elseif (preg_match('/^9(\d{9})$/', $mobile, $matches)) {
            $mobile = '09' . $matches[1];
        }
        
        // بررسی نهایی فرمت
        if (preg_match('/^09\d{9}$/', $mobile)) {
            return $mobile;
        }
        
        return false;
    }
    
    /**
     * اعتبارسنجی کد OTP
     * @param string $otp کد OTP
     * @return string|false کد استاندارد یا false
     */
    public static function validateOTP($otp) {
        if (empty($otp)) {
            return false;
        }
        
        // تبدیل اعداد فارسی/عربی
        $otp = self::convertToEnglishNumbers($otp);
        
        // حذف فاصله‌ها
        $otp = preg_replace('/\s+/', '', $otp);
        
        // بررسی طول و محتوا (6 رقم)
        if (preg_match('/^\d{6}$/', $otp)) {
            return $otp;
        }
        
        return false;
    }
    
    /**
     * اعتبارسنجی شماره تلفن عمومی (موبایل یا ثابت)
     * @param string $phone شماره تلفن
     * @return array آرایه حاوی type و number
     */
    public static function validatePhone($phone) {
        $result = ['type' => null, 'number' => null];
        
        if (empty($phone)) {
            return $result;
        }
        
        // بررسی موبایل
        $mobile = self::validateMobile($phone);
        if ($mobile) {
            $result['type'] = 'mobile';
            $result['number'] = $mobile;
            return $result;
        }
        
        // تبدیل اعداد برای تلفن ثابت
        $phone = self::convertToEnglishNumbers($phone);
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        // بررسی تلفن ثابت (کدهای شهرهای مختلف)
        $landlinePatterns = [
            '/^0\d{2,3}\d{8}$/',     // کد شهر + شماره
            '/^0\d{10}$/',           // 11 رقمی
        ];
        
        foreach ($landlinePatterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                $result['type'] = 'landline';
                $result['number'] = $phone;
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * فرمت کردن شماره موبایل برای نمایش
     * @param string $mobile شماره موبایل
     * @param string $format فرمت نمایش (dots, spaces, none)
     * @return string شماره فرمت شده
     */
    public static function formatMobile($mobile, $format = 'dots') {
        $mobile = self::validateMobile($mobile);
        if (!$mobile) {
            return $mobile;
        }
        
        switch ($format) {
            case 'dots':
                return substr($mobile, 0, 4) . '.' . substr($mobile, 4, 3) . '.' . substr($mobile, 7);
            
            case 'spaces':
                return substr($mobile, 0, 4) . ' ' . substr($mobile, 4, 3) . ' ' . substr($mobile, 7);
            
            case 'dash':
                return substr($mobile, 0, 4) . '-' . substr($mobile, 4, 3) . '-' . substr($mobile, 7);
                
            case 'international':
                return '+98' . substr($mobile, 1);
            
            default:
                return $mobile;
        }
    }
    
    /**
     * اعتبارسنجی کد ملی ایران
     * @param string $nationalCode کد ملی
     * @return string|false کد ملی معتبر یا false
     */
    public static function validateNationalCode($nationalCode) {
        if (empty($nationalCode)) {
            return false;
        }
        
        // تبدیل اعداد
        $nationalCode = self::convertToEnglishNumbers($nationalCode);
        $nationalCode = preg_replace('/[^\d]/', '', $nationalCode);
        
        // بررسی طول
        if (strlen($nationalCode) !== 10) {
            return false;
        }
        
        // بررسی اعداد یکسان
        if (preg_match('/^(\d)\1{9}$/', $nationalCode)) {
            return false;
        }
        
        // الگوریتم چک کد ملی
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($nationalCode[$i]) * (10 - $i);
        }
        
        $remainder = $sum % 11;
        $checkDigit = intval($nationalCode[9]);
        
        if ($remainder < 2) {
            return ($checkDigit === $remainder) ? $nationalCode : false;
        } else {
            return ($checkDigit === (11 - $remainder)) ? $nationalCode : false;
        }
    }
    
    /**
     * تبدیل تاریخ میلادی به شمسی
     * @param string $date تاریخ میلادی
     * @return string تاریخ شمسی
     */
    public static function toShamsiDate($date = null) {
        if (!$date) {
            $date = date('Y-m-d H:i:s');
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if (!$timestamp) {
            return $date;
        }
        
        // استفاده از کتابخانه jDateTime در صورت وجود
        if (class_exists('jDateTime')) {
            return jDateTime::date('Y/m/d H:i', $timestamp);
        }
        
        // تبدیل ساده (نیاز به پیاده‌سازی دقیق‌تر)
        return date('Y/m/d H:i', $timestamp);
    }
    
    /**
     * لاگ گرفتن از تبدیلات با جزئیات
     * @param string $operation عملیات انجام شده
     * @param string $input ورودی
     * @param string $output خروجی
     * @param array $context اطلاعات اضافی
     */
    public static function logConversion($operation, $input, $output, $context = []) {
        $logData = [
            'operation' => $operation,
            'input' => $input,
            'output' => $output,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        if (!empty($context)) {
            $logData['context'] = $context;
        }
        
        error_log('Xi2 PersianUtils: ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
}

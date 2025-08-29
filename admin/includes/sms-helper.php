<?php
/**
 * کلاس مدیریت SMS برای زیتو (Xi2)
 * پشتیبانی از 0098SMS با روش‌های Link API و Web Service
 */

class SMSHelper {
    private $settings;
    
    public function __construct($sms_settings) {
        $this->settings = $sms_settings;
    }
    
    /**
     * ارسال SMS با انتخاب خودکار بهترین روش
     */
    public function sendSMS($phone, $message, $type = 'general') {
        // اعتبارسنجی
        if (!$this->validatePhone($phone)) {
            return $this->formatResponse(false, 'شماره موبایل نامعتبر است');
        }
        
        if (empty(trim($message))) {
            return $this->formatResponse(false, 'متن پیامک الزامی است');
        }
        
        if (mb_strlen($message) > 160) {
            return $this->formatResponse(false, 'متن پیامک نباید بیشتر از 160 کاراکتر باشد');
        }
        
        // تلاش ارسال با Web Service (اولویت اول)
        if (class_exists('SoapClient') && extension_loaded('soap')) {
            $result = $this->sendWithWebService($phone, $message);
            if ($result['success']) {
                return $result;
            }
        }
        
        // تلاش ارسال با Link API (روش جایگزین)
        return $this->sendWithLinkAPI($phone, $message);
    }
    
    /**
     * ارسال با Web Service API
     */
    private function sendWithWebService($phone, $message) {
        try {
            ini_set("soap.wsdl_cache_enabled", "0");
            
            $sms_client = new SoapClient(
                'https://webservice.0098sms.com/service.asmx?wsdl',
                array(
                    'encoding' => 'UTF-8',
                    'connection_timeout' => 30,
                    'cache_wsdl' => WSDL_CACHE_NONE
                )
            );
            
            $parameters = array(
                'username' => $this->settings['api_username'],
                'password' => $this->settings['api_password'],
                'mobileno' => $phone,
                'pnlno' => $this->settings['sender_number'],
                'text' => $message,
                'isflash' => false
            );
            
            $response = $sms_client->SendSMS($parameters);
            $result_code = trim($response->SendSMSResult);
            
            // بررسی کدهای موفقیت
            if ($result_code === '2' || (is_numeric($result_code) && strlen($result_code) >= 9)) {
                return $this->formatResponse(true, 'پیامک با موفقیت ارسال شد', [
                    'method' => 'webservice',
                    'response_code' => $result_code,
                    'sms_id' => strlen($result_code) >= 9 ? $result_code : null
                ]);
            } else {
                return $this->formatResponse(false, $this->getErrorMessage($result_code), [
                    'method' => 'webservice',
                    'response_code' => $result_code
                ]);
            }
            
        } catch (Exception $e) {
            return $this->formatResponse(false, 'خطا در Web Service: ' . $e->getMessage(), [
                'method' => 'webservice',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * ارسال با Link API
     */
    private function sendWithLinkAPI($phone, $message) {
        // ساخت URL بدون encode کردن پسورد
        $url = 'https://0098sms.com/sendsmslink.aspx?' . 
               'FROM=' . urlencode($this->settings['sender_number']) . 
               '&TO=' . urlencode($phone) . 
               '&TEXT=' . urlencode($message) . 
               '&USERNAME=' . urlencode($this->settings['api_username']) . 
               '&PASSWORD=' . $this->settings['api_password'] . // خام بدون encode
               '&DOMAIN=0098';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Xi2-SMS/1.0',
            CURLOPT_HEADER => false,
            CURLOPT_NOBODY => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return ['success' => false, 'message' => 'خطا در اتصال: ' . $curl_error];
        }
        
        if ($http_code !== 200) {
            return ['success' => false, 'message' => 'خطای سرور: HTTP ' . $http_code];
        }
        
        // پردازش پاسخ - ممکن است شامل HTML باشد
        $response_lines = explode("\n", trim($response));
        $response_code = 'unknown';
        
        foreach ($response_lines as $line) {
            $line = trim($line);
            if (is_numeric($line)) {
                $response_code = $line;
                break;
            }
            if (preg_match('/^(\d+)/', $line, $matches)) {
                $response_code = $matches[1];
                break;
            }
        }
        
        if ($response_code === '0') {
            $this->logSMS($phone, $message, 'sent', $response_code, 'admin');
            return ['success' => true, 'message' => 'پیامک با موفقیت ارسال شد'];
        }
        
        $error_messages = [
            '1' => 'شماره گیرنده اشتباه است',
            '2' => 'گیرنده تعریف نشده است',
            '9' => 'اعتبار پیامک شما کافی نیست',
            '12' => 'نام کاربری و کلمه عبور اشتباه است',
            '14' => 'سقف ارسال روزانه پر شده است',
            '16' => 'عدم مجوز شماره برای ارسال از لینک'
        ];
        
        $error_msg = $error_messages[$response_code] ?? 'خطای نامشخص';
        $this->logSMS($phone, $message, 'failed', $response_code, 'admin');
        return ['success' => false, 'message' => $error_msg . ' (کد: ' . $response_code . ')'];
    }
    
    /**
     * بررسی وضعیت پیامک (فقط برای Web Service)
     */
    public function checkDeliveryStatus($sms_id) {
        if (!class_exists('SoapClient') || !$sms_id) {
            return $this->formatResponse(false, 'امکان بررسی وضعیت وجود ندارد');
        }
        
        try {
            ini_set("soap.wsdl_cache_enabled", "0");
            
            $sms_client = new SoapClient(
                'https://webservice.0098sms.com/service.asmx?wsdl',
                array('encoding' => 'UTF-8')
            );
            
            $parameters = array(
                'username' => $this->settings['api_username'],
                'password' => $this->settings['api_password'],
                'smsid' => $sms_id
            );
            
            $response = $sms_client->smsdeliveryState($parameters);
            $status_code = trim($response->smsdeliveryStateResult);
            
            return $this->formatResponse(true, $this->getDeliveryStatus($status_code), [
                'status_code' => $status_code,
                'sms_id' => $sms_id
            ]);
            
        } catch (Exception $e) {
            return $this->formatResponse(false, 'خطا در بررسی وضعیت: ' . $e->getMessage());
        }
    }
    
    /**
     * دریافت اعتبار باقیمانده
     */
    public function getRemainingCredit() {
        if (!class_exists('SoapClient')) {
            return $this->formatResponse(false, 'سرویس Web Service در دسترس نیست');
        }
        
        try {
            ini_set("soap.wsdl_cache_enabled", "0");
            
            $sms_client = new SoapClient(
                'https://webservice.0098sms.com/service.asmx?wsdl',
                array('encoding' => 'UTF-8')
            );
            
            $parameters = array(
                'username' => $this->settings['api_username'],
                'password' => $this->settings['api_password']
            );
            
            $response = $sms_client->RemainSms($parameters);
            $credit = $response->remainResult;
            
            return $this->formatResponse(true, 'اعتبار باقیمانده: ' . number_format($credit) . ' پیامک', [
                'credit' => $credit
            ]);
            
        } catch (Exception $e) {
            return $this->formatResponse(false, 'خطا در دریافت اعتبار: ' . $e->getMessage());
        }
    }
    
    /**
     * اعتبارسنجی شماره موبایل
     */
    private function validatePhone($phone) {
        return preg_match('/^09\d{9}$/', $phone);
    }
    
    /**
     * تفسیر کدهای خطا - مطابق مستندات کامل
     */
    private function getErrorMessage($code) {
        $errors = [
            '0' => 'عملیات با موفقیت به پایان رسید',
            '1' => 'شماره گیرنده اشتباه است',
            '2' => 'گیرنده تعریف نشده است',
            '3' => 'فرستنده تعریف نشده است',
            '4' => 'متن تنظیم نشده است',
            '5' => 'نام کاربری تنظیم نشده است',
            '6' => 'کلمه عبور تنظیم نشده است',
            '7' => 'نام دامین تنظیم نشده است',
            '8' => 'مجوز شما باطل شده است',
            '9' => 'اعتبار پیامک شما کافی نیست',
            '10' => 'برای این شماره لینک تعریف نشده است',
            '11' => 'عدم مجوز برای اتصال لینک',
            '12' => 'نام کاربری و کلمه ی عبور اشتباه است',
            '13' => 'کاراکتر غیرمجاز در متن وجود دارد',
            '14' => 'سقف ارسال روزانه پر شده است',
            '16' => 'عدم مجوز شماره برای ارسال از لینک',
            '17' => 'خطا در شماره پنل. لطفا با پشتیبانی تماس بگیرید',
            '18' => 'اتمام تاریخ اعتبار شماره پنل. برای استفاده تمدید شود',
            '19' => 'تنظیمات کد OTP انجام نشده است',
            '20' => 'فرمت کد OTP صحیح نیست',
            '21' => 'تنظیمات کد OTP توسط ادمین تایید نشده است',
            '22' => 'اطلاعات مالک شماره ثبت و تایید نشده است',
            '23' => 'هنوز اجازه ارسال به این شماره پنل داده نشده است',
            '24' => 'ارسال از IP غیرمجاز انجام شده است'
        ];
        
        return $errors[$code] ?? "خطای نامشخص (کد: $code)";
    }
    
    /**
     * تفسیر وضعیت تحویل
     */
    private function getDeliveryStatus($code) {
        $statuses = [
            '4' => 'مخاطب دریافت پیام‌های تبلیغاتی خود را بسته است',
            '5' => 'رسیده به گوشی',
            '6' => 'نرسیده به گوشی',
            '7' => 'وضعیت دریافت پیامک منقضی شده است',
            '8' => 'وضعیت پیامک نامشخص است',
            '9' => 'اطلاعات مالک شماره، ثبت و تایید نشده است',
            '21' => 'هنوز اجازه ارسال به این شماره پنل داده نشده است',
            '31' => 'ارسال از IP غیر مجاز انجام شده است'
        ];
        
        return $statuses[$code] ?? "وضعیت نامشخص (کد: $code)";
    }
    
    /**
     * فرمت استاندارد پاسخ
     */
    private function formatResponse($success, $message, $data = []) {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * ثبت لاگ پیامک
     */
    private function logSMS($recipient, $message, $status, $response, $sent_by = 'system') {
        try {
            require_once '../src/database/config.php';
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("INSERT INTO sms_logs (recipient, message, status, provider_response, sent_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$recipient, $message, $status, $response, $sent_by]);
            
            return $db->lastInsertId();
        } catch (Exception $e) {
            error_log('SMS Log Error: ' . $e->getMessage());
            return false;
        }
    }
}

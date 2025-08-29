<?php
// تست سریع SMSHelper
require_once 'admin/includes/sms-helper.php';
require_once 'src/database/config.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM sms_settings WHERE provider = '0098' AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $sms_settings = $stmt->fetch();
    
    if ($sms_settings) {
        echo "✅ تنظیمات SMS یافت شد<br>";
        echo "نام کاربری: " . htmlspecialchars($sms_settings['api_username']) . "<br>";
        echo "شماره ارسال‌کننده: " . htmlspecialchars($sms_settings['sender_number']) . "<br><br>";
        
        // تست کلاس SMSHelper
        $sms_helper = new SMSHelper($sms_settings);
        echo "✅ کلاس SMSHelper بارگذاری شد<br>";
        
        // تست اعتبار باقیمانده
        $credit_result = $sms_helper->getRemainingCredit();
        if ($credit_result['success']) {
            echo "💰 " . $credit_result['message'] . "<br>";
        } else {
            echo "⚠️ خطا در دریافت اعتبار: " . $credit_result['message'] . "<br>";
        }
        
        // تست اعتبارسنجی شماره (غیرمستقیم)
        echo "<br>🔍 تست اعتبارسنجی شماره:<br>";
        
        $valid_test = $sms_helper->sendSMS('09123456789', 'تست', 'test');
        echo "09123456789: " . ($valid_test['message'] !== 'شماره موبایل نامعتبر است' ? "✅ معتبر" : "❌ نامعتبر") . "<br>";
        
        $invalid_test = $sms_helper->sendSMS('912345678', 'تست', 'test');
        echo "912345678: " . ($invalid_test['message'] !== 'شماره موبایل نامعتبر است' ? "✅ معتبر" : "❌ نامعتبر") . "<br>";
        
    } else {
        echo "❌ تنظیمات SMS یافت نشد";
    }
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage();
}
?>

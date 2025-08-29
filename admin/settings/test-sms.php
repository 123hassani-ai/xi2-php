<?php
/**
 * زیتو (Xi2) - تست ارسال پیامک
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth-check.php';
require_once '../../src/database/config.php';
require_once '../includes/sms-helper.php';
require_once '../includes/path-config.php';

$page_title = 'تست ارسال پیامک';
$css_path = '../';

$message = '';
$message_type = '';
$test_result = '';

// Debug info
$debug_info = [];
$debug_info[] = "POST Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A');
$debug_info[] = "POST Data: " . print_r($_POST, true);
$debug_info[] = "Session: " . print_r($_SESSION, true);

try {
    $db = Database::getInstance()->getConnection();
    
    // ایجاد جدول لاگ پیامک اگر وجود ندارد
    $create_logs_table = "
    CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(15),
        message TEXT,
        message_type ENUM('otp', 'test', 'notification') DEFAULT 'test',
        sent_by VARCHAR(50),
        status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
        provider_response TEXT,
        user_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_logs_table);
    
    // دریافت تنظیمات SMS
    $stmt = $db->query("SELECT * FROM sms_settings WHERE provider = '0098' AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $sms_settings = $stmt->fetch();
    
    if (!$sms_settings) {
        $message = 'ابتدا تنظیمات SMS را پیکربندی کنید';
        $message_type = 'warning';
    }
    
    // پردازش فرم تست
    if ($_POST && isset($_POST['send_test']) && $sms_settings) {
        $test_phone = trim($_POST['test_phone'] ?? '');
        $test_message = trim($_POST['test_message'] ?? '');
        
        // اعتبارسنجی
        $errors = [];
        if (empty($test_phone)) $errors[] = 'شماره موبایل الزامی است';
        if (!preg_match('/^09\d{9}$/', $test_phone)) $errors[] = 'فرمت شماره موبایل صحیح نیست';
        if (empty($test_message)) $errors[] = 'متن پیامک الزامی است';
        if (mb_strlen($test_message) > 160) $errors[] = 'متن پیامک نباید بیشتر از 160 کاراکتر باشد';
        
        if (empty($errors)) {
            // استفاده از کلاس SMSHelper جدید
            $sms_helper = new SMSHelper($sms_settings);
            $sms_result = $sms_helper->sendSMS($test_phone, $test_message, 'test');
            
            // تحلیل نتیجه
            if ($sms_result['success']) {
                $status = 'sent';
                $test_result = $sms_result['message'] . "\n";
                $test_result .= "متد ارسال: " . ($sms_result['data']['method'] ?? 'نامشخص') . "\n";
                $test_result .= "کد پاسخ: " . ($sms_result['data']['response_code'] ?? 'N/A');
                
                if (isset($sms_result['data']['sms_id']) && $sms_result['data']['sms_id']) {
                    $test_result .= "\nشناسه پیامک برای پیگیری: " . $sms_result['data']['sms_id'];
                }
                
                $message = 'پیامک تست با موفقیت ارسال شد';
                $message_type = 'success';
            } else {
                $status = 'failed';
                $test_result = "خطا در ارسال پیامک\n";
                $test_result .= "متد: " . ($sms_result['data']['method'] ?? 'نامشخص') . "\n";
                $test_result .= "پیام خطا: " . $sms_result['message'];
                
                if (isset($sms_result['data']['response_code'])) {
                    $test_result .= "\nکد خطا: " . $sms_result['data']['response_code'];
                }
                
                $message = 'خطا در ارسال پیامک: ' . $sms_result['message'];
                $message_type = 'danger';
            }
            
            // ثبت لاگ
            try {
                $stmt = $db->prepare("
                    INSERT INTO sms_logs 
                    (recipient, message, message_type, sent_by, status, provider_response) 
                    VALUES (?, ?, 'test', ?, ?, ?)
                ");
                
                $log_response = json_encode($sms_result['data']);
                
                $stmt->execute([
                    $test_phone,
                    $test_message,
                    get_admin_username(),
                    $status,
                    $log_response
                ]);
                
                error_log("Xi2 Admin: Test SMS - Phone: {$test_phone} - Status: {$status} - Method: " . ($sms_result['data']['method'] ?? 'unknown'));
                
            } catch (Exception $e) {
                error_log('Xi2 Admin: Failed to log SMS test: ' . $e->getMessage());
            }
            
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'danger';
        }
    }
    
} catch (Exception $e) {
    $message = 'خطا در سیستم: ' . $e->getMessage();
    $message_type = 'danger';
    error_log('Xi2 Admin: SMS Test Error: ' . $e->getMessage());
}

/**
 * تفسیر کدهای خطای SMS
 */
function getSMSErrorMessage($code) {
    $errors = [
        '0' => 'عملیات موفق',
        '1' => 'شماره گیرنده اشتباه است',
        '2' => 'گیرنده تعریف نشده است',
        '9' => 'اعتبار پیامک شما کافی نیست',
        '12' => 'نام کاربری و کلمه عبور اشتباه است',
        '14' => 'سقف ارسال روزانه پر شده است',
        '16' => 'عدم مجوز شماره برای ارسال از لینک'
    ];
    
    return $errors[$code] ?? 'خطای نامشخص';
}

include '../includes/header.php';
?>

<!-- Debug Panel -->
<?php if (isset($_GET['debug'])): ?>
<div class="card" style="margin-bottom: 20px; background-color: #f8f9fa;">
    <div class="card-header">
        <h3 class="card-title">🐛 Debug Information</h3>
    </div>
    <div class="card-body">
        <pre style="font-size: 12px; max-height: 300px; overflow-y: scroll;">
<?php echo implode("\n", $debug_info); ?>
        </pre>
    </div>
</div>
<?php endif; ?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?>">
    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>" style="margin-left: 8px;"></i>
    <?php echo $message; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
            ارسال پیامک آزمایشی
        </h3>
    </div>
    <div class="card-body">
        <?php if (!$sms_settings): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle" style="margin-left: 8px;"></i>
                ابتدا باید تنظیمات SMS را پیکربندی کنید.
                <a href="<?php echo admin_url('settings/sms.php'); ?>" class="btn btn-sm btn-primary" style="margin-right: 15px;">
                    تنظیمات SMS
                </a>
            </div>
        <?php else: ?>
            <form method="POST" id="testSmsForm">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="test_phone">
                                <i class="fas fa-mobile-alt" style="margin-left: 5px;"></i>
                                شماره موبایل
                            </label>
                            <input 
                                type="text" 
                                id="test_phone" 
                                name="test_phone" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($sms_settings['test_number'] ?? ($_POST['test_phone'] ?? '')); ?>"
                                required
                                pattern="09\d{9}"
                                placeholder="09123456789"
                                maxlength="11"
                            >
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>وضعیت سرویس</label>
                            <div style="padding: 12px 15px; background: #dcfce7; border: 2px solid #bbf7d0; border-radius: 8px; color: #166534;">
                                <i class="fas fa-check-circle" style="margin-left: 5px;"></i>
                                سرویس فعال است
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="test_message">
                        <i class="fas fa-comment" style="margin-left: 5px;"></i>
                        متن پیامک
                        <small style="color: #6b7280; font-weight: normal;">
                            (<span id="charCount">0</span>/160 کاراکتر)
                        </small>
                    </label>
                    <textarea 
                        id="test_message" 
                        name="test_message" 
                        class="form-control" 
                        rows="4"
                        maxlength="160"
                        required
                        placeholder="متن پیامک تست خود را اینجا بنویسید..."
                    ><?php echo htmlspecialchars($_POST['test_message'] ?? 'سلام! این یک پیامک تست از سیستم زیتو است. تاریخ: ' . date('Y/m/d H:i')); ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="send_test" class="btn btn-success" id="sendBtn">
                        <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                        ارسال پیامک تست
                    </button>
                    
                    <a href="<?php echo admin_url('logs/sms-logs.php'); ?>" class="btn btn-info" style="margin-right: 15px;">
                        <i class="fas fa-list-alt" style="margin-left: 8px;"></i>
                        مشاهده لاگ‌ها
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($test_result): ?>
<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-terminal" style="margin-left: 8px;"></i>
            نتیجه تست
        </h3>
    </div>
    <div class="card-body">
        <div class="test-result <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
            <?php echo nl2br(htmlspecialchars($test_result)); ?>
        </div>
        
        <?php if ($message_type === 'success'): ?>
        <div style="margin-top: 15px; color: #059669;">
            <i class="fas fa-info-circle" style="margin-left: 5px;"></i>
            <strong>نکته:</strong> پیامک ممکن است چند دقیقه طول بکشد تا به گوشی مقصد برسد.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- اطلاعات سرویس -->
<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle" style="margin-left: 8px;"></i>
            اطلاعات سرویس
        </h3>
    </div>
    <div class="card-body">
        <?php if ($sms_settings): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <strong>ارائه‌دهنده:</strong>
                <span class="badge badge-info">0098SMS</span>
            </div>
            <div>
                <strong>شماره ارسال‌کننده:</strong>
                <span class="badge badge-info"><?php echo htmlspecialchars($sms_settings['sender_number']); ?></span>
            </div>
            <div>
                <strong>نام کاربری API:</strong>
                <span class="badge badge-info"><?php echo htmlspecialchars($sms_settings['api_username']); ?></span>
            </div>
            <div>
                <strong>آخرین به‌روزرسانی:</strong>
                <span class="badge badge-info"><?php echo date('Y/m/d H:i', strtotime($sms_settings['updated_at'])); ?></span>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            تنظیمات SMS پیکربندی نشده است.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('test_message');
    const charCount = document.getElementById('charCount');
    const form = document.getElementById('testSmsForm');
    const sendBtn = document.getElementById('sendBtn');
    
    // شمارش کاراکتر
    function updateCharCount() {
        const length = messageTextarea.value.length;
        charCount.textContent = length;
        
        if (length > 160) {
            charCount.style.color = '#ef4444';
            messageTextarea.style.borderColor = '#ef4444';
        } else if (length > 140) {
            charCount.style.color = '#f59e0b';
            messageTextarea.style.borderColor = '#f59e0b';
        } else {
            charCount.style.color = '#6b7280';
            messageTextarea.style.borderColor = '#e2e8f0';
        }
    }
    
    messageTextarea.addEventListener('input', updateCharCount);
    updateCharCount(); // Initial count
    
    // منع ارسال مجدد سریع
    form.addEventListener('submit', function() {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left: 8px;"></i>در حال ارسال...';
        
        setTimeout(function() {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane" style="margin-left: 8px;"></i>ارسال پیامک تست';
        }, 5000);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
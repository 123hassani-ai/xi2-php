<?php
/**
 * زیتو (Xi2) - تست ارسال پیامک با پشتیبانی اعداد فارسی
 */
require_once '../includes/auth-check.php';
require_once '../../src/database/config.php';
require_once '../../src/includes/persian-utils.php';
require_once '../includes/path-config.php';

$page_title = 'تست ارسال پیامک';

$message = '';
$message_type = '';
$test_result = '';

try {
    $db = Database::getInstance()->getConnection();
    
    // دریافت تنظیمات SMS
    $stmt = $db->query("SELECT * FROM sms_settings WHERE provider = '0098' AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $sms_settings = $stmt->fetch();
    
    if (!$sms_settings) {
        $message = 'ابتدا تنظیمات SMS را پیکربندی کنید';
        $message_type = 'warning';
    }
    
    // پردازش فرم تست
    if ($_POST && isset($_POST['send_test']) && $sms_settings) {
        // استفاده از PersianUtils برای پاک‌سازی و تبدیل
        $test_phone_raw = trim($_POST['test_phone'] ?? '');
        $test_message = PersianUtils::sanitizeInput($_POST['test_message'] ?? '');
        
        // تبدیل شماره موبایل با PersianUtils
        $test_phone = PersianUtils::validateMobile($test_phone_raw);
        
        if (!$test_phone) {
            $message = 'شماره موبایل صحیح نیست. فرمت مجاز: 09123456789';
            $message_type = 'danger';
            
            // لاگ تبدیل ناموفق
            PersianUtils::logConversion('admin_sms_test_mobile_failed', $test_phone_raw, 'false', [
                'admin_user' => get_admin_username()
            ]);
            
        } elseif (empty($test_message)) {
            $message = 'متن پیامک الزامی است';
            $message_type = 'danger';
        } else {
            // لاگ تبدیل موفق
            if ($test_phone_raw !== $test_phone) {
                PersianUtils::logConversion('admin_sms_test_mobile_converted', $test_phone_raw, $test_phone, [
                    'admin_user' => get_admin_username()
                ]);
            }
            
            // ارسال مستقیم با cURL
            $url = 'https://0098sms.com/sendsmslink.aspx?' . 
                   'FROM=' . urlencode($sms_settings['sender_number']) . 
                   '&TO=' . urlencode($test_phone) . // شماره تبدیل شده
                   '&TEXT=' . urlencode($test_message) . 
                   '&USERNAME=' . urlencode($sms_settings['api_username']) . 
                   '&PASSWORD=' . $sms_settings['api_password'] . // خام بدون encode
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
                $message = 'خطا در اتصال: ' . $curl_error;
                $message_type = 'danger';
                $test_result = 'cURL Error: ' . $curl_error;
            } elseif ($http_code !== 200) {
                $message = 'خطای سرور: HTTP ' . $http_code;
                $message_type = 'danger';
                $test_result = 'HTTP Code: ' . $http_code;
            } else {
                // پاسخ ممکن است شامل HTML باشد، فقط اولین خط مهم است
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
                    $message = 'پیامک با موفقیت ارسال شد!';
                    $message_type = 'success';
                    $test_result = 'موفقیت آمیز! کد پاسخ: ' . $response_code;
                    $status = 'sent';
                } else {
                    $error_messages = [
                        '1' => 'شماره گیرنده اشتباه است',
                        '2' => 'گیرنده تعریف نشده است', 
                        '9' => 'اعتبار پیامک شما کافی نیست',
                        '12' => 'نام کاربری و کلمه عبور اشتباه است',
                        '14' => 'سقف ارسال روزانه پر شده است',
                        '16' => 'عدم مجوز شماره برای ارسال از لینک'
                    ];
                    
                    $error_msg = $error_messages[$response_code] ?? 'خطای نامشخص';
                    $message = 'خطا در ارسال: ' . $error_msg . ' (کد: ' . $response_code . ')';
                    $message_type = 'danger';
                    $test_result = 'کد پاسخ: ' . $response_code . ' - ' . $error_msg;
                    $status = 'failed';
                }
                
                // ثبت لاگ
                try {
                    $stmt = $db->prepare("
                        INSERT INTO sms_logs 
                        (recipient, message, message_type, sent_by, status, provider_response) 
                        VALUES (?, ?, 'test', ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $test_phone,
                        $test_message,
                        get_admin_username(),
                        $status ?? 'failed',
                        $response_code
                    ]);
                    
                } catch (Exception $e) {
                    error_log('SMS Log Error: ' . $e->getMessage());
                }
            }
        }
    }
    
} catch (Exception $e) {
    $message = 'خطا در سیستم: ' . $e->getMessage();
    $message_type = 'danger';
    error_log('SMS Test Error: ' . $e->getMessage());
}

include '../includes/header.php';
?>

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
            <form method="POST" action="">
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
                                value="<?php echo htmlspecialchars($sms_settings['test_number'] ?? '09120540123'); ?>"
                                required
                                pattern="09\d{9}"
                                placeholder="09120540123"
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
                            (حداکثر 160 کاراکتر)
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
                    <button type="submit" name="send_test" class="btn btn-success">
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
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
        
        <div class="alert alert-info">
            <strong>کدهای پاسخ مهم:</strong><br>
            <strong>0</strong>: موفقیت | 
            <strong>9</strong>: اعتبار کافی نیست | <strong>12</strong>: اطلاعات ورود اشتباه |
            <strong>16</strong>: عدم مجوز شماره
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            تنظیمات SMS پیکربندی نشده است.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

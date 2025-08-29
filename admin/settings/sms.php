<?php
/**
 * زیتو (Xi2) - تنظیمات پیامک
 */
require_once '../includes/auth-check.php';
require_once '../../src/database/config.php';
require_once '../includes/path-config.php';

$page_title = 'تنظیمات پیامک';
$css_path = '../';

$message = '';
$message_type = '';

try {
    $db = Database::getInstance()->getConnection();
    
    // ایجاد جدول تنظیمات SMS اگر وجود ندارد
    $create_table = "
    CREATE TABLE IF NOT EXISTS sms_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider VARCHAR(50) DEFAULT '0098',
        api_username VARCHAR(100),
        api_password VARCHAR(255),
        sender_number VARCHAR(20),
        test_number VARCHAR(15),
        is_active TINYINT(1) DEFAULT 1,
        updated_by VARCHAR(50),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($create_table);
    
    // دریافت تنظیمات فعلی
    $stmt = $db->query("SELECT * FROM sms_settings WHERE provider = '0098' ORDER BY id DESC LIMIT 1");
    $current_settings = $stmt->fetch();
    
    // اگر تنظیماتی وجود ندارد، مقادیر پیش‌فرض از مستندات را بگذاریم
    if (!$current_settings) {
        $current_settings = [
            'api_username' => 'zsms8829',
            'api_password' => 'j494moo*O^HU',
            'sender_number' => '3000164545',
            'test_number' => '',
            'is_active' => 1
        ];
    }
    
    // پردازش فرم
    if ($_POST && isset($_POST['save_settings'])) {
        $api_username = trim($_POST['api_username'] ?? '');
        $api_password = trim($_POST['api_password'] ?? '');
        $sender_number = trim($_POST['sender_number'] ?? '');
        $test_number = trim($_POST['test_number'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // اعتبارسنجی
        $errors = [];
        if (empty($api_username)) $errors[] = 'نام کاربری API الزامی است';
        if (empty($api_password)) $errors[] = 'رمز عبور API الزامی است';
        if (empty($sender_number)) $errors[] = 'شماره ارسال‌کننده الزامی است';
        if (!empty($test_number) && !preg_match('/^09\d{9}$/', $test_number)) {
            $errors[] = 'شماره تست باید فرمت صحیح موبایل ایران داشته باشد';
        }
        
        if (empty($errors)) {
            try {
                // حذف تنظیمات قبلی
                $db->exec("DELETE FROM sms_settings WHERE provider = '0098'");
                
                // درج تنظیمات جدید
                $stmt = $db->prepare("
                    INSERT INTO sms_settings 
                    (provider, api_username, api_password, sender_number, test_number, is_active, updated_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    '0098',
                    $api_username,
                    $api_password,
                    $sender_number,
                    $test_number,
                    $is_active,
                    get_admin_username()
                ]);
                
                $message = 'تنظیمات با موفقیت ذخیره شد';
                $message_type = 'success';
                
                // Log
                error_log('Xi2 Admin: SMS Settings Updated - User: ' . get_admin_username());
                
                // به‌روزرسانی تنظیمات فعلی
                $current_settings = [
                    'api_username' => $api_username,
                    'api_password' => $api_password,
                    'sender_number' => $sender_number,
                    'test_number' => $test_number,
                    'is_active' => $is_active
                ];
                
            } catch (Exception $e) {
                $message = 'خطا در ذخیره تنظیمات: ' . $e->getMessage();
                $message_type = 'danger';
                error_log('Xi2 Admin: SMS Settings Save Error: ' . $e->getMessage());
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'danger';
        }
    }
    
} catch (Exception $e) {
    $message = 'خطا در اتصال به پایگاه داده: ' . $e->getMessage();
    $message_type = 'danger';
    error_log('Xi2 Admin: SMS Settings DB Error: ' . $e->getMessage());
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
            <i class="fas fa-cog" style="margin-left: 8px;"></i>
            پیکربندی سرویس SMS 0098
        </h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="api_username">
                            <i class="fas fa-user" style="margin-left: 5px;"></i>
                            نام کاربری API
                        </label>
                        <input 
                            type="text" 
                            id="api_username" 
                            name="api_username" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($current_settings['api_username'] ?? ''); ?>"
                            required
                            placeholder="مثال: zsms8829"
                        >
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="api_password">
                            <i class="fas fa-key" style="margin-left: 5px;"></i>
                            رمز عبور API
                        </label>
                        <input 
                            type="password" 
                            id="api_password" 
                            name="api_password" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($current_settings['api_password'] ?? ''); ?>"
                            required
                            placeholder="رمز عبور API خود را وارد کنید"
                        >
                        <small class="text-muted">برای امنیت، رمز عبور مخفی نمایش داده می‌شود</small>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="sender_number">
                            <i class="fas fa-phone" style="margin-left: 5px;"></i>
                            شماره ارسال‌کننده (Panel Number)
                        </label>
                        <input 
                            type="text" 
                            id="sender_number" 
                            name="sender_number" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($current_settings['sender_number'] ?? ''); ?>"
                            required
                            placeholder="مثال: 3000164545"
                        >
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="test_number">
                            <i class="fas fa-mobile-alt" style="margin-left: 5px;"></i>
                            شماره تست (اختیاری)
                        </label>
                        <input 
                            type="text" 
                            id="test_number" 
                            name="test_number" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($current_settings['test_number'] ?? ''); ?>"
                            placeholder="09123456789"
                            pattern="09\d{9}"
                        >
                        <small class="text-muted">برای تست ارسال پیامک</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1"
                        <?php echo ($current_settings['is_active'] ?? 1) ? 'checked' : ''; ?>
                        style="margin-left: 8px;"
                    >
                    <i class="fas fa-toggle-on" style="margin-left: 5px;"></i>
                    سرویس پیامک فعال باشد
                </label>
            </div>
            
            <div class="form-group">
                <button type="submit" name="save_settings" class="btn btn-primary">
                    <i class="fas fa-save" style="margin-left: 8px;"></i>
                    ذخیره تنظیمات
                </button>
                
                <a href="<?php echo admin_url('settings/test-sms.php'); ?>" class="btn btn-success" style="margin-right: 15px;">
                    <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                    تست ارسال پیامک
                </a>
            </div>
        </form>
    </div>
</div>

<!-- راهنمای استفاده -->
<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle" style="margin-left: 8px;"></i>
            راهنمای استفاده
        </h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h4>نکات مهم:</h4>
            <ul style="margin: 10px 0; padding-right: 20px;">
                <li>تمام فیلدها به جز شماره تست الزامی هستند</li>
                <li>اطلاعات API از پنل کاربری سرویس 0098sms.com دریافت کنید</li>
                <li>شماره ارسال‌کننده باید از پنل شما تأیید شده باشد</li>
                <li>شماره تست باید فرمت صحیح موبایل ایران (09xxxxxxxxx) داشته باشد</li>
                <li>پس از ذخیره تنظیمات، حتماً تست ارسال انجام دهید</li>
            </ul>
        </div>
        
        <div class="alert alert-warning">
            <h4>اطلاعات فعلی (از مستندات):</h4>
            <ul style="margin: 10px 0; padding-right: 20px;">
                <li><strong>نام کاربری:</strong> zsms8829</li>
                <li><strong>شماره پنل:</strong> 3000164545</li>
                <li><strong>API URL:</strong> https://0098sms.com/sendsmslink.aspx</li>
                <li><strong>WebService:</strong> https://webservice.0098sms.com/service.asmx?wsdl</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
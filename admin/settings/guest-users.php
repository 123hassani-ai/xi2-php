<?php
/**
 * زیتو (Xi2) - تنظیمات کاربران میهمان - نسخه کارآمد
 */

require_once __DIR__ . '/../includes/auth-check.php';

$page_title = 'تنظیمات کاربران میهمان';
$current_page = 'guest-users';

// فعال‌سازی debug mode
$debug_mode = isset($_GET['debug']);
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// پردازش فرم
$message = '';
$messageType = '';
$settings = [
    'max_uploads' => 10,
    'max_file_size' => 5,
    'allowed_types' => 'jpg,jpeg,png,gif,pdf,doc,docx'
];

// متغیرهای آماری
$total_guest_uploads = 0;
$unique_guests = 0;

// تابع پاکسازی ورودی
function sanitize_string($input) {
    return trim(htmlspecialchars($input));
}

try {
    require_once __DIR__ . '/../../src/database/config.php';
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // خواندن تنظیمات فعلی از دیتابیس
    try {
        $stmt = $connection->query("SELECT setting_key, setting_value FROM guest_settings");
        $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (!empty($db_settings)) {
            $settings = array_merge($settings, $db_settings);
        }
    } catch (Exception $e) {
        if ($debug_mode) {
            $message = "خطا در خواندن تنظیمات: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // پردازش فرم در صورت ارسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        // بروزرسانی تنظیمات
        $max_uploads = intval($_POST['max_uploads']);
        $max_file_size = intval($_POST['max_file_size']);
        $allowed_types = sanitize_string($_POST['allowed_types']);
        
        $new_values = [
            'max_uploads' => $max_uploads,
            'max_file_size' => $max_file_size,
            'allowed_types' => $allowed_types
        ];
        
        // ذخیره در دیتابیس
        try {
            $sql = "INSERT INTO guest_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            
            $success_count = 0;
            foreach ($new_values as $key => $value) {
                $stmt = $connection->prepare($sql);
                if ($stmt->execute([$key, $value])) {
                    $success_count++;
                }
            }
            
            if ($success_count === count($new_values)) {
                $settings = $new_values;
                $message = "تنظیمات با موفقیت بروزرسانی شد";
                $messageType = 'success';
            } else {
                $message = "خطا در بروزرسانی برخی تنظیمات";
                $messageType = 'error';
            }
            
        } catch (Exception $e) {
            $message = "خطا در بروزرسانی تنظیمات: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // آمارگیری
    try {
        $stmt = $connection->query("SELECT COUNT(*) as total FROM guest_uploads");
        $total_guest_uploads = $stmt->fetch()['total'];
        
        $stmt = $connection->query("SELECT COUNT(DISTINCT device_id) as unique_devices FROM guest_uploads");
        $unique_guests = $stmt->fetch()['unique_devices'];
    } catch (Exception $e) {
        if ($debug_mode) {
            $message .= " | خطا در آمارگیری: " . $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    $message = "خطای اتصال به پایگاه داده: " . $e->getMessage();
    $messageType = 'error';
    if ($debug_mode) {
        $message .= "\n" . $e->getTraceAsString();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .settings-card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 2em;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        transition: transform 0.2s;
    }
    
    .btn:hover {
        transform: translateY(-2px);
    }
    
    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: bold;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        white-space: pre-wrap;
    }
    
    .debug-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        border: 1px solid #dee2e6;
    }
</style>

<div class="container">
    <h1>⚙️ تنظیمات کاربران میهمان</h1>
    
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $total_guest_uploads ?></div>
            <div>کل آپلودها</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $unique_guests ?></div>
            <div>کاربران منحصر به فرد</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $settings['max_uploads'] ?></div>
            <div>حداکثر آپلود</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $settings['max_file_size'] ?>MB</div>
            <div>حداکثر اندازه فایل</div>
        </div>
    </div>
    
    <div class="settings-card">
        <h2>🔧 تنظیمات محدودیت‌ها</h2>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>🔢 حداکثر تعداد آپلود برای هر کاربر میهمان:</label>
                <input type="number" name="max_uploads" value="<?= htmlspecialchars($settings['max_uploads']) ?>" min="1" max="100" required>
                <small>بین 1 تا 100</small>
            </div>
            
            <div class="form-group">
                <label>📏 حداکثر اندازه فایل (مگابایت):</label>
                <input type="number" name="max_file_size" value="<?= htmlspecialchars($settings['max_file_size']) ?>" min="1" max="50" required>
                <small>بین 1 تا 50 مگابایت</small>
            </div>
            
            <div class="form-group">
                <label>📄 انواع فایل مجاز:</label>
                <textarea name="allowed_types" rows="3" required><?= htmlspecialchars($settings['allowed_types']) ?></textarea>
                <small>پسوندهای فایل را با کاما از هم جدا کنید (مثال: jpg,png,pdf)</small>
            </div>
            
            <button type="submit" name="update_settings" class="btn">
                💾 ذخیره تنظیمات
            </button>
        </form>
    </div>
    
    <?php if ($debug_mode): ?>
        <div class="debug-info">
            <h3>🔍 اطلاعات Debug</h3>
            <p><strong>تنظیمات فعلی:</strong> <?= json_encode($settings, JSON_UNESCAPED_UNICODE) ?></p>
            <p><strong>آمار:</strong> <?= $total_guest_uploads ?> آپلود از <?= $unique_guests ?> کاربر</p>
            <p><strong>زمان:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

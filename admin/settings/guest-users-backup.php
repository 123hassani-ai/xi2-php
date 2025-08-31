<?php
/**
 * زیتو (Xi2) - تنظیمات کاربران میهمان
 * مدیریت محدودیت‌ها و تنظیمات کاربران میهمان
 * طراحی شده طبق پرامپت شماره 3
 */

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../../src/includes/logger.php';

$page_title = 'تنظیمات کاربران میهمان';
$current_page = 'guest-users';

// فعال‌سازی debug mode
$debug_mode = isset($_GET['debug']) || isset($_SESSION['debug_mode']);
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $_SESSION['debug_mode'] = true;
}

$logger = Xi2Logger::getInstance();
$logger->info("Loading Guest Users Settings Page", ['user' => get_admin_username(), 'debug_mode' => $debug_mode]);

// پردازش فرم
$message = '';
$messageType = '';
$settings = [
    'max_uploads' => 10,
    'max_file_size' => 5,
    'allowed_types' => 'jpg,jpeg,png,gif,pdf,doc,docx'
];

$logger->debug("Default settings loaded", $settings);

try {
    $logger->info("Starting database operations");
    
    require_once __DIR__ . '/../../src/database/config.php';
    $logger->success("Database config loaded successfully");
    
    $db = Database::getInstance();
    $logger->success("Database instance created");
    
    $connection = $db->getConnection();
    $logger->success("Database connection established");
    
    // تست اتصال
    $testQuery = $connection->query("SELECT 1 as test");
    $testResult = $testQuery->fetch();
    $logger->success("Database connection test passed", ['result' => $testResult]);
    
    // خواندن تنظیمات فعلی از دیتابیس
    $logger->info("Reading current settings from database");
    try {
        $stmt = $connection->query("SELECT setting_key, setting_value FROM guest_settings");
        $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $logger->database("SELECT", "SELECT setting_key, setting_value FROM guest_settings", [], $db_settings);
        
        if (!empty($db_settings)) {
            $old_settings = $settings;
            $settings = array_merge($settings, $db_settings);
            $logger->success("Settings merged from database", [
                'old' => $old_settings,
                'from_db' => $db_settings,
                'merged' => $settings
            ]);
        } else {
            $logger->warning("No settings found in database, using defaults");
        }
    } catch (Exception $e) {
        $logger->error("Error reading settings from database: " . $e->getMessage());
    }
    
    // پردازش فرم در صورت ارسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        $logger->info("Form submission detected");
        $logger->form("update_settings", $_POST);
        
        // بروزرسانی تنظیمات
        $max_uploads = intval($_POST['max_uploads']);
        $max_file_size = intval($_POST['max_file_size']);
        $allowed_types = sanitize_string($_POST['allowed_types']);
        
        $new_values = [
            'max_uploads' => $max_uploads,
            'max_file_size' => $max_file_size,
            'allowed_types' => $allowed_types
        ];
        $logger->info("Processed form values", $new_values);
        
        // ذخیره در دیتابیس
        $logger->info("Saving settings to database");
        try {
            $sql = "
                INSERT INTO guest_settings (setting_key, setting_value, updated_at) 
                VALUES 
                ('max_uploads', ?, NOW()),
                ('max_file_size', ?, NOW()),
                ('allowed_types', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = NOW()
            ";
            
            $stmt = $connection->prepare($sql);
            $params = [$max_uploads, $max_file_size, $allowed_types];
            
            $logger->database("PREPARE", $sql, $params);
            
            $result = $stmt->execute($params);
            $affected_rows = $stmt->rowCount();
            
            $logger->database("EXECUTE", "INSERT...ON DUPLICATE KEY UPDATE", $params, [
                'success' => $result,
                'affected_rows' => $affected_rows
            ]);
            
            if ($result) {
                // بروزرسانی array محلی
                $old_local_settings = $settings;
                $settings['max_uploads'] = $max_uploads;
                $settings['max_file_size'] = $max_file_size;
                $settings['allowed_types'] = $allowed_types;
                
                $logger->success("Settings updated successfully", [
                    'old' => $old_local_settings,
                    'new' => $settings,
                    'affected_rows' => $affected_rows
                ]);
                
                $message = 'تنظیمات با موفقیت بروزرسانی شد';
                $messageType = 'success';
                
                // تأیید نهایی - خواندن مجدد از دیتابیس
                $verify_stmt = $connection->query("SELECT setting_key, setting_value FROM guest_settings");
                $verify_settings = $verify_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $logger->info("Verification: Settings after update", $verify_settings);
                
            } else {
                $logger->error("Database update failed", [
                    'statement_error' => $stmt->errorInfo(),
                    'connection_error' => $connection->errorInfo()
                ]);
                $message = 'خطا در بروزرسانی تنظیمات';
                $messageType = 'error';
            }
            
        } catch (Exception $e) {
            $logger->error("Exception during database update", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $message = 'خطا در بروزرسانی تنظیمات: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
        
        $message = 'تنظیمات با موفقیت بروزرسانی شد';
        $messageType = 'success';
    }
    
    // آمار میهمان‌ها
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM guest_uploads");
        $total_guest_uploads = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(DISTINCT device_id) as count FROM guest_uploads");
        $unique_guests = $stmt->fetch()['count'] ?? 0;
    } catch (Exception $e) {
        // جدول guest_uploads هنوز وجود ندارد
        $total_guest_uploads = 0;
        $unique_guests = 0;
    }
    
} catch (Exception $e) {
    $message = 'خطا در دریافت تنظیمات: ' . $e->getMessage();
    $messageType = 'error';
    $total_guest_uploads = 0;
    $unique_guests = 0;
}

function sanitize_string($input) {
    return trim(htmlspecialchars($input));
}

include __DIR__ . '/../includes/header.php';
?>

<div class="settings-container">
    <div class="page-header">
        <h1>⚙️ تنظیمات کاربران میهمان</h1>
        <p>مدیریت محدودیت‌ها و تنظیمات کاربران میهمان</p>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <div class="settings-grid">
        <!-- فرم تنظیمات -->
        <div class="card">
            <div class="card-header">
                <h2>📊 تنظیمات اصلی</h2>
            </div>
            <div class="card-body">
                <form method="POST" class="settings-form">
                    <input type="hidden" name="update_settings" value="1">
                    
                    <div class="form-group">
                        <label for="max_uploads">حداکثر تعداد آپلود:</label>
                        <input type="number" id="max_uploads" name="max_uploads" 
                               value="<?= htmlspecialchars($settings['max_uploads']) ?>" 
                               class="form-control" min="1" max="50" required>
                        <small class="form-help">تعداد فایل‌هایی که یک میهمان می‌تواند آپلود کند</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_file_size">حداکثر حجم فایل (مگابایت):</label>
                        <input type="number" id="max_file_size" name="max_file_size" 
                               value="<?= htmlspecialchars($settings['max_file_size']) ?>" 
                               class="form-control" min="1" max="100" required>
                        <small class="form-help">حداکثر حجم فایل برای آپلود میهمان‌ها</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="allowed_types">فرمت‌های مجاز:</label>
                        <input type="text" id="allowed_types" name="allowed_types" 
                               value="<?= htmlspecialchars($settings['allowed_types']) ?>" 
                               class="form-control" required>
                        <small class="form-help">فرمت‌های فایل مجاز، با کاما جدا شده (مثل: jpg,png,pdf)</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            ذخیره تنظیمات
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- آمار و گزارشات -->
        <div class="card">
            <div class="card-header">
                <h2>📈 آمار کاربران میهمان</h2>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">📁</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($total_guest_uploads ?? 0) ?></div>
                            <div class="stat-label">کل آپلودها</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($unique_guests ?? 0) ?></div>
                            <div class="stat-label">میهمان‌های منحصربفرد</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $settings['max_uploads'] ?></div>
                            <div class="stat-label">محدودیت فعلی</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">💾</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $settings['max_file_size'] ?> MB</div>
                            <div class="stat-label">حجم مجاز</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- راهنما و نکات -->
        <div class="card full-width">
            <div class="card-header">
                <h2>💡 راهنما و نکات</h2>
            </div>
            <div class="card-body">
                <div class="tips-grid">
                    <div class="tip-item">
                        <div class="tip-icon">🎯</div>
                        <div class="tip-content">
                            <h4>محدودیت مناسب</h4>
                            <p>توصیه می‌شود محدودیت را بین ۵ تا ۱۵ فایل قرار دهید تا کاربران تشویق به ثبت‌نام شوند.</p>
                        </div>
                    </div>
                    
                    <div class="tip-item">
                        <div class="tip-icon">📏</div>
                        <div class="tip-content">
                            <h4>حجم فایل</h4>
                            <p>حجم ۵ مگابایت برای میهمان‌ها مناسب است. برای کاربران پلاس حجم بیشتری در نظر بگیرید.</p>
                        </div>
                    </div>
                    
                    <div class="tip-item">
                        <div class="tip-icon">🔧</div>
                        <div class="tip-content">
                            <h4>فرمت‌های امن</h4>
                            <p>فقط فرمت‌های امن و رایج را اجازه دهید. از فرمت‌های اجرایی مثل exe, bat, sh خودداری کنید.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 2rem;
    text-align: center;
}

.page-header h1 {
    color: #1a202c;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: #6b7280;
    font-size: 1.1rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.full-width {
    grid-column: 1 / -1;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

.form-actions {
    text-align: left;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.stat-icon {
    font-size: 2rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a202c;
    line-height: 1;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.tip-item {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 0.5rem;
}

.tip-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.tip-content h4 {
    margin-bottom: 0.5rem;
    color: #1e40af;
}

.tip-content p {
    color: #1e40af;
    margin: 0;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php if ($debug_mode): ?>
<div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem;">
    <h3>🐛 Debug Panel</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
        <div>
            <h4>Current Settings Array:</h4>
            <pre style="background: white; padding: 1rem; border-radius: 0.25rem; font-size: 0.875rem;"><?php print_r($settings); ?></pre>
        </div>
        <div>
            <h4>Database Values:</h4>
            <pre style="background: white; padding: 1rem; border-radius: 0.25rem; font-size: 0.875rem;"><?php
            try {
                $stmt = $db->query("SELECT setting_key, setting_value, updated_at FROM guest_settings ORDER BY setting_key");
                $current_db_values = $stmt->fetchAll(PDO::FETCH_ASSOC);
                print_r($current_db_values);
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
            ?></pre>
        </div>
    </div>
    <p style="margin-top: 1rem; font-size: 0.875rem; color: #6c757d;">
        Debug mode is enabled. Add <code>?debug</code> to URL to see debug info.
        <a href="?" style="color: #dc3545;">Disable Debug</a>
    </p>
</div>
<?php else: ?>
<div style="text-align: center; margin-top: 1rem;">
    <a href="?debug" style="color: #6c757d; font-size: 0.875rem; text-decoration: none;">🐛 Enable Debug Mode</a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

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

// متغیرهای آماری
$total_guest_uploads = 0;
$unique_guests = 0;

// تابع پاکسازی ورودی
function sanitize_string($input) {
    return trim(htmlspecialchars($input));
}

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
    
    // آمار میهمان‌ها
    $logger->info("Reading guest statistics");
    try {
        $stmt = $connection->query("SELECT COUNT(*) as count FROM guest_uploads");
        $total_guest_uploads = $stmt->fetch()['count'] ?? 0;
        $logger->success("Total guest uploads counted", ['count' => $total_guest_uploads]);
        
        $stmt = $connection->query("SELECT COUNT(DISTINCT device_id) as count FROM guest_uploads");
        $unique_guests = $stmt->fetch()['count'] ?? 0;
        $logger->success("Unique guests counted", ['count' => $unique_guests]);
    } catch (Exception $e) {
        $logger->error("Error reading guest statistics: " . $e->getMessage());
        $total_guest_uploads = 0;
        $unique_guests = 0;
    }
    
} catch (Exception $e) {
    $logger->error("Critical database error", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    $message = 'خطا در دریافت تنظیمات: ' . $e->getMessage();
    $messageType = 'error';
    $total_guest_uploads = 0;
    $unique_guests = 0;
}

// بررسی دیتابیس در debug mode
if ($debug_mode) {
    $logger->info("Debug mode enabled - showing debug information");
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Main Content -->
<div class="container">
    <div class="settings-header">
        <h1 class="page-title">
            <i class="fas fa-user-friends"></i> تنظیمات کاربران میهمان
        </h1>
        <p class="page-description">تنظیمات محدودیت‌ها و امکانات کاربران میهمان</p>
    </div>

    <!-- پیام‌ها -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType === 'error' ? 'danger' : 'success' ?>">
            <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- نمایش Debug Information -->
    <?php if ($debug_mode): ?>
    <div class="debug-panel">
        <h3>🐛 Debug Information</h3>
        
        <!-- Current Settings -->
        <div class="debug-section">
            <h4>📋 Current Settings</h4>
            <pre><?= json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>
        
        <!-- Database Test -->
        <div class="debug-section">
            <h4>💾 Database Test</h4>
            <p>Connection Status: <span class="badge badge-success">Connected</span></p>
            <p>Test Query Result: <span class="badge badge-info"><?= isset($testResult['test']) ? $testResult['test'] : 'N/A' ?></span></p>
        </div>
        
        <!-- Real-time Database Values -->
        <div class="debug-section">
            <h4>🔄 Real-time Database Values</h4>
            <?php
            try {
                $stmt = $connection->query("SELECT setting_key, setting_value, updated_at FROM guest_settings ORDER BY updated_at DESC");
                $current_db_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($current_db_settings)) {
                    echo '<table class="table table-sm table-bordered">';
                    echo '<thead><tr><th>Key</th><th>Value</th><th>Updated</th></tr></thead><tbody>';
                    foreach ($current_db_settings as $setting) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($setting['setting_key']) . '</td>';
                        echo '<td>' . htmlspecialchars($setting['setting_value']) . '</td>';
                        echo '<td>' . htmlspecialchars($setting['updated_at'] ?? 'N/A') . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p class="text-warning">No settings found in database</p>';
                }
            } catch (Exception $e) {
                echo '<p class="text-danger">Error reading database: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <!-- Logger Debug -->
        <div class="debug-section">
            <h4>📝 Logger Status</h4>
            <p>Logger Instance: <span class="badge badge-success">Active</span></p>
            <p>Log File: <?= $logger->getLogFile() ?></p>
        </div>
    </div>
    
    <style>
    .debug-panel {
        background: #f8f9fa;
        border: 2px solid #007bff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,123,255,0.1);
    }
    .debug-section {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .debug-section h4 {
        margin-top: 0;
        color: #495057;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 8px;
    }
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85em;
    }
    .badge-success { background: #28a745; color: white; }
    .badge-info { background: #17a2b8; color: white; }
    .badge-warning { background: #ffc107; color: #212529; }
    .badge-danger { background: #dc3545; color: white; }
    </style>
    <?php endif; ?>

    <div class="row">
        <!-- تنظیمات -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>تنظیمات میهمان‌ها</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="max_uploads">حداکثر تعداد فایل آپلود</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                id="max_uploads" 
                                name="max_uploads" 
                                value="<?= htmlspecialchars($settings['max_uploads']) ?>" 
                                min="1" 
                                max="50"
                                required
                            >
                            <small class="form-text text-muted">تعداد فایل‌هایی که هر میهمان می‌تواند آپلود کند</small>
                        </div>

                        <div class="form-group">
                            <label for="max_file_size">حداکثر حجم فایل (مگابایت)</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                id="max_file_size" 
                                name="max_file_size" 
                                value="<?= htmlspecialchars($settings['max_file_size']) ?>" 
                                min="1" 
                                max="100"
                                required
                            >
                            <small class="form-text text-muted">حداکثر حجم هر فایل برای میهمان‌ها</small>
                        </div>

                        <div class="form-group">
                            <label for="allowed_types">فرمت‌های مجاز</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="allowed_types" 
                                name="allowed_types" 
                                value="<?= htmlspecialchars($settings['allowed_types']) ?>" 
                                required
                            >
                            <small class="form-text text-muted">فرمت‌های مجاز جدا شده با کاما (مثال: jpg,png,pdf)</small>
                        </div>

                        <button type="submit" name="update_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> ذخیره تغییرات
                        </button>
                        
                        <?php if (!$debug_mode): ?>
                        <a href="?debug=1" class="btn btn-outline-secondary">
                            <i class="fas fa-bug"></i> فعال‌سازی Debug
                        </a>
                        <?php else: ?>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> غیرفعال‌سازی Debug
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- آمار -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3>آمار میهمان‌ها</h3>
                </div>
                <div class="card-body">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($total_guest_uploads) ?></div>
                        <div class="stat-label">کل آپلودها</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($unique_guests) ?></div>
                        <div class="stat-label">میهمان‌های منحصربفرد</div>
                    </div>
                </div>
            </div>

            <!-- راهنما -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3>راهنما</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li>میهمان‌ها نیاز به ثبت‌نام ندارند</li>
                        <li>آپلودهای آن‌ها موقتی هستند</li>
                        <li>پس از مدت مشخص حذف می‌شوند</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Console Logging -->
<script>
// Log all form interactions to console
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Xi2 Guest Users Settings Page Loaded');
    console.log('📋 Current Settings:', <?= json_encode($settings) ?>);
    
    // Log form changes
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('change', function(e) {
            console.log('📝 Form Field Changed:', {
                field: e.target.name,
                value: e.target.value,
                type: e.target.type
            });
        });
        
        form.addEventListener('submit', function(e) {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            console.log('📤 Form Submitted:', data);
        });
    }
    
    // Log any PHP messages to console
    <?php if (!empty($message)): ?>
    console.log('💬 PHP Message:', {
        type: '<?= $messageType ?>',
        message: '<?= addslashes($message) ?>'
    });
    <?php endif; ?>
    
    // Log statistics
    console.log('📊 Statistics:', {
        total_uploads: <?= $total_guest_uploads ?>,
        unique_guests: <?= $unique_guests ?>
    });
});

// Custom logging function for Xi2
window.Xi2Log = function(level, message, data = null) {
    const timestamp = new Date().toISOString();
    const logEntry = {
        timestamp: timestamp,
        level: level.toUpperCase(),
        message: message,
        page: 'guest-users-settings'
    };
    
    if (data) {
        logEntry.data = data;
    }
    
    const colors = {
        ERROR: 'color: #dc3545; font-weight: bold;',
        WARNING: 'color: #ffc107; font-weight: bold;',
        INFO: 'color: #17a2b8;',
        SUCCESS: 'color: #28a745; font-weight: bold;',
        DEBUG: 'color: #6c757d;'
    };
    
    console.log(`%c[Xi2-${level.toUpperCase()}] ${message}`, colors[level.toUpperCase()] || '', data || '');
    
    // Send to server if needed
    if (level === 'ERROR' || level === 'WARNING') {
        // You can add AJAX call here to log to server
    }
};

// Test the logging function
Xi2Log('info', 'Guest Users Settings page initialized successfully');
</script>

<style>
.settings-header {
    margin-bottom: 30px;
}

.page-title {
    color: #2c3e50;
    margin-bottom: 10px;
}

.page-description {
    color: #6c757d;
    font-size: 1.1em;
}

.stat-item {
    text-align: center;
    padding: 20px 0;
    border-bottom: 1px solid #eee;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9em;
}

.card {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.form-group {
    margin-bottom: 20px;
}

.btn {
    margin-right: 10px;
}

.alert {
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 25px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

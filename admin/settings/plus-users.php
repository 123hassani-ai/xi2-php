<?php
/**
 * زیتو (Xi2) - تنظیمات کاربران پلاس
 * مدیریت محدودیت‌ها و تنظیمات کاربران پلاس
 * طراحی شده طبق پرامپت شماره 3
 */

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../../src/includes/logger.php';

$page_title = 'تنظیمات کاربران پلاس';
$current_page = 'plus-users';

// فعال‌سازی debug mode
$debug_mode = isset($_GET['debug']) || isset($_SESSION['debug_mode']);
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $_SESSION['debug_mode'] = true;
}

$logger = Xi2Logger::getInstance();
$logger->info("Loading Plus Users Settings Page", ['user' => get_admin_username(), 'debug_mode' => $debug_mode]);

// پردازش فرم
$message = '';
$messageType = '';
$settings = [
    'unlimited_uploads' => 1,
    'max_file_size' => 50,
    'allowed_types' => 'jpg,jpeg,png,gif,pdf,doc,docx,zip,rar,mp4,mp3',
    'api_access' => 1,
    'priority_support' => 1
];

$logger->debug("Default Plus settings loaded", $settings);

// متغیرهای آماری
$total_plus_uploads = 0;
$active_plus_users = 0;

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
    $logger->info("Reading current plus settings from database");
    try {
        $stmt = $connection->query("SELECT setting_key, setting_value FROM plus_settings");
        $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $logger->database("SELECT", "SELECT setting_key, setting_value FROM plus_settings", [], $db_settings);
        
        if (!empty($db_settings)) {
            $old_settings = $settings;
            $settings = array_merge($settings, $db_settings);
            $logger->success("Plus settings merged from database", [
                'old' => $old_settings,
                'from_db' => $db_settings,
                'merged' => $settings
            ]);
        } else {
            $logger->warning("No plus settings found in database, using defaults");
        }
    } catch (Exception $e) {
        $logger->error("Error reading plus settings from database: " . $e->getMessage());
    }
    
    // پردازش فرم در صورت ارسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        $logger->info("Plus settings form submission detected");
        $logger->form("update_plus_settings", $_POST);
        
        // بروزرسانی تنظیمات
        $unlimited_uploads = isset($_POST['unlimited_uploads']) ? 1 : 0;
        $max_file_size = intval($_POST['max_file_size']);
        $allowed_types = sanitize_string($_POST['allowed_types']);
        $api_access = isset($_POST['api_access']) ? 1 : 0;
        $priority_support = isset($_POST['priority_support']) ? 1 : 0;
        
        $new_values = [
            'unlimited_uploads' => $unlimited_uploads,
            'max_file_size' => $max_file_size,
            'allowed_types' => $allowed_types,
            'api_access' => $api_access,
            'priority_support' => $priority_support
        ];
        $logger->info("Processed plus form values", $new_values);
        
        // ذخیره در دیتابیس
        $logger->info("Saving plus settings to database");
        try {
            $sql = "
                INSERT INTO plus_settings (setting_key, setting_value, updated_at) 
                VALUES 
                ('unlimited_uploads', ?, NOW()),
                ('max_file_size', ?, NOW()),
                ('allowed_types', ?, NOW()),
                ('api_access', ?, NOW()),
                ('priority_support', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = NOW()
            ";
            
            $stmt = $connection->prepare($sql);
            $params = [$unlimited_uploads, $max_file_size, $allowed_types, $api_access, $priority_support];
            
            $logger->database("PREPARE", $sql, $params);
            
            $result = $stmt->execute($params);
            $affected_rows = $stmt->rowCount();
            
            $logger->database("EXECUTE", "INSERT...ON DUPLICATE KEY UPDATE (plus_settings)", $params, [
                'success' => $result,
                'affected_rows' => $affected_rows
            ]);
            
            if ($result) {
                // بروزرسانی array محلی
                $old_local_settings = $settings;
                $settings['unlimited_uploads'] = $unlimited_uploads;
                $settings['max_file_size'] = $max_file_size;
                $settings['allowed_types'] = $allowed_types;
                $settings['api_access'] = $api_access;
                $settings['priority_support'] = $priority_support;
                
                $logger->success("Plus settings updated successfully", [
                    'old' => $old_local_settings,
                    'new' => $settings,
                    'affected_rows' => $affected_rows
                ]);
                
                $message = 'تنظیمات کاربران پلاس با موفقیت بروزرسانی شد';
                $messageType = 'success';
                
                // تأیید نهایی - خواندن مجدد از دیتابیس
                $verify_stmt = $connection->query("SELECT setting_key, setting_value FROM plus_settings");
                $verify_settings = $verify_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $logger->info("Verification: Plus settings after update", $verify_settings);
                
            } else {
                $logger->error("Database update failed for plus settings", [
                    'statement_error' => $stmt->errorInfo(),
                    'connection_error' => $connection->errorInfo()
                ]);
                $message = 'خطا در بروزرسانی تنظیمات پلاس';
                $messageType = 'error';
            }
            
        } catch (Exception $e) {
            $logger->error("Exception during plus settings database update", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $message = 'خطا در بروزرسانی تنظیمات پلاس: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // آمار کاربران پلاس
    $logger->info("Reading plus users statistics");
    try {
        // کاربران پلاس فعال
        $stmt = $connection->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'plus'");
        $active_plus_users = $stmt->fetch()['count'] ?? 0;
        $logger->success("Active plus users counted", ['count' => $active_plus_users]);
        
        // کل آپلودهای کاربران پلاس (اگر جدول آپلود وجود داشت)
        try {
            $stmt = $connection->query("
                SELECT COUNT(*) as count 
                FROM uploads u 
                JOIN users us ON u.user_id = us.id 
                WHERE us.user_type = 'plus'
            ");
            $total_plus_uploads = $stmt->fetch()['count'] ?? 0;
            $logger->success("Plus users uploads counted", ['count' => $total_plus_uploads]);
        } catch (Exception $e) {
            $logger->warning("Could not count plus uploads: " . $e->getMessage());
            $total_plus_uploads = 0;
        }
        
    } catch (Exception $e) {
        $logger->error("Error reading plus users statistics: " . $e->getMessage());
        $total_plus_uploads = 0;
        $active_plus_users = 0;
    }
    
} catch (Exception $e) {
    $logger->error("Critical database error in plus settings", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    $message = 'خطا در دریافت تنظیمات پلاس: ' . $e->getMessage();
    $messageType = 'error';
    $total_plus_uploads = 0;
    $active_plus_users = 0;
}

// بررسی دیتابیس در debug mode
if ($debug_mode) {
    $logger->info("Debug mode enabled for plus settings - showing debug information");
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Main Content -->
<div class="container">
    <div class="settings-header">
        <h1 class="page-title">
            <i class="fas fa-crown"></i> تنظیمات کاربران پلاس
        </h1>
        <p class="page-description">تنظیمات ویژه و امکانات کاربران پلاس</p>
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
        <h3>🐛 Plus Users Debug Information</h3>
        
        <!-- Current Plus Settings -->
        <div class="debug-section">
            <h4>📋 Current Plus Settings</h4>
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
            <h4>🔄 Real-time Plus Settings Database Values</h4>
            <?php
            try {
                $stmt = $connection->query("SELECT setting_key, setting_value, updated_at FROM plus_settings ORDER BY updated_at DESC");
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
                    echo '<p class="text-warning">No plus settings found in database</p>';
                }
            } catch (Exception $e) {
                echo '<p class="text-danger">Error reading plus settings database: ' . htmlspecialchars($e->getMessage()) . '</p>';
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
        border: 2px solid #ffc107;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(255,193,7,0.1);
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
                    <h3>تنظیمات کاربران پلاس</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="unlimited_uploads" 
                                            name="unlimited_uploads" 
                                            <?= !empty($settings['unlimited_uploads']) ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="unlimited_uploads">
                                            آپلود نامحدود فایل
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="api_access" 
                                            name="api_access" 
                                            <?= !empty($settings['api_access']) ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="api_access">
                                            دسترسی API
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_file_size">حداکثر حجم فایل (مگابایت)</label>
                                    <input 
                                        type="number" 
                                        class="form-control" 
                                        id="max_file_size" 
                                        name="max_file_size" 
                                        value="<?= htmlspecialchars($settings['max_file_size']) ?>" 
                                        min="1" 
                                        max="500"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input" 
                                            id="priority_support" 
                                            name="priority_support" 
                                            <?= !empty($settings['priority_support']) ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="priority_support">
                                            پشتیبانی اولویت‌دار
                                        </label>
                                    </div>
                                </div>
                            </div>
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
                            <small class="form-text text-muted">فرمت‌های مجاز جدا شده با کاما (مثال: jpg,png,pdf,zip,mp4)</small>
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
                    <h3>آمار کاربران پلاس</h3>
                </div>
                <div class="card-body">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($active_plus_users) ?></div>
                        <div class="stat-label">کاربران پلاس فعال</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($total_plus_uploads) ?></div>
                        <div class="stat-label">کل آپلودهای پلاس</div>
                    </div>
                </div>
            </div>

            <!-- مزایای پلاس -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3>مزایای کاربران پلاس</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li>آپلود نامحدود فایل</li>
                        <li>حجم فایل بالاتر</li>
                        <li>فرمت‌های بیشتر</li>
                        <li>دسترسی API</li>
                        <li>پشتیبانی اولویت‌دار</li>
                        <li>ذخیره‌سازی طولانی‌مدت</li>
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
    console.log('👑 Xi2 Plus Users Settings Page Loaded');
    console.log('📋 Current Plus Settings:', <?= json_encode($settings) ?>);
    
    // Log form changes
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('change', function(e) {
            console.log('📝 Plus Form Field Changed:', {
                field: e.target.name,
                value: e.target.type === 'checkbox' ? e.target.checked : e.target.value,
                type: e.target.type
            });
        });
        
        form.addEventListener('submit', function(e) {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Add checkbox states
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                if (!data.hasOwnProperty(checkbox.name)) {
                    data[checkbox.name] = false;
                } else {
                    data[checkbox.name] = true;
                }
            });
            
            console.log('📤 Plus Settings Form Submitted:', data);
        });
    }
    
    // Log any PHP messages to console
    <?php if (!empty($message)): ?>
    console.log('💬 PHP Message (Plus):', {
        type: '<?= $messageType ?>',
        message: '<?= addslashes($message) ?>'
    });
    <?php endif; ?>
    
    // Log statistics
    console.log('📊 Plus Statistics:', {
        active_plus_users: <?= $active_plus_users ?>,
        total_plus_uploads: <?= $total_plus_uploads ?>
    });
});

// Custom logging function for Xi2 Plus
window.Xi2PlusLog = function(level, message, data = null) {
    const timestamp = new Date().toISOString();
    const logEntry = {
        timestamp: timestamp,
        level: level.toUpperCase(),
        message: message,
        page: 'plus-users-settings'
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
    
    console.log(`%c[Xi2-PLUS-${level.toUpperCase()}] ${message}`, colors[level.toUpperCase()] || '', data || '');
    
    // Send to server if needed
    if (level === 'ERROR' || level === 'WARNING') {
        // You can add AJAX call here to log to server
    }
};

// Test the logging function
Xi2PlusLog('info', 'Plus Users Settings page initialized successfully');
</script>

<style>
.settings-header {
    margin-bottom: 30px;
}

.page-title {
    color: #2c3e50;
    margin-bottom: 10px;
}

.page-title i {
    color: #ffc107;
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
    color: #ffc107;
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
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.form-group {
    margin-bottom: 20px;
}

.form-check {
    padding-top: 10px;
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

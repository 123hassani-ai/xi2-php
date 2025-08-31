<?php
/**
 * زیتو (Xi2) - تنظیمات کاربران پلاس
 * مدیریت محدودیت‌ها و تنظیمات کاربران پلاس
 * طراحی شده طبق پرامپت شماره 3
 */

require_once __DIR__ . '/../includes/auth-check.php';

$page_title = 'تنظیمات کاربران پلاس';
$current_page = 'plus-users';

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

try {
    require_once __DIR__ . '/../../src/database/config.php';
    
    $db = Database::getInstance()->getConnection();
    
    // خواندن تنظیمات فعلی از دیتابیس
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM plus_settings");
        $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (!empty($db_settings)) {
            $settings = array_merge($settings, $db_settings);
        }
    } catch (Exception $e) {
        // جدول plus_settings هنوز وجود ندارد
    }
    
    // پردازش فرم در صورت ارسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        // بروزرسانی تنظیمات
        $unlimited_uploads = isset($_POST['unlimited_uploads']) ? 1 : 0;
        $max_file_size = intval($_POST['max_file_size']);
        $allowed_types = sanitize_string($_POST['allowed_types']);
        $api_access = isset($_POST['api_access']) ? 1 : 0;
        $priority_support = isset($_POST['priority_support']) ? 1 : 0;
        
        // ذخیره در دیتابیس
        $stmt = $db->prepare("
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
        ");
        
        $stmt->execute([$unlimited_uploads, $max_file_size, $allowed_types, $api_access, $priority_support]);
        
        // بروزرسانی array محلی
        $settings['unlimited_uploads'] = $unlimited_uploads;
        $settings['max_file_size'] = $max_file_size;
        $settings['allowed_types'] = $allowed_types;
        $settings['api_access'] = $api_access;
        $settings['priority_support'] = $priority_support;
        
        $message = 'تنظیمات با موفقیت بروزرسانی شد';
        $messageType = 'success';
    }
    
    // آمار کاربران پلاس
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'plus'");
        $total_plus_users = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM uploads WHERE user_id IN (SELECT id FROM users WHERE user_type = 'plus')");
        $plus_uploads = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'plus' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $new_plus_users = $stmt->fetch()['count'] ?? 0;
    } catch (Exception $e) {
        // جداول هنوز وجود ندارند
        $total_plus_users = 0;
        $plus_uploads = 0;
        $new_plus_users = 0;
    }
    
} catch (Exception $e) {
    $message = 'خطا در دریافت تنظیمات: ' . $e->getMessage();
    $messageType = 'error';
    $total_plus_users = 0;
    $plus_uploads = 0;
    $new_plus_users = 0;
}

function sanitize_string($input) {
    return trim(htmlspecialchars($input));
}

include __DIR__ . '/../includes/header.php';
?>

<div class="settings-container">
    <div class="page-header">
        <h1>⭐ تنظیمات کاربران پلاس</h1>
        <p>مدیریت ویژگی‌ها و تنظیمات کاربران پلاس</p>
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
                <h2>⚙️ تنظیمات اصلی</h2>
            </div>
            <div class="card-body">
                <form method="POST" class="settings-form">
                    <input type="hidden" name="update_settings" value="1">
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="unlimited_uploads" value="1" 
                                   <?= $settings['unlimited_uploads'] ? 'checked' : '' ?>>
                            آپلود نامحدود
                        </label>
                        <small class="form-help">امکان آپلود فایل‌های نامحدود برای کاربران پلاس</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_file_size">حداکثر حجم فایل (مگابایت):</label>
                        <input type="number" id="max_file_size" name="max_file_size" 
                               value="<?= htmlspecialchars($settings['max_file_size']) ?>" 
                               class="form-control" min="1" max="500" required>
                        <small class="form-help">حداکثر حجم فایل برای آپلود کاربران پلاس (توصیه: ۵۰ مگابایت)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="allowed_types">فرمت‌های مجاز:</label>
                        <textarea id="allowed_types" name="allowed_types" 
                                  class="form-control" rows="3" required><?= htmlspecialchars($settings['allowed_types']) ?></textarea>
                        <small class="form-help">فرمت‌های فایل مجاز، با کاما جدا شده (شامل فرمت‌های پیشرفته)</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="api_access" value="1" 
                                   <?= $settings['api_access'] ? 'checked' : '' ?>>
                            دسترسی API
                        </label>
                        <small class="form-help">امکان استفاده از API برای آپلود خودکار</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="priority_support" value="1" 
                                   <?= $settings['priority_support'] ? 'checked' : '' ?>>
                            پشتیبانی اولویت‌دار
                        </label>
                        <small class="form-help">پاسخ سریع‌تر به درخواست‌های پشتیبانی</small>
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
                <h2>📊 آمار کاربران پلاس</h2>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-item plus-stat">
                        <div class="stat-icon">👑</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($total_plus_users ?? 0) ?></div>
                            <div class="stat-label">کل کاربران پلاس</div>
                        </div>
                    </div>
                    
                    <div class="stat-item plus-stat">
                        <div class="stat-icon">📁</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($plus_uploads ?? 0) ?></div>
                            <div class="stat-label">آپلودهای پلاس</div>
                        </div>
                    </div>
                    
                    <div class="stat-item plus-stat">
                        <div class="stat-icon">🆕</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= number_format($new_plus_users ?? 0) ?></div>
                            <div class="stat-label">عضو جدید (30 روز)</div>
                        </div>
                    </div>
                    
                    <div class="stat-item plus-stat">
                        <div class="stat-icon">💾</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $settings['max_file_size'] ?> MB</div>
                            <div class="stat-label">حد آپلود</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ویژگی‌های پلاس -->
    <div class="card full-width">
        <div class="card-header">
            <h2>✨ ویژگی‌های کاربران پلاس</h2>
        </div>
        <div class="card-body">
            <div class="features-grid">
                <div class="feature-item <?= $settings['unlimited_uploads'] ? 'active' : 'inactive' ?>">
                    <div class="feature-icon">🚀</div>
                    <div class="feature-content">
                        <h4>آپلود نامحدود</h4>
                        <p>امکان آپلود فایل‌های نامحدود بدون محدودیت تعدادی</p>
                        <span class="feature-status"><?= $settings['unlimited_uploads'] ? 'فعال' : 'غیرفعال' ?></span>
                    </div>
                </div>
                
                <div class="feature-item <?= $settings['api_access'] ? 'active' : 'inactive' ?>">
                    <div class="feature-icon">🔌</div>
                    <div class="feature-content">
                        <h4>دسترسی API</h4>
                        <p>استفاده از API برای آپلود خودکار و یکپارچه‌سازی</p>
                        <span class="feature-status"><?= $settings['api_access'] ? 'فعال' : 'غیرفعال' ?></span>
                    </div>
                </div>
                
                <div class="feature-item <?= $settings['priority_support'] ? 'active' : 'inactive' ?>">
                    <div class="feature-icon">🎧</div>
                    <div class="feature-content">
                        <h4>پشتیبانی اولویت‌دار</h4>
                        <p>پاسخ سریع‌تر و حل سریع مشکلات</p>
                        <span class="feature-status"><?= $settings['priority_support'] ? 'فعال' : 'غیرفعال' ?></span>
                    </div>
                </div>
                
                <div class="feature-item active">
                    <div class="feature-icon">📊</div>
                    <div class="feature-content">
                        <h4>آمار تفصیلی</h4>
                        <p>دسترسی به آمار کامل آپلود و بازدید فایل‌ها</p>
                        <span class="feature-status">فعال</span>
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

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.full-width {
    grid-column: 1 / -1;
}

.checkbox-group .checkbox-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    gap: 0.75rem;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #3b82f6;
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
    resize: vertical;
}

.form-help {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
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
    border-radius: 0.5rem;
}

.plus-stat {
    background: linear-gradient(135deg, #fef3c7, #f59e0b);
    color: #92400e;
}

.stat-icon {
    font-size: 2rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.feature-item {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: 0.5rem;
}

.feature-item.active {
    background: #f0f9ff;
    border: 2px solid #3b82f6;
}

.feature-item.inactive {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    opacity: 0.6;
}

.feature-content h4 {
    margin-bottom: 0.5rem;
    color: #1e40af;
}

.feature-status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.active .feature-status {
    background: #dcfce7;
    color: #166534;
}

.inactive .feature-status {
    background: #fee2e2;
    color: #dc2626;
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

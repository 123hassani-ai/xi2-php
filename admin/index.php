<?php
/**
 * زیتو (Xi2) - داشبورد ادمین
 */
require_once 'includes/auth-check.php';
require_once '../src/database/config.php';
require_once 'includes/path-config.php';

$page_title = 'داشبورد اصلی';
$css_path = '';

try {
    $db = Database::getInstance()->getConnection();
    
    // آمار کلی
    $stats = [
        'total_users' => 0,
        'total_uploads' => 0,
        'sms_sent_today' => 0,
        'sms_sent_total' => 0
    ];
    
    // تعداد کاربران
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'] ?? 0;
    
    // تعداد فایل‌های آپلود شده
    $stmt = $db->query("SELECT COUNT(*) as count FROM uploads");
    $stats['total_uploads'] = $stmt->fetch()['count'] ?? 0;
    
    // پیامک‌های امروز (اگر جدول وجود داشته باشد)
    try {
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sms_logs WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $stats['sms_sent_today'] = $stmt->fetch()['count'] ?? 0;
        
        // کل پیامک‌ها
        $stmt = $db->query("SELECT COUNT(*) as count FROM sms_logs");
        $stats['sms_sent_total'] = $stmt->fetch()['count'] ?? 0;
    } catch (Exception $e) {
        // جدول sms_logs هنوز وجود ندارد
    }
    
    // آخرین کاربران
    $stmt = $db->query("SELECT id, mobile, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $latest_users = $stmt->fetchAll();
    
    // آخرین فایل‌های آپلود شده
    $stmt = $db->query("SELECT id, file_name, user_id, created_at FROM uploads ORDER BY created_at DESC LIMIT 5");
    $latest_uploads = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Xi2 Admin Dashboard Error: ' . $e->getMessage());
    $error_message = 'خطا در دریافت اطلاعات داشبورد';
}

include 'includes/header.php';
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
        <div class="stat-label">
            <i class="fas fa-users" style="margin-left: 5px;"></i>
            کل کاربران
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['total_uploads']); ?></div>
        <div class="stat-label">
            <i class="fas fa-cloud-upload-alt" style="margin-left: 5px;"></i>
            کل آپلودها
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['sms_sent_today']); ?></div>
        <div class="stat-label">
            <i class="fas fa-sms" style="margin-left: 5px;"></i>
            پیامک امروز
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['sms_sent_total']); ?></div>
        <div class="stat-label">
            <i class="fas fa-paper-plane" style="margin-left: 5px;"></i>
            کل پیامک‌ها
        </div>
    </div>
</div>

<?php if (isset($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle" style="margin-left: 8px;"></i>
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<!-- Latest Activity -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- آخرین کاربران -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-plus" style="margin-left: 8px;"></i>
                آخرین کاربران
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($latest_users)): ?>
                <p style="color: #6b7280; text-align: center; padding: 20px;">
                    کاربری موجود نیست
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>شماره موبایل</th>
                                <th>تاریخ عضویت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                <td>
                                    <?php 
                                    $date = new DateTime($user['created_at']);
                                    echo $date->format('Y/m/d H:i');
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- آخرین فایل‌ها -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-upload" style="margin-left: 8px;"></i>
                آخرین فایل‌ها
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($latest_uploads)): ?>
                <p style="color: #6b7280; text-align: center; padding: 20px;">
                    فایلی موجود نیست
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>فایل</th>
                                <th>کاربر</th>
                                <th>تاریخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_uploads as $upload): ?>
                            <tr>
                                <td>
                                    <span style="font-size: 12px; color: #6b7280;">
                                        <?php echo htmlspecialchars(substr($upload['file_name'], 0, 30)) . (strlen($upload['file_name']) > 30 ? '...' : ''); ?>
                                    </span>
                                </td>
                                <td>#<?php echo $upload['user_id']; ?></td>
                                <td>
                                    <?php 
                                    $date = new DateTime($upload['created_at']);
                                    echo $date->format('Y/m/d H:i');
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- System Status -->
<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-server" style="margin-left: 8px;"></i>
            وضعیت سیستم
        </h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div>
                <strong>نسخه PHP:</strong>
                <span class="badge badge-info"><?php echo phpversion(); ?></span>
            </div>
            <div>
                <strong>حافظه استفاده شده:</strong>
                <span class="badge badge-warning"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</span>
            </div>
            <div>
                <strong>زمان سرور:</strong>
                <span class="badge badge-info"><?php echo date('Y/m/d H:i:s'); ?></span>
            </div>
            <div>
                <strong>آپتایم PHP:</strong>
                <span class="badge badge-success"><?php echo gmdate('H:i:s', time() - $_SERVER['REQUEST_TIME']); ?></span>
            </div>
        </div>
    </div>
</div>

<script>
// Auto refresh stats every 30 seconds
setInterval(function() {
    // فقط اگر صفحه در فوکس باشد
    if (!document.hidden) {
        location.reload();
    }
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>
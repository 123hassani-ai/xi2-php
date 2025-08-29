<?php
/**
 * زیتو (Xi2) - لاگ پیامک‌ها
 */
require_once '../includes/auth-check.php';
require_once '../../src/database/config.php';
require_once '../includes/path-config.php';

$page_title = 'گزارش پیامک‌ها';
$css_path = '../';

$logs = [];
$total_count = 0;
$stats = [
    'total' => 0,
    'sent' => 0,
    'failed' => 0,
    'today' => 0
];

try {
    $db = Database::getInstance()->getConnection();
    
    // پارامترهای فیلتر
    $filter_date = $_GET['date'] ?? '';
    $filter_status = $_GET['status'] ?? '';
    $filter_type = $_GET['type'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // ساخت کوئری
    $where_conditions = [];
    $params = [];
    
    if ($filter_date) {
        $where_conditions[] = "DATE(created_at) = ?";
        $params[] = $filter_date;
    }
    
    if ($filter_status) {
        $where_conditions[] = "status = ?";
        $params[] = $filter_status;
    }
    
    if ($filter_type) {
        $where_conditions[] = "message_type = ?";
        $params[] = $filter_type;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // تعداد کل رکوردها
    $count_sql = "SELECT COUNT(*) as count FROM sms_logs $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_count = $stmt->fetch()['count'] ?? 0;
    
    // دریافت لاگ‌ها
    $sql = "SELECT * FROM sms_logs $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $logs = $stmt->fetchAll();
    
    // آمار کلی
    $stmt = $db->query("SELECT COUNT(*) as count FROM sms_logs");
    $stats['total'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM sms_logs WHERE status = 'sent'");
    $stats['sent'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM sms_logs WHERE status = 'failed'");
    $stats['failed'] = $stmt->fetch()['count'] ?? 0;
    
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM sms_logs WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $stats['today'] = $stmt->fetch()['count'] ?? 0;
    
} catch (Exception $e) {
    $error_message = 'خطا در دریافت لاگ‌ها: ' . $e->getMessage();
    error_log('Xi2 Admin: SMS Logs Error: ' . $e->getMessage());
}

// محاسبه تعداد صفحات
$total_pages = ceil($total_count / $per_page);

include '../includes/header.php';
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
        <div class="stat-label">
            <i class="fas fa-paper-plane" style="margin-left: 5px;"></i>
            کل پیامک‌ها
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number" style="color: #059669;"><?php echo number_format($stats['sent']); ?></div>
        <div class="stat-label">
            <i class="fas fa-check-circle" style="margin-left: 5px;"></i>
            ارسال موفق
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number" style="color: #dc2626;"><?php echo number_format($stats['failed']); ?></div>
        <div class="stat-label">
            <i class="fas fa-times-circle" style="margin-left: 5px;"></i>
            ارسال ناموفق
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number" style="color: #0ea5e9;"><?php echo number_format($stats['today']); ?></div>
        <div class="stat-label">
            <i class="fas fa-calendar-day" style="margin-left: 5px;"></i>
            امروز
        </div>
    </div>
</div>

<?php if (isset($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle" style="margin-left: 8px;"></i>
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter" style="margin-left: 8px;"></i>
            فیلتر و جستجو
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label for="date">تاریخ</label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        class="form-control"
                        value="<?php echo htmlspecialchars($filter_date); ?>"
                    >
                </div>
            </div>
            
            <div class="form-col">
                <div class="form-group">
                    <label for="status">وضعیت</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">همه</option>
                        <option value="sent" <?php echo $filter_status === 'sent' ? 'selected' : ''; ?>>ارسال شده</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>ناموفق</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                    </select>
                </div>
            </div>
            
            <div class="form-col">
                <div class="form-group">
                    <label for="type">نوع</label>
                    <select id="type" name="type" class="form-control">
                        <option value="">همه</option>
                        <option value="otp" <?php echo $filter_type === 'otp' ? 'selected' : ''; ?>>کد تأیید</option>
                        <option value="test" <?php echo $filter_type === 'test' ? 'selected' : ''; ?>>تست</option>
                        <option value="notification" <?php echo $filter_type === 'notification' ? 'selected' : ''; ?>>اطلاع‌رسانی</option>
                    </select>
                </div>
            </div>
            
            <div class="form-col">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search" style="margin-left: 8px;"></i>
                            جستجو
                        </button>
                                                <a href="<?php echo admin_url('logs/sms-logs.php'); ?>" class="btn btn-secondary" style="margin-right: 10px;">
                            <i class="fas fa-refresh" style="margin-left: 5px;"></i>
                            بازنشانی
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- نتایج -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">
            <i class="fas fa-list" style="margin-left: 8px;"></i>
            لیست پیامک‌ها
            <?php if ($total_count > 0): ?>
                <span class="badge badge-info" style="margin-right: 10px;"><?php echo number_format($total_count); ?> مورد</span>
            <?php endif; ?>
        </h3>
        
        <div>
                        <a href="<?php echo admin_url('settings/test-sms.php'); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                ارسال پیامک تست
            </a>
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($logs)): ?>
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; color: #d1d5db;"></i>
                <h4>هیچ پیامکی یافت نشد</h4>
                <p>پیامکی با این فیلتر وجود ندارد</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>شماره</th>
                            <th>متن پیامک</th>
                            <th>نوع</th>
                            <th>وضعیت</th>
                            <th>ارسال‌کننده</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-size: 12px;">
                                    <?php echo htmlspecialchars($log['recipient']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 13px; max-width: 200px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($log['message']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $type_labels = [
                                    'otp' => '<span class="badge badge-info">کد تأیید</span>',
                                    'test' => '<span class="badge badge-warning">تست</span>',
                                    'notification' => '<span class="badge badge-success">اطلاع‌رسانی</span>'
                                ];
                                echo $type_labels[$log['message_type']] ?? '<span class="badge badge-secondary">نامشخص</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                $status_labels = [
                                    'sent' => '<span class="badge badge-success">ارسال شده</span>',
                                    'failed' => '<span class="badge badge-danger">ناموفق</span>',
                                    'pending' => '<span class="badge badge-warning">در انتظار</span>',
                                    'delivered' => '<span class="badge badge-info">تحویل داده شده</span>'
                                ];
                                echo $status_labels[$log['status']] ?? '<span class="badge badge-secondary">نامشخص</span>';
                                ?>
                            </td>
                            <td>
                                <small style="color: #6b7280;">
                                    <?php echo htmlspecialchars($log['sent_by']); ?>
                                </small>
                            </td>
                            <td>
                                <small style="color: #6b7280;">
                                    <?php 
                                    $date = new DateTime($log['created_at']);
                                    echo $date->format('Y/m/d H:i');
                                    ?>
                                </small>
                            </td>
                            <td>
                                <button 
                                    type="button" 
                                    class="btn btn-sm btn-info" 
                                    onclick="showLogDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)"
                                    title="جزئیات"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- صفحه‌بندی -->
            <?php if ($total_pages > 1): ?>
            <div style="padding: 20px; border-top: 1px solid #e2e8f0; text-align: center;">
                <div style="display: inline-flex; gap: 5px;">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-sm btn-secondary">قبلی</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-sm btn-secondary">بعدی</a>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 10px; color: #6b7280; font-size: 12px;">
                    صفحه <?php echo $page; ?> از <?php echo $total_pages; ?> 
                    (<?php echo number_format($total_count); ?> مورد)
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal برای نمایش جزئیات -->
<div id="logModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0;">جزئیات پیامک</h3>
        </div>
        <div id="modalContent" style="padding: 20px;">
            <!-- محتوا با JavaScript پر می‌شود -->
        </div>
        <div style="padding: 20px; border-top: 1px solid #e2e8f0; text-align: center;">
            <button type="button" class="btn btn-secondary" onclick="hideLogDetails()">بستن</button>
        </div>
    </div>
</div>

<script>
function showLogDetails(log) {
    const modal = document.getElementById('logModal');
    const content = document.getElementById('modalContent');
    
    const date = new Date(log.created_at);
    const formattedDate = date.toLocaleDateString('fa-IR') + ' ' + date.toLocaleTimeString('fa-IR');
    
    const statusLabels = {
        'sent': 'ارسال شده',
        'failed': 'ناموفق',
        'pending': 'در انتظار',
        'delivered': 'تحویل داده شده'
    };
    
    const typeLabels = {
        'otp': 'کد تأیید',
        'test': 'تست',
        'notification': 'اطلاع‌رسانی'
    };
    
    content.innerHTML = `
        <div style="display: grid; gap: 15px;">
            <div>
                <strong>شماره گیرنده:</strong>
                <span style="font-family: monospace;">${log.recipient}</span>
            </div>
            <div>
                <strong>متن پیامک:</strong>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-top: 5px; direction: rtl;">
                    ${log.message}
                </div>
            </div>
            <div>
                <strong>نوع پیامک:</strong>
                ${typeLabels[log.message_type] || 'نامشخص'}
            </div>
            <div>
                <strong>وضعیت:</strong>
                ${statusLabels[log.status] || 'نامشخص'}
            </div>
            <div>
                <strong>ارسال‌کننده:</strong>
                ${log.sent_by || 'نامشخص'}
            </div>
            <div>
                <strong>تاریخ ارسال:</strong>
                ${formattedDate}
            </div>
            ${log.provider_response ? `
            <div>
                <strong>پاسخ سرویس‌دهنده:</strong>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; margin-top: 5px; font-family: monospace; font-size: 12px; direction: ltr; text-align: left;">
                    ${log.provider_response}
                </div>
            </div>
            ` : ''}
            ${log.user_id ? `
            <div>
                <strong>ID کاربر:</strong>
                #${log.user_id}
            </div>
            ` : ''}
        </div>
    `;
    
    modal.style.display = 'flex';
}

function hideLogDetails() {
    document.getElementById('logModal').style.display = 'none';
}

// بستن modal با کلیک روی پس‌زمینه
document.getElementById('logModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLogDetails();
    }
});

// بستن modal با ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideLogDetails();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

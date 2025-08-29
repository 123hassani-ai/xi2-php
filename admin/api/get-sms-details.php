<?php
/**
 * زیتو (Xi2) - API دریافت جزئیات پیامک
 */
require_once '../includes/auth-check.php';
require_once '../../src/database/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance()->getConnection();
    
    // دریافت آمار کلی
    $stats = [
        'total_sms' => 0,
        'sent_today' => 0,
        'sent_this_month' => 0,
        'success_rate' => 0,
        'recent_logs' => []
    ];
    
    // کل پیامک‌ها
    $stmt = $db->query("SELECT COUNT(*) as count FROM sms_logs");
    $stats['total_sms'] = $stmt->fetch()['count'] ?? 0;
    
    // پیامک‌های امروز
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM sms_logs WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $stats['sent_today'] = $stmt->fetch()['count'] ?? 0;
    
    // پیامک‌های این ماه
    $this_month = date('Y-m');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM sms_logs WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$this_month]);
    $stats['sent_this_month'] = $stmt->fetch()['count'] ?? 0;
    
    // نرخ موفقیت
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
        FROM sms_logs
    ");
    $result = $stmt->fetch();
    if ($result['total'] > 0) {
        $stats['success_rate'] = round(($result['sent'] / $result['total']) * 100, 1);
    }
    
    // آخرین لاگ‌ها
    $stmt = $db->query("SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 5");
    $stats['recent_logs'] = $stmt->fetchAll();
    
    // پاسخ موفق
    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'خطا در دریافت اطلاعات: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    error_log('Xi2 Admin: SMS Details API Error: ' . $e->getMessage());
}

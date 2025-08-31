<?php
/**
 * Xi2 Smart Logging System - Logger Interface
 * 
 * @description Interface برای تمام logger classes
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

interface LoggerInterface 
{
    /**
     * ثبت event جدید
     * 
     * @param array $eventData اطلاعات کامل event
     * @return bool موفقیت در ثبت
     */
    public function logEvent(array $eventData): bool;
    
    /**
     * ثبت error با جزئیات کامل
     * 
     * @param string $message پیام خطا
     * @param array $context اطلاعات اضافی
     * @param string $level سطح خطا
     * @return bool موفقیت در ثبت
     */
    public function logError(string $message, array $context = [], string $level = 'error'): bool;
    
    /**
     * ثبت فعالیت کاربر
     * 
     * @param int|null $userId شناسه کاربر
     * @param string $action نوع عمل
     * @param array $details جزئیات عمل
     * @return bool موفقیت در ثبت
     */
    public function logUserActivity($userId, string $action, array $details = []): bool;
    
    /**
     * ثبت اطلاعات performance
     * 
     * @param array $performanceData داده‌های عملکرد
     * @return bool موفقیت در ثبت
     */
    public function logPerformance(array $performanceData): bool;
    
    /**
     * دریافت لاگ‌ها بر اساس فیلترها
     * 
     * @param array $filters فیلترهای جستجو
     * @param int $limit تعداد نتایج
     * @return array لیست لاگ‌ها
     */
    public function getLogs(array $filters = [], int $limit = 100): array;
}

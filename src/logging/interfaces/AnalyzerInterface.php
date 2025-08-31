<?php
/**
 * Xi2 Smart Logging System - Analyzer Interface
 * 
 * @description Interface برای تحلیل هوشمند لاگ‌ها
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

interface AnalyzerInterface 
{
    /**
     * تحلیل realtime یک event
     * 
     * @param array $eventData داده‌های event
     * @return array نتیجه تحلیل
     */
    public function analyzeEvent(array $eventData): array;
    
    /**
     * شناسایی الگوهای مشکل‌دار
     * 
     * @param array $events لیست eventها
     * @return array الگوهای شناسایی شده
     */
    public function detectPatterns(array $events): array;
    
    /**
     * پیش‌بینی مشکلات آینده
     * 
     * @param array $patterns الگوهای شناسایی شده
     * @return array پیش‌بینی‌ها
     */
    public function predictIssues(array $patterns): array;
    
    /**
     * تحلیل رفتار کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param array $userEvents فعالیت‌های کاربر
     * @return array تحلیل رفتاری
     */
    public function analyzeUserBehavior(int $userId, array $userEvents): array;
    
    /**
     * محاسبه سطح اعتماد به تحلیل
     * 
     * @param array $analysisData داده‌های تحلیل
     * @return float درصد اعتماد (0-1)
     */
    public function calculateConfidence(array $analysisData): float;
    
    /**
     * تولید گزارش تحلیل
     * 
     * @param array $analysisData داده‌های تحلیل شده
     * @return array گزارش کامل
     */
    public function generateReport(array $analysisData): array;
}

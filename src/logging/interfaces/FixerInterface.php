<?php
/**
 * Xi2 Smart Logging System - Fixer Interface
 * 
 * @description Interface برای رفع خودکار مسائل
 * @version 2.0
 * @author Xi2 Intelligent Logger Architect
 */

interface FixerInterface 
{
    /**
     * اعمال راه‌حل خودکار
     * 
     * @param string $issueType نوع مشکل
     * @param array $context اطلاعات مربوط به مشکل
     * @return array نتیجه اعمال راه‌حل
     */
    public function applyFix(string $issueType, array $context): array;
    
    /**
     * بررسی امکان رفع خودکار مشکل
     * 
     * @param string $issueType نوع مشکل
     * @return bool امکان رفع خودکار
     */
    public function canAutoFix(string $issueType): bool;
    
    /**
     * دریافت لیست راه‌حل‌های موجود
     * 
     * @return array لیست راه‌حل‌ها
     */
    public function getAvailableFixes(): array;
    
    /**
     * ثبت نتیجه اعمال راه‌حل
     * 
     * @param string $issueType نوع مشکل
     * @param array $fixResult نتیجه راه‌حل
     * @return bool موفقیت در ثبت
     */
    public function logFixResult(string $issueType, array $fixResult): bool;
    
    /**
     * محاسبه نرخ موفقیت راه‌حل‌ها
     * 
     * @param string|null $issueType نوع خاص مشکل (اختیاری)
     * @return float نرخ موفقیت (0-1)
     */
    public function getSuccessRate(?string $issueType = null): float;
}

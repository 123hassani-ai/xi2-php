<?php
/**
 * تنظیمات مسیرهای پروژه زیتو (Xi2)
 * این فایل به صورت خودکار مسیرها را تشخیص می‌دهد
 */

class PathConfig {
    private static $instance = null;
    private $basePath;
    private $adminPath;
    private $assetsPath;
    
    private function __construct() {
        // تشخیص خودکار مسیر پروژه
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // استخراج مسیر پایه پروژه
        if (strpos($scriptName, '/admin/') !== false) {
            // اگر در پوشه admin هستیم
            $parts = explode('/admin/', $scriptName);
            $this->basePath = $parts[0];
        } elseif (strpos($scriptName, '/xi2.ir/') !== false) {
            // اگر در پوشه xi2.ir هستیم
            $parts = explode('/xi2.ir/', $scriptName);
            $this->basePath = $parts[0] . '/xi2.ir';
        } else {
            // مسیر ریشه
            $this->basePath = dirname($_SERVER['SCRIPT_NAME']);
            if ($this->basePath === '/') {
                $this->basePath = '';
            }
        }
        
        $this->adminPath = $this->basePath . '/admin';
        $this->assetsPath = $this->basePath . '/src/assets';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getBasePath() {
        return $this->basePath;
    }
    
    public function getAdminPath() {
        return $this->adminPath;
    }
    
    public function getAssetsPath() {
        return $this->assetsPath;
    }
    
    public function getAdminUrl($path = '') {
        return $this->adminPath . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    public function getAssetsUrl($path = '') {
        return $this->assetsPath . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    public function getCurrentPath() {
        return $_SERVER['REQUEST_URI'];
    }
    
    // برای debugging
    public function debug() {
        return [
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'basePath' => $this->basePath,
            'adminPath' => $this->adminPath,
            'assetsPath' => $this->assetsPath
        ];
    }
}

// تابع کمکی سراسری
function admin_url($path = '') {
    return PathConfig::getInstance()->getAdminUrl($path);
}

function assets_url($path = '') {
    return PathConfig::getInstance()->getAssetsUrl($path);
}

function base_url($path = '') {
    $basePath = PathConfig::getInstance()->getBasePath();
    return $basePath . ($path ? '/' . ltrim($path, '/') : '');
}

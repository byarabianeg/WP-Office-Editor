<?php
class WP_Office_Editor_Autoloader {
    
    private static $instance = null;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        spl_autoload_register([$this, 'autoload']);
    }
    
    public function autoload($class_name) {
        // تحميل فقط كلاسات الإضافة
        if (strpos($class_name, 'WP_Office_Editor') !== 0) {
            return;
        }
        
        // تحويل اسم الكلاس إلى مسار الملف
        $file_name = strtolower(str_replace(['WP_Office_Editor_', '_'], ['', '-'], $class_name));
        $file_path = WPOE_PLUGIN_DIR . 'includes/class-' . $file_name . '.php';
        
        // تحميل الملف إذا كان موجوداً
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
        
        // البحث في المجلدات الأخرى
        $folders = ['admin', 'includes', 'public'];
        foreach ($folders as $folder) {
            $file_path = WPOE_PLUGIN_DIR . $folder . '/class-' . $file_name . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
    }
}

// تهيئة الـ Autoloader
WP_Office_Editor_Autoloader::init();
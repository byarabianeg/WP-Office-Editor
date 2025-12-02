<?php
/**
 * Plugin Name: WP Office Editor
 * Plugin URI: https://github.com/byarabianeg/WP-Office-Editor
 * Description: محرر متقدم يشبه Microsoft Office داخل ووردبريس مع دعم التعاون في الوقت الحقيقي والذكاء الاصطناعي
 * Version: 2.0.0
 * Author: Your Name
 * Text Domain: wp-office-editor
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// منع الوصول المباشر
defined('ABSPATH') || exit;

// تعريف ثوابت الإضافة
define('WPOE_VERSION', '2.0.0');
define('WPOE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPOE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPOE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// التحقق من إصدار PHP
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php echo sprintf(__('WP Office Editor requires PHP 7.4 or higher. Your current version is %s. Please upgrade.', 'wp-office-editor'), PHP_VERSION); ?></p>
        </div>
        <?php
    });
    return;
}

// تحميل الملفات الأساسية
require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-autoloader.php';
require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-activator.php';
require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-deactivator.php';

// تسجيل وظائف التفعيل والإلغاء
register_activation_hook(__FILE__, ['WP_Office_Editor_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['WP_Office_Editor_Deactivator', 'deactivate']);

// تهيئة الإضافة
add_action('plugins_loaded', function() {
    // تحميل ملفات اللغات
    load_plugin_textdomain('wp-office-editor', false, dirname(WPOE_PLUGIN_BASENAME) . '/languages');
    
    // تشغيل الإضافة
    if (class_exists('WP_Office_Editor')) {
        $plugin = new WP_Office_Editor();
        $plugin->run();
    }
});

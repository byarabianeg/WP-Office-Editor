<?php
/**
 * Plugin Name: WP Office Editor
 * Description: محرر متقدم يشبه Microsoft Word داخل لوحة التحكم لإنشاء المقالات.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-wp-office-editor.php';

function run_wp_office_editor() {
    $plugin = new WP_Office_Editor();
    $plugin->run();
}
run_wp_office_editor();
?>

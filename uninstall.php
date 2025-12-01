<?php
/**
 * Uninstall script for WP Office Editor
 *
 * This file is executed when the plugin is deleted from WordPress.
 *
 * @package WP_Office_Editor
 */

// منع التشغيل المباشر
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * 1. حذف الإعدادات Options
 */
$options = [
    'wp_office_editor_settings',
    'wp_office_editor_editor_theme',
    'wp_office_editor_toolbar_layout',
    'wp_office_editor_recent_files',
    'wp_office_editor_last_opened_tab',
    'wp_office_editor_custom_shortcodes',
];

foreach ( $options as $opt ) {
    delete_option( $opt );
    delete_site_option( $opt ); // إن كانت multisite
}

/**
 * 2. حذف أي بيانات meta مخصصة إن وجدت
 */
global $wpdb;

// حذف جميع post_meta المرتبطة بالمستندات التي أنشأتها الإضافة
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} 
     WHERE meta_key LIKE '_wp_office_editor_%'"
);

/**
 * 3. حذف أي سجلات مخصصة في جدول wp_posts
 * (للمحررات التي تعتمد على Custom Post Type)
 */
$wpdb->query(
    "DELETE FROM {$wpdb->posts}
     WHERE post_type = 'wp_office_document'"
);

/**
 * 4. حذف أي Transients
 */
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_wp_office_editor_%'
        OR option_name LIKE '_transient_timeout_wp_office_editor_%'"
);

/**
 * 5. يمكن إضافة أي جداول مخصصة هنا إذا تم إنشاؤها مستقبلاً
 *
 * مثال:
 * $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wp_office_editor_files" );
 */


<?php
class WP_Office_Editor_Admin {

    public function add_menu_page() {
        add_menu_page(
            'WP Office Editor',
            'Office Editor',
            'edit_posts',
            'wp-office-editor',
            [$this, 'editor_page_callback'],
            'dashicons-edit',
            6
        );
    }

    public function editor_page_callback() {
        include plugin_dir_path(__FILE__) . 'views/editor-page.php';
    }

    /**
     * Enqueue admin scripts and styles, CKEditor (local build) + plugin's JS/CSS
     */
    public function enqueue_assets($hook_suffix) {
        // Only load on our plugin page
        if ($hook_suffix !== 'toplevel_page_wp-office-editor') {
            return;
        }

        // CSS
        wp_enqueue_style('wp-office-editor-admin', plugin_dir_url(__FILE__) . '../assets/css/editor-style.css', [], '1.0.0');

        // --- CKEditor local build (Decoupled Document) ---
        // We expect the build file at: assets/vendor/ckeditor5/ckeditor.js
        wp_enqueue_script(
            'wp-office-editor-ckeditor',
            plugin_dir_url(__FILE__) . '../assets/vendor/ckeditor5/ckeditor.js',
            array(),
            '1.0.0',
            true
        );

        // Our editor init script (which expects Decoupled editor API)
        wp_enqueue_script(
            'wp-office-editor-init',
            plugin_dir_url(__FILE__) . '../assets/js/editor-init.js',
            array('wp-office-editor-ckeditor', 'jquery'),
            '1.0.0',
            true
        );

        // Localize data (ajax_url + nonce)
        $nonce = wp_create_nonce('wp_office_editor_nonce');
        wp_localize_script('wp-office-editor-init', 'WP_OFFICE_EDITOR', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => $nonce,
            'site_url' => site_url()
        ]);
    }
}

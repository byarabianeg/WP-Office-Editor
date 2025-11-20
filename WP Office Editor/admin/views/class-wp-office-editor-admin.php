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
}
?>

<?php
class WP_Office_Editor {

    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-wp-office-editor-loader.php';
        require_once plugin_dir_path(__FILE__) . 'class-wp-office-editor-handler.php';
        require_once plugin_dir_path(__FILE__) . '../admin/class-wp-office-editor-admin.php';
    }

    public function run() {
        $loader = new WP_Office_Editor_Loader();

        $admin = new WP_Office_Editor_Admin();
        $loader->add_action('admin_menu', $admin, 'add_menu_page');

        $handler = new WP_Office_Editor_Handler();
        $loader->add_action('wp_ajax_wp_office_editor_save_post', $handler, 'save_post');

        $loader->run();
        $loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_assets');
    }
}
?>

==============================
==== includes/class-wp-office-editor-loader.php ====
<?php
class WP_Office_Editor_Loader {

    private $actions = [];

    public function add_action($hook, $component, $callback) {
        $this->actions[] = [ $hook, $component, $callback ];
    }

    public function run() {
        foreach ($this->actions as $hook) {
            add_action($hook[0], [$hook[1], $hook[2]]);
            $loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_assets');
        }
    }
}
?>

<?php
/**
 * Main plugin class that wires up the loader, admin and handlers.
 *
 * @package WP_Office_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Office_Editor {

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Plugin file (main).
     *
     * @var string
     */
    protected $plugin_file;

    /**
     * Constructor.
     *
     * @param string $plugin_file
     */
    public function __construct( $plugin_file = '' ) {
        $this->plugin_file = $plugin_file;
        $this->load_dependencies();
    }

    /**
     * Load required files.
     */
    private function load_dependencies() {
        $base_dir = plugin_dir_path( dirname( __FILE__ ) );

        // Loader
        $loader_path = plugin_dir_path( __FILE__ ) . 'class-wp-office-editor-loader.php';
        if ( file_exists( $loader_path ) ) {
            require_once $loader_path;
        }

        // Admin
        $admin_path = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-office-editor-admin.php';
        if ( file_exists( $admin_path ) ) {
            require_once $admin_path;
        }

        // Handler
        $handler_path = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-office-editor-handler.php';
        if ( file_exists( $handler_path ) ) {
            require_once $handler_path;
        }
    }

    /**
     * Register hooks and run.
     */
    public function run() {

        // Use loader if available
        if ( class_exists( 'WP_Office_Editor_Loader' ) ) {
            $loader = new WP_Office_Editor_Loader();

            // Admin
            if ( class_exists( 'WP_Office_Editor_Admin' ) ) {
                $admin = new WP_Office_Editor_Admin();
                $loader->add_action( 'admin_menu', $admin, 'register_menu_page' );
                $loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets', 10, 1 );
            }

            // Handler - constructor registers its own ajax hooks
            if ( class_exists( 'WP_Office_Editor_Handler' ) ) {
                $handler = new WP_Office_Editor_Handler();
            }

            // Run loader
            $loader->run();
        } else {
            // Fallback procedural registration
            if ( class_exists( 'WP_Office_Editor_Admin' ) ) {
                $admin = new WP_Office_Editor_Admin();
                add_action( 'admin_menu', array( $admin, 'register_menu_page' ) );
                add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );
            }

            if ( class_exists( 'WP_Office_Editor_Handler' ) ) {
                $handler = new WP_Office_Editor_Handler();
            }
        }

        // Activation / deactivation hooks
        if ( function_exists( 'register_activation_hook' ) && file_exists( $this->plugin_file ) ) {
            register_activation_hook( $this->plugin_file, array( $this, 'activate' ) );
            register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate' ) );
        }
    }

    /**
     * Activation callback.
     */
    public function activate() {
        if ( false === get_option( 'wp_office_editor_options' ) ) {
            $default = array(
                'default_post_status' => 'draft',
                'max_upload_size'     => 5 * 1024 * 1024
            );
            add_option( 'wp_office_editor_options', $default );
        }
    }

    /**
     * Deactivation callback.
     */
    public function deactivate() {
        // no destructive actions
    }
}

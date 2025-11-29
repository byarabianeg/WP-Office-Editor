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
     * Plugin basename (main plugin file).
     *
     * @var string
     */
    protected $plugin_file;

    /**
     * Constructor.
     *
     * @param string $plugin_file The main plugin file path (usually __FILE__ from bootstrap).
     */
    public function __construct( $plugin_file = '' ) {
        $this->plugin_file = $plugin_file;

        $this->load_dependencies();
    }

    /**
     * Load required files for the plugin.
     */
    private function load_dependencies() {
        $base_dir = plugin_dir_path( dirname( __FILE__ ) );

        // Loader class - small utility to register actions/filters.
        if ( file_exists( $base_dir . 'includes/class-wp-office-editor-loader.php' ) ) {
            require_once $base_dir . 'includes/class-wp-office-editor-loader.php';
        } else {
            // Fallback: try same folder (if include path differs)
            if ( file_exists( plugin_dir_path( __FILE__ ) . 'class-wp-office-editor-loader.php' ) ) {
                require_once plugin_dir_path( __FILE__ ) . 'class-wp-office-editor-loader.php';
            }
        }

        // Admin class
        if ( file_exists( $base_dir . 'admin/class-wp-office-editor-admin.php' ) ) {
            require_once $base_dir . 'admin/class-wp-office-editor-admin.php';
        }

        // Handlers (AJAX / processing)
        if ( file_exists( $base_dir . 'includes/class-wp-office-editor-handler.php' ) ) {
            require_once $base_dir . 'includes/class-wp-office-editor-handler.php';
        }
    }

    /**
     * Register hooks and run the loader.
     *
     * This sets up the admin pages, asset enqueueing and ajax handlers.
     */
    public function run() {

        // If Loader class exists, use it for structured hook registration.
        if ( class_exists( 'WP_Office_Editor_Loader' ) ) {
            $loader = new WP_Office_Editor_Loader();

            // Admin
            if ( class_exists( 'WP_Office_Editor_Admin' ) ) {
                $admin = new WP_Office_Editor_Admin();

                // Add menu & page callback
                $loader->add_action( 'admin_menu', $admin, 'register_menu_page' );

                // Enqueue scripts/styles on admin pages
                $loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
            }

            // Handlers (AJAX)
            if ( class_exists( 'WP_Office_Editor_Handler' ) ) {
                $handler = new WP_Office_Editor_Handler();

                // Note: Handler class registers its own wp_ajax hooks in its constructor.
                // But we keep a reference in case we want to use instance methods as callbacks elsewhere.
            }

            // Run all registered actions/filters
            if ( method_exists( $loader, 'run' ) ) {
                $loader->run();
            }
        } else {
            // Fallback: no loader class — register hooks procedurally.

            // Admin
            if ( class_exists( 'WP_Office_Editor_Admin' ) ) {
                $admin = new WP_Office_Editor_Admin();
                add_action( 'admin_menu', array( $admin, 'register_menu_page' ) );
                add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );
            }

            // Handlers
            if ( class_exists( 'WP_Office_Editor_Handler' ) ) {
                // Handler registers ajax hooks in constructor
                $handler = new WP_Office_Editor_Handler();
            }
        }

        // Optional: Activation / Deactivation hooks (no heavy logic here)
        if ( function_exists( 'register_activation_hook' ) && file_exists( $this->plugin_file ) ) {
            register_activation_hook( $this->plugin_file, array( $this, 'activate' ) );
            register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate' ) );
        }
    }

    /**
     * Activation callback.
     * Use this to create default options or roles/capabilities if needed.
     */
    public function activate() {
        // Example: create default option
        if ( false === get_option( 'wp_office_editor_options' ) ) {
            $default = array(
                'default_post_status' => 'draft',
                'max_upload_size'     => 5 * 1024 * 1024, // 5MB
            );
            add_option( 'wp_office_editor_options', $default );
        }
    }

    /**
     * Deactivation callback.
     * Clean up transient data if necessary.
     */
    public function deactivate() {
        // Nothing destructive here by default.
    }
}

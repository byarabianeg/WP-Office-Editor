<?php
/**
 * Admin class responsible for loading assets and registering admin pages.
 *
 * @package WP_Office_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Office_Editor_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_menu_page() {
        add_menu_page(
            __( 'Office Editor', 'wp-office-editor' ),
            __( 'Office Editor', 'wp-office-editor' ),
            'edit_posts',
            'wp-office-editor',
            array( $this, 'load_editor_page' ),
            'dashicons-edit',
            25
        );
    }

    public function load_editor_page() {
        $view = plugin_dir_path( __FILE__ ) . 'views/editor-page.php';
        if ( file_exists( $view ) ) {
            include_once $view;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Editor view not found.', 'wp-office-editor' ) . '</p></div>';
        }
    }

    public function enqueue_assets( $hook_suffix ) {

        // Load only on our plugin page
        if ( $hook_suffix !== 'toplevel_page_wp-office-editor' ) {
            return;
        }

        // Use plugin_dir helpers pointed at plugin root (one level up from admin folder)
        $plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
        $plugin_path = plugin_dir_path( dirname( __FILE__ ) );

        // CKEditor build
        $ckeditor_file = $plugin_path . 'assets/vendor/ckeditor5/ckeditor.js';
        $ckeditor_url  = $plugin_url  . 'assets/vendor/ckeditor5/ckeditor.js';
        $ckeditor_ver  = file_exists( $ckeditor_file ) ? filemtime( $ckeditor_file ) : time();

        wp_register_script( 'wp-office-editor-ckeditor', $ckeditor_url, array(), $ckeditor_ver, true );
        wp_enqueue_script( 'wp-office-editor-ckeditor' );

        // Editor init
        $init_file = $plugin_path . 'assets/js/editor-init.js';
        $init_url  = $plugin_url  . 'assets/js/editor-init.js';
        $init_ver  = file_exists( $init_file ) ? filemtime( $init_file ) : time();

        wp_register_script( 'wp-office-editor-init', $init_url, array( 'wp-office-editor-ckeditor', 'jquery' ), $init_ver, true );
        wp_enqueue_script( 'wp-office-editor-init' );

        // Styles
        $css_file = $plugin_path . 'assets/css/editor-style.css';
        $css_url  = $plugin_url  . 'assets/css/editor-style.css';
        $css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : time();

        wp_register_style( 'wp-office-editor-style', $css_url, array(), $css_ver );
        wp_enqueue_style( 'wp-office-editor-style' );

        // Localize
        wp_localize_script( 'wp-office-editor-init', 'WP_OFFICE_EDITOR', array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'wp_office_editor_nonce' ),
            'site_url'     => site_url(),
            'ckeditor_url' => $ckeditor_url,
            'init_url'     => $init_url,
            'ckeditor_exists' => file_exists( $ckeditor_file ),
            'init_exists'     => file_exists( $init_file ),
        ) );
    }
}

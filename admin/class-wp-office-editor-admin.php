<?php
/**
 * Admin class responsible for loading assets and registering admin pages.
 *
 * @package WP_Office_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Office_Editor_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register main plugin menu page
     */
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

    /**
     * Load the admin editor page
     */
    public function load_editor_page() {
        include_once plugin_dir_path( __FILE__ ) . 'views/editor-page.php';
    }

    /**
     * Enqueue scripts and styles required for admin editor
     *
     * This function loads CKEditor + the JS initializer + CSS with versioning using filemtime()
     */
    public function enqueue_assets( $hook_suffix ) {

        // Only load assets in plugin page
        if ( $hook_suffix !== 'toplevel_page_wp-office-editor' ) {
            return;
        }

        $plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
        $plugin_path = plugin_dir_path( dirname( __FILE__ ) );

        /*
        |--------------------------------------------------------------------------
        | 1) CKEditor Build
        |--------------------------------------------------------------------------
        */
        $ckeditor_file = $plugin_path . 'assets/vendor/ckeditor5/ckeditor.js';
        $ckeditor_url  = $plugin_url  . 'assets/vendor/ckeditor5/ckeditor.js';
        $ckeditor_ver  = file_exists( $ckeditor_file ) ? filemtime( $ckeditor_file ) : time();

        wp_register_script(
            'wp-office-editor-ckeditor',
            $ckeditor_url,
            array(),
            $ckeditor_ver,
            true
        );

        wp_enqueue_script( 'wp-office-editor-ckeditor' );

        /*
        |--------------------------------------------------------------------------
        | 2) Editor Init Script (initialization of CKEditor inside WordPress)
        |--------------------------------------------------------------------------
        */
        $init_file = $plugin_path . 'assets/js/editor-init.js';
        $init_url  = $plugin_url  . 'assets/js/editor-init.js';
        $init_ver  = file_exists( $init_file ) ? filemtime( $init_file ) : time();

        wp_register_script(
            'wp-office-editor-init',
            $init_url,
            array( 'wp-office-editor-ckeditor', 'jquery' ),
            $init_ver,
            true
        );

        wp_enqueue_script( 'wp-office-editor-init' );

        /*
        |--------------------------------------------------------------------------
        | 3) CSS Styling for Editor Page
        |--------------------------------------------------------------------------
        */
        $css_file = $plugin_path . 'assets/css/editor-style.css';
        $css_url  = $plugin_url  . 'assets/css/editor-style.css';
        $css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : time();

        wp_register_style(
            'wp-office-editor-style',
            $css_url,
            array(),
            $css_ver
        );

        wp_enqueue_style( 'wp-office-editor-style' );

        /*
        |--------------------------------------------------------------------------
        | 4) Localized Variables for AJAX Usage
        |--------------------------------------------------------------------------
        */
        wp_localize_script(
            'wp-office-editor-init',
            'WP_OFFICE_EDITOR',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wp_office_editor_nonce' ),
                'site_url' => site_url()
            )
        );
    }
}

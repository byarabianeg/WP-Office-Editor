<?php
class WP_Office_Editor {
    
    private $loader;
    private $plugin_name;
    private $version;
    
    public function __construct() {
        $this->plugin_name = 'wp-office-editor';
        $this->version = WPOE_VERSION;
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_ajax_hooks();
        $this->define_shortcodes();
        $this->init_ai_system();
    }
    
    private function load_dependencies() {
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-loader.php';
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-i18n.php';
        
        $this->loader = new WP_Office_Editor_Loader();
    }
    
    private function init_ai_system() {
        // Initialize AI system if API key is configured
        $settings = get_option('wpoe_settings', []);
        if (!empty($settings['ai_api_key'])) {
            require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-ai.php';
            $this->ai = new WP_Office_Editor_AI();
        }
    }
    
    private function set_locale() {
        $plugin_i18n = new WP_Office_Editor_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    private function define_admin_hooks() {
        // Load admin files only in admin area
        if (is_admin()) {
            require_once WPOE_PLUGIN_DIR . 'admin/class-wp-office-editor-admin.php';
            $admin = new WP_Office_Editor_Admin($this->plugin_name, $this->version);
            
            $this->loader->add_action('admin_menu', $admin, 'add_menu_pages');
            $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
            $this->loader->add_action('admin_init', $admin, 'register_settings');
            
            // AI admin hooks
            $this->loader->add_action('admin_init', $admin, 'register_ai_settings');
        }
    }
    
    private function define_ajax_hooks() {
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-ajax.php';
        $ajax = new WP_Office_Editor_Ajax();
        
        // Save document
        $this->loader->add_action('wp_ajax_wpoe_save_document', $ajax, 'save_document');
        $this->loader->add_action('wp_ajax_nopriv_wpoe_save_document', $ajax, 'save_document_nopriv');
        
        // Load document
        $this->loader->add_action('wp_ajax_wpoe_load_document', $ajax, 'load_document');
        $this->loader->add_action('wp_ajax_nopriv_wpoe_load_document', $ajax, 'load_document_nopriv');
        
        // Upload images
        $this->loader->add_action('wp_ajax_wpoe_upload_image', $ajax, 'upload_image');
        $this->loader->add_action('wp_ajax_nopriv_wpoe_upload_image', $ajax, 'upload_image_nopriv');
        
        // Export
        $this->loader->add_action('wp_ajax_wpoe_export_document', $ajax, 'export_document');
        
        // AI Generation
        $this->loader->add_action('wp_ajax_wpoe_ai_generate', $ajax, 'ai_generate');
        $this->loader->add_action('wp_ajax_wpoe_ai_validate_key', $ajax, 'ai_validate_key');
        $this->loader->add_action('wp_ajax_wpoe_ai_get_templates', $ajax, 'ai_get_templates');
        $this->loader->add_action('wp_ajax_wpoe_ai_get_usage', $ajax, 'ai_get_usage');
        
        // Get documents list
        $this->loader->add_action('wp_ajax_wpoe_get_documents', $ajax, 'get_documents');
        
        // Delete document
        $this->loader->add_action('wp_ajax_wpoe_delete_document', $ajax, 'delete_document');
        
        // Publish as post
        $this->loader->add_action('wp_ajax_wpoe_publish_post', $ajax, 'publish_post');
        
        // Save sharing
        $this->loader->add_action('wp_ajax_wpoe_save_sharing', $ajax, 'save_sharing');
    }
    
    private function define_shortcodes() {
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-shortcodes.php';
        $shortcodes = new WP_Office_Editor_Shortcodes();
        
        $this->loader->add_shortcode('wpoe_document', $shortcodes, 'document_shortcode');
        $this->loader->add_shortcode('office_editor', $shortcodes, 'document_shortcode'); // Alias
    }
    
    public function run() {
        $this->loader->run();
    }
}
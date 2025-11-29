public function enqueue_assets( $hook_suffix ) {

    if ( $hook_suffix !== 'toplevel_page_wp-office-editor' ) {
        return;
    }

    // Base Paths
    $plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
    $plugin_path = plugin_dir_path( dirname( __FILE__ ) );

    /*
     * 1) Enqueue CKEditor Build
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
    wp_enqueue_script('wp-office-editor-ckeditor');

    /*
     * 2) Enqueue Editor Init JS
     */
    $init_file = $plugin_path . 'assets/js/editor-init.js';
    $init_url  = $plugin_url  . 'assets/js/editor-init.js';
    $init_ver  = file_exists( $init_file ) ? filemtime( $init_file ) : time();

    wp_register_script(
        'wp-office-editor-init',
        $init_url,
        array('wp-office-editor-ckeditor', 'jquery'),
        $init_ver,
        true
    );
    wp_enqueue_script('wp-office-editor-init');

    /*
     * 3) Enqueue Styles
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
    wp_enqueue_style('wp-office-editor-style');

    /*
     * 4) Localized Settings (Nonce + AJAX)
     */
    wp_localize_script( 'wp-office-editor-init', 'WP_OFFICE_EDITOR', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'wp_office_editor_nonce' ),
        'site_url' => site_url()
    ));
}

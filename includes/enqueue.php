<?php

function wp_office_editor_enqueue_assets( $hook ) {

    if ( $hook !== 'toplevel_page_wp-office-editor' ) {
        return;
    }

    // Load custom CKEditor build
    wp_enqueue_script(
        'wp-office-editor-ckeditor',
        plugin_dir_url( __FILE__ ) . '../wp-office-editor-ckeditor-build/build/ckeditor.js',
        [],
        filemtime( plugin_dir_path( __FILE__ ) . '../wp-office-editor-ckeditor-build/build/ckeditor.js' ),
        true
    );

    // Load main editor script
    wp_enqueue_script(
        'wp-office-editor-init',
        plugin_dir_url( __FILE__ ) . '../assets/js/editor-init.js',
        [ 'wp-office-editor-ckeditor' ], // IMPORTANT: CKEditor loads first
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/js/editor-init.js' ),
        true
    );

    // Load CSS
    wp_enqueue_style(
        'wp-office-editor-style',
        plugin_dir_url( __FILE__ ) . '../assets/css/editor-style.css',
        [],
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/css/editor-style.css' )
    );
}

add_action( 'admin_enqueue_scripts', 'wp_office_editor_enqueue_assets' );

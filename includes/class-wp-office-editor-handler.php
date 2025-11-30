<?php
/**
 * Handles AJAX requests for WP Office Editor plugin.
 *
 * @package WP_Office_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // No direct access
}

class WP_Office_Editor_Handler {

    /**
     * Constructor
     */
    public function __construct() {

        // Save Document
        add_action( 'wp_ajax_oe_save_document', array( $this, 'save_document_handler' ) );

        // Upload Image
        add_action( 'wp_ajax_oe_upload_image', array( $this, 'upload_image_handler' ) );
    }

    /**
     * Validate nonce + permission
     */
    private function validate_security() {

        if ( ! isset( $_REQUEST['nonce'] ) ||
             ! wp_verify_nonce( $_REQUEST['nonce'], 'wp_office_editor_nonce' ) ) {

            wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'no_permission' ), 403 );
        }
    }

    /**
     * Save or update a document
     */
    public function save_document_handler() {

        $this->validate_security();

        $title   = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : 'بدون عنوان';
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! isset( $_POST['content'] ) ) {
            wp_send_json_error( array( 'message' => 'no_content' ), 400 );
        }

        /*
        |--------------------------------------------------------------------------
        | Clean CKEditor HTML Content
        |--------------------------------------------------------------------------
        */
        $allowed_tags = wp_kses_allowed_html( 'post' );

        // Add extended CKEditor tags
        $allowed_tags['span']['style']   = true;
        $allowed_tags['table']['class']  = true;
        $allowed_tags['figure']['class'] = true;

        $content = wp_kses( $_POST['content'], $allowed_tags );

        /*
        |--------------------------------------------------------------------------
        | Insert or Update Post
        |--------------------------------------------------------------------------
        */
        $post_data = array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_type'    => 'post'
        );

        if ( $post_id > 0 ) {
            $post_data['ID'] = $post_id;
            $new_post_id = wp_update_post( $post_data, true );
        } else {
            $new_post_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $new_post_id ) ) {
            wp_send_json_error(
                array(
                    'message' => 'save_failed',
                    'error'   => $new_post_id->get_error_message()
                ),
                500
            );
        }

        wp_send_json_success(
            array(
                'message' => 'document_saved',
                'post_id' => $new_post_id,
                'edit_url' => get_edit_post_link( $new_post_id )
            )
        );
    }

    /**
     * Upload Image for CKEditor
     */
    public function upload_image_handler() {

        $this->validate_security();

        if ( ! isset( $_FILES['upload'] ) ) {
            wp_send_json_error( array( 'message' => 'no_file' ), 400 );
        }

        $file = $_FILES['upload'];

        /*
        |--------------------------------------------------------------------------
        | Validate file type + size
        |--------------------------------------------------------------------------
        */
        $allowed_types = array(
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        );

        if ( ! in_array( $file['type'], $allowed_types, true ) ) {
            wp_send_json_error( array( 'message' => 'file_type_not_allowed' ), 400 );
        }

        $max_size = 5 * 1024 * 1024; // 5 MB

        if ( $file['size'] > $max_size ) {
            wp_send_json_error( array( 'message' => 'file_too_large' ), 400 );
        }

        /*
        |--------------------------------------------------------------------------
        | Upload using WordPress API
        |--------------------------------------------------------------------------
        */
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload = wp_handle_upload( $file, array( 'test_form' => false ) );

        if ( isset( $upload['error'] ) ) {
            wp_send_json_error( array( 'message' => $upload['error'] ), 500 );
        }

        $image_url = $upload['url'];
        $file_path = $upload['file'];

        // Add to Media Library
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name( basename( $file_path ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attachment_id = wp_insert_attachment( $attachment, $file_path );

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        /*
        |--------------------------------------------------------------------------
        | Return JSON response (CKEditor Format)
        |--------------------------------------------------------------------------
        */
        wp_send_json_success(
            array(
                'url' => $image_url,
                'id'  => $attachment_id,
                'message' => 'upload_success'
            )
        );
    }

}

<?php
/**
 * Handles AJAX requests for WP Office Editor plugin.
 *
 * @package WP_Office_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Office_Editor_Handler {

    public function __construct() {

        // Save document (create or update)
        add_action( 'wp_ajax_oe_save_document', array( $this, 'save_document_handler' ) );

        // Load document for ?doc_id=xxx (open in new tab/window)
        add_action( 'wp_ajax_oe_get_document', array( $this, 'get_document_handler' ) );

        // Image upload handler for CKEditor
        add_action( 'wp_ajax_oe_upload_image', array( $this, 'upload_image_handler' ) );
    }

    /**
     * Validate nonce and user permissions
     */
    private function validate_security() {

        if ( ! isset( $_REQUEST['nonce'] ) ) {
            wp_send_json_error( array( 'message' => 'missing_nonce' ), 403 );
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'wp_office_editor_nonce' ) ) {
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

        $title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : 'بدون عنوان';
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! isset( $_POST['content'] ) ) {
            wp_send_json_error( array( 'message' => 'no_content' ), 400 );
        }

        // Sanitize content
        $allowed_tags = wp_kses_allowed_html( 'post' );
        $allowed_tags['span']['style']   = true;
        $allowed_tags['table']['class']  = true;
        $allowed_tags['figure']['class'] = true;

        $content = wp_kses(
            wp_kses_post( wp_unslash( $_POST['content'] ) ),
            $allowed_tags
        );

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

        // Generate shortcode
        $shortcode = '[wp_office_editor id="' . intval( $new_post_id ) . '"]';

        wp_send_json_success(
            array(
                'message'   => 'document_saved',
                'post_id'   => $new_post_id,
                'edit_url'  => get_edit_post_link( $new_post_id ),
                'shortcode' => $shortcode
            )
        );
    }

    /**
     * Load a document by ID (used by ?doc_id=...)
     */
    public function get_document_handler() {
        $this->validate_security();

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( $post_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'invalid_post_id' ), 400 );
        }

        $post = get_post( $post_id );

        if ( ! $post ) {
            wp_send_json_error( array( 'message' => 'not_found' ), 404 );
        }

        wp_send_json_success(
            array(
                'post_id' => $post->ID,
                'title'   => get_the_title( $post ),
                'content' => $post->post_content,
            )
        );
    }

    /**
     * Upload image for CKEditor
     */
    public function upload_image_handler() {
        $this->validate_security();

        if ( empty( $_FILES['upload'] ) ) {
            wp_send_json_error( array( 'message' => 'no_file' ), 400 );
        }

        $file = $_FILES['upload'];

        // Allowed types
        $allowed_types = array(
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        );

        if ( ! in_array( $file['type'], $allowed_types, true ) ) {
            wp_send_json_error( array( 'message' => 'file_type_not_allowed' ), 400 );
        }

        // Max size 5MB
        $max_size = 5 * 1024 * 1024;

        if ( $file['size'] > $max_size ) {
            wp_send_json_error( array( 'message' => 'file_too_large' ), 400 );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $overrides = array( 'test_form' => false );
        $movefile  = wp_handle_upload( $file, $overrides );

        if ( isset( $movefile['error'] ) ) {
            wp_send_json_error( array( 'message' => $movefile['error'] ), 500 );
        }

        $file_url  = $movefile['url'];
        $file_path = $movefile['file'];
        $file_type = $movefile['type'];

        // Insert into media library
        $attachment = array(
            'post_mime_type' => $file_type,
            'post_title'     => sanitize_file_name( basename( $file_path ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $file_path );

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        wp_send_json_success(
            array(
                'url'     => $file_url,
                'id'      => $attach_id,
                'message' => 'upload_success'
            )
        );
    }
}

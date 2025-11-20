<?php
class WP_Office_Editor_Handler {

    /**
     * حفظ البوست (يسمى عن طريق action = wp_office_editor_save_post)
     */
    public function save_post() {
        // تحقق من الصلاحية و nonce
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('permission_denied', 403);
        }

        if ( empty($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'wp_office_editor_nonce') ) {
            wp_send_json_error('invalid_nonce', 403);
        }

        $title   = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

        if (empty($title) && empty($content)) {
            wp_send_json_error('empty_content', 400);
        }

        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
        ]);

        if (is_wp_error($post_id) || $post_id == 0) {
            wp_send_json_error('insert_failed', 500);
        }

        wp_send_json_success([ 'post_id' => $post_id ]);
    }

    /**
     * رفع صورة من CKEditor -> تُخزن في Media Library وتُعيد رابط الصورة
     * CKEditor يرسل الملف تحت اسم "upload"
     */
    public function upload_image() {
        // صلاحية
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'permission_denied'], 403);
        }

        if ( empty($_REQUEST['nonce']) || ! wp_verify_nonce($_REQUEST['nonce'], 'wp_office_editor_nonce') ) {
            wp_send_json_error(['message' => 'invalid_nonce'], 403);
        }

        if ( empty($_FILES) || ! isset($_FILES['upload']) ) {
            wp_send_json_error(['message' => 'no_file'], 400);
        }

        // التعامل مع الملف
        $file = $_FILES['upload'];

        // Use WP functions to handle upload
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $overrides = ['test_form' => false];

        $movefile = wp_handle_upload( $file, $overrides );

        if ( isset($movefile['error']) ) {
            wp_send_json_error(['message' => $movefile['error'] ], 500);
        }

        // Insert into Media Library
        $filetype = wp_check_filetype( $movefile['file'], null );
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( $file['name'] ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attach_id = wp_insert_attachment( $attachment, $movefile['file'] );

        if ( ! is_wp_error( $attach_id ) ) {
            $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            // العنوان الذي يتوقعه CKEditor: { "url": "..." }
            $image_url = wp_get_attachment_url( $attach_id );
            wp_send_json_success(['url' => $image_url, 'attachment_id' => $attach_id]);
        } else {
            wp_send_json_error(['message' => 'attachment_insert_failed'], 500);
        }
    }
}

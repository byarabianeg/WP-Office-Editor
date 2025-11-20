<?php
class WP_Office_Editor_Handler {

    public function save_post() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $title   = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);

        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish'
        ]);

        wp_send_json_success([ 'post_id' => $post_id ]);
    }
}
?>


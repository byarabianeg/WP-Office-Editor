if ( ! isset($_REQUEST['nonce']) || ! wp_verify_nonce( $_REQUEST['nonce'], 'wp_office_editor_nonce' ) ) {
    wp_send_json_error(array('message' => 'invalid_nonce'), 403);
}

if ( ! current_user_can('edit_posts') ) {
    wp_send_json_error(array('message' => 'no_permission'), 403);
}

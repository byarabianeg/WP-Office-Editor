<?php
class WP_Office_Editor_Documents {
    
    private $post_type = 'wpoe_document';
    
    public function __construct() {
        $this->register_post_type();
    }
    
    /**
     * تسجيل نوع المستند المخصص
     */
    private function register_post_type() {
        register_post_type($this->post_type, [
            'labels' => [
                'name' => __('Office Documents', 'wp-office-editor'),
                'singular_name' => __('Document', 'wp-office-editor'),
                'add_new' => __('Add New Document', 'wp-office-editor'),
                'add_new_item' => __('Add New Document', 'wp-office-editor'),
                'edit_item' => __('Edit Document', 'wp-office-editor'),
                'new_item' => __('New Document', 'wp-office-editor'),
                'view_item' => __('View Document', 'wp-office-editor'),
                'search_items' => __('Search Documents', 'wp-office-editor'),
                'not_found' => __('No documents found', 'wp-office-editor'),
                'not_found_in_trash' => __('No documents found in Trash', 'wp-office-editor'),
                'all_items' => __('All Documents', 'wp-office-editor'),
                'menu_name' => __('Documents', 'wp-office-editor')
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'publicly_queryable' => true,
            'exclude_from_search' => true,
            'has_archive' => false,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => 'edit_posts',
                'read_post' => 'edit_posts',
                'delete_post' => 'delete_posts',
                'edit_posts' => 'edit_posts',
                'edit_others_posts' => 'edit_others_posts',
                'publish_posts' => 'publish_posts',
                'read_private_posts' => 'read_private_posts'
            ],
            'supports' => ['title', 'editor', 'author', 'custom-fields'],
            'show_in_rest' => true
        ]);
    }
    
    /**
     * حفظ المستند
     */
    public function save_document($data) {
        $document_id = $data['document_id'];
        $post_data = [
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_status' => $data['status'],
            'post_type' => $this->post_type,
            'post_author' => get_current_user_id()
        ];
        
        if ($document_id) {
            $post_data['ID'] = $document_id;
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
        }
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }
        
        // حفظ البيانات التعريفية
        if (isset($data['meta']) && is_array($data['meta'])) {
            update_post_meta($result, '_wpoe_meta', $data['meta']);
            
            // حفظ الإحصائيات
            $stats = [
                'words' => str_word_count(strip_tags($data['content'])),
                'characters' => strlen(strip_tags($data['content'])),
                'last_modified' => current_time('mysql'),
                'modified_by' => get_current_user_id()
            ];
            update_post_meta($result, '_wpoe_stats', $stats);
        }
        
        // إنشاء شورت كود فريد إذا لم يكن موجوداً
        $shortcode = get_post_meta($result, '_wpoe_shortcode', true);
        if (!$shortcode) {
            $shortcode = '[wpoe_document id="' . $result . '"]';
            update_post_meta($result, '_wpoe_shortcode', $shortcode);
        }
        
        return [
            'success' => true,
            'document_id' => $result,
            'shortcode' => $shortcode
        ];
    }
    
    /**
     * الحصول على المستند
     */
    public function get_document($document_id) {
        $post = get_post($document_id);
        
        if (!$post || $post->post_type !== $this->post_type) {
            return false;
        }
        
        // التحقق من الصلاحيات
        if (!current_user_can('edit_post', $document_id)) {
            $shared_users = get_post_meta($document_id, '_wpoe_shared_users', true);
            $current_user = get_current_user_id();
            
            if (!in_array($current_user, (array)$shared_users) && $post->post_author != $current_user) {
                return false;
            }
        }
        
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'status' => $post->post_status,
            'author' => $post->post_author,
            'created' => $post->post_date,
            'modified' => $post->post_modified,
            'shortcode' => get_post_meta($document_id, '_wpoe_shortcode', true),
            'meta' => get_post_meta($document_id, '_wpoe_meta', true),
            'stats' => get_post_meta($document_id, '_wpoe_stats', true)
        ];
    }
    
    /**
     * الحصول على قائمة المستندات
     */
    public function get_documents($page = 1, $per_page = 20, $search = '') {
        $args = [
            'post_type' => $this->post_type,
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            'author' => get_current_user_id(),
            'orderby' => 'modified',
            'order' => 'DESC'
        ];
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        
        $documents = [];
        foreach ($query->posts as $post) {
            $documents[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'author' => $post->post_author,
                'created' => $post->post_date,
                'modified' => $post->post_modified,
                'shortcode' => get_post_meta($post->ID, '_wpoe_shortcode', true),
                'words' => get_post_meta($post->ID, '_wpoe_stats', true)['words'] ?? 0,
                'edit_url' => admin_url('admin.php?page=wp-office-editor&document=' . $post->ID)
            ];
        }
        
        return [
            'documents' => $documents,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        ];
    }
    
    /**
     * حذف المستند
     */
    public function delete_document($document_id) {
        if (!current_user_can('delete_post', $document_id)) {
            return [
                'success' => false,
                'message' => __('You do not have permission to delete this document.', 'wp-office-editor')
            ];
        }
        
        $result = wp_delete_post($document_id, true);
        
        if ($result) {
            return [
                'success' => true,
                'message' => __('Document deleted successfully.', 'wp-office-editor')
            ];
        } else {
            return [
                'success' => false,
                'message' => __('Failed to delete document.', 'wp-office-editor')
            ];
        }
    }
    
    /**
     * الحصول على إحصائيات المستند
     */
    public function get_document_stats($document_id) {
        $stats = get_post_meta($document_id, '_wpoe_stats', true);
        
        if (!$stats) {
            $post = get_post($document_id);
            $content = strip_tags($post->post_content);
            
            $stats = [
                'words' => str_word_count($content),
                'characters' => strlen($content),
                'pages' => ceil(str_word_count($content) / 250), // تقدير الصفحات
                'created' => $post->post_date,
                'modified' => $post->post_modified,
                'modified_by' => $post->post_author
            ];
            
            update_post_meta($document_id, '_wpoe_stats', $stats);
        }
        
        return $stats;
    }
    
    /**
     * تحديث إحصائيات المستند
     */
    public function update_document_stats($document_id, $content) {
        $stats = [
            'words' => str_word_count(strip_tags($content)),
            'characters' => strlen(strip_tags($content)),
            'pages' => ceil(str_word_count(strip_tags($content)) / 250),
            'last_modified' => current_time('mysql'),
            'modified_by' => get_current_user_id()
        ];
        
        update_post_meta($document_id, '_wpoe_stats', $stats);
        
        return $stats;
    }
}
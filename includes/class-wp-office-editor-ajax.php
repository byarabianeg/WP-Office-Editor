<?php
class WP_Office_Editor_Ajax {
    
    private $documents;
    private $export;
    private $ai;
    
    public function __construct() {
        // تحميل الكلاسات عند الحاجة فقط
        add_action('init', [$this, 'init_components']);
    }
    
    public function init_components() {
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-documents.php';
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-export.php';
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-ai.php';
        
        $this->documents = new WP_Office_Editor_Documents();
        $this->export = new WP_Office_Editor_Export();
        $this->ai = new WP_Office_Editor_AI();
    }
    
    /**
     * إدارة الألسنة
     */
    public function manage_tabs() {
        $this->verify_nonce();
        $this->verify_permission('edit_posts');
        
        require_once WPOE_PLUGIN_DIR . 'includes/class-wp-office-editor-tabs.php';
        $tabs_manager = new WP_Office_Editor_Tabs();
        
        $action = isset($_POST['tab_action']) ? sanitize_text_field($_POST['tab_action']) : '';
        $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
        
        switch ($action) {
            case 'create':
                $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
                $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
                $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
                
                $result = $tabs_manager->create_tab($title, $document_id, $content);
                break;
                
            case 'update':
                $data = $this->get_tab_data();
                $result = $tabs_manager->update_tab($tab_id, $data);
                break;
                
            case 'switch':
                $result = $tabs_manager->switch_tab($tab_id);
                break;
                
            case 'close':
                $force = isset($_POST['force']) ? (bool)$_POST['force'] : false;
                
                if ($force) {
                    // إغلاق بالقوة حتى مع وجود تغييرات غير محفوظة
                    $tabs_manager->update_tab($tab_id, ['has_unsaved_changes' => false]);
                }
                
                $result = $tabs_manager->close_tab($tab_id);
                break;
                
            case 'close_others':
                $result = $tabs_manager->close_other_tabs($tab_id);
                break;
                
            case 'close_all':
                $force = isset($_POST['force']) ? (bool)$_POST['force'] : false;
                
                if ($force) {
                    // إغلاق جميع الألسنة بالقوة
                    $all_tabs = $tabs_manager->get_all_tabs();
                    foreach ($all_tabs as $tab_id => $tab) {
                        $tabs_manager->update_tab($tab_id, ['has_unsaved_changes' => false]);
                    }
                }
                
                $result = $tabs_manager->close_all_tabs();
                break;
                
            case 'get':
                $result = [
                    'success' => true,
                    'tab' => $tabs_manager->get_tab($tab_id)
                ];
                break;
                
            case 'get_all':
                $result = [
                    'success' => true,
                    'tabs' => $tabs_manager->get_all_tabs(),
                    'current_tab' => $tabs_manager->get_current_tab(),
                    'has_unsaved_changes' => $tabs_manager->has_unsaved_changes()
                ];
                break;
                
            case 'save_backup':
                $data = isset($_POST['data']) ? $this->sanitize_tab_data($_POST['data']) : [];
                $saved = $tabs_manager->save_tab_backup($tab_id, $data);
                
                $result = [
                    'success' => $saved,
                    'message' => $saved ? 'Backup saved' : 'Failed to save backup'
                ];
                break;
                
            case 'restore_backup':
                $backup_data = $tabs_manager->restore_tab_backup($tab_id);
                
                $result = [
                    'success' => !empty($backup_data),
                    'data' => $backup_data
                ];
                break;
                
            case 'merge':
                $target_tab_id = isset($_POST['target_tab_id']) ? sanitize_text_field($_POST['target_tab_id']) : '';
                $position = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : 'append';
                
                $result = $tabs_manager->merge_tabs($tab_id, $target_tab_id, $position);
                break;
                
            case 'sort':
                $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'last_modified';
                $sorted_tabs = $tabs_manager->sort_tabs($order);
                
                $result = [
                    'success' => true,
                    'tabs' => $sorted_tabs
                ];
                break;
                
            case 'search':
                $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
                $results = $tabs_manager->search_tabs($keyword);
                
                $result = [
                    'success' => true,
                    'results' => $results,
                    'count' => count($results)
                ];
                break;
                
            case 'cleanup':
                $hours = isset($_POST['hours']) ? intval($_POST['hours']) : 24;
                $result = $tabs_manager->cleanup_old_tabs($hours);
                break;
                
            case 'export':
                $export_data = $tabs_manager->export_tabs_data();
                
                $result = [
                    'success' => true,
                    'data' => $export_data,
                    'filename' => 'wpoe-tabs-backup-' . date('Y-m-d-H-i-s') . '.json'
                ];
                break;
                
            case 'import':
                $import_data = isset($_POST['import_data']) ? json_decode(stripslashes($_POST['import_data']), true) : [];
                
                if (empty($import_data)) {
                    $result = [
                        'success' => false,
                        'message' => __('No import data provided.', 'wp-office-editor')
                    ];
                } else {
                    $result = $tabs_manager->import_tabs_data($import_data);
                }
                break;
                
            default:
                $result = [
                    'success' => false,
                    'message' => __('Invalid tab action.', 'wp-office-editor')
                ];
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * الحصول على بيانات التبويب من الطلب
     */
    private function get_tab_data() {
        $data = [];
        
        $fields = [
            'title' => 'sanitize_text_field',
            'content' => 'wp_kses_post',
            'document_id' => 'intval',
            'is_new' => function($val) { return (bool)$val; },
            'has_unsaved_changes' => function($val) { return (bool)$val; },
            'status' => 'sanitize_text_field'
        ];
        
        foreach ($fields as $field => $sanitizer) {
            if (isset($_POST[$field])) {
                if (is_callable($sanitizer)) {
                    $data[$field] = $sanitizer($_POST[$field]);
                } else {
                    $data[$field] = call_user_func($sanitizer, $_POST[$field]);
                }
            }
        }
        
        // معالجة البيانات التعريفية
        if (isset($_POST['metadata']) && is_array($_POST['metadata'])) {
            $metadata = [];
            $allowed_meta = ['word_count', 'char_count', 'page_count', 'zoom_level', 'view_mode'];
            
            foreach ($_POST['metadata'] as $key => $value) {
                if (in_array($key, $allowed_meta)) {
                    $metadata[$key] = sanitize_text_field($value);
                }
            }
            
            $data['metadata'] = $metadata;
        }
        
        return $data;
    }
    
    /**
     * تنظيف بيانات التبويب
     */
    private function sanitize_tab_data($data) {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_tab_data($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * حفظ المستند (مستخدمين مسجلين)
     */
    public function save_document() {
        $this->verify_nonce();
        $this->verify_permission('edit_posts');
        
        $data = $this->get_request_data();
        $result = $this->documents->save_document($data);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Document saved successfully.', 'wp-office-editor'),
                'document_id' => $result['document_id'],
                'shortcode' => '[wpoe_document id="' . $result['document_id'] . '"]',
                'edit_link' => admin_url('admin.php?page=wp-office-editor&document=' . $result['document_id']),
                'view_link' => get_permalink($result['document_id'])
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * حفظ المستند (زوار)
     */
    public function save_document_nopriv() {
        // يمكنك إضافة منطق للحفظ للزوار إذا لزم الأمر
        wp_send_json_error(['message' => __('You must be logged in to save documents.', 'wp-office-editor')]);
    }
    
    /**
     * تحميل المستند (مستخدمين مسجلين)
     */
    public function load_document() {
        $this->verify_nonce();
        $this->verify_permission('edit_posts');
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if (!$document_id) {
            wp_send_json_error(['message' => __('Invalid document ID.', 'wp-office-editor')]);
        }
        
        $document = $this->documents->get_document($document_id);
        
        if ($document) {
            wp_send_json_success([
                'document' => $document
            ]);
        } else {
            wp_send_json_error(['message' => __('Document not found or you do not have permission to access it.', 'wp-office-editor')]);
        }
    }
    
    /**
     * تحميل المستند (زوار)
     */
    public function load_document_nopriv() {
        // يمكن للزوار تحميل المستندات المشتركة
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        $share_token = isset($_POST['share_token']) ? sanitize_text_field($_POST['share_token']) : '';
        
        if (!$document_id || !$share_token) {
            wp_send_json_error(['message' => __('Invalid request.', 'wp-office-editor')]);
        }
        
        // التحقق من token المشاركة
        $stored_token = get_post_meta($document_id, '_wpoe_share_token', true);
        
        if ($share_token !== $stored_token) {
            wp_send_json_error(['message' => __('Invalid share token.', 'wp-office-editor')]);
        }
        
        $document = $this->documents->get_document($document_id);
        
        if ($document) {
            wp_send_json_success([
                'document' => $document
            ]);
        } else {
            wp_send_json_error(['message' => __('Document not found.', 'wp-office-editor')]);
        }
    }
    
    /**
     * رفع الصور (مستخدمين مسجلين)
     */
    public function upload_image() {
        $this->verify_nonce();
        $this->verify_permission('upload_files');
        
        if (!isset($_FILES['upload'])) {
            wp_send_json_error(['message' => __('No file uploaded.', 'wp-office-editor')]);
        }
        
        // معالجة الرفع
        $result = $this->handle_upload($_FILES['upload']);
        
        if ($result['success']) {
            wp_send_json_success([
                'url' => $result['url'],
                'id' => $result['id']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * رفع الصور (زوار)
     */
    public function upload_image_nopriv() {
        // يمكن للزوار رفع الصور إذا كانوا يحررون مستنداً مشتركاً
        $share_token = isset($_POST['share_token']) ? sanitize_text_field($_POST['share_token']) : '';
        
        if (!$share_token) {
            wp_send_json_error(['message' => __('Invalid share token.', 'wp-office-editor')]);
        }
        
        if (!isset($_FILES['upload'])) {
            wp_send_json_error(['message' => __('No file uploaded.', 'wp-office-editor')]);
        }
        
        // معالجة الرفع
        $result = $this->handle_upload($_FILES['upload']);
        
        if ($result['success']) {
            wp_send_json_success([
                'url' => $result['url'],
                'id' => $result['id']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * معالجة رفع الملفات
     */
    private function handle_upload($file) {
        // السماح بأنواع الملفات
        $allowed_types = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ];
        
        // التحقق من نوع الملف
        if (!in_array($file['type'], $allowed_types)) {
            return [
                'success' => false,
                'message' => __('File type not allowed.', 'wp-office-editor')
            ];
        }
        
        // التحقق من حجم الملف (10MB كحد أقصى)
        if ($file['size'] > 10 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => __('File is too large. Maximum size is 10MB.', 'wp-office-editor')
            ];
        }
        
        // التعامل مع الرفع
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $overrides = ['test_form' => false];
        $upload = wp_handle_upload($file, $overrides);
        
        if (isset($upload['error'])) {
            return [
                'success' => false,
                'message' => $upload['error']
            ];
        }
        
        // إدراج الملف في مكتبة الوسائط
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $upload['url']
        ];
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attach_id)) {
            return [
                'success' => false,
                'message' => $attach_id->get_error_message()
            ];
        }
        
        // إنشاء بيانات التعريف
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return [
            'success' => true,
            'url' => $upload['url'],
            'id' => $attach_id
        ];
    }
    
    /**
     * التصدير
     */
    public function export_document() {
        $this->verify_nonce();
        $this->verify_permission('edit_posts');
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'docx';
        
        if (!$document_id) {
            wp_die(__('Invalid document ID.', 'wp-office-editor'));
        }
        
        // تصدير المستند
        $result = $this->export->export_document($document_id, $format);
        
        if ($result['success']) {
            // توجيه للتحميل
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . filesize($result['filepath']));
            readfile($result['filepath']);
            
            // تنظيف الملف المؤقت
            unlink($result['filepath']);
            exit;
        } else {
            wp_die($result['message']);
        }
    }
    
    /**
     * توليد محتوى بواسطة الذكاء الاصطناعي
     */
    public function ai_generate() {
        $this->verify_nonce();
        $this->verify_permission('edit_posts');
        
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $context = isset($_POST['context']) ? wp_kses_post($_POST['context']) : '';
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'generate';
        
        if (empty($prompt)) {
            wp_send_json_error(['message' => __('Please enter a prompt.', 'wp-office-editor')]);
        }
        
        // استدعاء خدمة AI
        $result = $this->ai->generate_content($prompt, $context, $action);
        
        if ($result['success']) {
            wp_send_json_success([
                'content' => $result['content'],
                'tokens_used' => $result['tokens_used'],
                'model' => $result['model']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * الحصول على قائمة المستندات
     */
    public function get_documents() {
        $this->verify_nonce();
        $this->verify_permission('edit_posts');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $documents = $this->documents->get_documents($page, $per_page, $search);
        
        wp_send_json_success($documents);
    }
    
    /**
     * حذف المستند
     */
    public function delete_document() {
        $this->verify_nonce();
        $this->verify_permission('delete_posts');
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if (!$document_id) {
            wp_send_json_error(['message' => __('Invalid document ID.', 'wp-office-editor')]);
        }
        
        $result = $this->documents->delete_document($document_id);
        
        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * النشر كمقال
     */
    public function publish_post() {
        $this->verify_nonce();
        $this->verify_permission('publish_posts');
        
        $data = $this->get_request_data();
        
        // إنشاء مقال جديد
        $post_data = [
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        ];
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
        }
        
        // تحديث المستند الأصلي برابط المقال
        if (isset($data['document_id']) && $data['document_id']) {
            update_post_meta($data['document_id'], '_wpoe_published_post', $post_id);
        }
        
        wp_send_json_success([
            'message' => __('Post published successfully.', 'wp-office-editor'),
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
            'edit_post_url' => get_edit_post_link($post_id, '')
        ]);
    }
    
    /**
     * حفظ إعدادات المشاركة
     */
    public function save_sharing() {
        $this->verify_nonce();
        $this->verify_permission('edit_posts');
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        $users = isset($_POST['users']) ? array_map('intval', (array)$_POST['users']) : [];
        
        if (!$document_id) {
            wp_send_json_error(['message' => __('Invalid document ID.', 'wp-office-editor')]);
        }
        
        // حفظ إعدادات المشاركة
        update_post_meta($document_id, '_wpoe_shared_users', $users);
        
        // إنشاء رابط مشاركة فريد
        $share_token = wp_generate_password(32, false);
        update_post_meta($document_id, '_wpoe_share_token', $share_token);
        
        wp_send_json_success([
            'message' => __('Sharing settings saved.', 'wp-office-editor'),
            'share_url' => add_query_arg([
                'wpoe_share' => $share_token,
                'document' => $document_id
            ], site_url('/wp-admin/admin.php?page=wp-office-editor'))
        ]);
    }
    
    /**
     * التحقق من nonce
     */
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpoe_nonce')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'wp-office-editor')]);
        }
    }
    
    /**
     * التحقق من الصلاحيات
     */
    private function verify_permission($capability) {
        if (!current_user_can($capability)) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-office-editor')]);
        }
    }
    
    /**
     * الحصول على بيانات الطلب
     */
    private function get_request_data() {
        return [
            'document_id' => isset($_POST['document_id']) ? intval($_POST['document_id']) : 0,
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft',
            'meta' => isset($_POST['meta']) ? $this->sanitize_meta($_POST['meta']) : []
        ];
    }
    
    /**
     * تنظيف بيانات التعريف
     */
    private function sanitize_meta($meta) {
        if (!is_array($meta)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($meta as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}
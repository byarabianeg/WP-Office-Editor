<?php
class WP_Office_Editor_Collaboration {
    
    private $socket_server_url;
    private $socket_server_port;
    private $is_enabled;
    private $collaboration_key;
    
    public function __construct() {
        $settings = get_option('wpoe_settings', []);
        
        $this->is_enabled = isset($settings['enable_realtime']) ? (bool)$settings['enable_realtime'] : false;
        $this->socket_server_url = isset($settings['socket_server_url']) ? $settings['socket_server_url'] : '';
        $this->socket_server_port = isset($settings['socket_server_port']) ? $settings['socket_server_port'] : 3000;
        
        // مفتاح التعاون الفريد للإضافة
        $this->collaboration_key = 'wpoe_collab_' . md5(get_site_url());
        
        if ($this->is_enabled) {
            $this->init_hooks();
            $this->maybe_start_socket_server();
        }
    }
    
    /**
     * تهيئة الروابط
     */
    private function init_hooks() {
        // إضافة سكريبتات التعاون
        add_action('admin_enqueue_scripts', [$this, 'enqueue_collaboration_scripts']);
        
        // نقاط نهاية AJAX للتعاون
        add_action('wp_ajax_wpoe_get_collaboration_token', [$this, 'get_collaboration_token']);
        add_action('wp_ajax_wpoe_get_collaborators', [$this, 'get_collaborators']);
        add_action('wp_ajax_wpoe_invite_collaborator', [$this, 'invite_collaborator']);
        add_action('wp_ajax_wpoe_remove_collaborator', [$this, 'remove_collaborator']);
        add_action('wp_ajax_wpoe_get_document_history', [$this, 'get_document_history']);
        
        // نقاط نهاية REST API للتعاون
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        
        // معالجة المشاركة عبر الروابط
        add_action('init', [$this, 'handle_share_link']);
    }
    
    /**
     * تحميل سكريبتات التعاون
     */
    public function enqueue_collaboration_scripts($hook) {
        if (strpos($hook, 'wp-office-editor') === false) {
            return;
        }
        
        // Socket.IO Client
        wp_enqueue_script(
            'wpoe-socket-io',
            'https://cdn.socket.io/4.5.0/socket.io.min.js',
            [],
            '4.5.0',
            true
        );
        
        // سكريبت التعاون
        wp_enqueue_script(
            'wpoe-collaboration',
            WPOE_PLUGIN_URL . 'assets/js/collaboration.js',
            ['jquery', 'wpoe-socket-io', 'wpoe-editor'],
            WPOE_VERSION,
            true
        );
        
        // نقل بيانات التعاون لـ JavaScript
        $current_user = wp_get_current_user();
        
        $collaboration_data = [
            'server_url' => $this->socket_server_url ?: $this->get_default_server_url(),
            'server_port' => $this->socket_server_port,
            'user' => [
                'id' => $current_user->ID,
                'name' => $current_user->display_name,
                'email' => $current_user->user_email,
                'avatar' => get_avatar_url($current_user->ID, ['size' => 32]),
                'color' => $this->generate_user_color($current_user->ID)
            ],
            'collaboration_key' => $this->collaboration_key,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpoe_collaboration_nonce')
        ];
        
        wp_localize_script('wpoe-collaboration', 'wpoe_collaboration', $collaboration_data);
    }
    
    /**
     * الحصول على عنوان الخادم الافتراضي
     */
    private function get_default_server_url() {
        return preg_replace('/^https?:\/\//', '', get_site_url());
    }
    
    /**
     * توليد لون فريد للمستخدم
     */
    private function generate_user_color($user_id) {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
            '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2'
        ];
        
        $index = $user_id % count($colors);
        return $colors[$index];
    }
    
    /**
     * بدء تشغيل خادم Socket إذا لزم الأمر
     */
    private function maybe_start_socket_server() {
        // يمكن إضافة منطق لبدء خادم Node.js تلقائياً
        // هذا يتطلب تثبيت Node.js على الخادم
        
        // بدلاً من ذلك، يمكن استخدام خدمة خارجية أو خادم منفصل
    }
    
    /**
     * الحصول على رمز التعاون
     */
    public function get_collaboration_token() {
        // التحقق من nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpoe_collaboration_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $user_id = get_current_user_id();
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if (!$document_id) {
            wp_send_json_error(['message' => 'Invalid document ID']);
        }
        
        // التحقق من صلاحيات المستخدم على المستند
        if (!current_user_can('edit_post', $document_id)) {
            wp_send_json_error(['message' => 'You do not have permission to collaborate on this document']);
        }
        
        // إنشاء أو تحديث رمز التعاون
        $collaboration_token = $this->generate_collaboration_token($document_id, $user_id);
        
        // حفظ معلومات التعاون
        $collaborators = get_post_meta($document_id, '_wpoe_collaborators', true);
        if (!is_array($collaborators)) {
            $collaborators = [];
        }
        
        // إضافة المستخدم الحالي إذا لم يكن موجوداً
        $user_exists = false;
        foreach ($collaborators as $collaborator) {
            if ($collaborator['id'] == $user_id) {
                $user_exists = true;
                break;
            }
        }
        
        if (!$user_exists) {
            $collaborators[] = [
                'id' => $user_id,
                'name' => wp_get_current_user()->display_name,
                'email' => wp_get_current_user()->user_email,
                'joined_at' => current_time('mysql'),
                'role' => 'editor'
            ];
            
            update_post_meta($document_id, '_wpoe_collaborators', $collaborators);
        }
        
        wp_send_json_success([
            'token' => $collaboration_token,
            'document_id' => $document_id,
            'server_url' => $this->socket_server_url ?: $this->get_default_server_url(),
            'server_port' => $this->socket_server_port
        ]);
    }
    
    /**
     * توليد رمز تعاون فريد
     */
    private function generate_collaboration_token($document_id, $user_id) {
        $token_data = [
            'document_id' => $document_id,
            'user_id' => $user_id,
            'timestamp' => time(),
            'expires' => time() + (24 * 60 * 60) // 24 ساعة
        ];
        
        $token = base64_encode(json_encode($token_data));
        $signature = hash_hmac('sha256', $token, $this->collaboration_key);
        
        return $token . '.' . $signature;
    }
    
    /**
     * التحقق من رمز التعاون
     */
    public function verify_collaboration_token($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 2) {
            return false;
        }
        
        $token_data = $parts[0];
        $signature = $parts[1];
        
        // التحقق من التوقيع
        $expected_signature = hash_hmac('sha256', $token_data, $this->collaboration_key);
        
        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }
        
        // فك تشفير بيانات الرمز
        $data = json_decode(base64_decode($token_data), true);
        
        if (!$data) {
            return false;
        }
        
        // التحقق من انتهاء الصلاحية
        if (isset($data['expires']) && $data['expires'] < time()) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * الحصول على قائمة المتعاونين
     */
    public function get_collaborators() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpoe_collaboration_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if (!$document_id) {
            wp_send_json_error(['message' => 'Invalid document ID']);
        }
        
        $collaborators = get_post_meta($document_id, '_wpoe_collaborators', true);
        
        if (!is_array($collaborators)) {
            $collaborators = [];
        }
        
        // إضافة معلومات إضافية للمستخدمين
        foreach ($collaborators as &$collaborator) {
            $user = get_user_by('id', $collaborator['id']);
            if ($user) {
                $collaborator['avatar'] = get_avatar_url($collaborator['id'], ['size' => 32]);
                $collaborator['color'] = $this->generate_user_color($collaborator['id']);
                $collaborator['is_online'] = $this->is_user_online($collaborator['id'], $document_id);
                $collaborator['last_active'] = get_user_meta($collaborator['id'], 'wpoe_last_active', true);
            }
        }
        
        wp_send_json_success([
            'collaborators' => $collaborators,
            'total' => count($collaborators)
        ]);
    }
    
    /**
     * التحقق مما إذا كان المستخدم متصلاً
     */
    private function is_user_online($user_id, $document_id) {
        // يمكن تنفيذ هذا باستخدام قاعدة بيانات أو نظام تخزين مناسب
        // هذا مثال مبسط
        $last_active = get_user_meta($user_id, 'wpoe_last_active', true);
        
        if (!$last_active) {
            return false;
        }
        
        $last_active_time = strtotime($last_active);
        $current_time = time();
        
        // إذا كان النشاط خلال آخر 5 دقائق، يعتبر متصلاً
        return ($current_time - $last_active_time) < 300;
    }
    
    /**
     * دعوة متعاون جديد
     */
    public function invite_collaborator() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpoe_collaboration_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'editor';
        
        if (!$document_id || !$email) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        // التحقق من صلاحيات المستخدم الحالي
        if (!current_user_can('edit_post', $document_id)) {
            wp_send_json_error(['message' => 'You do not have permission to invite collaborators']);
        }
        
        // البحث عن المستخدم بالبريد الإلكتروني
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // إذا لم يكن المستخدم موجوداً، يمكن إنشاء دعوة أو إرسال بريد إلكتروني
            wp_send_json_error(['message' => 'User not found']);
        }
        
        // الحصول على قائمة المتعاونين الحالية
        $collaborators = get_post_meta($document_id, '_wpoe_collaborators', true);
        if (!is_array($collaborators)) {
            $collaborators = [];
        }
        
        // التحقق مما إذا كان المستخدم مدعواً بالفعل
        foreach ($collaborators as $collaborator) {
            if ($collaborator['id'] == $user->ID) {
                wp_send_json_error(['message' => 'User is already a collaborator']);
            }
        }
        
        // إضافة المتعاون الجديد
        $collaborators[] = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'invited_by' => get_current_user_id(),
            'invited_at' => current_time('mysql'),
            'role' => $role,
            'status' => 'invited'
        ];
        
        update_post_meta($document_id, '_wpoe_collaborators', $collaborators);
        
        // إرسال بريد إلكتروني بالدعوة
        $this->send_invitation_email($user, $document_id);
        
        wp_send_json_success([
            'message' => 'Invitation sent successfully',
            'collaborator' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'role' => $role
            ]
        ]);
    }
    
    /**
     * إرسال بريد دعوة
     */
    private function send_invitation_email($user, $document_id) {
        $document = get_post($document_id);
        $inviter = wp_get_current_user();
        
        $subject = sprintf(__('You have been invited to collaborate on "%s"', 'wp-office-editor'), $document->post_title);
        
        $message = sprintf(
            __('Hello %s,', 'wp-office-editor'),
            $user->display_name
        ) . "\n\n";
        
        $message .= sprintf(
            __('%s has invited you to collaborate on the document "%s".', 'wp-office-editor'),
            $inviter->display_name,
            $document->post_title
        ) . "\n\n";
        
        $message .= __('To access the document, click the link below:', 'wp-office-editor') . "\n";
        $message .= $this->get_collaboration_link($document_id, $user->ID) . "\n\n";
        
        $message .= __('Best regards,', 'wp-office-editor') . "\n";
        $message .= __('WP Office Editor Team', 'wp-office-editor');
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * الحصول على رابط التعاون
     */
    private function get_collaboration_link($document_id, $user_id) {
        $token_data = [
            'document_id' => $document_id,
            'user_id' => $user_id,
            'timestamp' => time()
        ];
        
        $token = base64_encode(json_encode($token_data));
        $signature = hash_hmac('sha256', $token, $this->collaboration_key);
        
        $collaboration_token = $token . '.' . $signature;
        
        return add_query_arg([
            'wpoe_collaborate' => $collaboration_token,
            'document' => $document_id
        ], admin_url('admin.php?page=wp-office-editor'));
    }
    
    /**
     * إزالة متعاون
     */
    public function remove_collaborator() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpoe_collaboration_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$document_id || !$user_id) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        // التحقق من صلاحيات المستخدم الحالي
        if (!current_user_can('edit_post', $document_id)) {
            wp_send_json_error(['message' => 'You do not have permission to remove collaborators']);
        }
        
        // الحصول على قائمة المتعاونين
        $collaborators = get_post_meta($document_id, '_wpoe_collaborators', true);
        
        if (!is_array($collaborators)) {
            wp_send_json_error(['message' => 'No collaborators found']);
        }
        
        // البحث عن المتعاون وإزالته
        $new_collaborators = [];
        $removed = false;
        
        foreach ($collaborators as $collaborator) {
            if ($collaborator['id'] == $user_id) {
                $removed = true;
                continue;
            }
            $new_collaborators[] = $collaborator;
        }
        
        if ($removed) {
            update_post_meta($document_id, '_wpoe_collaborators', $new_collaborators);
            wp_send_json_success(['message' => 'Collaborator removed successfully']);
        } else {
            wp_send_json_error(['message' => 'Collaborator not found']);
        }
    }
    
    /**
     * الحصول على تاريخ المستند (التغييرات)
     */
    public function get_document_history() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpoe_collaboration_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        
        if (!$document_id) {
            wp_send_json_error(['message' => 'Invalid document ID']);
        }
        
        // الحصول على مراجعات المستند
        $revisions = wp_get_post_revisions($document_id, [
            'numberposts' => $limit,
            'order' => 'DESC'
        ]);
        
        $history = [];
        
        foreach ($revisions as $revision) {
            $author = get_user_by('id', $revision->post_author);
            
            $history[] = [
                'id' => $revision->ID,
                'date' => $revision->post_date,
                'date_gmt' => $revision->post_date_gmt,
                'author' => $author ? [
                    'id' => $author->ID,
                    'name' => $author->display_name,
                    'avatar' => get_avatar_url($author->ID, ['size' => 32])
                ] : null,
                'content_diff' => $this->get_content_diff($document_id, $revision->ID),
                'message' => $revision->post_excerpt
            ];
        }
        
        wp_send_json_success([
            'history' => $history,
            'total' => count($history)
        ]);
    }
    
    /**
     * الحصول على الفرق بين المحتوى الحالي والمراجعة
     */
    private function get_content_diff($document_id, $revision_id) {
        $current_content = get_post_field('post_content', $document_id);
        $revision_content = get_post_field('post_content', $revision_id);
        
        // حساب الفروق البسيطة (يمكن استخدام مكتبة متقدمة للفروق)
        $diff = [
            'current_length' => strlen($current_content),
            'revision_length' => strlen($revision_content),
            'length_diff' => strlen($current_content) - strlen($revision_content),
            'has_changes' => $current_content !== $revision_content
        ];
        
        return $diff;
    }
    
    /**
     * تسجيل نقاط نهاية REST API
     */
    public function register_rest_endpoints() {
        register_rest_route('wpoe/v1', '/collaboration/status', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_collaboration_status'],
            'permission_callback' => [$this, 'rest_permission_check']
        ]);
        
        register_rest_route('wpoe/v1', '/collaboration/update', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_update_collaboration'],
            'permission_callback' => [$this, 'rest_permission_check']
        ]);
        
        register_rest_route('wpoe/v1', '/collaboration/cursor', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_update_cursor'],
            'permission_callback' => [$this, 'rest_permission_check']
        ]);
    }
    
    /**
     * التحقق من صلاحيات REST API
     */
    public function rest_permission_check($request) {
        // يمكن إضافة منطق التحقق من الصلاحيات هنا
        return current_user_can('edit_posts');
    }
    
    /**
     * الحصول على حالة التعاون (REST)
     */
    public function rest_get_collaboration_status($request) {
        $document_id = $request->get_param('document_id');
        
        if (!$document_id) {
            return new WP_Error('missing_document_id', 'Document ID is required', ['status' => 400]);
        }
        
        $collaborators = get_post_meta($document_id, '_wpoe_collaborators', true);
        
        return rest_ensure_response([
            'document_id' => $document_id,
            'collaborators' => is_array($collaborators) ? $collaborators : [],
            'last_modified' => get_post_modified_time('c', false, $document_id),
            'is_locked' => get_post_meta($document_id, '_wpoe_locked', true) === 'yes',
            'locked_by' => get_post_meta($document_id, '_wpoe_locked_by', true)
        ]);
    }
    
    /**
     * تحديث التعاون (REST)
     */
    public function rest_update_collaboration($request) {
        $document_id = $request->get_param('document_id');
        $content = $request->get_param('content');
        $user_id = $request->get_param('user_id');
        
        if (!$document_id || !$content || !$user_id) {
            return new WP_Error('missing_params', 'Missing required parameters', ['status' => 400]);
        }
        
        // التحقق من قفل المستند
        $locked_by = get_post_meta($document_id, '_wpoe_locked_by', true);
        $is_locked = get_post_meta($document_id, '_wpoe_locked', true) === 'yes';
        
        if ($is_locked && $locked_by != $user_id) {
            return new WP_Error('document_locked', 'Document is locked by another user', ['status' => 423]);
        }
        
        // تحديث المحتوى
        $result = wp_update_post([
            'ID' => $document_id,
            'post_content' => wp_kses_post($content)
        ], true);
        
        if (is_wp_error($result)) {
            return new WP_Error('update_failed', 'Failed to update document', ['status' => 500]);
        }
        
        // تسجيل التغيير في المراجعات
        wp_save_post_revision($document_id);
        
        // تحديث وقت التعديل الأخير
        update_post_meta($document_id, '_wpoe_last_collaborator', $user_id);
        update_post_meta($document_id, '_wpoe_last_collaboration', current_time('mysql'));
        
        return rest_ensure_response([
            'success' => true,
            'document_id' => $document_id,
            'updated_at' => current_time('mysql')
        ]);
    }
    
    /**
     * تحديث موضع المؤشر (REST)
     */
    public function rest_update_cursor($request) {
        $document_id = $request->get_param('document_id');
        $user_id = $request->get_param('user_id');
        $cursor_position = $request->get_param('cursor_position');
        $selection_range = $request->get_param('selection_range');
        
        if (!$document_id || !$user_id) {
            return new WP_Error('missing_params', 'Missing required parameters', ['status' => 400]);
        }
        
        // تخزين موضع المؤشر مؤقتاً
        $cursor_key = 'wpoe_cursor_' . $document_id . '_' . $user_id;
        $cursor_data = [
            'user_id' => $user_id,
            'document_id' => $document_id,
            'cursor_position' => $cursor_position,
            'selection_range' => $selection_range,
            'updated_at' => time()
        ];
        
        // تخزين لمدة 10 ثواني
        set_transient($cursor_key, $cursor_data, 10);
        
        // الحصول على جميع مواضع المؤشرات النشطة
        $all_cursors = [];
        $collaborators = get_post_meta($document_id, '_wpoe_collaborators', true);
        
        if (is_array($collaborators)) {
            foreach ($collaborators as $collaborator) {
                $collaborator_cursor_key = 'wpoe_cursor_' . $document_id . '_' . $collaborator['id'];
                $collaborator_cursor = get_transient($collaborator_cursor_key);
                
                if ($collaborator_cursor && $collaborator['id'] != $user_id) {
                    $all_cursors[] = $collaborator_cursor;
                }
            }
        }
        
        return rest_ensure_response([
            'success' => true,
            'cursor_data' => $cursor_data,
            'other_cursors' => $all_cursors
        ]);
    }
    
    /**
     * معالجة رابط المشاركة
     */
    public function handle_share_link() {
        if (!isset($_GET['wpoe_collaborate'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['wpoe_collaborate']);
        $document_id = isset($_GET['document']) ? intval($_GET['document']) : 0;
        
        $token_data = $this->verify_collaboration_token($token);
        
        if (!$token_data || !$document_id) {
            wp_die(__('Invalid collaboration link', 'wp-office-editor'));
        }
        
        // التحقق من تطابق معرف المستند
        if ($token_data['document_id'] != $document_id) {
            wp_die(__('Document mismatch', 'wp-office-editor'));
        }
        
        // تسجيل دخول المستخدم إذا لزم الأمر
        $user_id = $token_data['user_id'];
        
        if (!is_user_logged_in()) {
            // يمكن إضافة منطق لتسجيل الدخول التلقائي هنا
            wp_die(__('Please log in to collaborate', 'wp-office-editor'));
        }
        
        // التحقق من أن المستخدم المسجل هو نفس المستخدم في الرمز
        if (get_current_user_id() != $user_id) {
            wp_die(__('You are not authorized to access this document', 'wp-office-editor'));
        }
        
        // إعادة التوجيه إلى صفحة المحرر
        wp_redirect(admin_url('admin.php?page=wp-office-editor&document=' . $document_id));
        exit;
    }
    
    /**
     * قفل المستند لمنع التعديلات المتضاربة
     */
    public function lock_document($document_id, $user_id) {
        update_post_meta($document_id, '_wpoe_locked', 'yes');
        update_post_meta($document_id, '_wpoe_locked_by', $user_id);
        update_post_meta($document_id, '_wpoe_locked_at', current_time('mysql'));
        
        return true;
    }
    
    /**
     * فتح قفل المستند
     */
    public function unlock_document($document_id, $user_id) {
        $locked_by = get_post_meta($document_id, '_wpoe_locked_by', true);
        
        if ($locked_by == $user_id) {
            delete_post_meta($document_id, '_wpoe_locked');
            delete_post_meta($document_id, '_wpoe_locked_by');
            delete_post_meta($document_id, '_wpoe_locked_at');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * التحقق مما إذا كان المستند مقفولاً
     */
    public function is_document_locked($document_id) {
        return get_post_meta($document_id, '_wpoe_locked', true) === 'yes';
    }
    
    /**
     * الحصول على معلومات القفل
     */
    public function get_lock_info($document_id) {
        return [
            'is_locked' => $this->is_document_locked($document_id),
            'locked_by' => get_post_meta($document_id, '_wpoe_locked_by', true),
            'locked_at' => get_post_meta($document_id, '_wpoe_locked_at', true)
        ];
    }
    
    /**
     * إنشاء نسخة من المستند للتعاون
     */
    public function create_collaboration_copy($document_id, $user_id) {
        $original = get_post($document_id);
        
        if (!$original) {
            return false;
        }
        
        // إنشاء نسخة جديدة
        $new_post = [
            'post_title' => $original->post_title . ' (Collaboration Copy)',
            'post_content' => $original->post_content,
            'post_status' => 'draft',
            'post_type' => 'wpoe_document',
            'post_author' => $user_id
        ];
        
        $new_id = wp_insert_post($new_post);
        
        if ($new_id) {
            // نسخة البيانات التعريفية
            $meta_keys = [
                '_wpoe_collaborators',
                '_wpoe_shared_users',
                '_wpoe_settings'
            ];
            
            foreach ($meta_keys as $key) {
                $value = get_post_meta($document_id, $key, true);
                if ($value) {
                    update_post_meta($new_id, $key, $value);
                }
            }
            
            // إضافة المستخدم الحالي كمتعاون
            $collaborators = get_post_meta($new_id, '_wpoe_collaborators', true);
            if (!is_array($collaborators)) {
                $collaborators = [];
            }
            
            $collaborators[] = [
                'id' => $user_id,
                'name' => wp_get_current_user()->display_name,
                'email' => wp_get_current_user()->user_email,
                'joined_at' => current_time('mysql'),
                'role' => 'owner'
            ];
            
            update_post_meta($new_id, '_wpoe_collaborators', $collaborators);
            
            return $new_id;
        }
        
        return false;
    }
    
    /**
     * دمج التغييرات من نسخة التعاون
     */
    public function merge_collaboration_changes($source_id, $target_id) {
        $source = get_post($source_id);
        $target = get_post($target_id);
        
        if (!$source || !$target) {
            return false;
        }
        
        // دمج المحتوى
        $merged_content = $target->post_content . "\n\n--- Collaboration Changes ---\n\n" . $source->post_content;
        
        // تحديث المستند الهدف
        $result = wp_update_post([
            'ID' => $target_id,
            'post_content' => $merged_content
        ], true);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // تسجيل عملية الدمج
        update_post_meta($target_id, '_wpoe_merged_from', $source_id);
        update_post_meta($target_id, '_wpoe_merged_at', current_time('mysql'));
        
        // حذف نسخة التعاون إذا لزم الأمر
        wp_delete_post($source_id, true);
        
        return true;
    }
    
    /**
     * الحصول على إحصائيات التعاون
     */
    public function get_collaboration_stats($document_id) {
        $collaborators = get_post_meta($document_id, '_wpoe_collaborators', true);
        
        if (!is_array($collaborators)) {
            return [
                'total_collaborators' => 0,
                'active_collaborators' => 0,
                'total_changes' => 0,
                'last_collaboration' => null
            ];
        }
        
        $active_collaborators = 0;
        $current_time = time();
        
        foreach ($collaborators as $collaborator) {
            $last_active = get_user_meta($collaborator['id'], 'wpoe_last_active', true);
            
            if ($last_active) {
                $last_active_time = strtotime($last_active);
                if (($current_time - $last_active_time) < 300) { // 5 دقائق
                    $active_collaborators++;
                }
            }
        }
        
        // الحصول على عدد المراجعات
        $revisions = wp_get_post_revisions($document_id);
        
        return [
            'total_collaborators' => count($collaborators),
            'active_collaborators' => $active_collaborators,
            'total_changes' => count($revisions),
            'last_collaboration' => get_post_meta($document_id, '_wpoe_last_collaboration', true),
            'collaboration_time' => get_post_meta($document_id, '_wpoe_collaboration_time', true)
        ];
    }
}
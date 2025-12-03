<?php
class WP_Office_Editor_Admin {
    
    private $plugin_name;
    private $version;
    private $settings;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = get_option('wpoe_settings', []);
    }
    
    public function add_menu_pages() {
        // الصفحة الرئيسية للمحرر
        $editor_hook = add_menu_page(
            __('WP Office Editor', 'wp-office-editor'),
            __('Office Editor', 'wp-office-editor'),
            'edit_posts',
            'wp-office-editor',
            [$this, 'display_editor_page'],
            'dashicons-edit',
            25
        );
        
        // تحميل السكريبتات فقط في صفحة المحرر
        add_action('load-' . $editor_hook, [$this, 'load_editor_assets']);
        
        // صفحة المستندات
        add_submenu_page(
            'wp-office-editor',
            __('Documents', 'wp-office-editor'),
            __('Documents', 'wp-office-editor'),
            'edit_posts',
            'wpoe-documents',
            [$this, 'display_documents_page']
        );
        
        // صفحة الإعدادات
        add_submenu_page(
            'wp-office-editor',
            __('Settings', 'wp-office-editor'),
            __('Settings', 'wp-office-editor'),
            'manage_options',
            'wpoe-settings',
            [$this, 'display_settings_page']
        );
        
        // صفحة قوالب AI (مخفي من القائمة الرئيسية)
        add_submenu_page(
            null,
            __('AI Templates', 'wp-office-editor'),
            __('AI Templates', 'wp-office-editor'),
            'edit_posts',
            'wpoe-ai-templates',
            [$this, 'display_ai_templates_page']
        );
    }
    
    public function load_editor_assets() {
        // هذا سيتم تنفيذه فقط عند تحميل صفحة المحرر
        add_action('admin_enqueue_scripts', [$this, 'enqueue_editor_assets']);
    }
    
    public function display_editor_page() {
        // التحقق من الصلاحيات
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to access this page.', 'wp-office-editor'));
        }
        
        // بيانات الجلسة
        $document_id = isset($_GET['document']) ? intval($_GET['document']) : 0;
        $document_title = $document_id ? get_the_title($document_id) : __('New Document', 'wp-office-editor');
        
        include WPOE_PLUGIN_DIR . 'admin/views/editor-page.php';
    }
    
    public function display_documents_page() {
        include WPOE_PLUGIN_DIR . 'admin/views/documents-page.php';
    }
    
    public function display_settings_page() {
        include WPOE_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    public function display_ai_templates_page() {
        include WPOE_PLUGIN_DIR . 'admin/views/ai-templates-page.php';
    }
    
    public function enqueue_styles($hook) {
        // تحميل الأنماط فقط في صفحات الإضافة
        if (strpos($hook, 'wp-office-editor') === false && strpos($hook, 'wpoe-') === false) {
            return;
        }
        
        // CSS الأساسي
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            WPOE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $this->version,
            'all'
        );
        
        // Font Awesome للأيقونات
        wp_enqueue_style(
            $this->plugin_name . '-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
    }
    
    public function enqueue_editor_assets($hook) {
        // تحميل فقط في صفحة المحرر
        if ($hook !== 'toplevel_page_wp-office-editor') {
            return;
        }
        
        // CKEditor المحلي
        wp_enqueue_script(
            'wpoe-ckeditor',
            WPOE_PLUGIN_URL . 'assets/vendor/ckeditor5/ckeditor.js',
            [],
            $this->version,
            true
        );
        
        // المحرر الرئيسي
        wp_enqueue_script(
            'wpoe-editor',
            WPOE_PLUGIN_URL . 'assets/js/editor.js',
            ['jquery', 'wpoe-ckeditor'],
            $this->version,
            true
        );
        
        // نقل البيانات لـ JavaScript
        $localized_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpoe_nonce'),
            'current_user' => [
                'id' => get_current_user_id(),
                'name' => wp_get_current_user()->display_name,
                'avatar' => get_avatar_url(get_current_user_id(), ['size' => 32])
            ],
            'site_url' => site_url(),
            'admin_url' => admin_url(),
            'plugin_url' => WPOE_PLUGIN_URL,
            'settings' => $this->settings,
            'document_id' => isset($_GET['document']) ? intval($_GET['document']) : 0,
            'i18n' => $this->get_i18n_strings()
        ];
        
        wp_localize_script('wpoe-editor', 'wpoe_data', $localized_data);
        
        // Socket.IO للتعاون في الوقت الحقيقي (إذا كان مفعلاً)
        if (!empty($this->settings['enable_realtime'])) {
            wp_enqueue_script(
                'wpoe-socketio',
                'https://cdn.socket.io/4.5.0/socket.io.min.js',
                [],
                '4.5.0',
                true
            );
        }
    }
    
    public function enqueue_scripts($hook) {
        // لا نحتاج هذا الآن لأننا نستخدم enqueue_editor_assets
    }
    
    private function get_i18n_strings() {
        return [
            'save' => __('Save', 'wp-office-editor'),
            'saving' => __('Saving...', 'wp-office-editor'),
            'saved' => __('Saved', 'wp-office-editor'),
            'unsaved' => __('Unsaved Changes', 'wp-office-editor'),
            'publish' => __('Publish', 'wp-office-editor'),
            'export' => __('Export', 'wp-office-editor'),
            'new_document' => __('New Document', 'wp-office-editor'),
            'open_document' => __('Open Document', 'wp-office-editor'),
            'confirm_delete' => __('Are you sure you want to delete this document?', 'wp-office-editor'),
            'confirm_leave' => __('You have unsaved changes. Are you sure you want to leave?', 'wp-office-editor'),
            'ai_generating' => __('AI is generating content...', 'wp-office-editor'),
            'ai_error' => __('Error generating AI content', 'wp-office-editor'),
            'share' => __('Share', 'wp-office-editor'),
            'copy' => __('Copy', 'wp-office-editor'),
            'copied' => __('Copied!', 'wp-office-editor'),
            'words' => __('words', 'wp-office-editor'),
            'characters' => __('characters', 'wp-office-editor')
        ];
    }
    
    public function register_settings() {
        register_setting('wpoe_settings_group', 'wpoe_settings', [$this, 'sanitize_settings']);
        
        // قسم عام
        add_settings_section(
            'wpoe_general_section',
            __('General Settings', 'wp-office-editor'),
            [$this, 'general_section_callback'],
            'wpoe-settings'
        );
        
        // AI API Key
        add_settings_field(
            'wpoe_ai_api_key',
            __('AI API Key', 'wp-office-editor'),
            [$this, 'ai_api_key_callback'],
            'wpoe-settings',
            'wpoe_general_section'
        );
        
        // تمكين التعاون في الوقت الحقيقي
        add_settings_field(
            'wpoe_enable_realtime',
            __('Enable Real-time Collaboration', 'wp-office-editor'),
            [$this, 'enable_realtime_callback'],
            'wpoe-settings',
            'wpoe_general_section'
        );
        
        // أنواع التصدير المدعومة
        add_settings_field(
            'wpoe_export_formats',
            __('Export Formats', 'wp-office-editor'),
            [$this, 'export_formats_callback'],
            'wpoe-settings',
            'wpoe_general_section'
        );
        
        // Auto-save interval
        add_settings_field(
            'wpoe_auto_save_interval',
            __('Auto-save Interval (seconds)', 'wp-office-editor'),
            [$this, 'auto_save_interval_callback'],
            'wpoe-settings',
            'wpoe_general_section'
        );
    }
    
    public function sanitize_settings($input) {
        $sanitized = [];
        
        if (isset($input['ai_api_key'])) {
            $sanitized['ai_api_key'] = sanitize_text_field($input['ai_api_key']);
        }
        
        if (isset($input['enable_realtime'])) {
            $sanitized['enable_realtime'] = absint($input['enable_realtime']);
        }
        
        if (isset($input['export_formats'])) {
            $allowed_formats = ['docx', 'pdf', 'odt', 'html'];
            $sanitized['export_formats'] = array_intersect($input['export_formats'], $allowed_formats);
        }
        
        if (isset($input['auto_save_interval'])) {
            $interval = absint($input['auto_save_interval']);
            $sanitized['auto_save_interval'] = min(max($interval, 10), 300); // بين 10 و 300 ثانية
        }
        
        return $sanitized;
    }
    
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for WP Office Editor.', 'wp-office-editor') . '</p>';
    }
    
    public function ai_api_key_callback() {
        $value = isset($this->settings['ai_api_key']) ? $this->settings['ai_api_key'] : '';
        echo '<input type="password" id="wpoe_ai_api_key" name="wpoe_settings[ai_api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your OpenAI API key for AI features.', 'wp-office-editor') . '</p>';
    }
    
    public function enable_realtime_callback() {
        $value = isset($this->settings['enable_realtime']) ? $this->settings['enable_realtime'] : 0;
        echo '<label><input type="checkbox" id="wpoe_enable_realtime" name="wpoe_settings[enable_realtime]" value="1" ' . checked(1, $value, false) . ' /> ' . __('Enable real-time collaboration', 'wp-office-editor') . '</label>';
        echo '<p class="description">' . __('Allow multiple users to edit the same document simultaneously.', 'wp-office-editor') . '</p>';
    }
    
    public function export_formats_callback() {
        $formats = [
            'docx' => 'Word (.docx)',
            'pdf' => 'PDF',
            'odt' => 'OpenDocument (.odt)',
            'html' => 'HTML'
        ];
        
        $selected = isset($this->settings['export_formats']) ? $this->settings['export_formats'] : ['docx', 'pdf'];
        
        foreach ($formats as $key => $label) {
            $checked = in_array($key, $selected) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="wpoe_settings[export_formats][]" value="' . esc_attr($key) . '" ' . $checked . ' /> ';
            echo esc_html($label);
            echo '</label>';
        }
    }
    
    public function auto_save_interval_callback() {
        $value = isset($this->settings['auto_save_interval']) ? $this->settings['auto_save_interval'] : 30;
        echo '<input type="number" id="wpoe_auto_save_interval" name="wpoe_settings[auto_save_interval]" value="' . esc_attr($value) . '" min="10" max="300" step="5" class="small-text" />';
        echo '<p class="description">' . __('How often to auto-save documents (in seconds).', 'wp-office-editor') . '</p>';
    }
}
<?php
class WP_Office_Editor_Tabs {
    
    private $tabs;
    private $current_tab;
    private $max_tabs;
    
    public function __construct() {
        $this->tabs = [];
        $this->current_tab = 0;
        $this->max_tabs = 10; // الحد الأقصى للألسنة المفتوحة
        
        $this->init_session();
    }
    
    /**
     * تهيئة جلسة الألسنة
     */
    private function init_session() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $session_key = 'wpoe_tabs_' . get_current_user_id();
        
        if (!isset($_SESSION[$session_key])) {
            $_SESSION[$session_key] = [
                'tabs' => [],
                'current_tab' => 0,
                'last_tab_id' => 0
            ];
        }
        
        $this->tabs = $_SESSION[$session_key]['tabs'];
        $this->current_tab = $_SESSION[$session_key]['current_tab'];
    }
    
    /**
     * حفظ جلسة الألسنة
     */
    private function save_session() {
        $session_key = 'wpoe_tabs_' . get_current_user_id();
        $_SESSION[$session_key] = [
            'tabs' => $this->tabs,
            'current_tab' => $this->current_tab,
            'last_tab_id' => $this->get_last_tab_id()
        ];
    }
    
    /**
     * إنشاء تبويب جديد
     */
    public function create_tab($title = '', $document_id = 0, $content = '', $is_new = true) {
        if (count($this->tabs) >= $this->max_tabs) {
            return [
                'success' => false,
                'message' => __('Maximum number of tabs reached.', 'wp-office-editor')
            ];
        }
        
        $tab_id = $this->generate_tab_id();
        
        $tab = [
            'id' => $tab_id,
            'title' => $title ?: __('New Document', 'wp-office-editor'),
            'document_id' => $document_id,
            'content' => $content,
            'is_new' => $is_new,
            'has_unsaved_changes' => false,
            'status' => 'draft',
            'created_at' => current_time('mysql'),
            'last_modified' => current_time('mysql'),
            'auto_save_data' => null,
            'metadata' => [
                'word_count' => 0,
                'char_count' => 0,
                'zoom_level' => 100,
                'view_mode' => 'edit'
            ]
        ];
        
        $this->tabs[$tab_id] = $tab;
        $this->current_tab = $tab_id;
        $this->save_session();
        
        return [
            'success' => true,
            'tab' => $tab,
            'tab_id' => $tab_id
        ];
    }
    
    /**
     * توليد معرف فريد للتبويب
     */
    private function generate_tab_id() {
        $last_id = $this->get_last_tab_id();
        $new_id = $last_id + 1;
        
        // تحديث آخر معرف في الجلسة
        $session_key = 'wpoe_tabs_' . get_current_user_id();
        if (isset($_SESSION[$session_key])) {
            $_SESSION[$session_key]['last_tab_id'] = $new_id;
        }
        
        return 'tab_' . $new_id;
    }
    
    /**
     * الحصول على آخر معرف تبويب
     */
    private function get_last_tab_id() {
        $session_key = 'wpoe_tabs_' . get_current_user_id();
        return isset($_SESSION[$session_key]['last_tab_id']) ? $_SESSION[$session_key]['last_tab_id'] : 0;
    }
    
    /**
     * تحديث بيانات التبويب
     */
    public function update_tab($tab_id, $data) {
        if (!isset($this->tabs[$tab_id])) {
            return [
                'success' => false,
                'message' => __('Tab not found.', 'wp-office-editor')
            ];
        }
        
        $allowed_fields = [
            'title', 'content', 'document_id', 'is_new', 
            'has_unsaved_changes', 'status', 'metadata'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                if ($key === 'metadata' && is_array($value)) {
                    $this->tabs[$tab_id][$key] = array_merge(
                        $this->tabs[$tab_id][$key] ?? [],
                        $value
                    );
                } else {
                    $this->tabs[$tab_id][$key] = $value;
                }
            }
        }
        
        $this->tabs[$tab_id]['last_modified'] = current_time('mysql');
        $this->save_session();
        
        return [
            'success' => true,
            'tab' => $this->tabs[$tab_id]
        ];
    }
    
    /**
     * الحصول على تبويب
     */
    public function get_tab($tab_id) {
        if (!isset($this->tabs[$tab_id])) {
            return null;
        }
        
        return $this->tabs[$tab_id];
    }
    
    /**
     * الحصول على جميع الألسنة
     */
    public function get_all_tabs() {
        return $this->tabs;
    }
    
    /**
     * الحصول على التبويب الحالي
     */
    public function get_current_tab() {
        if (isset($this->tabs[$this->current_tab])) {
            return $this->tabs[$this->current_tab];
        }
        
        // إذا لم يكن هناك تبويب حالي، إنشاء واحد جديد
        return $this->create_default_tab();
    }
    
    /**
     * إنشاء تبويب افتراضي
     */
    private function create_default_tab() {
        $result = $this->create_tab();
        return $result['tab'] ?? null;
    }
    
    /**
     * تغيير التبويب الحالي
     */
    public function switch_tab($tab_id) {
        if (!isset($this->tabs[$tab_id])) {
            return [
                'success' => false,
                'message' => __('Tab not found.', 'wp-office-editor')
            ];
        }
        
        $previous_tab = $this->current_tab;
        $this->current_tab = $tab_id;
        $this->save_session();
        
        return [
            'success' => true,
            'previous_tab' => $previous_tab,
            'current_tab' => $tab_id,
            'tab' => $this->tabs[$tab_id]
        ];
    }
    
    /**
     * إغلاق تبويب
     */
    public function close_tab($tab_id) {
        if (!isset($this->tabs[$tab_id])) {
            return [
                'success' => false,
                'message' => __('Tab not found.', 'wp-office-editor')
            ];
        }
        
        $tab_to_close = $this->tabs[$tab_id];
        
        // إذا كان التبويب يحتوي على تغييرات غير محفوظة، التحذير
        if ($tab_to_close['has_unsaved_changes']) {
            return [
                'success' => false,
                'has_unsaved_changes' => true,
                'tab' => $tab_to_close
            ];
        }
        
        // إزالة التبويب
        unset($this->tabs[$tab_id]);
        
        // إذا كان التبويب المغلق هو الحالي، تغيير التبويب الحالي
        if ($this->current_tab === $tab_id) {
            if (!empty($this->tabs)) {
                $this->current_tab = array_key_first($this->tabs);
            } else {
                // إنشاء تبويب جديد إذا لم يتبقى أي تبويب
                $result = $this->create_tab();
                $this->current_tab = $result['tab_id'];
            }
        }
        
        $this->save_session();
        
        return [
            'success' => true,
            'closed_tab' => $tab_to_close,
            'current_tab' => $this->current_tab
        ];
    }
    
    /**
     * إغلاق جميع الألسنة
     */
    public function close_all_tabs() {
        $closed_tabs = $this->tabs;
        $this->tabs = [];
        
        // إنشاء تبويب جديد
        $result = $this->create_tab();
        $this->current_tab = $result['tab_id'];
        
        $this->save_session();
        
        return [
            'success' => true,
            'closed_tabs' => $closed_tabs,
            'new_tab' => $this->tabs[$this->current_tab]
        ];
    }
    
    /**
     * إغلاق جميع الألسنة ما عدا المحدد
     */
    public function close_other_tabs($keep_tab_id) {
        if (!isset($this->tabs[$keep_tab_id])) {
            return [
                'success' => false,
                'message' => __('Tab to keep not found.', 'wp-office-editor')
            ];
        }
        
        $closed_tabs = [];
        foreach ($this->tabs as $tab_id => $tab) {
            if ($tab_id !== $keep_tab_id) {
                if (!$tab['has_unsaved_changes']) {
                    $closed_tabs[$tab_id] = $tab;
                    unset($this->tabs[$tab_id]);
                }
            }
        }
        
        $this->current_tab = $keep_tab_id;
        $this->save_session();
        
        return [
            'success' => true,
            'closed_tabs' => $closed_tabs,
            'kept_tab' => $this->tabs[$keep_tab_id]
        ];
    }
    
    /**
     * حفظ بيانات النسخ الاحتياطي للتبويب
     */
    public function save_tab_backup($tab_id, $data) {
        if (!isset($this->tabs[$tab_id])) {
            return false;
        }
        
        $this->tabs[$tab_id]['auto_save_data'] = [
            'data' => $data,
            'saved_at' => current_time('mysql'),
            'size' => strlen(serialize($data))
        ];
        
        $this->save_session();
        
        return true;
    }
    
    /**
     * استعادة بيانات النسخ الاحتياطي
     */
    public function restore_tab_backup($tab_id) {
        if (!isset($this->tabs[$tab_id]) || !isset($this->tabs[$tab_id]['auto_save_data'])) {
            return null;
        }
        
        return $this->tabs[$tab_id]['auto_save_data']['data'];
    }
    
    /**
     * تحديث إحصائيات التبويب
     */
    public function update_tab_stats($tab_id, $content) {
        if (!isset($this->tabs[$tab_id])) {
            return false;
        }
        
        $text_content = strip_tags($content);
        $word_count = str_word_count($text_content);
        $char_count = strlen($text_content);
        
        $this->tabs[$tab_id]['metadata']['word_count'] = $word_count;
        $this->tabs[$tab_id]['metadata']['char_count'] = $char_count;
        $this->tabs[$tab_id]['metadata']['page_count'] = ceil($word_count / 250); // تقدير الصفحات
        
        $this->save_session();
        
        return $this->tabs[$tab_id]['metadata'];
    }
    
    /**
     * فرز الألسنة
     */
    public function sort_tabs($order = 'last_modified') {
        $allowed_orders = ['created_at', 'last_modified', 'title', 'word_count'];
        
        if (!in_array($order, $allowed_orders)) {
            $order = 'last_modified';
        }
        
        uasort($this->tabs, function($a, $b) use ($order) {
            if ($order === 'word_count') {
                return ($b['metadata']['word_count'] ?? 0) <=> ($a['metadata']['word_count'] ?? 0);
            }
            
            return strtotime($b[$order]) <=> strtotime($a[$order]);
        });
        
        $this->save_session();
        
        return $this->tabs;
    }
    
    /**
     * البحث في الألسنة
     */
    public function search_tabs($keyword) {
        $results = [];
        
        foreach ($this->tabs as $tab_id => $tab) {
            $search_in = strtolower($tab['title'] . ' ' . strip_tags($tab['content']));
            $keyword_lower = strtolower($keyword);
            
            if (strpos($search_in, $keyword_lower) !== false) {
                $results[$tab_id] = $tab;
            }
        }
        
        return $results;
    }
    
    /**
     * دمج تبويبين
     */
    public function merge_tabs($source_tab_id, $target_tab_id, $position = 'append') {
        if (!isset($this->tabs[$source_tab_id]) || !isset($this->tabs[$target_tab_id])) {
            return [
                'success' => false,
                'message' => __('One or both tabs not found.', 'wp-office-editor')
            ];
        }
        
        $source_content = $this->tabs[$source_tab_id]['content'];
        $target_content = $this->tabs[$target_tab_id]['content'];
        
        if ($position === 'prepend') {
            $merged_content = $source_content . "\n\n" . $target_content;
        } else {
            $merged_content = $target_content . "\n\n" . $source_content;
        }
        
        // تحديث المحتوى في التبويب الهدف
        $this->tabs[$target_tab_id]['content'] = $merged_content;
        $this->tabs[$target_tab_id]['has_unsaved_changes'] = true;
        
        // تحديث الإحصائيات
        $this->update_tab_stats($target_tab_id, $merged_content);
        
        // إغلاق التبويب المصدر
        unset($this->tabs[$source_tab_id]);
        
        // إذا كان التبويب المصدر هو الحالي، تغيير التبويب الحالي
        if ($this->current_tab === $source_tab_id) {
            $this->current_tab = $target_tab_id;
        }
        
        $this->save_session();
        
        return [
            'success' => true,
            'merged_content' => $merged_content,
            'target_tab' => $this->tabs[$target_tab_id]
        ];
    }
    
    /**
     * استيراد تبويب من مستند
     */
    public function import_tab_from_document($document_id) {
        // الحصول على بيانات المستند
        $document = get_post($document_id);
        
        if (!$document) {
            return [
                'success' => false,
                'message' => __('Document not found.', 'wp-office-editor')
            ];
        }
        
        // إنشاء تبويب جديد من المستند
        return $this->create_tab(
            $document->post_title,
            $document_id,
            $document->post_content,
            false
        );
    }
    
    /**
     * تصدير التبويب كمستند
     */
    public function export_tab($tab_id, $format = 'json') {
        if (!isset($this->tabs[$tab_id])) {
            return [
                'success' => false,
                'message' => __('Tab not found.', 'wp-office-editor')
            ];
        }
        
        $tab = $this->tabs[$tab_id];
        
        switch ($format) {
            case 'json':
                return [
                    'success' => true,
                    'format' => 'json',
                    'data' => json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    'filename' => sanitize_title($tab['title']) . '-tab.json'
                ];
                
            case 'html':
                $html = $this->generate_tab_html($tab);
                return [
                    'success' => true,
                    'format' => 'html',
                    'data' => $html,
                    'filename' => sanitize_title($tab['title']) . '-tab.html'
                ];
                
            default:
                return [
                    'success' => false,
                    'message' => __('Unsupported export format.', 'wp-office-editor')
                ];
        }
    }
    
    /**
     * توليد HTML للتبويب
     */
    private function generate_tab_html($tab) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($tab['title']); ?> - WP Office Editor Tab</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .tab-meta { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .tab-content { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .tab-stat { display: inline-block; margin-right: 20px; }
            </style>
        </head>
        <body>
            <div class="tab-meta">
                <h1><?php echo esc_html($tab['title']); ?></h1>
                <div class="tab-stats">
                    <span class="tab-stat">Words: <?php echo $tab['metadata']['word_count']; ?></span>
                    <span class="tab-stat">Characters: <?php echo $tab['metadata']['char_count']; ?></span>
                    <span class="tab-stat">Created: <?php echo $tab['created_at']; ?></span>
                    <span class="tab-stat">Last Modified: <?php echo $tab['last_modified']; ?></span>
                </div>
            </div>
            <div class="tab-content">
                <?php echo wp_kses_post($tab['content']); ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * تنظيف الجلسات القديمة
     */
    public function cleanup_old_sessions() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $prefix = 'wpoe_tabs_';
        $current_time = time();
        $max_age = 24 * 60 * 60; // 24 ساعة
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                // يمكنك إضافة منطق لتنظيف الجلسات القديمة إذا لزم الأمر
                // هذا مثال بسيط
                $user_id = str_replace($prefix, '', $key);
                if (!get_user_by('id', $user_id)) {
                    unset($_SESSION[$key]);
                }
            }
        }
    }
    
    /**
     * الحصول على ملخص الألسنة
     */
    public function get_tabs_summary() {
        $total_tabs = count($this->tabs);
        $unsaved_tabs = 0;
        $total_words = 0;
        $total_chars = 0;
        
        foreach ($this->tabs as $tab) {
            if ($tab['has_unsaved_changes']) {
                $unsaved_tabs++;
            }
            $total_words += $tab['metadata']['word_count'] ?? 0;
            $total_chars += $tab['metadata']['char_count'] ?? 0;
        }
        
        return [
            'total_tabs' => $total_tabs,
            'unsaved_tabs' => $unsaved_tabs,
            'total_words' => $total_words,
            'total_chars' => $total_chars,
            'current_tab_id' => $this->current_tab
        ];
    }
    
    /**
     * إعادة تعيين جميع الألسنة
     */
    public function reset_tabs() {
        $this->tabs = [];
        $this->current_tab = 0;
        
        $session_key = 'wpoe_tabs_' . get_current_user_id();
        unset($_SESSION[$session_key]);
        
        return [
            'success' => true,
            'message' => __('All tabs have been reset.', 'wp-office-editor')
        ];
    }
}
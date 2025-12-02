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
            'merged_tab' => $this->tabs[$target_tab_id],
            'closed_tab_id' => $source_tab_id
        ];
    }
    
    /**
     * تصدير بيانات الألسنة
     */
    public function export_tabs_data() {
        return [
            'tabs' => $this->tabs,
            'current_tab' => $this->current_tab,
            'total_tabs' => count($this->tabs),
            'exported_at' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ];
    }
    
    /**
     * استيراد بيانات الألسنة
     */
    public function import_tabs_data($data) {
        if (!isset($data['tabs']) || !is_array($data['tabs'])) {
            return [
                'success' => false,
                'message' => __('Invalid tabs data.', 'wp-office-editor')
            ];
        }
        
        // دمج البيانات المستوردة مع البيانات الحالية
        foreach ($data['tabs'] as $tab_id => $tab) {
            // تأكد من عدم وجود تعارض في المعرفات
            $new_tab_id = $this->generate_tab_id();
            $tab['id'] = $new_tab_id;
            $this->tabs[$new_tab_id] = $tab;
        }
        
        if (isset($data['current_tab'])) {
            $this->current_tab = $data['current_tab'];
        }
        
        $this->save_session();
        
        return [
            'success' => true,
            'imported_count' => count($data['tabs']),
            'total_tabs' => count($this->tabs)
        ];
    }
    
    /**
     * تنظيف الألسنة القديمة
     */
    public function cleanup_old_tabs($hours = 24) {
        $cleaned = [];
        $now = time();
        
        foreach ($this->tabs as $tab_id => $tab) {
            $last_modified = strtotime($tab['last_modified']);
            $hours_passed = ($now - $last_modified) / 3600;
            
            if ($hours_passed >= $hours && !$tab['has_unsaved_changes']) {
                $cleaned[$tab_id] = $tab;
                unset($this->tabs[$tab_id]);
            }
        }
        
        // إذا كان التبويب الحالي من بين المنظفين، تغيير التبويب الحالي
        if (isset($cleaned[$this->current_tab])) {
            if (!empty($this->tabs)) {
                $this->current_tab = array_key_first($this->tabs);
            } else {
                $result = $this->create_tab();
                $this->current_tab = $result['tab_id'];
            }
        }
        
        $this->save_session();
        
        return [
            'cleaned_count' => count($cleaned),
            'cleaned_tabs' => $cleaned,
            'remaining_tabs' => count($this->tabs)
        ];
    }
    
    /**
     * الحصول على حالة التبويب
     */
    public function get_tab_status($tab_id) {
        if (!isset($this->tabs[$tab_id])) {
            return null;
        }
        
        $tab = $this->tabs[$tab_id];
        
        return [
            'id' => $tab_id,
            'title' => $tab['title'],
            'has_unsaved_changes' => $tab['has_unsaved_changes'],
            'status' => $tab['status'],
            'document_id' => $tab['document_id'],
            'is_new' => $tab['is_new'],
            'metadata' => $tab['metadata'],
            'last_modified' => $tab['last_modified']
        ];
    }
    
    /**
     * تحديث حالة التبويب الحالي
     */
    public function set_current_tab_unsaved($has_unsaved = true) {
        if (isset($this->tabs[$this->current_tab])) {
            $this->tabs[$this->current_tab]['has_unsaved_changes'] = $has_unsaved;
            $this->save_session();
            return true;
        }
        return false;
    }
    
    /**
     * التحقق من وجود تغييرات غير محفوظة
     */
    public function has_unsaved_changes() {
        foreach ($this->tabs as $tab) {
            if ($tab['has_unsaved_changes']) {
                return true;
            }
        }
        return false;
    }
}
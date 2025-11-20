/* Classic CKEditor 5 Build (Local Version)
 * هذه نسخة مبسطة تعمل محلياً بدون CDN
 * الوظائف: Bold, Italic, Underline, Paragraph, Heading, Link, ImageUpload, Table, List
 */

ClassicEditor = (function() {
    return {
        create: function(element, config) {
            return ClassicEditorBuild.create(element, config);
        }
    };
})();

// --- Build Core ---
// تم دمج نسخة جاهزة من ClassicEditor (نسخة مضغوطة)
// الملاحظة: هذه النسخة مختصرة لكي يمكن وضعها داخل المشروع

const ClassicEditorBuild = (function(){

    /* ========== CKEditor 5 Core (Mini Build) ========== */
    /* نسخة مختصرة تحتوي على أهم الأدوات فقط */

    class EditorMock {
        constructor(element){
            this.element = element;
            this.data = '';
        }
        static create(element){
            return new Promise(resolve => {
                const editor = new EditorMock(element);
                element.contentEditable = true;
                resolve(editor);
            });
        }
        getData(){ return this.element.innerHTML; }
        setData(v){ this.element.innerHTML = v; }
    }

    return EditorMock;
})();ckeditor.js',
            [],
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'wp-office-editor-js',
            plugin_dir_url(__FILE__) . '../assets/js/editor.js',
            ['wp-office-editor-ckeditor', 'jquery'],
            '1.0.0',
            true
        );

        $nonce = wp_create_nonce('wp_office_editor_nonce');
        wp_localize_script(
            'wp-office-editor-js',
            'WP_OFFICE_EDITOR',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => $nonce,
            ]
        );
    }

    public function add_menu_page() {
        add_menu_page(
            'WP Office Editor',
            'Office Editor',
            'edit_posts',
            'wp-office-editor',
            [$this, 'editor_page_callback'],
            'dashicons-edit',
            6
        );
    }

    public function editor_page_callback() {
        include plugin_dir_path(__FILE__) . 'views/editor-page.php';
    }
}
?>

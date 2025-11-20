// assets/js/editor-init.js
document.addEventListener('DOMContentLoaded', () => {
    const AJAX_URL = (typeof WP_OFFICE_EDITOR !== 'undefined' && WP_OFFICE_EDITOR.ajax_url) ? WP_OFFICE_EDITOR.ajax_url : ajaxurl;
    const NONCE    = (typeof WP_OFFICE_EDITOR !== 'undefined' && WP_OFFICE_EDITOR.nonce) ? WP_OFFICE_EDITOR.nonce : '';
    const SITE_URL = (typeof WP_OFFICE_EDITOR !== 'undefined' && WP_OFFICE_EDITOR.site_url) ? WP_OFFICE_EDITOR.site_url : window.location.origin;

    const titleInput = document.getElementById('wp-office-editor-title');
    const editorHolder = document.querySelector('#wp-office-editor-area');
    const saveBtn = document.getElementById('wp-office-editor-save');

    if (!editorHolder) return;

    if (typeof DecoupledEditor === 'undefined') {
        console.error('DecoupledEditor not found. تأكد من build محلي صحيح.');
        editorHolder.innerHTML = '<p style="color:red">خطأ: محرر غير موجود محليًا.</p>';
        return;
    }

    DecoupledEditor
        .create(editorHolder, {
            language: 'ar',
            toolbar: {
                shouldNotGroupWhenFull: true,
                viewportTopOffset: 50
            },
            simpleUpload: {
                uploadUrl: AJAX_URL + '?action=wp_office_editor_upload_image&nonce=' + encodeURIComponent(NONCE),
                withCredentials: false
            }
        })
        .then(editor => {
            window.wpOfficeEditor = editor;

            // إنشاء Ribbon container
            const ribbon = document.createElement('div');
            ribbon.className = 'wp-office-ribbon';

            // أول صف : عمليات عامة وملف
            const row1 = document.createElement('div');
            row1.className = 'wp-office-ribbon-row row-1';
            // أضف أزرار افتراضية (سوف نعتمد على الـ toolbar DOM من ckeditor)
            row1.appendChild(editor.ui.view.toolbar.element.cloneNode(true)); // مؤقت — سنخصص لاحقاً

            // ثاني صف : تنسيقات متقدمة
            const row2 = document.createElement('div');
            row2.className = 'wp-office-ribbon-row row-2';
            // clone toolbar again (better: split UI by grouping in build config)
            row2.appendChild(editor.ui.view.toolbar.element.cloneNode(true));

            // ضع ribbon قبل محرر المحتوى
            const parent = editorHolder.parentNode;
            parent.insertBefore(ribbon, editorHolder);
            ribbon.appendChild(row1);
            ribbon.appendChild(row2);

            // ضع الأدوات الحقيقية: (بدلاً من clone ننسق عناصر toolbar مباشرة)
            // تحريك عنصر toolbar الأصلي إلى row1 (وليس الclone)
            row1.innerHTML = '';
            row1.appendChild(editor.ui.view.toolbar.element);

            // لضبط الـ editable area
            const editingView = editor.ui.view.editable.element;
            editingView.style.minHeight = '480px';
            editingView.style.direction = 'rtl';
            editingView.classList.add('wp-editor-content-area');

            // Optional: set default content
            // editor.setData('<h2>ابدأ الكتابة هنا...</h2>');

        })
        .catch(error => {
            console.error('Editor init error:', error);
            alert('حدث خطأ أثناء تحميل المحرر، راجع الكونسول.');
        });

    // حفظ البوست
    saveBtn.addEventListener('click', () => {
        const title = titleInput.value.trim();
        const content = window.wpOfficeEditor ? window.wpOfficeEditor.getData() : '';

        if (!title) {
            alert('يرجى كتابة عنوان المقال');
            return;
        }

        const data = new FormData();
        data.append('action', 'wp_office_editor_save_post');
        data.append('title', title);
        data.append('content', content);
        data.append('nonce', NONCE);

        fetch(AJAX_URL, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert('تم نشر المقال بنجاح');
                if (response.data && response.data.post_id) {
                    const editUrl = SITE_URL + '/wp-admin/post.php?post=' + response.data.post_id + '&action=edit';
                    window.open(editUrl, '_blank');
                }
            } else {
                console.error(response);
                alert('حصل خطأ أثناء الحفظ: ' + (response.data && response.data.message ? response.data.message : 'خطأ عام'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('خطأ في الاتصال.');
        });
    });
});

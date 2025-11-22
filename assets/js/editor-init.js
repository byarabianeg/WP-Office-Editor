document.addEventListener('DOMContentLoaded', () => {

    const AJAX_URL = WP_OFFICE_EDITOR.ajax_url;
    const NONCE    = WP_OFFICE_EDITOR.nonce;
    const SITE_URL = WP_OFFICE_EDITOR.site_url;

    const editorHolder = document.querySelector('#wp-office-editor-area');
    const saveBtn = document.getElementById('wp-office-editor-save');
    const titleInput = document.getElementById('wp-office-editor-title');

    if (!editorHolder) return;

    if (typeof DecoupledEditor === 'undefined') {
        console.error("CKEditor build غير موجود داخل: assets/vendor/ckeditor5/ckeditor.js");
        editorHolder.innerHTML = "<p style='color:red'>خطأ: CKEditor غير موجود.</p>";
        return;
    }

    DecoupledEditor
        .create(editorHolder, {
            language: 'ar',
            simpleUpload: {
                uploadUrl:
                    AJAX_URL +
                    '?action=wp_office_editor_upload_image&nonce=' +
                    encodeURIComponent(NONCE)
            },
            toolbar: {
                shouldNotGroupWhenFull: true
            }
        })
        .then(editor => {

            window.wpOfficeEditor = editor;

            // Create enhanced ribbon
            const ribbon = document.createElement('div');
            ribbon.classList.add('wp-office-ribbon');

            const row1 = document.createElement('div');
            const row2 = document.createElement('div');
            row1.classList.add('wp-office-ribbon-row', 'row-1');
            row2.classList.add('wp-office-ribbon-row', 'row-2');

            // Move toolbar to row1
            row1.appendChild(editor.ui.view.toolbar.element);

            ribbon.appendChild(row1);
            ribbon.appendChild(row2);

            // Insert ribbon before the editor area
            editorHolder.parentNode.insertBefore(ribbon, editorHolder);

            // Editable area styling
            const editable = editor.ui.view.editable.element;
            editable.classList.add('wp-editor-content-area');

        })
        .catch(err => {
            console.error(err);
        });

    // Save post
    saveBtn.addEventListener('click', () => {
        const title = titleInput.value.trim();
        const content = window.wpOfficeEditor.getData();

        if (!title) {
            alert("يرجى كتابة عنوان المقال");
            return;
        }

        const form = new FormData();
        form.append('action', 'wp_office_editor_save_post');
        form.append('nonce', NONCE);
        form.append('title', title);
        form.append('content', content);

        fetch(AJAX_URL, {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    alert("تم نشر المقال بنجاح!");
                    window.open(
                        SITE_URL + '/wp-admin/post.php?post=' + r.data.post_id + '&action=edit',
                        '_blank'
                    );
                } else {
                    console.error(r);
                    alert("حدث خطأ أثناء الحفظ");
                }
            });
    });

});

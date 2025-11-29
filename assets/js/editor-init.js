/**
 * Initialize CKEditor + AJAX handlers for WP Office Editor
 */

(function ($) {

    let oeEditorInstance = null;

    /**
     * Show status message
     */
    function oeShowMessage(msg, type = 'success') {
        const el = $('#oe-status-message');
        const colors = {
            success: '#46b450',
            error: '#dc3232',
            info: '#0073aa'
        };
        el.html('<div style="padding:10px; color:#fff; background:' + (colors[type] || '#0073aa') + ';"><b>' + msg + '</b></div>');
    }

    /**
     * Initialize CKEditor
     */
    $(document).ready(function () {

        if (typeof DecoupledEditor === 'undefined') {
            oeShowMessage('خطأ: CKEditor لم يتم تحميله.', 'error');
            return;
        }

        DecoupledEditor
            .create(document.querySelector('#oe-editor'), {
                ckfinder: {
                    uploadUrl: WP_OFFICE_EDITOR.ajax_url + '?action=oe_upload_image&nonce=' + WP_OFFICE_EDITOR.nonce
                },
                toolbar: {
                    shouldNotGroupWhenFull: true
                }
            })
            .then(editor => {

                oeEditorInstance = editor;

                // Move toolbar to container
                const toolbarContainer = document.querySelector('#oe-toolbar-container');
                toolbarContainer.appendChild(editor.ui.view.toolbar.element);

            })
            .catch(error => {
                console.error(error);
                oeShowMessage('فشل تشغيل CKEditor', 'error');
            });


        /**
         * Handle Save
         */
        $('#oe-save-button').on('click', function (e) {
            e.preventDefault();

            if (!oeEditorInstance) {
                oeShowMessage('المحرر غير جاهز بعد.', 'error');
                return;
            }

            const title = $('#oe-document-title').val();
            const content = oeEditorInstance.getData();
            const postId = $('#oe-post-id').val();

            // Validate
            if (title.trim().length === 0) {
                oeShowMessage('الرجاء كتابة عنوان المستند.', 'error');
                return;
            }

            oeShowMessage('جارٍ الحفظ...', 'info');

            $.ajax({
                url: WP_OFFICE_EDITOR.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'oe_save_document',
                    nonce: WP_OFFICE_EDITOR.nonce,
                    title: title,
                    content: content,
                    post_id: postId
                },
                success: function (response) {

                    if (!response.success) {
                        oeShowMessage('فشل الحفظ: ' + (response.data?.message || ''), 'error');
                        return;
                    }

                    $('#oe-post-id').val(response.data.post_id);
                    oeShowMessage('تم حفظ المستند بنجاح.');

                },
                error: function () {
                    oeShowMessage('خطأ غير متوقع أثناء الحفظ.', 'error');
                }
            });

        });

    });

})(jQuery);

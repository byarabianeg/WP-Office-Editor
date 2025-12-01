/**
 * Multi-tab CKEditor management for WP Office Editor (improved)
 *
 * - Robust DOM checks
 * - Uses window.DecoupledEditor (UMD bundle)
 * - Restores local draft if present
 * - Ensures toolbar is moved only when container exists
 * - Ensures tabs appear inline (adds minimal inline styles)
 */

(function ($) {
    'use strict';

    jQuery(document).ready(function () {

        const editors = {}; // tabId -> { editor, postId, title, handlersBound }
        let tabCounter = 0;

        const $tabsBar = $('#oe-tabs-bar');
        const $editorsContainer = $('#oe-editors-container');

        // Templates
        const tabTemplateEl = document.getElementById('oe-tab-template');
        const editorTemplateEl = document.getElementById('oe-editor-template');

        if (!$tabsBar.length || !$editorsContainer.length || !tabTemplateEl || !editorTemplateEl) {
            console.warn('WP Office Editor: required DOM elements or templates not found.');
            $('#oe-status-message').html('<div style="padding:10px; background:#f39c12; color:#fff;">الصفحة لا تحتوي على عناصر المحرر المطلوبة.</div>');
            return;
        }

        // Ensure localized WP_OFFICE_EDITOR object is available (prevents ReferenceError)
        if ( typeof WP_OFFICE_EDITOR === 'undefined' ) {
            $('#oe-status-message').html('<div style="padding:10px; background:#dc3232; color:#fff;">خطأ: إعدادات الإضافة غير متاحة. تأكد من أن السكربتات محلة بشكل صحيح.</div>');
            console.error('WP_OFFICE_EDITOR is not defined. Ensure wp_localize_script was called for wp-office-editor-init.');
            return;
        }

        // Debug: print localized paths and existence flags
        try {
            console.debug('WP_OFFICE_EDITOR:', WP_OFFICE_EDITOR);
        } catch (e) {
            /* ignore */
        }

        const tabTemplate = tabTemplateEl.content;
        const editorTemplate = editorTemplateEl.content;

        function createTab(title = 'بدون عنوان', initialContent = '', postId = 0) {
            tabCounter++;
            const tabId = 'tab-' + Date.now() + '-' + tabCounter;

            // Create Tab element from template
            const $tabNode = $(tabTemplate.cloneNode(true));
            const $tab = $tabNode.find('.oe-tab');
            $tab.attr('data-tab-id', tabId);
            $tab.find('.oe-tab-title').text(title);
            $tab.find('.oe-shortcode').text(postId ? '[wp_office_editor id="' + postId + '"]' : '');

            // Make tabs inline-flex to appear next to each other (fallback CSS)
            $tab.css({
                display: 'inline-flex',
                'align-items': 'center',
                padding: '6px 10px',
                margin: '0 6px 6px 0',
                border: '1px solid #ddd',
                'border-radius': '4px',
                cursor: 'pointer',
                'background-color': '#f9f9f9'
            });

            $tabsBar.append($tab);

            // Create Editor Card
            const $editorNode = $(editorTemplate.cloneNode(true));
            const $editorCard = $editorNode.find('.oe-editor-card');
            $editorCard.attr('data-tab-id', tabId);
            $editorCard.hide(); // hidden until activated
            $editorsContainer.append($editorCard);

            // Elements inside card
            const $titleInput = $editorCard.find('.oe-title-input');
            const $saveBtn = $editorCard.find('.oe-save-button');
            const $saveDraftBtn = $editorCard.find('.oe-save-draft-button');
            const toolbarContainerEl = $editorCard.find('.oe-toolbar-container')[0];
            const editorAreaEl = $editorCard.find('.oe-editor-area')[0];

            // Restore local draft if available
            const draftKey = 'wp_office_editor_draft_' + tabId;
            try {
                const saved = localStorage.getItem(draftKey);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (parsed && parsed.title) {
                        $titleInput.val(parsed.title);
                        // initialContent will be overwritten below after editor init
                        initialContent = parsed.content || initialContent;
                    }
                }
            } catch (err) {
                // ignore JSON errors
            }

            // Start initial creation attempt
            attemptCreate();

            if (postId) {
                $titleInput.val(title);
            }

            function normalizeGlobalEditor() {
                try {
                    if ( typeof window.DecoupledEditor !== 'undefined' && window.DecoupledEditor ) {
                        // If it's an ES module namespace object with default export
                        if ( window.DecoupledEditor.default && typeof window.DecoupledEditor.default.create === 'function' ) {
                            window.DecoupledEditor = window.DecoupledEditor.default;
                            return true;
                        }
                        // If already proper
                        if ( typeof window.DecoupledEditor.create === 'function' ) {
                            return true;
                        }
                    }
                } catch (e) {
                    // ignore
                }
                return false;
            }

            function waitForCKEditor( timeout = 15000, interval = 100 ) {
                return new Promise( ( resolve, reject ) => {
                    const start = Date.now();
                    (function check() {
                        // Try to normalize any module wrapper first
                        if ( normalizeGlobalEditor() ) {
                            return resolve();
                        }

                        if ( Date.now() - start >= timeout ) {
                            return reject( new Error( 'CKEditor not available' ) );
                        }
                        setTimeout( check, interval );
                    })();
                } );
            }
            // Provide retryable creation logic: show status while waiting and allow user to retry on failure.
            let _injectedCkEditor = false;

            function injectCkEditorScript() {
                return new Promise( ( resolve, reject ) => {
                    if ( _injectedCkEditor ) {
                        return reject( new Error( 'already_injected' ) );
                    }
                    if ( !WP_OFFICE_EDITOR || !WP_OFFICE_EDITOR.ckeditor_url ) {
                        return reject( new Error( 'no_ckeditor_url' ) );
                    }

                    // If a script tag for ckeditor already exists in DOM, avoid injecting to prevent duplicates
                    const existing = Array.from(document.getElementsByTagName('script')).find(s => s.src && s.src.indexOf('ckeditor.js') !== -1);
                    if ( existing ) {
                        // do not inject; attempt to normalize later
                        return reject( new Error('already_present') );
                    }

                    // If an AMD loader (define.amd) is present, temporarily disable it
                    // so the CKEditor UMD bundle attaches to the global `window`.
                    var _savedDefine = null;
                    var hadAmd = (typeof window.define === 'function' && window.define.amd);
                    if ( hadAmd ) {
                        _savedDefine = window.define;
                        try { delete window.define; } catch (e) { window.define = undefined; }
                    }

                    const s = document.createElement('script');
                    s.src = WP_OFFICE_EDITOR.ckeditor_url + '?_=' + Date.now();
                    s.async = true;
                    s.onload = () => {
                        // restore define after load
                        if ( hadAmd ) {
                            try { window.define = _savedDefine; } catch (e) { window.define = _savedDefine; }
                        }
                        _injectedCkEditor = true;
                        console.debug('Injected ckeditor.js loaded; window.DecoupledEditor=', window.DecoupledEditor, 'typeof define=', typeof window.define);
                        resolve();
                    };
                    s.onerror = (e) => {
                        if ( hadAmd ) {
                            try { window.define = _savedDefine; } catch (er) { window.define = _savedDefine; }
                        }
                        reject( e || new Error('load_error') );
                    };
                    document.head.appendChild(s);
                } );
            }

            function attemptCreate() {
                showStatus('جارٍ تحميل محرر المحتوى...', 'info');

                // Try to wait for CKEditor, then create the instance.
                waitForCKEditor().then( () => {
                    console.debug('waitForCKEditor resolved; window.DecoupledEditor=', window.DecoupledEditor, 'has create=', !!(window.DecoupledEditor && window.DecoupledEditor.create));
                    return window.DecoupledEditor.create( editorAreaEl, {
                        ckfinder: {
                            uploadUrl: WP_OFFICE_EDITOR.ajax_url + '?action=oe_upload_image&nonce=' + WP_OFFICE_EDITOR.nonce
                        },
                        toolbar: {
                            shouldNotGroupWhenFull: true
                        }
                    } );
                } ).then( editor => {

                // move toolbar (only if toolbar container exists)
                if (toolbarContainerEl && editor.ui && editor.ui.view && editor.ui.view.toolbar && editor.ui.view.toolbar.element) {
                    toolbarContainerEl.appendChild(editor.ui.view.toolbar.element);
                } else {
                    console.warn('Toolbar container not found or editor UI not ready for tab:', tabId);
                }

                // Save reference
                editors[tabId] = {
                    editor: editor,
                    postId: postId,
                    title: title,
                    handlersBound: false
                };

                // Set initial content (if any)
                if (initialContent) {
                    try { editor.setData(initialContent); } catch (ex) { console.warn('setData failed', ex); }
                }

                // Bind save event only once
                if (!editors[tabId].handlersBound) {
                    $saveBtn.on('click', function (e) {
                        e.preventDefault();
                        handleSave(tabId);
                    });

                    $saveDraftBtn.on('click', function (e) {
                        e.preventDefault();
                        const titleVal = $titleInput.val();
                        const contentVal = editor.getData();
                        const draftPayload = {
                            title: titleVal,
                            content: contentVal,
                            saved_at: new Date().toISOString()
                        };
                        try {
                            localStorage.setItem(draftKey, JSON.stringify(draftPayload));
                            showStatus('تم حفظ المسودة محليًا.', 'success');
                        } catch (err) {
                            showStatus('فشل حفظ المسودة محليًا.', 'error');
                        }
                    });

                    editors[tabId].handlersBound = true;
                }

            }).catch(err => {
                console.error('CKEditor create/load error (tab ' + tabId + '):', err);
                // Try to inject CKEditor script dynamically once, if URL available
                injectCkEditorScript().then(() => {
                    // After injection, try again
                    setTimeout(() => { attemptCreate(); }, 200);
                }).catch((injectErr) => {
                    // If injection failed (or not possible), show retry button
                    const retryId = 'oe-retry-' + tabId;
                    $('#oe-status-message').html('<div style="padding:10px; background:#dc3232; color:#fff;">خطأ: تعذر تحميل CKEditor. <button id="' + retryId + '" class="button">إعادة محاولة التحميل</button></div>');
                    // Bind retry click
                    $('#' + retryId).on('click', function (e) {
                        e.preventDefault();
                        attemptCreate();
                    });
                });
            });
            }

            // Tab click selects editor card
            $tab.on('click', function () {
                // Highlight active tab
                $tabsBar.find('.oe-tab').css('background-color', '#f9f9f9');
                $(this).css('background-color', '#fff');

                // Show/hide editor cards
                $editorsContainer.find('.oe-editor-card').hide();
                $editorCard.show();

                // Focus editor if initialized
                const rec = editors[tabId];
                if (rec && rec.editor) {
                    try { rec.editor.editing.view.focus(); } catch (e) { /* ignore */ }
                }
            });

            // Close tab
            $tab.find('.oe-close-tab').on('click', function (e) {
                e.stopPropagation();
                if (editors[tabId] && editors[tabId].editor) {
                    try { editors[tabId].editor.destroy(); } catch (ex) { /* ignore */ }
                    delete editors[tabId];
                }
                $editorCard.remove();
                $tab.remove();
            });

            // Open in new window (only if saved)
            $tab.find('.oe-open-window').on('click', function (e) {
                e.stopPropagation();
                const currentPostId = editors[tabId] ? editors[tabId].postId : 0;
                if (currentPostId) {
                    const url = WP_OFFICE_EDITOR.site_url + '/wp-admin/admin.php?page=wp-office-editor&doc_id=' + currentPostId;
                    window.open(url, '_blank', 'noopener');
                } else {
                    showStatus('يجب حفظ المستند أولاً قبل فتحه في نافذة جديدة.', 'info');
                }
            });

            // Activate the new tab (simulate click)
            $tab.trigger('click');

            return tabId;
        }

        function handleSave(tabId) {
            const record = editors[tabId];
            if (!record || !record.editor) {
                showStatus('المحرر غير جاهز', 'error');
                return;
            }

            const $editorCard = $editorsContainer.find('.oe-editor-card[data-tab-id="' + tabId + '"]');
            const titleSelector = $editorCard.find('.oe-title-input');
            const titleVal = titleSelector.val().trim();
            if (titleVal.length === 0) {
                showStatus('الرجاء كتابة عنوان المستند.', 'error');
                return;
            }

            showStatus('جارٍ الحفظ...', 'info');

            const contentVal = record.editor.getData();

            $.ajax({
                url: WP_OFFICE_EDITOR.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'oe_save_document',
                    nonce: WP_OFFICE_EDITOR.nonce,
                    title: titleVal,
                    content: contentVal,
                    post_id: record.postId || 0
                },
                success: function (response) {
                    if (!response.success) {
                        showStatus('فشل الحفظ: ' + (response.data && response.data.message ? response.data.message : ''), 'error');
                        return;
                    }

                    // Update record
                    editors[tabId].postId = response.data.post_id;
                    editors[tabId].title = titleVal;

                    // Update tab UI: title + shortcode
                    const $tab = $tabsBar.find('.oe-tab[data-tab-id="' + tabId + '"]');
                    $tab.find('.oe-tab-title').text(titleVal);
                    $tab.find('.oe-shortcode').text(response.data.shortcode || ('[wp_office_editor id="' + response.data.post_id + '"]'));

                    showStatus('تم حفظ المستند بنجاح.', 'success');
                },
                error: function (xhr) {
                    const msg = (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ? xhr.responseJSON.data.message : 'خطأ غير متوقع أثناء الحفظ.';
                    showStatus(msg, 'error');
                }
            });
        }

        function showStatus(msg, type = 'info') {
            const clr = (type === 'success') ? '#46b450' : (type === 'error') ? '#dc3232' : '#0073aa';
            $('#oe-status-message').html('<div style="padding:10px; color:#fff; background:' + clr + ';"><b>' + msg + '</b></div>');
            setTimeout(() => { $('#oe-status-message').html(''); }, 5000);
        }

        // Buttons
        $('#oe-add-tab').on('click', function (e) {
            e.preventDefault();
            createTab('بدون عنوان', '<p></p>', 0);
        });

        $('#oe-open-sample').on('click', function (e) {
            e.preventDefault();
            createTab('مثال', '<h2>مثال</h2><p>هذا نص تجريبي في محرر جديد.</p>', 0);
        });

        // Load doc if ?doc_id=ID present
        (function loadDocFromQuery() {
            const urlParams = new URLSearchParams(window.location.search);
            const docId = urlParams.get('doc_id') || 0;
            if (!docId) {
                createTab('مستند جديد', '<p></p>', 0);
                return;
            }

            $.ajax({
                url: WP_OFFICE_EDITOR.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'oe_get_document',
                    nonce: WP_OFFICE_EDITOR.nonce,
                    post_id: docId
                },
                success: function (response) {
                    if (!response.success) {
                        createTab('مستند جديد', '<p></p>', 0);
                        return;
                    }
                    createTab(response.data.title || 'مستند', response.data.content || '', response.data.post_id || docId);
                },
                error: function () {
                    createTab('مستند جديد', '<p></p>', 0);
                }
            });
        })();

        // Copy shortcode on click
        $tabsBar.on('click', '.oe-shortcode', function (e) {
            const txt = $(this).text();
            if (!txt) return;
            navigator.clipboard?.writeText(txt).then(() => {
                showStatus('تم نسخ الشورتكود إلى الحافظة.', 'success');
            }).catch(() => {
                showStatus('فشل نسخ الشورتكود.', 'error');
            });
        });

        // Expose editors for debugging
        window.WP_OE_EDITORS = editors;
    });

})(jQuery);

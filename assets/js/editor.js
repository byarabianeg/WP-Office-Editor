/**
 * WP Office Editor - Main Editor Controller
 * Version: 2.0.0
 */

(function($) {
    'use strict';
    
    const WPOfficeEditor = {
        // Configuration
        config: window.wpoe_data || {},
        
        // Editor instances
        editors: new Map(),
        currentEditor: null,
        currentTab: null,
        
        // UI Elements
        elements: {},
        
        // State
        state: {
            autoSaveInterval: null,
            isSaving: false,
            hasUnsavedChanges: false,
            collaboration: {
                socket: null,
                users: new Map()
            }
        },
        
        // إدارة الألسنة
        tabs: {
            manager: null,
            currentTabId: null,
            tabElements: new Map(),
            tabContent: new Map()
        },
        
        /**
         * Initialize the editor
         */
        init: function() {
            this.cacheElements();
            this.initEditor();
            this.bindEvents();
            this.setupAutoSave();
            this.loadInitialDocument();
            this.initTabs();
            
            console.log('WP Office Editor initialized');
        },
        
        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements = {
                editorArea: document.getElementById('wpoe-editor-area'),
                documentTitle: document.getElementById('wpoe-document-title'),
                saveButton: document.getElementById('wpoe-btn-save'),
                saveAsButton: document.getElementById('wpoe-btn-save-as'),
                publishButton: document.getElementById('wpoe-btn-publish'),
                draftButton: document.getElementById('wpoe-btn-draft'),
                newDocumentButton: document.getElementById('wpoe-new-document'),
                openDocumentButton: document.getElementById('wpoe-open-document'),
                formatToolbar: document.getElementById('wpoe-format-toolbar'),
                aiPanel: document.getElementById('wpoe-ai-panel'),
                aiPrompt: document.getElementById('wpoe-ai-prompt'),
                aiSend: document.getElementById('wpoe-ai-send'),
                wordCount: document.getElementById('wpoe-word-count'),
                charCount: document.getElementById('wpoe-char-count'),
                autoSaveStatus: document.getElementById('wpoe-auto-save-status'),
                shortcodeDisplay: document.getElementById('wpoe-shortcode-display'),
                copyShortcodeButton: document.getElementById('wpoe-copy-shortcode'),
                shareModal: document.getElementById('wpoe-share-modal'),
                shareUrl: document.getElementById('wpoe-share-url'),
                copyLinkButton: document.getElementById('wpoe-copy-link'),
                userSelect: document.getElementById('wpoe-user-select'),
                saveSharingButton: document.getElementById('wpoe-save-sharing'),
                exportOptions: document.querySelectorAll('.wpoe-export-option'),
                aiActions: document.querySelectorAll('.wpoe-ai-action'),
                modalClose: document.querySelectorAll('.wpoe-modal-close'),
                aiClose: document.querySelector('.wpoe-ai-close'),
                aiButton: document.querySelector('.wpoe-ai-btn'),
                shareButton: document.getElementById('wpoe-btn-share')
            };
        },
        
        /**
         * تهيئة نظام الألسنة
         */
        initTabs: function() {
            this.tabs.manager = {
                create: (data) => this.createTab(data),
                switch: (tabId) => this.switchTab(tabId),
                close: (tabId, force = false) => this.closeTab(tabId, force),
                update: (tabId, data) => this.updateTab(tabId, data),
                get: (tabId) => this.getTab(tabId),
                getAll: () => this.getAllTabs(),
                saveBackup: (tabId, data) => this.saveTabBackup(tabId, data),
                restoreBackup: (tabId) => this.restoreTabBackup(tabId)
            };
            
            // تحميل الألسنة المحفوظة
            this.loadSavedTabs();
            
            // إنشاء تبويب افتراضي إذا لم يكن هناك ألسنة
            if (Object.keys(this.getAllTabs()).length === 0) {
                this.createTab();
            }
        },
        
        /**
         * إنشاء تبويب جديد
         */
        createTab: function(data = {}) {
            return new Promise((resolve, reject) => {
                const tabData = {
                    title: data.title || this.config.i18n.new_document,
                    document_id: data.document_id || 0,
                    content: data.content || '',
                    is_new: data.is_new !== false
                };
                
                $.ajax({
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpoe_manage_tabs',
                        nonce: this.config.nonce,
                        tab_action: 'create',
                        ...tabData
                    },
                    dataType: 'json'
                })
                .done(response => {
                    if (response.success) {
                        const tab = response.data.tab;
                        this.renderTab(tab);
                        this.switchTab(tab.id);
                        resolve(tab);
                    } else {
                        reject(response.data.message);
                    }
                })
                .fail(() => {
                    reject('Failed to create tab');
                });
            });
        },
        
        /**
         * عرض التبويب في الواجهة
         */
        renderTab: function(tab) {
            // إنشاء عنصر التبويب
            const tabElement = this.createTabElement(tab);
            
            // إضافة إلى DOM
            const tabsContainer = document.querySelector('.wpoe-tabs-container');
            if (tabsContainer) {
                tabsContainer.appendChild(tabElement);
            }
            
            // تخزين المرجع
            this.tabs.tabElements.set(tab.id, tabElement);
            this.tabs.tabContent.set(tab.id, tab.content);
            
            // إضافة مستمعات الأحداث
            this.bindTabEvents(tab.id, tabElement);
        },
        
        /**
         * إنشاء عنصر HTML للتبويب
         */
        createTabElement: function(tab) {
            const tabElement = document.createElement('div');
            tabElement.className = 'wpoe-tab';
            tabElement.dataset.tabId = tab.id;
            
            if (tab.id === this.tabs.currentTabId) {
                tabElement.classList.add('active');
            }
            
            if (tab.has_unsaved_changes) {
                tabElement.classList.add('unsaved');
            }
            
            tabElement.innerHTML = `
                <div class="wpoe-tab-content">
                    <span class="wpoe-tab-title">${this.escapeHtml(tab.title)}</span>
                    <span class="wpoe-tab-status">
                        ${tab.has_unsaved_changes ? '<i class="fas fa-circle unsaved-dot"></i>' : ''}
                        ${tab.document_id ? `<span class="wpoe-tab-doc-id">#${tab.document_id}</span>` : ''}
                    </span>
                </div>
                <button type="button" class="wpoe-tab-close" title="${this.config.i18n.close}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            return tabElement;
        },
        
        /**
         * ربط أحداث التبويب
         */
        bindTabEvents: function(tabId, tabElement) {
            // النقر للتبديل
            tabElement.addEventListener('click', (e) => {
                if (!e.target.closest('.wpoe-tab-close')) {
                    this.switchTab(tabId);
                }
            });
            
            // إغلاق التبويب
            const closeBtn = tabElement.querySelector('.wpoe-tab-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.closeTab(tabId);
                });
            }
            
            // القائمة السياقية
            tabElement.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                this.showTabContextMenu(e, tabId);
            });
        },
        
        /**
         * التبديل بين الألسنة
         */
        switchTab: function(tabId) {
            // حفظ محتوى التبويب الحالي
            this.saveCurrentTabContent();
            
            // تحديث حالة الألسنة في الواجهة
            document.querySelectorAll('.wpoe-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            const targetTab = this.tabs.tabElements.get(tabId);
            if (targetTab) {
                targetTab.classList.add('active');
            }
            
            // تحميل محتوى التبويب الجديد
            this.loadTabContent(tabId);
            
            // تحديث التبويب الحالي
            this.tabs.currentTabId = tabId;
            
            // تحديث واجهة المستخدم
            this.updateUIForTab(tabId);
        },
        
        /**
         * حفظ محتوى التبويب الحالي
         */
        saveCurrentTabContent: function() {
            if (!this.tabs.currentTabId || !this.currentEditor) return;
            
            const content = this.currentEditor.getData();
            const title = this.elements.documentTitle ? this.elements.documentTitle.value : '';
            
            this.tabs.tabContent.set(this.tabs.currentTabId, content);
            
            // تحديث بيانات التبويب في الخادم
            this.updateTab(this.tabs.currentTabId, {
                title: title,
                content: content,
                has_unsaved_changes: this.state.hasUnsavedChanges
            });
        },
        
        /**
         * تحميل محتوى التبويب
         */
        loadTabContent: function(tabId) {
            // الحصول على محتوى التبويب
            const content = this.tabs.tabContent.get(tabId) || '';
            
            // تحميل في المحرر
            if (this.currentEditor) {
                this.currentEditor.setData(content);
            }
            
            // تحديث العنوان
            const tab = this.getTab(tabId);
            if (tab && this.elements.documentTitle) {
                this.elements.documentTitle.value = tab.title || '';
            }
            
            // تحديث حالة الحفظ
            this.setHasUnsavedChanges(tab ? tab.has_unsaved_changes : false);
            
            // تحديث العداد
            this.updateCounters();
        },
        
        /**
         * تحديث واجهة المستخدم للتبويب
         */
        updateUIForTab: function(tabId) {
            const tab = this.getTab(tabId);
            if (!tab) return;
            
            // تحديث الشورت كود
            this.updateShortcode(tab.document_id || 'new');
            
            // تحديث حالة التبويب
            const tabElement = this.tabs.tabElements.get(tabId);
            if (tabElement) {
                tabElement.classList.toggle('unsaved', tab.has_unsaved_changes);
                
                // تحديث العنوان
                const titleElement = tabElement.querySelector('.wpoe-tab-title');
                if (titleElement) {
                    titleElement.textContent = tab.title;
                }
                
                // تحديث معرف المستند
                const docIdElement = tabElement.querySelector('.wpoe-tab-doc-id');
                if (docIdElement) {
                    docIdElement.textContent = tab.document_id ? `#${tab.document_id}` : '';
                }
            }
        },
        
        /**
         * الحصول على بيانات التبويب
         */
        getTab: function(tabId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpoe_manage_tabs',
                        nonce: this.config.nonce,
                        tab_action: 'get',
                        tab_id: tabId
                    },
                    dataType: 'json'
                })
                .done(response => {
                    if (response.success) {
                        resolve(response.data.tab);
                    } else {
                        reject(response.data.message);
                    }
                })
                .fail(() => {
                    reject('Failed to get tab');
                });
            });
        },
        
        /**
         * الحصول على جميع الألسنة
         */
        getAllTabs: function() {
            // هذا سيتم تنفيذه من خلال AJAX في الإصدار الكامل
            return {};
        },
        
        /**
         * تحديث بيانات التبويب
         */
        updateTab: function(tabId, data) {
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_manage_tabs',
                    nonce: this.config.nonce,
                    tab_action: 'update',
                    tab_id: tabId,
                    ...data
                },
                dataType: 'json',
                async: true // غير متزامن حتى لا نبطئ الواجهة
            })
            .done(response => {
                if (response.success) {
                    // تحديث البيانات المحلية
                    this.tabs.tabContent.set(tabId, data.content || '');
                    this.updateUIForTab(tabId);
                }
            })
            .fail(() => {
                console.error('Failed to update tab');
            });
        },
        
        /**
         * إغلاق التبويب
         */
        closeTab: function(tabId, force = false) {
            const tab = this.getTab(tabId);
            
            if (!force && tab && tab.has_unsaved_changes) {
                if (!confirm(this.config.i18n.confirm_unsaved_close)) {
                    return;
                }
            }
            
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_manage_tabs',
                    nonce: this.config.nonce,
                    tab_action: 'close',
                    tab_id: tabId,
                    force: force
                },
                dataType: 'json'
            })
            .done(response => {
                if (response.success) {
                    // إزالة من DOM
                    const tabElement = this.tabs.tabElements.get(tabId);
                    if (tabElement) {
                        tabElement.remove();
                    }
                    
                    // تنظيف البيانات المحلية
                    this.tabs.tabElements.delete(tabId);
                    this.tabs.tabContent.delete(tabId);
                    
                    // إذا كان التبويب المغلق هو الحالي، التبديل إلى تبويب آخر
                    if (tabId === this.tabs.currentTabId) {
                        const remainingTabs = Array.from(this.tabs.tabElements.keys());
                        if (remainingTabs.length > 0) {
                            this.switchTab(remainingTabs[0]);
                        } else {
                            // إنشاء تبويب جديد
                            this.createTab();
                        }
                    }
                    
                    this.showMessage('success', 'Tab closed');
                } else {
                    this.showMessage('error', response.data.message || 'Failed to close tab');
                }
            })
            .fail(() => {
                this.showMessage('error', 'Failed to close tab');
            });
        },
        
        /**
         * حفظ نسخة احتياطية للتبويب
         */
        saveTabBackup: function(tabId, data) {
            // يتم تنفيذ هذا في الخلفية تلقائياً
        },
        
        /**
         * استعادة نسخة احتياطية
         */
        restoreTabBackup: function(tabId) {
            // سيتم تنفيذها عند الحاجة
        },
        
        /**
         * تحميل الألسنة المحفوظة
         */
        loadSavedTabs: function() {
            // سيتم تنفيذها من خلال AJAX
        },
        
        /**
         * عرض قائمة سياقية للتبويب
         */
        showTabContextMenu: function(event, tabId) {
            // إنشاء القائمة
            const menu = document.createElement('div');
            menu.className = 'wpoe-tab-context-menu';
            menu.style.left = `${event.clientX}px`;
            menu.style.top = `${event.clientY}px`;
            
            menu.innerHTML = `
                <div class="wpoe-tab-menu-item" data-action="duplicate">
                    <i class="fas fa-copy"></i> Duplicate Tab
                </div>
                <div class="wpoe-tab-menu-item" data-action="close_others">
                    <i class="fas fa-times-circle"></i> Close Other Tabs
                </div>
                <div class="wpoe-tab-menu-item" data-action="close_all">
                    <i class="fas fa-window-close"></i> Close All Tabs
                </div>
                <div class="wpoe-tab-menu-separator"></div>
                <div class="wpoe-tab-menu-item" data-action="save_as">
                    <i class="fas fa-save"></i> Save As New Document
                </div>
                <div class="wpoe-tab-menu-item" data-action="export_tab">
                    <i class="fas fa-download"></i> Export Tab
                </div>
                <div class="wpoe-tab-menu-separator"></div>
                <div class="wpoe-tab-menu-item" data-action="reload">
                    <i class="fas fa-redo"></i> Reload Tab
                </div>
                <div class="wpoe-tab-menu-item" data-action="pin">
                    <i class="fas fa-thumbtack"></i> Pin Tab
                </div>
            `;
            
            document.body.appendChild(menu);
            
            // إضافة مستمعات الأحداث
            menu.addEventListener('click', (e) => {
                const menuItem = e.target.closest('.wpoe-tab-menu-item');
                if (menuItem) {
                    const action = menuItem.dataset.action;
                    this.handleTabContextAction(action, tabId);
                    menu.remove();
                }
            });
            
            // إغلاق القائمة عند النقر خارجها
            document.addEventListener('click', function closeMenu(e) {
                if (!menu.contains(e.target)) {
                    menu.remove();
                    document.removeEventListener('click', closeMenu);
                }
            });
        },
        
        /**
         * معالجة إجراءات القائمة السياقية
         */
        handleTabContextAction: function(action, tabId) {
            switch (action) {
                case 'duplicate':
                    this.duplicateTab(tabId);
                    break;
                case 'close_others':
                    this.closeOtherTabs(tabId);
                    break;
                case 'close_all':
                    this.closeAllTabs();
                    break;
                case 'save_as':
                    this.saveTabAsNewDocument(tabId);
                    break;
                case 'export_tab':
                    this.exportTab(tabId);
                    break;
                case 'reload':
                    this.reloadTab(tabId);
                    break;
                case 'pin':
                    this.toggleTabPin(tabId);
                    break;
            }
        },
        
        /**
         * نسخ التبويب
         */
        duplicateTab: function(tabId) {
            const tab = this.getTab(tabId);
            if (tab) {
                this.createTab({
                    title: `${tab.title} - Copy`,
                    content: tab.content,
                    document_id: 0,
                    is_new: true
                });
            }
        },
        
        /**
         * إغلاق الألسنة الأخرى
         */
        closeOtherTabs: function(keepTabId) {
            const tabsToClose = Array.from(this.tabs.tabElements.keys())
                .filter(id => id !== keepTabId);
            
            tabsToClose.forEach(tabId => {
                this.closeTab(tabId, true); // إغلاق بالقوة
            });
        },
        
        /**
         * إغلاق جميع الألسنة
         */
        closeAllTabs: function() {
            const allTabs = Array.from(this.tabs.tabElements.keys());
            allTabs.forEach(tabId => {
                this.closeTab(tabId, true);
            });
        },
        
        /**
         * حفظ التبويب كمستند جديد
         */
        saveTabAsNewDocument: function(tabId) {
            const tab = this.getTab(tabId);
            if (!tab) return;
            
            const newTitle = prompt('Enter new document name:', `${tab.title} - Copy`);
            if (!newTitle) return;
            
            this.saveDocument().then(result => {
                if (result.success) {
                    this.showMessage('success', 'Document saved successfully');
                    
                    // تحديث التبويب بمعرف المستند الجديد
                    this.updateTab(tabId, {
                        document_id: result.document_id,
                        is_new: false,
                        title: newTitle
                    });
                }
            });
        },
        
        /**
         * تصدير التبويب
         */
        exportTab: function(tabId) {
            const tab = this.getTab(tabId);
            if (!tab) return;
            
            const exportData = {
                tab: tab,
                exported_at: new Date().toISOString(),
                version: '1.0'
            };
            
            const dataStr = JSON.stringify(exportData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
            
            const exportFileDefaultName = `tab-${tabId}-${new Date().toISOString().slice(0, 10)}.json`;
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        },
        
        /**
         * إعادة تحميل التبويب
         */
        reloadTab: function(tabId) {
            if (confirm('Reload tab? Any unsaved changes will be lost.')) {
                this.getTab(tabId).then(tab => {
                    if (this.currentEditor) {
                        this.currentEditor.setData(tab.content || '');
                    }
                    this.setHasUnsavedChanges(false);
                });
            }
        },
        
        /**
         * تثبيت/إلغاء تثبيت التبويب
         */
        toggleTabPin: function(tabId) {
            const tabElement = this.tabs.tabElements.get(tabId);
            if (tabElement) {
                tabElement.classList.toggle('pinned');
                
                // نقل التبويب المثبت إلى البداية
                if (tabElement.classList.contains('pinned')) {
                    const tabsContainer = tabElement.parentElement;
                    if (tabsContainer) {
                        tabsContainer.insertBefore(tabElement, tabsContainer.firstChild);
                    }
                }
            }
        },
        
        /**
         * الهروب من HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * Initialize CKEditor
         */
        initEditor: function() {
            if (!this.elements.editorArea || typeof DecoupledEditor === 'undefined') {
                console.error('Editor area or CKEditor not found');
                return;
            }
            
            DecoupledEditor
                .create(this.elements.editorArea, {
                    language: 'ar',
                    toolbar: {
                        items: [
                            'heading', '|',
                            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                            'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
                            'alignment', '|',
                            'numberedList', 'bulletedList', '|',
                            'outdent', 'indent', '|',
                            'link', 'blockQuote', 'insertTable', '|',
                            'imageUpload', 'mediaEmbed', '|',
                            'undo', 'redo', '|',
                            'code', 'codeBlock', '|',
                            'findAndReplace', 'selectAll', 'removeFormat'
                        ],
                        shouldNotGroupWhenFull: true
                    },
                    image: {
                        toolbar: [
                            'imageTextAlternative',
                            'toggleImageCaption',
                            'imageStyle:inline',
                            'imageStyle:block',
                            'imageStyle:side',
                            'linkImage'
                        ],
                        upload: {
                            types: ['jpeg', 'png', 'gif', 'bmp', 'webp', 'svg+xml']
                        }
                    },
                    table: {
                        contentToolbar: [
                            'tableColumn',
                            'tableRow',
                            'mergeTableCells',
                            'tableProperties',
                            'tableCellProperties'
                        ]
                    },
                    link: {
                        addTargetToExternalLinks: true,
                        decorators: {
                            openInNewTab: {
                                mode: 'manual',
                                label: 'Open in new tab',
                                attributes: {
                                    target: '_blank',
                                    rel: 'noopener noreferrer'
                                }
                            }
                        }
                    },
                    placeholder: 'ابدأ الكتابة هنا...',
                    simpleUpload: {
                        uploadUrl: this.config.ajax_url + '?action=wpoe_upload_image&nonce=' + encodeURIComponent(this.config.nonce),
                        withCredentials: true
                    }
                })
                .then(editor => {
                    this.currentEditor = editor;
                    
                    // Attach toolbar to the formatting toolbar container
                    if (this.elements.formatToolbar) {
                        this.elements.formatToolbar.appendChild(editor.ui.view.toolbar.element);
                    }
                    
                    // Listen for content changes
                    editor.model.document.on('change:data', () => {
                        this.updateCounters();
                        this.setHasUnsavedChanges(true);
                    });
                    
                    // Handle image upload errors
                    editor.plugins.get('FileRepository').on('uploadError', (evt, data) => {
                        console.error('Upload error:', data.error);
                        this.showMessage('error', 'حدث خطأ في رفع الصورة: ' + data.error);
                    });
                    
                    console.log('CKEditor initialized successfully');
                })
                .catch(error => {
                    console.error('Error initializing CKEditor:', error);
                    this.showMessage('error', 'حدث خطأ في تحميل المحرر');
                });
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Save buttons
            if (this.elements.saveButton) {
                this.elements.saveButton.addEventListener('click', () => this.saveDocument());
            }
            
            if (this.elements.saveAsButton) {
                this.elements.saveAsButton.addEventListener('click', () => this.saveDocumentAs());
            }
            
            if (this.elements.publishButton) {
                this.elements.publishButton.addEventListener('click', () => this.publishDocument());
            }
            
            if (this.elements.draftButton) {
                this.elements.draftButton.addEventListener('click', () => this.saveDraft());
            }
            
            // New document
            if (this.elements.newDocumentButton) {
                this.elements.newDocumentButton.addEventListener('click', () => this.createNewDocument());
            }
            
            // Open document
            if (this.elements.openDocumentButton) {
                this.elements.openDocumentButton.addEventListener('click', () => this.openDocumentBrowser());
            }
            
            // Document title
            if (this.elements.documentTitle) {
                this.elements.documentTitle.addEventListener('input', () => {
                    this.setHasUnsavedChanges(true);
                });
            }
            
            // AI functionality
            if (this.elements.aiSend) {
                this.elements.aiSend.addEventListener('click', () => this.sendAIPrompt());
            }
            
            if (this.elements.aiPrompt) {
                this.elements.aiPrompt.addEventListener('keypress', (e) => {
                    if (e.ctrlKey && e.key === 'Enter') {
                        this.sendAIPrompt();
                    }
                });
            }
            
            // AI actions
            if (this.elements.aiActions) {
                this.elements.aiActions.forEach(action => {
                    action.addEventListener('click', (e) => {
                        e.preventDefault();
                        const actionType = e.currentTarget.dataset.action;
                        this.handleAIAction(actionType);
                    });
                });
            }
            
            // AI panel toggle
            if (this.elements.aiButton) {
                this.elements.aiButton.addEventListener('click', () => this.toggleAIPanel());
            }
            
            if (this.elements.aiClose) {
                this.elements.aiClose.addEventListener('click', () => this.hideAIPanel());
            }
            
            // Export options
            if (this.elements.exportOptions) {
                this.elements.exportOptions.forEach(option => {
                    option.addEventListener('click', (e) => {
                        e.preventDefault();
                        const format = e.currentTarget.dataset.format;
                        this.exportDocument(format);
                    });
                });
            }
            
            // Shortcode copy
            if (this.elements.copyShortcodeButton) {
                this.elements.copyShortcodeButton.addEventListener('click', () => this.copyShortcode());
            }
            
            // Share functionality
            if (this.elements.shareButton) {
                this.elements.shareButton.addEventListener('click', () => this.showShareModal());
            }
            
            if (this.elements.copyLinkButton) {
                this.elements.copyLinkButton.addEventListener('click', () => this.copyShareLink());
            }
            
            if (this.elements.saveSharingButton) {
                this.elements.saveSharingButton.addEventListener('click', () => this.saveSharing());
            }
            
            // Modal close buttons
            if (this.elements.modalClose) {
                this.elements.modalClose.forEach(button => {
                    button.addEventListener('click', () => {
                        this.hideShareModal();
                    });
                });
            }
            
            // Close modal when clicking outside
            if (this.elements.shareModal) {
                this.elements.shareModal.addEventListener('click', (e) => {
                    if (e.target === this.elements.shareModal) {
                        this.hideShareModal();
                    }
                });
            }
            
            // Before unload warning
            window.addEventListener('beforeunload', (e) => {
                if (this.state.hasUnsavedChanges) {
                    e.preventDefault();
                    e.returnValue = 'لديك تغييرات غير محفوظة. هل تريد المغادرة دون الحفظ؟';
                }
            });
        },
        
        /**
         * Setup auto-save functionality
         */
        setupAutoSave: function() {
            this.state.autoSaveInterval = setInterval(() => {
                if (this.state.hasUnsavedChanges && !this.state.isSaving) {
                    this.autoSave();
                }
            }, 30000); // Auto-save every 30 seconds
            
            console.log('Auto-save enabled (every 30 seconds)');
        },
        
        /**
         * Load initial document from URL or create new
         */
        loadInitialDocument: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const documentId = urlParams.get('document');
            
            if (documentId && documentId !== 'new') {
                this.loadDocument(documentId);
            } else {
                // Create new document
                this.setDocumentData({
                    id: 0,
                    title: 'مستند جديد',
                    content: '',
                    status: 'draft'
                });
            }
        },
        
        /**
         * Load document by ID
         */
        loadDocument: function(documentId) {
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_load_document',
                    nonce: this.config.nonce,
                    document_id: documentId
                },
                dataType: 'json',
                beforeSend: () => {
                    this.showLoading();
                }
            })
            .done(response => {
                if (response.success) {
                    this.setDocumentData(response.data.document);
                    this.showMessage('success', 'تم تحميل المستند بنجاح');
                } else {
                    this.showMessage('error', response.data.message || 'حدث خطأ في تحميل المستند');
                }
            })
            .fail(() => {
                this.showMessage('error', 'حدث خطأ في الاتصال بالخادم');
            })
            .always(() => {
                this.hideLoading();
            });
        },
        
        /**
         * Set document data in editor
         */
        setDocumentData: function(document) {
            // Set title
            if (this.elements.documentTitle) {
                this.elements.documentTitle.value = document.title || '';
            }
            
            // Set content in editor
            if (this.currentEditor && document.content) {
                this.currentEditor.setData(document.content);
            }
            
            // Update shortcode
            this.updateShortcode(document.id || 'new');
            
            // Reset unsaved changes flag
            this.setHasUnsavedChanges(false);
            
            // Update URL without reloading
            const url = new URL(window.location);
            if (document.id) {
                url.searchParams.set('document', document.id);
            } else {
                url.searchParams.delete('document');
            }
            window.history.replaceState({}, '', url);
        },
        
        /**
         * Save document
         */
        saveDocument: function(status = 'publish') {
            if (!this.currentEditor || this.state.isSaving) {
                return Promise.resolve(false);
            }
            
            return new Promise((resolve) => {
                this.state.isSaving = true;
                this.setSaveStatus('saving');
                
                const title = this.elements.documentTitle ? this.elements.documentTitle.value : '';
                const content = this.currentEditor.getData();
                
                $.ajax({
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpoe_save_document',
                        nonce: this.config.nonce,
                        document_id: this.getCurrentDocumentId(),
                        title: title,
                        content: content,
                        status: status
                    },
                    dataType: 'json'
                })
                .done(response => {
                    if (response.success) {
                        this.setHasUnsavedChanges(false);
                        this.setSaveStatus('saved');
                        this.updateShortcode(response.data.document_id);
                        this.showMessage('success', response.data.message || 'تم حفظ المستند بنجاح');
                        
                        // Update URL with new document ID
                        const url = new URL(window.location);
                        url.searchParams.set('document', response.data.document_id);
                        window.history.replaceState({}, '', url);
                        
                        resolve({
                            success: true,
                            document_id: response.data.document_id
                        });
                    } else {
                        this.showMessage('error', response.data.message || 'حدث خطأ أثناء الحفظ');
                        resolve({ success: false });
                    }
                })
                .fail(() => {
                    this.showMessage('error', 'حدث خطأ في الاتصال بالخادم');
                    resolve({ success: false });
                })
                .always(() => {
                    this.state.isSaving = false;
                });
            });
        },
        
        /**
         * Save document as new
         */
        saveDocumentAs: function() {
            const newTitle = prompt('أدخل اسم المستند الجديد:', 
                (this.elements.documentTitle ? this.elements.documentTitle.value : '') + ' - نسخة');
            
            if (!newTitle) return;
            
            // Create new document with current content
            if (this.elements.documentTitle) {
                this.elements.documentTitle.value = newTitle;
            }
            
            this.saveDocument().then(result => {
                if (result.success) {
                    this.showMessage('success', 'تم حفظ المستند الجديد بنجاح');
                }
            });
        },
        
        /**
         * Save as draft
         */
        saveDraft: function() {
            this.saveDocument('draft').then(result => {
                if (result.success) {
                    this.showMessage('success', 'تم حفظ المسودة بنجاح');
                }
            });
        },
        
        /**
         * Auto-save document
         */
        autoSave: function() {
            if (!this.state.hasUnsavedChanges || this.state.isSaving) {
                return;
            }
            
            this.saveDocument('auto-draft').then(result => {
                if (result.success) {
                    console.log('Auto-save completed');
                }
            });
        },
        
        /**
         * Publish as post
         */
        publishDocument: function() {
            if (!confirm('هل تريد نشر هذا المستند كمقال على الموقع؟')) {
                return;
            }
            
            const title = this.elements.documentTitle ? this.elements.documentTitle.value : '';
            const content = this.currentEditor ? this.currentEditor.getData() : '';
            
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_publish_post',
                    nonce: this.config.nonce,
                    document_id: this.getCurrentDocumentId(),
                    title: title,
                    content: content
                },
                dataType: 'json',
                beforeSend: () => {
                    this.showLoading();
                }
            })
            .done(response => {
                if (response.success) {
                    this.showMessage('success', response.data.message || 'تم نشر المقال بنجاح');
                    
                    // Open post in new tab
                    if (response.data.post_url) {
                        window.open(response.data.post_url, '_blank');
                    }
                    
                    // Update document with post ID
                    if (response.data.post_id) {
                        this.saveDocument().then(() => {
                            // Link document with post
                            // This would be handled by the backend
                        });
                    }
                } else {
                    this.showMessage('error', response.data.message || 'حدث خطأ أثناء النشر');
                }
            })
            .fail(() => {
                this.showMessage('error', 'حدث خطأ في الاتصال بالخادم');
            })
            .always(() => {
                this.hideLoading();
            });
        },
        
        /**
         * Export document
         */
        exportDocument: function(format) {
            const documentId = this.getCurrentDocumentId();
            
            if (!documentId) {
                this.showMessage('warning', 'يجب حفظ المستند أولاً قبل التصدير');
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.config.ajax_url;
            form.target = '_blank';
            form.style.display = 'none';
            
            const fields = {
                action: 'wpoe_export_document',
                nonce: this.config.nonce,
                document_id: documentId,
                format: format
            };
            
            Object.keys(fields).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },
        
        /**
         * AI functionality
         */
        sendAIPrompt: function() {
            const prompt = this.elements.aiPrompt ? this.elements.aiPrompt.value.trim() : '';
            
            if (!prompt) {
                this.showMessage('warning', 'يرجى إدخال نص للذكاء الاصطناعي');
                return;
            }
            
            const context = this.currentEditor ? this.currentEditor.getData() : '';
            
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_ai_generate',
                    nonce: this.config.nonce,
                    prompt: prompt,
                    context: context,
                    action_type: 'generate'
                },
                dataType: 'json',
                beforeSend: () => {
                    this.showAILoading();
                }
            })
            .done(response => {
                if (response.success) {
                    this.showAIResponse(response.data.content);
                    
                    // Clear prompt
                    if (this.elements.aiPrompt) {
                        this.elements.aiPrompt.value = '';
                    }
                } else {
                    this.showMessage('error', response.data.message || 'حدث خطأ في توليد النص');
                }
            })
            .fail(() => {
                this.showMessage('error', 'حدث خطأ في الاتصال بخدمة الذكاء الاصطناعي');
            })
            .always(() => {
                this.hideAILoading();
            });
        },
        
        /**
         * Handle AI actions
         */
        handleAIAction: function(action) {
            this.showAIPanel();
            
            let prompt = '';
            switch(action) {
                case 'improve':
                    prompt = 'قُم بتحسين النص التالي لغوياً وأسلوبياً مع الحفاظ على المعنى:';
                    break;
                case 'summarize':
                    prompt = 'لخص النص التالي في نقاط رئيسية:';
                    break;
                case 'translate':
                    prompt = 'ترجم النص التالي إلى الإنجليزية:';
                    break;
                case 'template_blog':
                    prompt = 'اكتب مقالاً مدونياً عن:';
                    break;
                case 'template_report':
                    prompt = 'اكتب تقريراً رسمياً عن:';
                    break;
                case 'template_letter':
                    prompt = 'اكتب رسالة رسمية بخصوص:';
                    break;
            }
            
            if (this.elements.aiPrompt) {
                this.elements.aiPrompt.value = prompt;
                this.elements.aiPrompt.focus();
            }
        },
        
        /**
         * Show AI panel
         */
        showAIPanel: function() {
            if (this.elements.aiPanel) {
                this.elements.aiPanel.classList.add('active');
            }
        },
        
        /**
         * Hide AI panel
         */
        hideAIPanel: function() {
            if (this.elements.aiPanel) {
                this.elements.aiPanel.classList.remove('active');
            }
        },
        
        /**
         * Toggle AI panel
         */
        toggleAIPanel: function() {
            if (this.elements.aiPanel) {
                if (this.elements.aiPanel.classList.contains('active')) {
                    this.hideAIPanel();
                } else {
                    this.showAIPanel();
                }
            }
        },
        
        /**
         * Show AI loading state
         */
        showAILoading: function() {
            // Implement loading state
        },
        
        /**
         * Hide AI loading state
         */
        hideAILoading: function() {
            // Implement hide loading state
        },
        
        /**
         * Show AI response
         */
        showAIResponse: function(content) {
            // Implement AI response display
        },
        
        /**
         * Update word and character counters
         */
        updateCounters: function() {
            if (!this.currentEditor) return;
            
            const content = this.currentEditor.getData();
            const textContent = this.stripHTML(content);
            
            // Word count
            const words = textContent.trim().split(/\s+/).filter(word => word.length > 0);
            if (this.elements.wordCount) {
                this.elements.wordCount.textContent = words.length;
            }
            
            // Character count
            if (this.elements.charCount) {
                this.elements.charCount.textContent = textContent.length;
            }
        },
        
        /**
         * Strip HTML tags
         */
        stripHTML: function(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        },
        
        /**
         * Update shortcode display
         */
        updateShortcode: function(documentId) {
            if (this.elements.shortcodeDisplay) {
                const shortcode = `[wpoe_document id="${documentId}"]`;
                this.elements.shortcodeDisplay.textContent = shortcode;
            }
        },
        
        /**
         * Copy shortcode to clipboard
         */
        copyShortcode: function() {
            if (this.elements.shortcodeDisplay) {
                const shortcode = this.elements.shortcodeDisplay.textContent;
                navigator.clipboard.writeText(shortcode).then(() => {
                    this.showMessage('success', 'تم نسخ الشورت كود');
                });
            }
        },
        
        /**
         * Create new document
         */
        createNewDocument: function() {
            if (this.state.hasUnsavedChanges) {
                if (!confirm('لديك تغييرات غير محفوظة. هل تريد إنشاء مستند جديد دون الحفظ؟')) {
                    return;
                }
            }
            
            // Reset editor
            if (this.currentEditor) {
                this.currentEditor.setData('');
            }
            
            if (this.elements.documentTitle) {
                this.elements.documentTitle.value = 'مستند جديد';
            }
            
            this.updateShortcode('new');
            this.setHasUnsavedChanges(false);
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.delete('document');
            window.history.replaceState({}, '', url);
        },
        
        /**
         * Open document browser
         */
        openDocumentBrowser: function() {
            // This would open a modal with document list
            // For now, show a message
            this.showMessage('info', 'متصفح المستندات قيد التطوير');
        },
        
        /**
         * Get current document ID from URL
         */
        getCurrentDocumentId: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const docId = urlParams.get('document');
            return docId && docId !== 'new' ? parseInt(docId) : 0;
        },
        
        /**
         * Set save status
         */
        setSaveStatus: function(status) {
            if (!this.elements.autoSaveStatus) return;
            
            this.elements.autoSaveStatus.className = 'wpoe-save-status';
            
            switch(status) {
                case 'saving':
                    this.elements.autoSaveStatus.classList.add('saving');
                    this.elements.autoSaveStatus.innerHTML = '<i class="fas fa-sync fa-spin"></i> جاري الحفظ...';
                    break;
                    
                case 'saved':
                    this.elements.autoSaveStatus.classList.add('saved');
                    this.elements.autoSaveStatus.innerHTML = '<i class="fas fa-check-circle"></i> تم الحفظ';
                    break;
                    
                case 'unsaved':
                    this.elements.autoSaveStatus.classList.add('unsaved');
                    this.elements.autoSaveStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> غير محفوظ';
                    break;
            }
        },
        
        /**
         * Set unsaved changes flag
         */
        setHasUnsavedChanges: function(hasChanges) {
            this.state.hasUnsavedChanges = hasChanges;
            this.setSaveStatus(hasChanges ? 'unsaved' : 'saved');
        },
        
        /**
         * Show share modal
         */
        showShareModal: function() {
            const documentId = this.getCurrentDocumentId();
            
            if (!documentId) {
                this.showMessage('warning', 'يجب حفظ المستند أولاً للمشاركة');
                return;
            }
            
            if (this.elements.shareModal) {
                this.elements.shareModal.classList.add('active');
                
                // Generate share URL
                const shareUrl = `${window.location.origin}${window.location.pathname}?page=wp-office-editor&share=${documentId}`;
                if (this.elements.shareUrl) {
                    this.elements.shareUrl.value = shareUrl;
                }
            }
        },
        
        /**
         * Hide share modal
         */
        hideShareModal: function() {
            if (this.elements.shareModal) {
                this.elements.shareModal.classList.remove('active');
            }
        },
        
        /**
         * Copy share link
         */
        copyShareLink: function() {
            if (this.elements.shareUrl) {
                const shareUrl = this.elements.shareUrl.value;
                navigator.clipboard.writeText(shareUrl).then(() => {
                    this.showMessage('success', 'تم نسخ رابط المشاركة');
                });
            }
        },
        
        /**
         * Save sharing settings
         */
        saveSharing: function() {
            const documentId = this.getCurrentDocumentId();
            
            if (!documentId) return;
            
            const selectedUsers = this.elements.userSelect ? 
                Array.from(this.elements.userSelect.selectedOptions).map(option => option.value) : [];
            
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_save_sharing',
                    nonce: this.config.nonce,
                    document_id: documentId,
                    users: selectedUsers
                },
                dataType: 'json'
            })
            .done(response => {
                if (response.success) {
                    this.showMessage('success', response.data.message || 'تم حفظ إعدادات المشاركة');
                    this.hideShareModal();
                } else {
                    this.showMessage('error', response.data.message || 'حدث خطأ أثناء الحفظ');
                }
            })
            .fail(() => {
                this.showMessage('error', 'حدث خطأ في الاتصال بالخادم');
            });
        },
        
        /**
         * Show loading indicator
         */
        showLoading: function() {
            // Implement loading indicator
        },
        
        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            // Implement hide loading indicator
        },
        
        /**
         * Show message
         */
        showMessage: function(type, text, duration = 3000) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.wpoe-message');
            existingMessages.forEach(msg => msg.remove());
            
            // Create message element
            const message = document.createElement('div');
            message.className = `wpoe-message wpoe-message-${type}`;
            message.innerHTML = `
                <div class="wpoe-message-content">
                    <span>${text}</span>
                    <button type="button" class="wpoe-message-close">&times;</button>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(message);
            
            // Show message
            setTimeout(() => {
                message.classList.add('show');
            }, 10);
            
            // Auto-remove after duration
            setTimeout(() => {
                message.classList.remove('show');
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 300);
            }, duration);
            
            // Close button
            const closeButton = message.querySelector('.wpoe-message-close');
            if (closeButton) {
                closeButton.addEventListener('click', () => {
                    message.classList.remove('show');
                    setTimeout(() => {
                        if (message.parentNode) {
                            message.parentNode.removeChild(message);
                        }
                    }, 300);
                });
            }
        }
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        WPOfficeEditor.init();
    });
    
    // Make available globally
    window.WPOfficeEditor = WPOfficeEditor;
    
})(jQuery);
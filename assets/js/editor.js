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
            tabContent: new Map(),
            // الحالة الموسعة من الملف الثاني
            tabs: new Map(),
            tabCounter: 0,
            maxTabs: 10
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
                shareButton: document.getElementById('wpoe-btn-share'),
                // عناصر التبويب من الملف الثاني
                newTabButton: document.getElementById('wpoe-new-tab'),
                closeAllTabsButton: document.getElementById('wpoe-close-all-tabs'),
                saveAllTabsButton: document.getElementById('wpoe-save-all-tabs'),
                tabsContainer: document.getElementById('wpoe-tabs-container'),
                // عناصر التعاون من الملف الثالث
                collaborationToggle: document.getElementById('wpoe-collaboration-toggle'),
                collaborationPanel: document.querySelector('.wpoe-collaborators-panel'),
                collaboratorsList: document.getElementById('wpoe-collaborators-list'),
                collaboratorsCount: document.getElementById('wpoe-collaborators-count'),
                connectionStatus: document.getElementById('wpoe-connection-status'),
                inviteCollaboratorButton: document.getElementById('wpoe-invite-collaborator')
            };
        },
        
        /**
         * تهيئة نظام الألسنة
         */
        initTabs: function() {
            // نظام الألسنة الموسع من الملف الثاني
            this.loadTabsFromStorage();
            this.setupTabEventListeners();
            this.renderTabs();
            
            // إذا لم يكن هناك ألسنة، إنشاء واحد جديد
            if (this.tabs.tabs.size === 0) {
                this.createNewTab();
            }
            
            // تحديد التبويب الحالي
            this.setCurrentTab(this.tabs.currentTabId || Array.from(this.tabs.tabs.keys())[0]);
        },
        
        /**
         * تحميل الألسنة من التخزين المحلي
         */
        loadTabsFromStorage: function() {
            try {
                const savedTabs = localStorage.getItem('wpoe_tabs');
                if (savedTabs) {
                    const tabsData = JSON.parse(savedTabs);
                    
                    tabsData.tabs.forEach(tab => {
                        this.tabs.tabs.set(tab.id, tab);
                        this.tabs.tabContent.set(tab.id, tab.content);
                    });
                    
                    this.tabs.currentTabId = tabsData.currentTab;
                    this.tabs.tabCounter = tabsData.tabCounter || 0;
                }
            } catch (e) {
                console.error('Error loading tabs from storage:', e);
            }
        },
        
        /**
         * حفظ الألسنة في التخزين المحلي
         */
        saveTabsToStorage: function() {
            try {
                const tabsData = {
                    tabs: Array.from(this.tabs.tabs.values()),
                    currentTab: this.tabs.currentTabId,
                    tabCounter: this.tabs.tabCounter,
                    lastSaved: new Date().toISOString()
                };
                
                localStorage.setItem('wpoe_tabs', JSON.stringify(tabsData));
            } catch (e) {
                console.error('Error saving tabs to storage:', e);
            }
        },
        
        /**
         * إعداد مستمعي الأحداث للتبويب
         */
        setupTabEventListeners: function() {
            // زر إنشاء تبويب جديد
            if (this.elements.newTabButton) {
                $(this.elements.newTabButton).on('click', () => this.createNewTab());
            }
            
            // زر إغلاق جميع الألسنة
            if (this.elements.closeAllTabsButton) {
                $(this.elements.closeAllTabsButton).on('click', () => this.closeAllTabs());
            }
            
            // زر حفظ جميع الألسنة
            if (this.elements.saveAllTabsButton) {
                $(this.elements.saveAllTabsButton).on('click', () => this.saveAllTabs());
            }
            
            // استماع لتغير العنوان
            if (this.elements.documentTitle) {
                $(this.elements.documentTitle).on('input', () => {
                    if (this.tabs.currentTabId) {
                        this.updateTabTitle(this.tabs.currentTabId, $(this.elements.documentTitle).val());
                    }
                });
            }
        },
        
        /**
         * إنشاء تبويب جديد
         */
        createNewTab: function(title = null, content = '', documentId = null) {
            if (this.tabs.tabs.size >= this.tabs.maxTabs) {
                this.showMessage('error', 'Maximum number of tabs reached (' + this.tabs.maxTabs + ')');
                return null;
            }
            
            this.tabs.tabCounter++;
            const tabId = 'tab_' + this.tabs.tabCounter;
            
            const tab = {
                id: tabId,
                title: title || this.config.i18n.new_document + ' ' + this.tabs.tabCounter,
                content: content,
                document_id: documentId,
                is_new: documentId === null,
                has_unsaved_changes: false,
                status: 'draft',
                created_at: new Date().toISOString(),
                last_modified: new Date().toISOString(),
                metadata: {
                    word_count: 0,
                    char_count: 0,
                    zoom_level: 100
                }
            };
            
            this.tabs.tabs.set(tabId, tab);
            this.tabs.tabContent.set(tabId, content);
            this.saveTabsToStorage();
            this.renderTabs();
            
            // إذا كان هذا أول تبويب، جعله الحالي
            if (this.tabs.tabs.size === 1) {
                this.setCurrentTab(tabId);
            }
            
            return tabId;
        },
        
        /**
         * تعيين التبويب الحالي
         */
        setCurrentTab: function(tabId) {
            if (!this.tabs.tabs.has(tabId)) {
                console.error('Tab not found:', tabId);
                return;
            }
            
            const previousTab = this.tabs.currentTabId;
            this.tabs.currentTabId = tabId;
            
            // تحديث واجهة المستخدم
            this.updateTabUI();
            
            // تحميل محتوى التبويب في المحرر
            this.loadTabContent(tabId);
            
            // حفظ حالة التبويب الحالي
            this.saveTabsToStorage();
            
            // إطلاق حدث تغيير التبويب
            $(document).trigger('wpoe:tabChanged', {
                previousTab: previousTab,
                currentTab: tabId,
                tab: this.tabs.tabs.get(tabId)
            });
        },
        
        /**
         * تحميل محتوى التبويب في المحرر
         */
        loadTabContent: function(tabId) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            // تحديث عنوان المستند
            if (this.elements.documentTitle) {
                $(this.elements.documentTitle).val(tab.title);
            }
            
            // تحديث المحرر
            if (this.currentEditor) {
                this.currentEditor.setData(tab.content || '');
            }
            
            // تحديث الشورت كود
            this.updateShortcode(tab.document_id || 'new');
            
            // تحديث الإحصائيات
            this.updateTabStats(tabId);
            
            // تحديث حالة التغييرات غير المحفوظة
            this.setHasUnsavedChanges(tab.has_unsaved_changes || false);
        },
        
        /**
         * تحديث عنوان التبويب
         */
        updateTabTitle: function(tabId, newTitle) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            if (tab.title !== newTitle) {
                tab.title = newTitle;
                tab.has_unsaved_changes = true;
                tab.last_modified = new Date().toISOString();
                
                this.state.hasUnsavedChanges = true;
                this.saveTabsToStorage();
                this.renderTabs();
            }
        },
        
        /**
         * تحديث محتوى التبويب
         */
        updateTabContent: function(tabId, newContent) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            if (tab.content !== newContent) {
                tab.content = newContent;
                tab.has_unsaved_changes = true;
                tab.last_modified = new Date().toISOString();
                
                this.tabs.tabContent.set(tabId, newContent);
                this.state.hasUnsavedChanges = true;
                this.saveTabsToStorage();
                
                // تحديث الإحصائيات
                this.updateTabStats(tabId);
            }
        },
        
        /**
         * حفظ محتوى التبويب الحالي
         */
        saveCurrentTabContent: function() {
            if (!this.tabs.currentTabId || !this.currentEditor) return;
            
            const content = this.currentEditor.getData();
            const title = this.elements.documentTitle ? this.elements.documentTitle.value : '';
            
            this.updateTabContent(this.tabs.currentTabId, content);
            this.updateTabTitle(this.tabs.currentTabId, title);
        },
        
        /**
         * تحديث إحصائيات التبويب
         */
        updateTabStats: function(tabId) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab || !this.currentEditor) return;
            
            const content = this.currentEditor.getData();
            const textContent = this.stripHTML(content);
            
            // حساب عدد الكلمات والأحرف
            const words = textContent.trim().split(/\s+/).filter(word => word.length > 0);
            const characters = textContent.length;
            
            tab.metadata.word_count = words.length;
            tab.metadata.char_count = characters;
            
            // تحديث العداد في الواجهة
            if (this.elements.wordCount) {
                this.elements.wordCount.textContent = words.length;
            }
            if (this.elements.charCount) {
                this.elements.charCount.textContent = characters;
            }
            
            this.saveTabsToStorage();
        },
        
        /**
         * إزالة HTML من النص
         */
        stripHTML: function(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        },
        
        /**
         * إغلاق تبويب
         */
        closeTab: function(tabId, force = false) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return false;
            
            // التحقق من وجود تغييرات غير محفوظة
            if (!force && tab.has_unsaved_changes) {
                if (!confirm(this.config.i18n.confirm_unsaved_close || 'This tab has unsaved changes. Close anyway?')) {
                    return false;
                }
            }
            
            // إزالة التبويب
            this.tabs.tabs.delete(tabId);
            this.tabs.tabContent.delete(tabId);
            
            // إزالة من DOM
            const tabElement = this.tabs.tabElements.get(tabId);
            if (tabElement) {
                tabElement.remove();
            }
            this.tabs.tabElements.delete(tabId);
            
            // إذا كان التبويب المغلق هو الحالي، تغيير التبويب الحالي
            if (this.tabs.currentTabId === tabId) {
                if (this.tabs.tabs.size > 0) {
                    // تحديد التبويب الأول في القائمة
                    const remainingTabs = Array.from(this.tabs.tabs.keys());
                    this.setCurrentTab(remainingTabs[0]);
                } else {
                    // إنشاء تبويب جديد إذا لم يتبقى أي تبويب
                    this.tabs.currentTabId = null;
                    this.createNewTab();
                }
            }
            
            this.saveTabsToStorage();
            this.renderTabs();
            
            return true;
        },
        
        /**
         * إغلاق جميع الألسنة
         */
        closeAllTabs: function() {
            // التحقق من وجود تغييرات غير محفوظة
            let hasUnsavedChanges = false;
            this.tabs.tabs.forEach(tab => {
                if (tab.has_unsaved_changes) {
                    hasUnsavedChanges = true;
                }
            });
            
            if (hasUnsavedChanges) {
                if (!confirm('Some tabs have unsaved changes. Close all tabs anyway?')) {
                    return;
                }
            }
            
            // إغلاق جميع الألسنة
            this.tabs.tabs.clear();
            this.tabs.tabContent.clear();
            this.tabs.tabElements.clear();
            this.tabs.currentTabId = null;
            
            // مسح المحتوى
            if (this.elements.tabsContainer) {
                this.elements.tabsContainer.innerHTML = '';
            }
            
            // إنشاء تبويب جديد
            this.createNewTab();
            
            this.saveTabsToStorage();
        },
        
        /**
         * حفظ جميع الألسنة
         */
        saveAllTabs: function() {
            let savedCount = 0;
            let errorCount = 0;
            
            this.tabs.tabs.forEach((tab, tabId) => {
                if (tab.has_unsaved_changes) {
                    try {
                        // حفظ التبويب
                        this.saveTab(tabId);
                        savedCount++;
                    } catch (e) {
                        console.error('Error saving tab:', tabId, e);
                        errorCount++;
                    }
                }
            });
            
            if (savedCount > 0) {
                this.showMessage('success', 'Saved ' + savedCount + ' tab(s)');
            }
            
            if (errorCount > 0) {
                this.showMessage('error', 'Failed to save ' + errorCount + ' tab(s)');
            }
        },
        
        /**
         * حفظ تبويب
         */
        saveTab: function(tabId, callback) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) {
                if (callback) callback(false, 'Tab not found');
                return;
            }
            
            // إعداد بيانات الحفظ
            const data = {
                action: 'wpoe_save_document',
                nonce: this.config.nonce,
                document_id: tab.document_id || 0,
                title: tab.title,
                content: tab.content,
                status: 'draft'
            };
            
            // إرسال طلب الحفظ
            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json'
            })
            .done(response => {
                if (response.success) {
                    // تحديث حالة التبويب
                    tab.has_unsaved_changes = false;
                    tab.document_id = response.data.document_id;
                    tab.status = 'saved';
                    
                    this.saveTabsToStorage();
                    this.renderTabs();
                    
                    if (callback) callback(true, response.data);
                } else {
                    if (callback) callback(false, response.data.message);
                }
            })
            .fail(() => {
                if (callback) callback(false, 'Network error');
            });
        },
        
        /**
         * تقديم الألسنة في الواجهة
         */
        renderTabs: function() {
            if (!this.elements.tabsContainer) {
                console.error('Tabs container not found');
                return;
            }
            
            // مسح المحتوى الحالي
            this.elements.tabsContainer.innerHTML = '';
            this.tabs.tabElements.clear();
            
            // إضافة زر جديد
            const newTabButton = document.createElement('button');
            newTabButton.className = 'wpoe-tab-button new-tab';
            newTabButton.title = 'New Tab';
            newTabButton.innerHTML = '<i class="fas fa-plus"></i>';
            newTabButton.addEventListener('click', () => this.createNewTab());
            
            this.elements.tabsContainer.appendChild(newTabButton);
            
            // إضافة الألسنة
            this.tabs.tabs.forEach((tab, tabId) => {
                const isActive = tabId === this.tabs.currentTabId;
                const hasUnsaved = tab.has_unsaved_changes;
                
                const tabElement = document.createElement('div');
                tabElement.className = 'wpoe-tab' + (isActive ? ' active' : '');
                tabElement.dataset.tabId = tabId;
                
                // عنوان التبويب
                const titleSpan = document.createElement('span');
                titleSpan.className = 'tab-title';
                titleSpan.textContent = tab.title || 'Untitled';
                
                // مؤشر التغييرات غير المحفوظة
                if (hasUnsaved) {
                    const unsavedIndicator = document.createElement('span');
                    unsavedIndicator.className = 'unsaved-indicator';
                    unsavedIndicator.textContent = ' ●';
                    titleSpan.appendChild(unsavedIndicator);
                }
                
                // زر الإغلاق
                const closeButton = document.createElement('button');
                closeButton.className = 'tab-close';
                closeButton.innerHTML = '&times;';
                closeButton.title = 'Close Tab';
                closeButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.closeTab(tabId);
                });
                
                tabElement.appendChild(titleSpan);
                tabElement.appendChild(closeButton);
                
                // حدث النقر على التبويب
                tabElement.addEventListener('click', () => {
                    if (!isActive) {
                        this.setCurrentTab(tabId);
                    }
                });
                
                // حدث النقر بالزر الأيمن (قائمة السياق)
                tabElement.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                    this.showTabContextMenu(e, tabId);
                });
                
                this.elements.tabsContainer.appendChild(tabElement);
                this.tabs.tabElements.set(tabId, tabElement);
            });
        },
        
        /**
         * تحديث واجهة التبويب
         */
        updateTabUI: function() {
            // تحديث حالة التبويب النشط
            document.querySelectorAll('.wpoe-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            if (this.tabs.currentTabId) {
                const currentTabElement = document.querySelector(`.wpoe-tab[data-tab-id="${this.tabs.currentTabId}"]`);
                if (currentTabElement) {
                    currentTabElement.classList.add('active');
                }
            }
            
            // تحديث عنوان الصفحة
            const currentTab = this.tabs.tabs.get(this.tabs.currentTabId);
            if (currentTab) {
                document.title = (currentTab.has_unsaved_changes ? '• ' : '') + 
                               currentTab.title + ' - WP Office Editor';
            }
            
            // تحديث حالة الحفظ التلقائي
            this.updateAutoSaveStatus();
        },
        
        /**
         * تحديث حالة الحفظ التلقائي
         */
        updateAutoSaveStatus: function() {
            const currentTab = this.tabs.tabs.get(this.tabs.currentTabId);
            if (!currentTab) return;
            
            if (this.elements.autoSaveStatus) {
                if (currentTab.has_unsaved_changes) {
                    this.elements.autoSaveStatus.classList.remove('saved');
                    this.elements.autoSaveStatus.classList.add('unsaved');
                    this.elements.autoSaveStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Unsaved Changes';
                } else {
                    this.elements.autoSaveStatus.classList.remove('unsaved');
                    this.elements.autoSaveStatus.classList.add('saved');
                    this.elements.autoSaveStatus.innerHTML = '<i class="fas fa-check-circle"></i> Saved';
                }
            }
        },
        
        /**
         * عرض قائمة سياق التبويب
         */
        showTabContextMenu: function(event, tabId) {
            // إزالة أي قوائم سياق سابقة
            document.querySelectorAll('.wpoe-tab-context-menu').forEach(menu => menu.remove());
            
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            const menuItems = [
                {
                    text: 'Save',
                    icon: 'save',
                    action: () => this.saveTab(tabId)
                },
                {
                    text: 'Save As...',
                    icon: 'copy',
                    action: () => this.saveTabAs(tabId)
                },
                { type: 'separator' },
                {
                    text: 'Duplicate Tab',
                    icon: 'clone',
                    action: () => this.duplicateTab(tabId)
                },
                {
                    text: 'Rename Tab',
                    icon: 'edit',
                    action: () => this.renameTab(tabId)
                },
                { type: 'separator' },
                {
                    text: 'Close Tab',
                    icon: 'times',
                    action: () => this.closeTab(tabId)
                },
                {
                    text: 'Close Other Tabs',
                    icon: 'times-circle',
                    action: () => this.closeOtherTabs(tabId)
                },
                {
                    text: 'Close All Tabs',
                    icon: 'window-close',
                    action: () => this.closeAllTabs()
                },
                { type: 'separator' },
                {
                    text: 'Export Tab...',
                    icon: 'download',
                    action: () => this.exportTab(tabId)
                }
            ];
            
            // إنشاء قائمة السياق
            const contextMenu = document.createElement('div');
            contextMenu.className = 'wpoe-tab-context-menu';
            contextMenu.style.position = 'fixed';
            contextMenu.style.left = event.pageX + 'px';
            contextMenu.style.top = event.pageY + 'px';
            contextMenu.style.zIndex = '10000';
            
            menuItems.forEach(item => {
                if (item.type === 'separator') {
                    const separator = document.createElement('div');
                    separator.className = 'context-menu-separator';
                    contextMenu.appendChild(separator);
                } else {
                    const menuItem = document.createElement('div');
                    menuItem.className = 'context-menu-item';
                    menuItem.innerHTML = `<i class="fas fa-${item.icon}"></i> ${item.text}`;
                    
                    menuItem.addEventListener('click', () => {
                        item.action();
                        document.querySelectorAll('.wpoe-tab-context-menu').forEach(menu => menu.remove());
                    });
                    
                    contextMenu.appendChild(menuItem);
                }
            });
            
            document.body.appendChild(contextMenu);
            
            // إغلاق القائمة عند النقر خارجها
            const closeMenu = () => {
                document.querySelectorAll('.wpoe-tab-context-menu').forEach(menu => menu.remove());
                document.removeEventListener('click', closeMenu);
            };
            
            setTimeout(() => {
                document.addEventListener('click', closeMenu);
            }, 10);
        },
        
        /**
         * حفظ التبويب باسم جديد
         */
        saveTabAs: function(tabId) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            const newTitle = prompt('Enter new document name:', tab.title);
            if (!newTitle) return;
            
            // إنشاء تبويب جديد بنفس المحتوى ولكن باسم جديد
            this.createNewTab(newTitle, tab.content, null);
        },
        
        /**
         * تكرار التبويب
         */
        duplicateTab: function(tabId) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            this.createNewTab(tab.title + ' (Copy)', tab.content, null);
        },
        
        /**
         * إعادة تسمية التبويب
         */
        renameTab: function(tabId) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            const newTitle = prompt('Rename tab:', tab.title);
            if (newTitle && newTitle !== tab.title) {
                this.updateTabTitle(tabId, newTitle);
            }
        },
        
        /**
         * إغلاق جميع الألسنة ما عدا المحدد
         */
        closeOtherTabs: function(keepTabId) {
            const tabsToClose = [];
            
            this.tabs.tabs.forEach((tab, tabId) => {
                if (tabId !== keepTabId) {
                    tabsToClose.push(tabId);
                }
            });
            
            // التحقق من وجود تغييرات غير محفوظة
            let hasUnsavedChanges = false;
            tabsToClose.forEach(tabId => {
                const tab = this.tabs.tabs.get(tabId);
                if (tab && tab.has_unsaved_changes) {
                    hasUnsavedChanges = true;
                }
            });
            
            if (hasUnsavedChanges) {
                if (!confirm('Some tabs have unsaved changes. Close them anyway?')) {
                    return;
                }
            }
            
            // إغلاق الألسنة
            tabsToClose.forEach(tabId => {
                this.tabs.tabs.delete(tabId);
                this.tabs.tabContent.delete(tabId);
                this.tabs.tabElements.delete(tabId);
            });
            
            // تحديث التبويب الحالي
            this.tabs.currentTabId = keepTabId;
            
            this.saveTabsToStorage();
            this.renderTabs();
            this.loadTabContent(keepTabId);
        },
        
        /**
         * تصدير التبويب
         */
        exportTab: function(tabId) {
            const tab = this.tabs.tabs.get(tabId);
            if (!tab) return;
            
            const format = prompt('Export format (html, txt, json):', 'html');
            if (!format) return;
            
            let content, filename, mimeType;
            
            switch (format.toLowerCase()) {
                case 'html':
                    content = this.generateExportHTML(tab);
                    filename = tab.title.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.html';
                    mimeType = 'text/html';
                    break;
                    
                case 'txt':
                    content = this.stripHTML(tab.content);
                    filename = tab.title.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.txt';
                    mimeType = 'text/plain';
                    break;
                    
                case 'json':
                    content = JSON.stringify(tab, null, 2);
                    filename = tab.title.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.json';
                    mimeType = 'application/json';
                    break;
                    
                default:
                    this.showMessage('error', 'Unsupported format: ' + format);
                    return;
            }
            
            // تنزيل الملف
            this.downloadFile(content, filename, mimeType);
        },
        
        /**
         * توليد HTML للتصدير
         */
        generateExportHTML: function(tab) {
            return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>${this.escapeHtml(tab.title)}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .document-header { border-bottom: 2px solid #0073aa; padding-bottom: 20px; margin-bottom: 30px; }
        .document-title { font-size: 28px; color: #333; margin-bottom: 10px; }
        .document-meta { color: #666; font-size: 14px; }
        .document-content { font-size: 16px; }
    </style>
</head>
<body>
    <div class="document-header">
        <h1 class="document-title">${this.escapeHtml(tab.title)}</h1>
        <div class="document-meta">
            Created: ${new Date(tab.created_at).toLocaleString()} | 
            Last Modified: ${new Date(tab.last_modified).toLocaleString()} | 
            Words: ${tab.metadata.word_count} | 
            Characters: ${tab.metadata.char_count}
        </div>
    </div>
    <div class="document-content">
        ${tab.content}
    </div>
</body>
</html>`;
        },
        
        /**
         * هروب HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * تنزيل الملف
         */
        downloadFile: function(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            URL.revokeObjectURL(url);
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
                        
                        // تحديث محتوى التبويب الحالي
                        if (this.tabs.currentTabId) {
                            this.updateTabContent(this.tabs.currentTabId, editor.getData());
                        }
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
                // إذا كان هناك معرف مستند في الرابط، نحمله في تبويب جديد
                this.loadDocument(documentId).then(documentData => {
                    this.createNewTab(documentData.title, documentData.content, documentId);
                });
            } else {
                // إنشاء مستند جديد سيتم في initTabs إذا لم يكن هناك ألسنة
            }
        },
        
        /**
         * Load document by ID
         */
        loadDocument: function(documentId) {
            return new Promise((resolve, reject) => {
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
                        resolve(response.data.document);
                    } else {
                        reject(response.data.message || 'حدث خطأ في تحميل المستند');
                    }
                })
                .fail(() => {
                    reject('حدث خطأ في الاتصال بالخادم');
                })
                .always(() => {
                    this.hideLoading();
                });
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
                const documentId = this.getCurrentDocumentId();
                
                $.ajax({
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpoe_save_document',
                        nonce: this.config.nonce,
                        document_id: documentId,
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
                        
                        // تحديث حالة التبويب الحالي
                        if (this.tabs.currentTabId) {
                            const tab = this.tabs.tabs.get(this.tabs.currentTabId);
                            if (tab) {
                                tab.has_unsaved_changes = false;
                                tab.document_id = response.data.document_id;
                                tab.status = status;
                                this.saveTabsToStorage();
                                this.renderTabs();
                            }
                        }
                        
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
            
            // إنشاء تبويب جديد بنفس المحتوى
            this.createNewTab(newTitle, this.currentEditor ? this.currentEditor.getData() : '', null);
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
            
            // تحديث إحصائيات التبويب الحالي
            if (this.tabs.currentTabId) {
                this.updateTabStats(this.tabs.currentTabId);
            }
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
            this.createNewTab();
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
            const currentTab = this.tabs.tabs.get(this.tabs.currentTabId);
            if (currentTab && currentTab.document_id) {
                return currentTab.document_id;
            }
            
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
            
            // تحديث حالة التبويب الحالي
            if (this.tabs.currentTabId) {
                const tab = this.tabs.tabs.get(this.tabs.currentTabId);
                if (tab) {
                    tab.has_unsaved_changes = hasChanges;
                    this.saveTabsToStorage();
                    this.renderTabs();
                }
            }
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
        },
        
        // حالة الذكاء الاصطناعي
        aiState: {
            isGenerating: false,
            currentGenerationId: null,
            chatHistory: [],
            templates: [],
            writingStyles: [],
            availableModels: []
        },
        
        /**
         * تهيئة نظام الذكاء الاصطناعي
         */
        initAI: function() {
            this.loadTemplates();
            this.loadWritingStyles();
            this.loadAvailableModels();
            this.setupAIEventListeners();
            this.loadChatHistory();
        },
        
        /**
         * تحميل القوالب
         */
        loadTemplates: function() {
            // يمكن تحميل القوالب من خادم AJAX
            this.aiState.templates = [
                {
                    id: 'blog_post',
                    name: 'Blog Post',
                    description: 'Generate a professional blog post',
                    icon: 'fa-blog',
                    fields: [
                        { name: 'topic', label: 'Topic', type: 'text', required: true },
                        { name: 'title', label: 'Title', type: 'text', required: false },
                        { name: 'tone', label: 'Tone', type: 'select', options: ['formal', 'casual', 'persuasive', 'academic'] },
                        { name: 'audience', label: 'Target Audience', type: 'text' },
                        { name: 'words', label: 'Word Count', type: 'number', min: 100, max: 5000 }
                    ]
                },
                {
                    id: 'report',
                    name: 'Report',
                    description: 'Generate a formal report',
                    icon: 'fa-chart-bar',
                    fields: [
                        { name: 'topic', label: 'Topic', type: 'text', required: true },
                        { name: 'structure', label: 'Structure', type: 'select', options: ['executive', 'technical', 'summary'] },
                        { name: 'sections', label: 'Number of Sections', type: 'number', min: 3, max: 10 }
                    ]
                },
                {
                    id: 'business_letter',
                    name: 'Business Letter',
                    description: 'Generate a business letter',
                    icon: 'fa-envelope',
                    fields: [
                        { name: 'recipient', label: 'Recipient', type: 'text', required: true },
                        { name: 'subject', label: 'Subject', type: 'text', required: true },
                        { name: 'purpose', label: 'Purpose', type: 'textarea' },
                        { name: 'tone', label: 'Tone', type: 'select', options: ['formal', 'semi-formal', 'urgent'] }
                    ]
                },
                {
                    id: 'email',
                    name: 'Email',
                    description: 'Generate a professional email',
                    icon: 'fa-mail-bulk',
                    fields: [
                        { name: 'recipient', label: 'To', type: 'text', required: true },
                        { name: 'subject', label: 'Subject', type: 'text', required: true },
                        { name: 'purpose', label: 'Purpose', type: 'textarea', required: true },
                        { name: 'tone', label: 'Tone', type: 'select', options: ['formal', 'casual', 'friendly'] }
                    ]
                }
            ];
        },
        
        /**
         * تحميل أنماط الكتابة
         */
        loadWritingStyles: function() {
            this.aiState.writingStyles = [
                { id: 'formal', name: 'Formal', description: 'Professional and business-like' },
                { id: 'casual', name: 'Casual', description: 'Friendly and conversational' },
                { id: 'persuasive', name: 'Persuasive', description: 'Convincing and influential' },
                { id: 'academic', name: 'Academic', description: 'Scholarly and research-based' },
                { id: 'creative', name: 'Creative', description: 'Imaginative and expressive' },
                { id: 'technical', name: 'Technical', description: 'Detailed and precise' }
            ];
        },
        
        /**
         * تحميل النماذج المتاحة
         */
        loadAvailableModels: function() {
            // يمكن جلب هذه من الخادم
            this.aiState.availableModels = [
                { id: 'gpt-3.5-turbo', name: 'GPT-3.5 Turbo', description: 'Fast and cost-effective', maxTokens: 4096 },
                { id: 'gpt-4', name: 'GPT-4', description: 'Most capable model', maxTokens: 8192 },
                { id: 'gpt-4-turbo-preview', name: 'GPT-4 Turbo', description: 'Latest model with 128K context', maxTokens: 128000 }
            ];
        },
        
        /**
         * إعداد مستمعي الأحداث للذكاء الاصطناعي
         */
        setupAIEventListeners: function() {
            // مستمعي الأحداث تم إضافتها في الكود السابق
        },
        
        /**
         * تحميل سجل المحادثة
         */
        loadChatHistory: function() {
            const savedHistory = localStorage.getItem('wpoe_ai_chat_history');
            if (savedHistory) {
                try {
                    this.aiState.chatHistory = JSON.parse(savedHistory);
                } catch (e) {
                    console.error('Error loading chat history:', e);
                    this.aiState.chatHistory = [];
                }
            }
        },
        
        /**
         * حفظ سجل المحادثة
         */
        saveChatHistory: function() {
            try {
                // حفظ آخر 50 رسالة فقط
                const recentHistory = this.aiState.chatHistory.slice(-50);
                localStorage.setItem('wpoe_ai_chat_history', JSON.stringify(recentHistory));
            } catch (e) {
                console.error('Error saving chat history:', e);
            }
        },
        
        /**
         * إرسال طلب الذكاء الاصطناعي
         */
        sendAIRequest: function(prompt, context, action, options = {}) {
            if (this.aiState.isGenerating) {
                return Promise.reject('Another generation is in progress');
            }
            
            this.aiState.isGenerating = true;
            this.aiState.currentGenerationId = Date.now();
            
            const generationId = this.aiState.currentGenerationId;
            
            // إظهار حالة التحميل
            this.showAILoadingState(true);
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpoe_ai_generate',
                        nonce: this.config.nonce,
                        prompt: prompt,
                        context: context,
                        action_type: action,
                        options: JSON.stringify(options)
                    },
                    dataType: 'json'
                })
                .done(response => {
                    if (this.aiState.currentGenerationId !== generationId) {
                        // تم إلغاء هذا الطلب
                        reject('Request cancelled');
                        return;
                    }
                    
                    if (response.success) {
                        // تسجيل المحادثة
                        this.addToChatHistory('user', prompt, action);
                        this.addToChatHistory('assistant', response.data.content, action);
                        
                        // حفظ السجل
                        this.saveChatHistory();
                        
                        resolve(response.data);
                    } else {
                        reject(response.data.message || 'Unknown error');
                    }
                })
                .fail((xhr, status, error) => {
                    reject('Request failed: ' + error);
                })
                .always(() => {
                    this.aiState.isGenerating = false;
                    this.showAILoadingState(false);
                });
            });
        },
        
        /**
         * إضافة إلى سجل المحادثة
         */
        addToChatHistory: function(role, content, action) {
            this.aiState.chatHistory.push({
                id: Date.now(),
                role: role,
                content: content,
                action: action,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * إظهار حالة التحميل للذكاء الاصطناعي
         */
        showAILoadingState: function(isLoading) {
            if (isLoading) {
                // إظهار مؤشر التحميل
                if (this.elements.aiSend) {
                    this.elements.aiSend.style.display = 'none';
                }
                
                // إنشاء زر إيقاف إذا لم يكن موجوداً
                let stopButton = document.getElementById('wpoe-ai-stop');
                if (!stopButton && this.elements.aiSend) {
                    stopButton = document.createElement('button');
                    stopButton.id = 'wpoe-ai-stop';
                    stopButton.innerHTML = '<i class="fas fa-stop"></i> إيقاف';
                    stopButton.type = 'button';
                    stopButton.className = this.elements.aiSend.className;
                    this.elements.aiSend.parentNode.insertBefore(stopButton, this.elements.aiSend.nextSibling);
                    
                    stopButton.addEventListener('click', () => {
                        this.stopAIGeneration();
                    });
                }
                
                if (stopButton) {
                    stopButton.style.display = 'inline-block';
                }
                
                if (this.elements.aiPrompt) {
                    this.elements.aiPrompt.disabled = true;
                }
                
                // إضافة رسالة تحميل إلى المحادثة
                this.addAILoadingMessage();
            } else {
                // إخفاء مؤشر التحميل
                if (this.elements.aiSend) {
                    this.elements.aiSend.style.display = 'inline-block';
                }
                
                const stopButton = document.getElementById('wpoe-ai-stop');
                if (stopButton) {
                    stopButton.style.display = 'none';
                }
                
                if (this.elements.aiPrompt) {
                    this.elements.aiPrompt.disabled = false;
                }
                
                // إزالة رسالة التحميل
                this.removeAILoadingMessage();
            }
        },
        
        /**
         * إضافة رسالة تحميل للذكاء الاصطناعي
         */
        addAILoadingMessage: function() {
            const messageId = 'loading-' + Date.now();
            const messageHTML = `
                <div class="wpoe-ai-message assistant-message" id="${messageId}">
                    <div class="wpoe-ai-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="wpoe-ai-content">
                        <div class="wpoe-ai-loading">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            
            const aiMessages = document.getElementById('wpoe-ai-messages');
            if (aiMessages) {
                aiMessages.innerHTML += messageHTML;
                this.scrollAIToBottom();
            }
            
            this.aiState.loadingMessageId = messageId;
        },
        
        /**
         * إزالة رسالة تحميل للذكاء الاصطناعي
         */
        removeAILoadingMessage: function() {
            if (this.aiState.loadingMessageId) {
                const loadingMessage = document.getElementById(this.aiState.loadingMessageId);
                if (loadingMessage) {
                    loadingMessage.remove();
                }
                this.aiState.loadingMessageId = null;
            }
        },
        
        /**
         * إيقاف توليد الذكاء الاصطناعي
         */
        stopAIGeneration: function() {
            this.aiState.isGenerating = false;
            this.aiState.currentGenerationId = null;
            this.showAILoadingState(false);
        },
        
        /**
         * التمرير إلى الأسفل في نافذة الذكاء الاصطناعي
         */
        scrollAIToBottom: function() {
            const messagesContainer = document.getElementById('wpoe-ai-messages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        },
        
        /**
         * تطبيق النص على المحرر
         */
        applyToEditor: function(content, method = 'insert') {
            if (!this.currentEditor) {
                console.error('Editor not available');
                return;
            }
            
            const editor = this.currentEditor;
            
            switch (method) {
                case 'replace':
                    editor.setData(content);
                    break;
                    
                case 'insert':
                    editor.model.change(writer => {
                        const selection = editor.model.document.selection;
                        const range = selection.getFirstRange();
                        
                        if (range) {
                            writer.insertText(content, range.start);
                        } else {
                            // إذا لم يكن هناك تحديد، أدخل في النهاية
                            const root = editor.model.document.getRoot();
                            const endPosition = writer.createPositionAt(root, 'end');
                            writer.insertText(content, endPosition);
                        }
                    });
                    break;
                    
                case 'append':
                    editor.model.change(writer => {
                        const root = editor.model.document.getRoot();
                        const endPosition = writer.createPositionAt(root, 'end');
                        writer.insertText(content, endPosition);
                    });
                    break;
                    
                case 'prepend':
                    editor.model.change(writer => {
                        const root = editor.model.document.getRoot();
                        const startPosition = writer.createPositionAt(root, 0);
                        writer.insertText(content, startPosition);
                    });
                    break;
            }
            
            // تحديث محتوى التبويب الحالي
            if (this.tabs.currentTabId) {
                this.updateTabContent(this.tabs.currentTabId, editor.getData());
            }
            
            // إظهار رسالة نجاح
            this.showMessage('success', 'تم تطبيق النص بنجاح');
        },
        
        /**
         * فتح نافذة القالب
         */
        openTemplateWindow: function(templateId) {
            const template = this.aiState.templates.find(t => t.id === templateId);
            
            if (!template) {
                console.error('Template not found:', templateId);
                return;
            }
            
            // إنشاء نموذج HTML للقالب
            let formHTML = `
                <div class="wpoe-ai-template-form" id="wpoe-ai-template-${template.id}">
                    <h3><i class="fas ${template.icon}"></i> ${template.name}</h3>
                    <p class="description">${template.description}</p>
                    <div class="template-fields">
            `;
            
            // إضافة الحقول
            template.fields.forEach(field => {
                const fieldId = `template-${template.id}-${field.name}`;
                const requiredAttr = field.required ? 'required' : '';
                
                formHTML += `<div class="form-field">`;
                formHTML += `<label for="${fieldId}">${field.label}</label>`;
                
                if (field.type === 'select') {
                    formHTML += `<select id="${fieldId}" ${requiredAttr}>`;
                    field.options.forEach(option => {
                        formHTML += `<option value="${option}">${option}</option>`;
                    });
                    formHTML += `</select>`;
                } else if (field.type === 'textarea') {
                    formHTML += `<textarea id="${fieldId}" ${requiredAttr} rows="3"></textarea>`;
                } else {
                    const inputType = field.type === 'number' ? 'number' : 'text';
                    const minAttr = field.min ? `min="${field.min}"` : '';
                    const maxAttr = field.max ? `max="${field.max}"` : '';
                    formHTML += `<input type="${inputType}" id="${fieldId}" ${requiredAttr} ${minAttr} ${maxAttr}>`;
                }
                
                formHTML += `</div>`;
            });
            
            formHTML += `
                    </div>
                    <div class="template-actions">
                        <button type="button" class="button button-secondary cancel-template">إلغاء</button>
                        <button type="button" class="button button-primary generate-template">توليد</button>
                    </div>
                </div>
            `;
            
            // إظهار النافذة
            this.showAIModal('AI Template', formHTML);
            
            // إضافة مستمعي الأحداث
            const generateButton = document.querySelector(`#wpoe-ai-template-${template.id} .generate-template`);
            const cancelButton = document.querySelector(`#wpoe-ai-template-${template.id} .cancel-template`);
            
            if (generateButton) {
                generateButton.addEventListener('click', () => {
                    this.generateFromTemplate(template);
                });
            }
            
            if (cancelButton) {
                cancelButton.addEventListener('click', () => {
                    this.closeAIModal();
                });
            }
        },
        
        /**
         * توليد من قالب
         */
        generateFromTemplate: function(template) {
            const formId = `wpoe-ai-template-${template.id}`;
            const data = {};
            
            // جمع بيانات النموذج
            template.fields.forEach(field => {
                const fieldId = `template-${template.id}-${field.name}`;
                const inputElement = document.getElementById(fieldId);
                
                if (inputElement) {
                    const value = inputElement.value;
                    
                    if (field.required && !value) {
                        this.showMessage('error', `يرجى ملء حقل "${field.label}"`);
                        return false;
                    }
                    
                    data[field.name] = value;
                }
            });
            
            // توليد المحتوى
            const prompt = this.fillTemplate(template.promptTemplate, data);
            
            this.sendAIRequest(prompt, '', template.id, data)
                .then(response => {
                    this.closeAIModal();
                    this.showAIPanel();
                    this.addAIMessage('assistant', response.content);
                    
                    // عرض خيارات التطبيق
                    this.showAIApplyOptions(response.content);
                })
                .catch(error => {
                    this.showMessage('error', error);
                });
        },
        
        /**
         * ملء القالب
         */
        fillTemplate: function(template, data) {
            let filledTemplate = template;
            
            Object.keys(data).forEach(key => {
                const placeholder = `{${key}}`;
                filledTemplate = filledTemplate.replace(new RegExp(placeholder, 'g'), data[key]);
            });
            
            return filledTemplate;
        },
        
        /**
         * إظهار خيارات التطبيق للذكاء الاصطناعي
         */
        showAIApplyOptions: function(content) {
            const optionsHTML = `
                <div class="wpoe-ai-apply-options">
                    <p>تطبيق النص على:</p>
                    <div class="apply-buttons">
                        <button type="button" class="button button-small apply-option" data-action="replace">
                            <i class="fas fa-exchange-alt"></i> استبدال النص الحالي
                        </button>
                        <button type="button" class="button button-small apply-option" data-action="insert">
                            <i class="fas fa-plus"></i> إدراج في الموضع الحالي
                        </button>
                        <button type="button" class="button button-small apply-option" data-action="append">
                            <i class="fas fa-arrow-down"></i> إضافة في النهاية
                        </button>
                        <button type="button" class="button button-small apply-option" data-action="prepend">
                            <i class="fas fa-arrow-up"></i> إضافة في البداية
                        </button>
                    </div>
                </div>
            `;
            
            const aiMessages = document.getElementById('wpoe-ai-messages');
            if (aiMessages) {
                aiMessages.innerHTML += optionsHTML;
                this.scrollAIToBottom();
            }
            
            // إضافة مستمعي الأحداث
            const applyButtons = document.querySelectorAll('.apply-option');
            applyButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const action = e.currentTarget.getAttribute('data-action');
                    this.applyToEditor(content, action);
                    e.currentTarget.closest('.wpoe-ai-apply-options').remove();
                });
            });
        },
        
        /**
         * إضافة رسالة للذكاء الاصطناعي
         */
        addAIMessage: function(role, content) {
            const messageId = 'message-' + Date.now();
            const messageHTML = `
                <div class="wpoe-ai-message ${role}-message" id="${messageId}">
                    <div class="wpoe-ai-avatar">
                        <i class="fas ${role === 'user' ? 'fa-user' : 'fa-robot'}"></i>
                    </div>
                    <div class="wpoe-ai-content">
                        <div class="wpoe-ai-text">
                            ${content}
                        </div>
                    </div>
                </div>
            `;
            
            const aiMessages = document.getElementById('wpoe-ai-messages');
            if (aiMessages) {
                aiMessages.innerHTML += messageHTML;
                this.scrollAIToBottom();
            }
        },
        
        /**
         * إظهار نافذة الذكاء الاصطناعي
         */
        showAIModal: function(title, content) {
            const modalHTML = `
                <div class="wpoe-modal active" id="wpoe-ai-modal">
                    <div class="wpoe-modal-content">
                        <div class="wpoe-modal-header">
                            <h3>${title}</h3>
                            <button type="button" class="wpoe-modal-close">&times;</button>
                        </div>
                        <div class="wpoe-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // إضافة مستمع إغلاق النافذة
            const closeButton = document.querySelector('#wpoe-ai-modal .wpoe-modal-close');
            if (closeButton) {
                closeButton.addEventListener('click', () => {
                    this.closeAIModal();
                });
            }
        },
        
        /**
         * إغلاق نافذة الذكاء الاصطناعي
         */
        closeAIModal: function() {
            const modal = document.getElementById('wpoe-ai-modal');
            if (modal) {
                modal.remove();
            }
        },
        
        /**
         * مسح سجل المحادثة للذكاء الاصطناعي
         */
        clearAIChatHistory: function() {
            if (confirm('هل تريد مسح سجل المحادثة؟')) {
                this.aiState.chatHistory = [];
                this.saveChatHistory();
                const aiMessages = document.getElementById('wpoe-ai-messages');
                if (aiMessages) {
                    aiMessages.innerHTML = '';
                }
                this.showMessage('success', 'تم مسح سجل المحادثة');
            }
        },
        
        // إضافة نظام التعاون
        Collaboration: {
            // حالة التعاون
            state: {
                socket: null,
                connected: false,
                documentId: null,
                token: null,
                collaborators: new Map(),
                cursorPositions: new Map(),
                lastUpdate: null,
                updateInterval: null,
                pendingChanges: []
            },
            
            /**
             * تهيئة نظام التعاون
             */
            init: function(documentId, token, serverUrl) {
                this.state.documentId = documentId;
                this.state.token = token;
                
                // التحقق من توفر Socket.IO
                if (typeof io === 'undefined') {
                    console.error('Socket.IO is not loaded');
                    return;
                }
                
                if (!serverUrl) {
                    console.error('Socket server URL is not configured');
                    return;
                }
                
                this.connectToServer(serverUrl);
                this.setupEventListeners();
                this.startUpdateInterval();
            },
            
            /**
             * الاتصال بخادم WebSocket
             */
            connectToServer: function(serverUrl) {
                try {
                    this.state.socket = io(serverUrl, {
                        transports: ['websocket', 'polling'],
                        query: {
                            documentId: this.state.documentId,
                            token: this.state.token
                        }
                    });
                    
                    this.setupSocketListeners();
                    
                } catch (error) {
                    console.error('Failed to connect to collaboration server:', error);
                }
            },
            
            /**
             * إعداد مستمعي Socket
             */
            setupSocketListeners: function() {
                const socket = this.state.socket;
                
                socket.on('connect', () => {
                    console.log('Connected to collaboration server');
                    this.state.connected = true;
                    this.updateConnectionStatus(true);
                    
                    // الانضمام إلى غرفة المستند
                    socket.emit('join-document', {
                        documentId: this.state.documentId,
                        token: this.state.token,
                        user: WPOfficeEditor.config.current_user
                    });
                });
                
                socket.on('disconnect', () => {
                    console.log('Disconnected from collaboration server');
                    this.state.connected = false;
                    this.updateConnectionStatus(false);
                });
                
                socket.on('error', (error) => {
                    console.error('Collaboration error:', error);
                    WPOfficeEditor.showMessage('error', 'Collaboration error: ' + error);
                });
                
                socket.on('user-joined', (data) => {
                    this.addCollaborator(data.user);
                    WPOfficeEditor.showMessage('info', data.user.name + ' joined the document');
                });
                
                socket.on('user-left', (data) => {
                    this.removeCollaborator(data.userId);
                    WPOfficeEditor.showMessage('info', 'User left the document');
                });
                
                socket.on('content-updated', (data) => {
                    this.handleRemoteUpdate(data);
                });
                
                socket.on('cursor-moved', (data) => {
                    this.updateCursorPosition(data.userId, data.position);
                });
                
                socket.on('selection-changed', (data) => {
                    this.updateSelection(data.userId, data.selection);
                });
                
                socket.on('collaborators-list', (data) => {
                    this.updateCollaboratorsList(data.collaborators);
                });
                
                socket.on('document-locked', (data) => {
                    this.handleDocumentLocked(data);
                });
                
                socket.on('document-unlocked', (data) => {
                    this.handleDocumentUnlocked(data);
                });
            },
            
            /**
             * إعداد مستمعي الأحداث
             */
            setupEventListeners: function() {
                // مستمع تغييرات المحرر
                if (WPOfficeEditor.currentEditor) {
                    WPOfficeEditor.currentEditor.model.document.on('change:data', (evt, data) => {
                        this.handleLocalChange(data);
                    });
                    
                    // تتبع حركة المؤشر
                    WPOfficeEditor.currentEditor.model.document.selection.on('change', () => {
                        this.sendCursorPosition();
                    });
                }
                
                // مستمع إغلاق الصفحة
                window.addEventListener('beforeunload', () => {
                    this.leaveDocument();
                });
            },
            
            /**
             * بدء فاصل التحديث
             */
            startUpdateInterval: function() {
                // إرسال تحديثات كل 2 ثانية
                this.state.updateInterval = setInterval(() => {
                    this.sendPendingChanges();
                }, 2000);
            },
            
            /**
             * معالجة التغييرات المحلية
             */
            handleLocalChange: function(changeData) {
                // تسجيل التغيير في قائمة الانتظار
                this.state.pendingChanges.push({
                    timestamp: Date.now(),
                    data: changeData,
                    user: WPOfficeEditor.config.current_user
                });
                
                // إذا كان هناك العديد من التغييرات، إرسالها فوراً
                if (this.state.pendingChanges.length > 10) {
                    this.sendPendingChanges();
                }
            },
            
            /**
             * إرسال التغييرات المعلقة
             */
            sendPendingChanges: function() {
                if (!this.state.connected || this.state.pendingChanges.length === 0) {
                    return;
                }
                
                const changes = [...this.state.pendingChanges];
                this.state.pendingChanges = [];
                
                this.state.socket.emit('content-change', {
                    documentId: this.state.documentId,
                    token: this.state.token,
                    changes: changes,
                    timestamp: Date.now()
                });
                
                this.state.lastUpdate = Date.now();
            },
            
            /**
             * معالجة التحديثات البعيدة
             */
            handleRemoteUpdate: function(updateData) {
                // تجاهل التحديثات من المستخدم الحالي
                if (updateData.userId === WPOfficeEditor.config.current_user.id) {
                    return;
                }
                
                if (WPOfficeEditor.currentEditor) {
                    // تطبيق التغييرات على المحرر
                    this.applyRemoteChanges(updateData.changes);
                    
                    // إظهار إشعار
                    if (updateData.userName) {
                        WPOfficeEditor.showMessage('info', updateData.userName + ' made changes');
                    }
                }
            },
            
            /**
             * تطبيق التغييرات البعيدة
             */
            applyRemoteChanges: function(changes) {
                const editor = WPOfficeEditor.currentEditor;
                
                editor.model.change(writer => {
                    changes.forEach(change => {
                        // تطبيق التغييرات حسب نوعها
                        // هذا مثال مبسط، في التطبيق الحقيقي تحتاج إلى معالجة أكثر تعقيداً
                        if (change.type === 'insert') {
                            const position = this.convertToPosition(change.position);
                            writer.insertText(change.text, position);
                        } else if (change.type === 'remove') {
                            const range = this.convertToRange(change.range);
                            writer.remove(range);
                        }
                    });
                });
                
                // تحديث محتوى التبويب الحالي
                if (WPOfficeEditor.tabs.currentTabId) {
                    WPOfficeEditor.updateTabContent(WPOfficeEditor.tabs.currentTabId, editor.getData());
                }
            },
            
            /**
             * إرسال موقع المؤشر
             */
            sendCursorPosition: function() {
                if (!this.state.connected || !WPOfficeEditor.currentEditor) {
                    return;
                }
                
                const selection = WPOfficeEditor.currentEditor.model.document.selection;
                const position = selection.getFirstPosition();
                
                this.state.socket.emit('cursor-move', {
                    documentId: this.state.documentId,
                    token: this.state.token,
                    position: {
                        path: position.path,
                        offset: position.offset
                    }
                });
            },
            
            /**
             * تحديث موقع مؤشر مستخدم آخر
             */
            updateCursorPosition: function(userId, position) {
                this.state.cursorPositions.set(userId, {
                    position: position,
                    updatedAt: Date.now()
                });
                
                this.updateCursorDisplay();
            },
            
            /**
             * تحديث عرض المؤشرات
             */
            updateCursorDisplay: function() {
                // إزالة المؤشرات القديمة (أكثر من 5 ثواني)
                const now = Date.now();
                this.state.cursorPositions.forEach((data, userId) => {
                    if (now - data.updatedAt > 5000) {
                        this.state.cursorPositions.delete(userId);
                    }
                });
                
                // عرض المؤشرات في المحرر
                // هذا يتطلب تكاملاً أكثر تقدماً مع CKEditor 5
            },
            
            /**
             * إضافة متعاون
             */
            addCollaborator: function(user) {
                this.state.collaborators.set(user.id, {
                    user: user,
                    joinedAt: Date.now(),
                    lastActivity: Date.now()
                });
                
                this.updateCollaboratorsDisplay();
            },
            
            /**
             * إزالة متعاون
             */
            removeCollaborator: function(userId) {
                this.state.collaborators.delete(userId);
                this.state.cursorPositions.delete(userId);
                this.updateCollaboratorsDisplay();
            },
            
            /**
             * تحديث قائمة المتعاونين
             */
            updateCollaboratorsList: function(collaborators) {
                this.state.collaborators.clear();
                
                collaborators.forEach(collaborator => {
                    this.state.collaborators.set(collaborator.user.id, {
                        user: collaborator.user,
                        joinedAt: collaborator.joinedAt,
                        lastActivity: collaborator.lastActivity
                    });
                });
                
                this.updateCollaboratorsDisplay();
            },
            
            /**
             * تحديث عرض المتعاونين
             */
            updateCollaboratorsDisplay: function() {
                const container = $('.wpoe-collaborators-list, #wpoe-collaborators-list');
                
                if (container.length === 0) {
                    return;
                }
                
                container.empty();
                
                // إضافة المستخدم الحالي أولاً
                const currentUser = WPOfficeEditor.config.current_user;
                const currentUserHtml = `
                    <div class="wpoe-collaborator me" title="${currentUser.name} (You)">
                        <img src="${currentUser.avatar}" alt="${currentUser.name}">
                    </div>
                `;
                container.append(currentUserHtml);
                
                // إضافة المتعاونين الآخرين
                this.state.collaborators.forEach((data, userId) => {
                    if (userId !== currentUser.id) {
                        const user = data.user;
                        const collaboratorHtml = `
                            <div class="wpoe-collaborator" title="${user.name}">
                                <img src="${user.avatar}" alt="${user.name}">
                                <span class="wpoe-collaborator-name">${user.name}</span>
                            </div>
                        `;
                        container.append(collaboratorHtml);
                    }
                });
                
                // تحديث العداد
                const count = this.state.collaborators.size;
                $('.wpoe-collaborators-count').text(count + ' collaborator' + (count !== 1 ? 's' : ''));
            },
            
            /**
             * تحديث حالة الاتصال
             */
            updateConnectionStatus: function(isConnected) {
                const statusElement = $('.wpoe-connection-status');
                
                if (statusElement.length === 0) {
                    return;
                }
                
                if (isConnected) {
                    statusElement.html('● <span style="color: #28a745;">' + WPOfficeEditor.config.i18n.connected + '</span>');
                } else {
                    statusElement.html('● <span style="color: #dc3545;">' + WPOfficeEditor.config.i18n.disconnected + '</span>');
                }
            },
            
            /**
             * مغادرة المستند
             */
            leaveDocument: function() {
                if (this.state.connected && this.state.socket) {
                    this.state.socket.emit('leave-document', {
                        documentId: this.state.documentId,
                        token: this.state.token
                    });
                }
                
                if (this.state.updateInterval) {
                    clearInterval(this.state.updateInterval);
                }
            },
            
            /**
             * معالجة قفل المستند
             */
            handleDocumentLocked: function(data) {
                if (WPOfficeEditor.currentEditor) {
                    WPOfficeEditor.currentEditor.isReadOnly = true;
                    WPOfficeEditor.showMessage('warning', 'Document is locked by ' + data.lockedBy);
                }
            },
            
            /**
             * معالجة فتح المستند
             */
            handleDocumentUnlocked: function(data) {
                if (WPOfficeEditor.currentEditor) {
                    WPOfficeEditor.currentEditor.isReadOnly = false;
                    WPOfficeEditor.showMessage('success', 'Document is now unlocked');
                }
            },
            
            /**
             * تحويل البيانات إلى موضع CKEditor
             */
            convertToPosition: function(positionData) {
                // هذا مثال مبسط، يحتاج إلى تكامل مع CKEditor 5 API
                return positionData;
            },
            
            /**
             * تحويل البيانات إلى نطاق CKEditor
             */
            convertToRange: function(rangeData) {
                // هذا مثال مبسط، يحتاج إلى تكامل مع CKEditor 5 API
                return rangeData;
            },
            
            /**
             * قفل المستند
             */
            lockDocument: function() {
                if (this.state.connected) {
                    this.state.socket.emit('lock-document', {
                        documentId: this.state.documentId,
                        token: this.state.token
                    });
                }
            },
            
            /**
             * فتح المستند
             */
            unlockDocument: function() {
                if (this.state.connected) {
                    this.state.socket.emit('unlock-document', {
                        documentId: this.state.documentId,
                        token: this.state.token
                    });
                }
            }
        }
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        WPOfficeEditor.init();
        
        // تهيئة نظام الذكاء الاصطناعي
        if (typeof WPOfficeEditor !== 'undefined') {
            if (WPOfficeEditor.initAI) {
                WPOfficeEditor.initAI();
            }
        }
        
        // تهيئة نظام التعاون إذا كان مفعلاً
        if (typeof wpoe_collaboration !== 'undefined' && wpoe_collaboration) {
            const urlParams = new URLSearchParams(window.location.search);
            const documentId = urlParams.get('document') || 0;
            
            if (documentId) {
                // بدء نظام التعاون
                if (typeof WPOECollaboration !== 'undefined') {
                    WPOECollaboration.init(documentId);
                }
                
                // إضافة زر تبديل التعاون
                addCollaborationToggle();
            }
        }
        
        // إضافة زر الذكاء الاصطناعي والتعاون إلى الواجهة
        addCollaborationAndAIButtons();
    });
    
    // Make available globally
    window.WPOfficeEditor = WPOfficeEditor;
    
})(jQuery);

// إضافة أزرار التعاون والذكاء الاصطناعي
function addCollaborationAndAIButtons() {
    // التحقق مما إذا كانت أزرار التعاون موجودة بالفعل
    if ($('#wpoe-collaboration-toggle').length > 0) {
        return;
    }
    
    // زر التعاون
    const collaborationToggle = `
        <button type="button" class="wpoe-collaboration-toggle" id="wpoe-collaboration-toggle" title="Toggle Collaboration Panel">
            <i class="fas fa-users"></i>
        </button>
    `;
    
    // زر الذكاء الاصطناعي (مضاف سابقاً)
    const aiToggle = `
        <button type="button" class="wpoe-ai-toggle" id="wpoe-ai-toggle" title="Toggle AI Panel">
            <i class="fas fa-robot"></i>
        </button>
    `;
    
    $('body').append(collaborationToggle + aiToggle);
    
    // إضافة مستمعي الأحداث
    $('#wpoe-collaboration-toggle').on('click', function() {
        toggleCollaborationPanel();
    });
    
    $('#wpoe-ai-toggle').on('click', function() {
        toggleAIPanel();
    });
}

// تبديل لوحة التعاون
function toggleCollaborationPanel() {
    const $panel = $('.wpoe-collaborators-panel');
    const $toggle = $('#wpoe-collaboration-toggle');
    
    if ($panel.length === 0) {
        createCollaborationPanel();
        return;
    }
    
    if ($panel.hasClass('active')) {
        $panel.removeClass('active');
        $toggle.removeClass('active');
    } else {
        $panel.addClass('active');
        $toggle.addClass('active');
    }
}

// إنشاء لوحة التعاون
function createCollaborationPanel() {
    const panelHTML = `
        <div class="wpoe-collaborators-panel active">
            <div class="wpoe-collaborators-header">
                <h3><i class="fas fa-users"></i> Collaborators</h3>
                <button type="button" class="wpoe-collaborators-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="wpoe-collaborators-body">
                <div class="wpoe-collaboration-loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading collaborators...
                </div>
                <div class="wpoe-collaborators-list" id="wpoe-collaborators-list"></div>
            </div>
            <div class="wpoe-collaborators-footer">
                <div class="wpoe-collaboration-stats">
                    <div class="wpoe-stat-item">
                        <span class="wpoe-stat-value" id="wpoe-collaborators-count">0</span>
                        <span class="wpoe-stat-label">Online</span>
                    </div>
                    <div class="wpoe-stat-item">
                        <span class="wpoe-stat-value" id="wpoe-connection-status">Offline</span>
                        <span class="wpoe-stat-label">Status</span>
                    </div>
                </div>
                <button type="button" class="button button-primary button-small" id="wpoe-invite-collaborator" style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-user-plus"></i> Invite Collaborator
                </button>
            </div>
        </div>
    `;
    
    $('body').append(panelHTML);
    
    // إضافة مستمعي الأحداث
    $('.wpoe-collaborators-close').on('click', function() {
        $('.wpoe-collaborators-panel').removeClass('active');
        $('#wpoe-collaboration-toggle').removeClass('active');
    });
    
    $('#wpoe-invite-collaborator').on('click', function() {
        if (typeof WPOECollaboration !== 'undefined') {
            WPOECollaboration.openInviteModal();
        }
    });
}

// تبديل لوحة الذكاء الاصطناعي (مضاف سابقاً)
function toggleAIPanel() {
    if (typeof WPOfficeEditor !== 'undefined' && WPOfficeEditor.toggleAIPanel) {
        WPOfficeEditor.toggleAIPanel();
    }
}
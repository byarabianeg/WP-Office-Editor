/**
 * WP Office Editor - Real-time Collaboration
 * Version: 2.0.0
 */

(function($) {
    'use strict';
    
    const WPOECollaboration = {
        // حالة التعاون
        state: {
            socket: null,
            connected: false,
            documentId: null,
            roomId: null,
            collaborators: new Map(),
            cursorPositions: new Map(),
            selectionRanges: new Map(),
            isLocked: false,
            lockedBy: null,
            lastUpdate: null,
            updateQueue: [],
            isProcessingQueue: false
        },
        
        // التهيئة
        init: function(documentId) {
            this.state.documentId = documentId;
            this.state.roomId = 'document_' + documentId;
            
            this.connectToServer();
            this.setupEventListeners();
            this.loadCollaborators();
            this.startHeartbeat();
            
            console.log('Collaboration initialized for document:', documentId);
        },
        
        // الاتصال بخادم Socket.IO
        connectToServer: function() {
            if (!wpoe_collaboration || !wpoe_collaboration.server_url) {
                console.error('Collaboration server URL not configured');
                return;
            }
            
            try {
                // بناء عنوان الخادم
                const serverUrl = wpoe_collaboration.server_url;
                const serverPort = wpoe_collaboration.server_port || 3000;
                const serverFullUrl = `http://${serverUrl}:${serverPort}`;
                
                // الاتصال بالخادم
                this.state.socket = io(serverFullUrl, {
                    transports: ['websocket', 'polling'],
                    reconnection: true,
                    reconnectionAttempts: 5,
                    reconnectionDelay: 1000
                });
                
                // مستمعي الأحداث
                this.setupSocketListeners();
                
            } catch (error) {
                console.error('Error connecting to collaboration server:', error);
            }
        },
        
        // إعداد مستمعي Socket.IO
        setupSocketListeners: function() {
            const socket = this.state.socket;
            
            socket.on('connect', () => {
                console.log('Connected to collaboration server');
                this.state.connected = true;
                this.joinDocumentRoom();
                this.emitUserPresence();
            });
            
            socket.on('disconnect', (reason) => {
                console.log('Disconnected from collaboration server:', reason);
                this.state.connected = false;
                this.showConnectionStatus('disconnected');
            });
            
            socket.on('connect_error', (error) => {
                console.error('Connection error:', error);
                this.showConnectionStatus('error');
            });
            
            socket.on('reconnect', (attemptNumber) => {
                console.log('Reconnected after', attemptNumber, 'attempts');
                this.state.connected = true;
                this.joinDocumentRoom();
                this.showConnectionStatus('connected');
            });
            
            socket.on('user-joined', (data) => {
                this.handleUserJoined(data);
            });
            
            socket.on('user-left', (data) => {
                this.handleUserLeft(data);
            });
            
            socket.on('document-update', (data) => {
                this.handleDocumentUpdate(data);
            });
            
            socket.on('cursor-update', (data) => {
                this.handleCursorUpdate(data);
            });
            
            socket.on('selection-update', (data) => {
                this.handleSelectionUpdate(data);
            });
            
            socket.on('document-locked', (data) => {
                this.handleDocumentLocked(data);
            });
            
            socket.on('document-unlocked', (data) => {
                this.handleDocumentUnlocked(data);
            });
            
            socket.on('chat-message', (data) => {
                this.handleChatMessage(data);
            });
            
            socket.on('collaboration-history', (data) => {
                this.handleCollaborationHistory(data);
            });
        },
        
        // الانضمام إلى غرفة المستند
        joinDocumentRoom: function() {
            if (!this.state.connected || !this.state.documentId) {
                return;
            }
            
            const userData = wpoe_collaboration.user;
            const roomData = {
                documentId: this.state.documentId,
                roomId: this.state.roomId,
                user: userData,
                timestamp: Date.now()
            };
            
            this.state.socket.emit('join-document', roomData);
        },
        
        // إرسال حضور المستخدم
        emitUserPresence: function() {
            setInterval(() => {
                if (this.state.connected) {
                    this.state.socket.emit('user-presence', {
                        userId: wpoe_collaboration.user.id,
                        documentId: this.state.documentId
                    });
                }
            }, 30000); // كل 30 ثانية
        },
        
        // إعداد مستمعي الأحداث
        setupEventListeners: function() {
            // تحديث المحتوى عند التغيير
            if (window.WPOfficeEditor && WPOfficeEditor.editor) {
                WPOfficeEditor.editor.model.document.on('change:data', () => {
                    this.onEditorChange();
                });
            }
            
            // تحديث موضع المؤشر
            $(document).on('mousemove keyup', () => {
                this.updateCursorPosition();
            });
            
            // تحديث النطاق المحدد
            $(document).on('mouseup keyup', () => {
                this.updateSelectionRange();
            });
            
            // زر إغلاق التعاون
            $('#wpoe-collaboration-close').on('click', () => {
                this.leaveCollaboration();
            });
            
            // زر تحديث قائمة المتعاونين
            $('#wpoe-collaboration-refresh').on('click', () => {
                this.loadCollaborators();
            });
            
            // زر دعوة متعاون
            $('#wpoe-invite-collaborator').on('click', () => {
                this.openInviteModal();
            });
            
            // زر قفل/فتح المستند
            $('#wpoe-toggle-lock').on('click', () => {
                this.toggleDocumentLock();
            });
            
            // زر إرسال رسالة دردشة
            $('#wpoe-chat-send').on('click', () => {
                this.sendChatMessage();
            });
            
            $('#wpoe-chat-input').on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendChatMessage();
                }
            });
        },
        
        // تحميل قائمة المتعاونين
        loadCollaborators: function() {
            $.ajax({
                url: wpoe_collaboration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_get_collaborators',
                    nonce: wpoe_collaboration.nonce,
                    document_id: this.state.documentId
                },
                dataType: 'json'
            })
            .done(response => {
                if (response.success) {
                    this.updateCollaboratorsList(response.data.collaborators);
                }
            })
            .fail(error => {
                console.error('Error loading collaborators:', error);
            });
        },
        
        // تحديث قائمة المتعاونين
        updateCollaboratorsList: function(collaborators) {
            const $list = $('#wpoe-collaborators-list');
            $list.empty();
            
            collaborators.forEach(collaborator => {
                const isCurrentUser = collaborator.id === wpoe_collaboration.user.id;
                const isOnline = collaborator.is_online;
                
                const collaboratorHTML = `
                    <div class="wpoe-collaborator-item ${isCurrentUser ? 'current-user' : ''}" data-user-id="${collaborator.id}">
                        <div class="wpoe-collaborator-avatar">
                            <img src="${collaborator.avatar || 'https://i.pravatar.cc/32'}" alt="${collaborator.name}">
                            <span class="wpoe-collaborator-status ${isOnline ? 'online' : 'offline'}"></span>
                        </div>
                        <div class="wpoe-collaborator-info">
                            <div class="wpoe-collaborator-name">
                                ${collaborator.name}
                                ${isCurrentUser ? '<span class="wpoe-you-badge">(You)</span>' : ''}
                            </div>
                            <div class="wpoe-collaborator-role">
                                ${collaborator.role || 'Editor'}
                            </div>
                            <div class="wpoe-collaborator-meta">
                                ${collaborator.last_active ? 'Last active: ' + this.formatTimeAgo(collaborator.last_active) : ''}
                            </div>
                        </div>
                        ${!isCurrentUser ? `
                        <div class="wpoe-collaborator-actions">
                            <button type="button" class="wpoe-collaborator-remove" title="Remove collaborator">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                $list.append(collaboratorHTML);
            });
            
            // تحديث العدد
            $('#wpoe-collaborators-count').text(collaborators.length);
            
            // إضافة مستمعي الأحداث لأزرار الإزالة
            $('.wpoe-collaborator-remove').on('click', (e) => {
                const userId = $(e.currentTarget).closest('.wpoe-collaborator-item').data('user-id');
                this.removeCollaborator(userId);
            });
        },
        
        // تنسيق الوقت المنقضي
        formatTimeAgo: function(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const seconds = Math.floor((now - time) / 1000);
            
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            return Math.floor(seconds / 86400) + ' days ago';
        },
        
        // التعامل مع انضمام مستخدم جديد
        handleUserJoined: function(data) {
            const userId = data.user.id;
            
            if (!this.state.collaborators.has(userId)) {
                this.state.collaborators.set(userId, data.user);
                
                // إضافة المستخدم إلى القائمة
                this.addCollaboratorToList(data.user);
                
                // إظهار إشعار
                this.showNotification(`${data.user.name} has joined the document`);
                
                // تحديث العدد
                this.updateCollaboratorsCount();
            }
        },
        
        // التعامل مع مغادرة مستخدم
        handleUserLeft: function(data) {
            const userId = data.userId;
            
            if (this.state.collaborators.has(userId)) {
                this.state.collaborators.delete(userId);
                
                // إزالة المستخدم من القائمة
                $(`[data-user-id="${userId}"]`).remove();
                
                // إظهار إشعار
                this.showNotification(`${data.userName || 'A user'} has left the document`);
                
                // تحديث العدد
                this.updateCollaboratorsCount();
                
                // إزالة مؤشر المستخدم
                this.removeUserCursor(userId);
            }
        },
        
        // إضافة متعاون إلى القائمة
        addCollaboratorToList: function(user) {
            const isCurrentUser = user.id === wpoe_collaboration.user.id;
            
            if (isCurrentUser) return; // لا تضيف المستخدم الحالي
            
            const $list = $('#wpoe-collaborators-list');
            
            const collaboratorHTML = `
                <div class="wpoe-collaborator-item" data-user-id="${user.id}">
                    <div class="wpoe-collaborator-avatar">
                        <img src="${user.avatar || 'https://i.pravatar.cc/32'}" alt="${user.name}">
                        <span class="wpoe-collaborator-status online"></span>
                    </div>
                    <div class="wpoe-collaborator-info">
                        <div class="wpoe-collaborator-name">${user.name}</div>
                        <div class="wpoe-collaborator-role">Collaborator</div>
                    </div>
                    <div class="wpoe-collaborator-actions">
                        <button type="button" class="wpoe-collaborator-remove" title="Remove collaborator">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            $list.append(collaboratorHTML);
        },
        
        // تحديث عدد المتعاونين
        updateCollaboratorsCount: function() {
            const count = this.state.collaborators.size + 1; // +1 للمستخدم الحالي
            $('#wpoe-collaborators-count').text(count);
        },
        
        // التعامل مع تحديث المستند
        handleDocumentUpdate: function(data) {
            // تجاهل التحديثات من المستخدم الحالي
            if (data.userId === wpoe_collaboration.user.id) {
                return;
            }
            
            // تحديث المحرر بالمحتوى الجديد
            if (window.WPOfficeEditor && WPOfficeEditor.editor) {
                WPOfficeEditor.editor.setData(data.content);
            }
            
            // إظهار إشعار
            this.showNotification(`${data.userName} made changes`);
            
            // تحديث وقت التعديل الأخير
            this.state.lastUpdate = Date.now();
        },
        
        // عند تغيير المحرر
        onEditorChange: function() {
            if (!this.state.connected || !window.WPOfficeEditor || !WPOfficeEditor.editor) {
                return;
            }
            
            const content = WPOfficeEditor.editor.getData();
            const changeData = {
                documentId: this.state.documentId,
                userId: wpoe_collaboration.user.id,
                userName: wpoe_collaboration.user.name,
                content: content,
                timestamp: Date.now()
            };
            
            // إرسال التحديث إلى الخادم
            this.state.socket.emit('document-update', changeData);
            
            // تحديث وقت التعديل الأخير
            this.state.lastUpdate = Date.now();
        },
        
        // تحديث موضع المؤشر
        updateCursorPosition: function() {
            if (!this.state.connected || !window.WPOfficeEditor || !WPOfficeEditor.editor) {
                return;
            }
            
            const editor = WPOfficeEditor.editor;
            const selection = editor.model.document.selection;
            
            if (selection && selection.rangeCount > 0) {
                const range = selection.getFirstRange();
                const position = range.start.path;
                
                const cursorData = {
                    documentId: this.state.documentId,
                    userId: wpoe_collaboration.user.id,
                    position: position,
                    timestamp: Date.now()
                };
                
                // إرسال تحديث المؤشر
                this.state.socket.emit('cursor-update', cursorData);
            }
        },
        
        // تحديث النطاق المحدد
        updateSelectionRange: function() {
            if (!this.state.connected || !window.WPOfficeEditor || !WPOfficeEditor.editor) {
                return;
            }
            
            const editor = WPOfficeEditor.editor;
            const selection = editor.model.document.selection;
            
            if (selection && selection.rangeCount > 0) {
                const range = selection.getFirstRange();
                const selectionData = {
                    documentId: this.state.documentId,
                    userId: wpoe_collaboration.user.id,
                    range: {
                        start: range.start.path,
                        end: range.end.path
                    },
                    timestamp: Date.now()
                };
                
                // إرسال تحديث النطاق المحدد
                this.state.socket.emit('selection-update', selectionData);
            }
        },
        
        // التعامل مع تحديث المؤشر
        handleCursorUpdate: function(data) {
            // تجاهل تحديثات المؤشر من المستخدم الحالي
            if (data.userId === wpoe_collaboration.user.id) {
                return;
            }
            
            // تخزين موضع المؤشر
            this.state.cursorPositions.set(data.userId, {
                position: data.position,
                timestamp: data.timestamp,
                user: this.state.collaborators.get(data.userId)
            });
            
            // عرض مؤشر المستخدم
            this.displayUserCursor(data.userId, data.position);
        },
        
        // التعامل مع تحديث النطاق المحدد
        handleSelectionUpdate: function(data) {
            if (data.userId === wpoe_collaboration.user.id) {
                return;
            }
            
            // تخزين النطاق المحدد
            this.state.selectionRanges.set(data.userId, {
                range: data.range,
                timestamp: data.timestamp,
                user: this.state.collaborators.get(data.userId)
            });
            
            // عرض النطاق المحدد للمستخدم
            this.displayUserSelection(data.userId, data.range);
        },
        
        // عرض مؤشر المستخدم
        displayUserCursor: function(userId, position) {
            // إزالة المؤشر القديم إذا كان موجوداً
            this.removeUserCursor(userId);
            
            // إنشاء عنصر المؤشر الجديد
            const user = this.state.collaborators.get(userId);
            if (!user) return;
            
            const cursorHTML = `
                <div class="wpoe-remote-cursor" id="wpoe-cursor-${userId}" data-user-id="${userId}">
                    <div class="wpoe-cursor-line" style="border-left-color: ${user.color};"></div>
                    <div class="wpoe-cursor-tooltip" style="background: ${user.color};">
                        <span class="wpoe-cursor-name">${user.name}</span>
                    </div>
                </div>
            `;
            
            // إضافة المؤشر إلى المحرر
            $('.ck-editor__editable').append(cursorHTML);
            
            // وضع المؤشر في الموضع الصحيح
            this.positionUserCursor(userId, position);
            
            // إزالة المؤشر بعد 5 ثواني من عدم النشاط
            setTimeout(() => {
                this.removeUserCursor(userId);
            }, 5000);
        },
        
        // وضع المؤشر في الموضع الصحيح
        positionUserCursor: function(userId, position) {
            const $cursor = $(`#wpoe-cursor-${userId}`);
            const $editor = $('.ck-editor__editable');
            
            // حساب الموضع بناءً على المسار
            // هذا يتطلب تنفيذ أكثر تعقيداً لتحويل المسار إلى إحداثيات واجهة المستخدم
            // هذا مثال مبسط
            
            const lineHeight = 24; // ارتفاع السطر الافتراضي
            const estimatedPosition = position[0] * lineHeight;
            
            $cursor.css({
                top: estimatedPosition + 'px',
                left: '20px'
            });
        },
        
        // إزالة مؤشر المستخدم
        removeUserCursor: function(userId) {
            $(`#wpoe-cursor-${userId}`).remove();
        },
        
        // عرض النطاق المحدد للمستخدم
        displayUserSelection: function(userId, range) {
            // تنفيذ عرض النطاق المحدد
            // هذا يتطلب تنفيذ معقد لتمييز النص المحدد
        },
        
        // التعامل مع قفل المستند
        handleDocumentLocked: function(data) {
            this.state.isLocked = true;
            this.state.lockedBy = data.userId;
            
            // تعطيل المحرر إذا كان المقفل ليس المستخدم الحالي
            if (data.userId !== wpoe_collaboration.user.id) {
                this.disableEditor();
                this.showLockNotification(data.userName);
            }
            
            // تحديث واجهة المستخدم
            this.updateLockUI();
        },
        
        // التعامل مع فتح قفل المستند
        handleDocumentUnlocked: function(data) {
            this.state.isLocked = false;
            this.state.lockedBy = null;
            
            // تمكين المحرر
            this.enableEditor();
            
            // تحديث واجهة المستخدم
            this.updateLockUI();
        },
        
        // قفل/فتح المستند
        toggleDocumentLock: function() {
            if (this.state.isLocked && this.state.lockedBy !== wpoe_collaboration.user.id) {
                this.showNotification('Document is locked by another user');
                return;
            }
            
            const action = this.state.isLocked ? 'unlock' : 'lock';
            
            $.ajax({
                url: wpoe_collaboration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_toggle_document_lock',
                    nonce: wpoe_collaboration.nonce,
                    document_id: this.state.documentId,
                    lock_action: action
                },
                dataType: 'json'
            })
            .done(response => {
                if (response.success) {
                    if (action === 'lock') {
                        this.state.isLocked = true;
                        this.state.lockedBy = wpoe_collaboration.user.id;
                        this.showNotification('Document locked');
                    } else {
                        this.state.isLocked = false;
                        this.state.lockedBy = null;
                        this.showNotification('Document unlocked');
                    }
                    
                    this.updateLockUI();
                }
            })
            .fail(error => {
                console.error('Error toggling document lock:', error);
            });
        },
        
        // تعطيل المحرر
        disableEditor: function() {
            if (window.WPOfficeEditor && WPOfficeEditor.editor) {
                WPOfficeEditor.editor.isReadOnly = true;
                $('.ck-editor__editable').addClass('read-only');
            }
        },
        
        // تمكين المحرر
        enableEditor: function() {
            if (window.WPOfficeEditor && WPOfficeEditor.editor) {
                WPOfficeEditor.editor.isReadOnly = false;
                $('.ck-editor__editable').removeClass('read-only');
            }
        },
        
        // إظهار إشعار القفل
        showLockNotification: function(userName) {
            const notificationHTML = `
                <div class="wpoe-lock-notification">
                    <i class="fas fa-lock"></i>
                    <span>Document is locked by ${userName}</span>
                    <button type="button" class="wpoe-notification-close">&times;</button>
                </div>
            `;
            
            $('.wpoe-editor-container').prepend(notificationHTML);
            
            $('.wpoe-notification-close').on('click', function() {
                $(this).closest('.wpoe-lock-notification').remove();
            });
        },
        
        // تحديث واجهة قفل المستند
        updateLockUI: function() {
            const $lockButton = $('#wpoe-toggle-lock');
            const $lockIcon = $lockButton.find('i');
            const $lockText = $lockButton.find('span');
            
            if (this.state.isLocked) {
                $lockButton.addClass('locked');
                $lockIcon.removeClass('fa-unlock').addClass('fa-lock');
                $lockText.text('Unlock Document');
                
                if (this.state.lockedBy === wpoe_collaboration.user.id) {
                    $lockButton.prop('disabled', false);
                    $lockButton.attr('title', 'You have locked this document');
                } else {
                    $lockButton.prop('disabled', true);
                    $lockButton.attr('title', 'Locked by another user');
                }
            } else {
                $lockButton.removeClass('locked');
                $lockIcon.removeClass('fa-lock').addClass('fa-unlock');
                $lockText.text('Lock Document');
                $lockButton.prop('disabled', false);
                $lockButton.attr('title', 'Lock document to prevent edits');
            }
        },
        
        // فتح نافذة دعوة المتعاونين
        openInviteModal: function() {
            const modalHTML = `
                <div class="wpoe-modal active" id="wpoe-invite-modal">
                    <div class="wpoe-modal-content">
                        <div class="wpoe-modal-header">
                            <h3><i class="fas fa-user-plus"></i> Invite Collaborator</h3>
                            <button type="button" class="wpoe-modal-close">&times;</button>
                        </div>
                        <div class="wpoe-modal-body">
                            <div class="wpoe-invite-form">
                                <div class="form-field">
                                    <label for="wpoe-invite-email">Email Address</label>
                                    <input type="email" id="wpoe-invite-email" placeholder="Enter email address">
                                </div>
                                <div class="form-field">
                                    <label for="wpoe-invite-role">Role</label>
                                    <select id="wpoe-invite-role">
                                        <option value="editor">Editor (Can edit)</option>
                                        <option value="viewer">Viewer (Read only)</option>
                                        <option value="commenter">Commenter (Can comment)</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="wpoe-invite-message">Message (Optional)</label>
                                    <textarea id="wpoe-invite-message" rows="3" placeholder="Add a personal message..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="wpoe-modal-footer">
                            <button type="button" class="button button-secondary" id="wpoe-invite-cancel">Cancel</button>
                            <button type="button" class="button button-primary" id="wpoe-invite-send">
                                <i class="fas fa-paper-plane"></i> Send Invitation
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            
            // إضافة مستمعي الأحداث
            $('#wpoe-invite-cancel, #wpoe-invite-modal .wpoe-modal-close').on('click', () => {
                $('#wpoe-invite-modal').remove();
            });
            
            $('#wpoe-invite-send').on('click', () => {
                this.sendInvitation();
            });
        },
        
        // إرسال دعوة
        sendInvitation: function() {
            const email = $('#wpoe-invite-email').val();
            const role = $('#wpoe-invite-role').val();
            const message = $('#wpoe-invite-message').val();
            
            if (!email) {
                this.showNotification('Please enter an email address', 'error');
                return;
            }
            
            $.ajax({
                url: wpoe_collaboration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_invite_collaborator',
                    nonce: wpoe_collaboration.nonce,
                    document_id: this.state.documentId,
                    email: email,
                    role: role,
                    message: message
                },
                dataType: 'json'
            })
            .done(response => {
                if (response.success) {
                    this.showNotification('Invitation sent successfully');
                    $('#wpoe-invite-modal').remove();
                } else {
                    this.showNotification(response.data.message || 'Error sending invitation', 'error');
                }
            })
            .fail(error => {
                console.error('Error sending invitation:', error);
                this.showNotification('Error sending invitation', 'error');
            });
        },
        
        // إزالة متعاون
        removeCollaborator: function(userId) {
            if (!confirm('Are you sure you want to remove this collaborator?')) {
                return;
            }
            
            $.ajax({
                url: wpoe_collaboration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpoe_remove_collaborator',
                    nonce: wpoe_collaboration.nonce,
                    document_id: this.state.documentId,
                    user_id: userId
                },
                dataType: 'json'
            })
            .done(response => {
                if (response.success) {
                    this.showNotification('Collaborator removed');
                    
                    // إزالة من القائمة
                    $(`[data-user-id="${userId}"]`).remove();
                    
                    // تحديث العدد
                    this.updateCollaboratorsCount();
                }
            })
            .fail(error => {
                console.error('Error removing collaborator:', error);
                this.showNotification('Error removing collaborator', 'error');
            });
        },
        
        // التعامل مع رسائل الدردشة
        handleChatMessage: function(data) {
            this.addChatMessage(data);
        },
        
        // إرسال رسالة دردشة
        sendChatMessage: function() {
            const $input = $('#wpoe-chat-input');
            const message = $input.val().trim();
            
            if (!message) {
                return;
            }
            
            const chatData = {
                documentId: this.state.documentId,
                userId: wpoe_collaboration.user.id,
                userName: wpoe_collaboration.user.name,
                message: message,
                timestamp: Date.now()
            };
            
            // إرسال إلى الخادم
            this.state.socket.emit('chat-message', chatData);
            
            // إضافة الرسالة إلى الدردشة
            this.addChatMessage(chatData);
            
            // مسح حقل الإدخال
            $input.val('');
        },
        
        // إضافة رسالة دردشة
        addChatMessage: function(data) {
            const $chatMessages = $('#wpoe-chat-messages');
            const isCurrentUser = data.userId === wpoe_collaboration.user.id;
            
            const messageHTML = `
                <div class="wpoe-chat-message ${isCurrentUser ? 'current-user' : ''}">
                    <div class="wpoe-chat-avatar">
                        <img src="${data.userAvatar || 'https://i.pravatar.cc/32'}" alt="${data.userName}">
                    </div>
                    <div class="wpoe-chat-content">
                        <div class="wpoe-chat-header">
                            <span class="wpoe-chat-name">${data.userName}</span>
                            <span class="wpoe-chat-time">${this.formatChatTime(data.timestamp)}</span>
                        </div>
                        <div class="wpoe-chat-text">${this.escapeHtml(data.message)}</div>
                    </div>
                </div>
            `;
            
            $chatMessages.append(messageHTML);
            
            // التمرير إلى الأسفل
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
        },
        
        // تنسيق وقت الدردشة
        formatChatTime: function(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        
        // تهريب HTML
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // التعامل مع تاريخ التعاون
        handleCollaborationHistory: function(data) {
            // عرض تاريخ التغييرات
            console.log('Collaboration history:', data);
        },
        
        // مغادرة التعاون
        leaveCollaboration: function() {
            if (this.state.connected && this.state.socket) {
                this.state.socket.emit('leave-document', {
                    documentId: this.state.documentId,
                    userId: wpoe_collaboration.user.id
                });
                
                this.state.socket.disconnect();
            }
            
            this.showNotification('You have left the collaboration session');
            
            // إعادة التوجيه إلى صفحة المستندات
            setTimeout(() => {
                window.location.href = admin_url + 'admin.php?page=wpoe-documents';
            }, 1000);
        },
        
        // إظهار حالة الاتصال
        showConnectionStatus: function(status) {
            const $status = $('#wpoe-connection-status');
            
            $status.removeClass('connected disconnected error');
            $status.addClass(status);
            
            let statusText = '';
            let statusIcon = '';
            
            switch (status) {
                case 'connected':
                    statusText = 'Connected';
                    statusIcon = 'fa-check-circle';
                    break;
                case 'disconnected':
                    statusText = 'Disconnected';
                    statusIcon = 'fa-times-circle';
                    break;
                case 'error':
                    statusText = 'Connection Error';
                    statusIcon = 'fa-exclamation-circle';
                    break;
            }
            
            $status.html(`<i class="fas ${statusIcon}"></i> <span>${statusText}</span>`);
        },
        
        // إظهار إشعار
        showNotification: function(message, type = 'info') {
            const notificationId = 'notification-' + Date.now();
            
            const notificationHTML = `
                <div class="wpoe-notification wpoe-notification-${type}" id="${notificationId}">
                    <div class="wpoe-notification-content">
                        <span>${message}</span>
                        <button type="button" class="wpoe-notification-close">&times;</button>
                    </div>
                </div>
            `;
            
            $('body').append(notificationHTML);
            
            // إظهار الإشعار
            setTimeout(() => {
                $('#' + notificationId).addClass('show');
            }, 10);
            
            // إزالة الإشعار بعد 5 ثواني
            setTimeout(() => {
                $('#' + notificationId).removeClass('show');
                setTimeout(() => {
                    $('#' + notificationId).remove();
                }, 300);
            }, 5000);
            
            // زر الإغلاق
            $('#' + notificationId + ' .wpoe-notification-close').on('click', function() {
                $(this).closest('.wpoe-notification').remove();
            });
        },
        
        // بدء نبضات القلب للحفاظ على الاتصال
        startHeartbeat: function() {
            setInterval(() => {
                if (this.state.connected) {
                    this.state.socket.emit('heartbeat', {
                        userId: wpoe_collaboration.user.id,
                        documentId: this.state.documentId
                    });
                }
            }, 30000); // كل 30 ثانية
        },
        
        // الحصول على إحصائيات التعاون
        getCollaborationStats: function() {
            return {
                connected: this.state.connected,
                collaborators: this.state.collaborators.size,
                isLocked: this.state.isLocked,
                lockedBy: this.state.lockedBy,
                lastUpdate: this.state.lastUpdate
            };
        }
    };
    
    // جعل نظام التعاون متاحاً عالمياً
    window.WPOECollaboration = WPOECollaboration;
    
    // التهيئة عند تحميل الصفحة
    $(document).ready(function() {
        // التحقق مما إذا كنا في صفحة المحرر
        if (window.location.href.indexOf('page=wp-office-editor') !== -1) {
            const urlParams = new URLSearchParams(window.location.search);
            const documentId = urlParams.get('document') || 0;
            
            if (documentId && wpoe_collaboration) {
                WPOECollaboration.init(documentId);
            }
        }
    });
    
})(jQuery);
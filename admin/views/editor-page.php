<?php
// التحقق من الصلاحيات
if (!current_user_can('edit_posts')) {
    wp_die(__('You do not have permission to access this page.', 'wp-office-editor'));
}

// بيانات الجلسة
$document_id = isset($_GET['document']) ? intval($_GET['document']) : 0;
$is_new = empty($document_id);
$document_title = $is_new ? __('New Document', 'wp-office-editor') : get_the_title($document_id);
$share_token = $document_id ? get_post_meta($document_id, '_wpoe_share_token', true) : '';

// تحميل إعدادات المستخدم
$user_settings = get_user_meta(get_current_user_id(), 'wpoe_user_settings', true);
if (empty($user_settings)) {
    $user_settings = [
        'theme' => 'light',
        'font_size' => '16px',
        'font_family' => 'default',
        'show_ai_panel' => true
    ];
}
?>

<div class="wrap wpoe-editor-wrap" data-theme="<?php echo esc_attr($user_settings['theme']); ?>">
    
    <!-- شريط العنوان -->
    <div class="wpoe-editor-header">
        <h1 class="wp-heading-inline">
            <i class="fas fa-file-word"></i>
            <?php echo esc_html($document_title); ?>
        </h1>
        
        <div class="wpoe-header-actions">
            <button type="button" class="button button-secondary" id="wpoe-new-document" title="<?php esc_attr_e('New Document', 'wp-office-editor'); ?>">
                <i class="fas fa-plus"></i> <span class="action-text"><?php _e('New', 'wp-office-editor'); ?></span>
            </button>
            
            <button type="button" class="button button-secondary" id="wpoe-open-document" title="<?php esc_attr_e('Open Document', 'wp-office-editor'); ?>">
                <i class="fas fa-folder-open"></i> <span class="action-text"><?php _e('Open', 'wp-office-editor'); ?></span>
            </button>
            
            <div class="wpoe-document-info">
                <span id="wpoe-auto-save-status" class="wpoe-status-saved">
                    <i class="fas fa-check-circle"></i> <span class="status-text"><?php _e('Saved', 'wp-office-editor'); ?></span>
                </span>
                
                <?php if (!$is_new): ?>
                <span class="wpoe-document-id" title="<?php esc_attr_e('Document ID', 'wp-office-editor'); ?>">
                    #<?php echo esc_html($document_id); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($share_token): ?>
                <span class="wpoe-shared-badge" title="<?php esc_attr_e('This document is shared', 'wp-office-editor'); ?>">
                    <i class="fas fa-share-alt"></i> <?php _e('Shared', 'wp-office-editor'); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- منطقة الألسنة -->
    <div class="wpoe-tabs-container">
        <div class="wpoe-tabs-scroll" id="wpoe-tabs-scroll">
            <!-- الألسنة ستضاف هنا ديناميكياً -->
        </div>
        
        <div class="wpoe-tabs-actions">
            <button type="button" class="wpoe-new-tab-btn" id="wpoe-new-tab-btn" title="<?php esc_attr_e('New Tab', 'wp-office-editor'); ?>">
                <i class="fas fa-plus"></i>
                <span class="action-text"><?php _e('New Tab', 'wp-office-editor'); ?></span>
            </button>
            
            <button type="button" class="wpoe-tab-action-btn" id="wpoe-tab-list-btn" title="<?php esc_attr_e('Tab List', 'wp-office-editor'); ?>">
                <i class="fas fa-list"></i>
            </button>
            
            <div class="wpoe-tabs-overflow" id="wpoe-tabs-overflow">
                <button type="button" class="wpoe-tab-action-btn" title="<?php esc_attr_e('More Actions', 'wp-office-editor'); ?>">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
                
                <div class="wpoe-tabs-overflow-menu">
                    <div class="wpoe-tab-menu-item" data-action="duplicate_current">
                        <i class="fas fa-copy"></i> <?php _e('Duplicate Current', 'wp-office-editor'); ?>
                    </div>
                    <div class="wpoe-tab-menu-item" data-action="close_other_tabs">
                        <i class="fas fa-times-circle"></i> <?php _e('Close Other Tabs', 'wp-office-editor'); ?>
                    </div>
                    <div class="wpoe-tab-menu-item" data-action="close_all_tabs">
                        <i class="fas fa-window-close"></i> <?php _e('Close All Tabs', 'wp-office-editor'); ?>
                    </div>
                    <div class="wpoe-tab-menu-separator"></div>
                    <div class="wpoe-tab-menu-item" data-action="pin_all_tabs">
                        <i class="fas fa-thumbtack"></i> <?php _e('Pin All Tabs', 'wp-office-editor'); ?>
                    </div>
                    <div class="wpoe-tab-menu-item" data-action="unpin_all_tabs">
                        <i class="fas fa-thumbtack"></i> <?php _e('Unpin All Tabs', 'wp-office-editor'); ?>
                    </div>
                    <div class="wpoe-tab-menu-separator"></div>
                    <div class="wpoe-tab-menu-item" data-action="export_all_tabs">
                        <i class="fas fa-download"></i> <?php _e('Export All Tabs', 'wp-office-editor'); ?>
                    </div>
                    <div class="wpoe-tab-menu-item" data-action="import_tabs">
                        <i class="fas fa-upload"></i> <?php _e('Import Tabs', 'wp-office-editor'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- شريط الأدوات الرئيسي -->
    <div class="wpoe-toolbar-main" id="wpoe-main-toolbar">
        <!-- File Group -->
        <div class="wpoe-toolbar-group">
            <button type="button" class="wpoe-toolbar-btn" id="wpoe-btn-save" title="<?php esc_attr_e('Save Document', 'wp-office-editor'); ?>">
                <i class="fas fa-save"></i> <span><?php _e('Save', 'wp-office-editor'); ?></span>
            </button>
            
            <button type="button" class="wpoe-toolbar-btn" id="wpoe-btn-save-as" title="<?php esc_attr_e('Save As Copy', 'wp-office-editor'); ?>">
                <i class="fas fa-copy"></i> <span><?php _e('Save As', 'wp-office-editor'); ?></span>
            </button>
            
            <div class="wpoe-dropdown">
                <button type="button" class="wpoe-toolbar-btn" title="<?php esc_attr_e('Export Document', 'wp-office-editor'); ?>">
                    <i class="fas fa-download"></i> <span><?php _e('Export', 'wp-office-editor'); ?></span>
                </button>
                <div class="wpoe-dropdown-content">
                    <?php
                    $export_formats = get_option('wpoe_settings', []);
                    $formats = isset($export_formats['export_formats']) ? $export_formats['export_formats'] : ['docx', 'pdf'];
                    
                    $format_labels = [
                        'docx' => ['icon' => 'fa-file-word', 'label' => __('Word (.docx)', 'wp-office-editor')],
                        'pdf' => ['icon' => 'fa-file-pdf', 'label' => __('PDF', 'wp-office-editor')],
                        'odt' => ['icon' => 'fa-file-alt', 'label' => __('OpenDocument (.odt)', 'wp-office-editor')],
                        'html' => ['icon' => 'fa-code', 'label' => __('HTML', 'wp-office-editor')]
                    ];
                    
                    foreach ($format_labels as $format => $info) {
                        if (in_array($format, $formats)) {
                            echo '<a href="#" class="wpoe-export-option" data-format="' . esc_attr($format) . '">';
                            echo '<i class="fas ' . esc_attr($info['icon']) . '"></i> ' . esc_html($info['label']);
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Edit Group -->
        <div class="wpoe-toolbar-group">
            <button type="button" class="wpoe-toolbar-btn" data-command="undo" title="<?php esc_attr_e('Undo', 'wp-office-editor'); ?>">
                <i class="fas fa-undo"></i>
            </button>
            
            <button type="button" class="wpoe-toolbar-btn" data-command="redo" title="<?php esc_attr_e('Redo', 'wp-office-editor'); ?>">
                <i class="fas fa-redo"></i>
            </button>
            
            <div class="wpoe-toolbar-separator"></div>
            
            <button type="button" class="wpoe-toolbar-btn" data-command="cut" title="<?php esc_attr_e('Cut', 'wp-office-editor'); ?>">
                <i class="fas fa-cut"></i>
            </button>
            
            <button type="button" class="wpoe-toolbar-btn" data-command="copy" title="<?php esc_attr_e('Copy', 'wp-office-editor'); ?>">
                <i class="fas fa-copy"></i>
            </button>
            
            <button type="button" class="wpoe-toolbar-btn" data-command="paste" title="<?php esc_attr_e('Paste', 'wp-office-editor'); ?>">
                <i class="fas fa-paste"></i>
            </button>
        </div>
        
        <!-- View Group -->
        <div class="wpoe-toolbar-group">
            <div class="wpoe-dropdown">
                <button type="button" class="wpoe-toolbar-btn" title="<?php esc_attr_e('View Options', 'wp-office-editor'); ?>">
                    <i class="fas fa-eye"></i> <span><?php _e('View', 'wp-office-editor'); ?></span>
                </button>
                <div class="wpoe-dropdown-content">
                    <div class="wpoe-view-section">
                        <strong><?php _e('Theme', 'wp-office-editor'); ?></strong>
                        <a href="#" class="wpoe-view-option" data-option="theme" data-value="light">
                            <i class="fas fa-sun"></i> <?php _e('Light', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-view-option" data-option="theme" data-value="dark">
                            <i class="fas fa-moon"></i> <?php _e('Dark', 'wp-office-editor'); ?>
                        </a>
                    </div>
                    
                    <div class="wpoe-view-section">
                        <strong><?php _e('Zoom', 'wp-office-editor'); ?></strong>
                        <a href="#" class="wpoe-view-option" data-option="zoom" data-value="75">
                            <i class="fas fa-search-minus"></i> 75%
                        </a>
                        <a href="#" class="wpoe-view-option" data-option="zoom" data-value="100">
                            <i class="fas fa-search"></i> 100%
                        </a>
                        <a href="#" class="wpoe-view-option" data-option="zoom" data-value="125">
                            <i class="fas fa-search-plus"></i> 125%
                        </a>
                        <a href="#" class="wpoe-view-option" data-option="zoom" data-value="150">
                            <i class="fas fa-search-plus"></i> 150%
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- AI Group -->
        <div class="wpoe-toolbar-group">
            <div class="wpoe-dropdown">
                <button type="button" class="wpoe-toolbar-btn wpoe-ai-btn" title="<?php esc_attr_e('AI Assistant', 'wp-office-editor'); ?>">
                    <i class="fas fa-robot"></i> <span><?php _e('AI Assistant', 'wp-office-editor'); ?></span>
                </button>
                <div class="wpoe-dropdown-content wpoe-ai-dropdown">
                    <div class="wpoe-ai-section">
                        <strong><?php _e('Generate Content', 'wp-office-editor'); ?></strong>
                        <a href="#" class="wpoe-ai-action" data-action="improve" title="<?php esc_attr_e('Improve Writing', 'wp-office-editor'); ?>">
                            <i class="fas fa-magic"></i> <?php _e('Improve Writing', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="summarize" title="<?php esc_attr_e('Summarize', 'wp-office-editor'); ?>">
                            <i class="fas fa-compress"></i> <?php _e('Summarize', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="translate" title="<?php esc_attr_e('Translate', 'wp-office-editor'); ?>">
                            <i class="fas fa-language"></i> <?php _e('Translate', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="expand" title="<?php esc_attr_e('Expand', 'wp-office-editor'); ?>">
                            <i class="fas fa-expand"></i> <?php _e('Expand', 'wp-office-editor'); ?>
                        </a>
                    </div>
                    
                    <div class="wpoe-ai-section">
                        <strong><?php _e('Templates', 'wp-office-editor'); ?></strong>
                        <a href="#" class="wpoe-ai-action" data-action="template_blog" title="<?php esc_attr_e('Blog Post', 'wp-office-editor'); ?>">
                            <i class="fas fa-blog"></i> <?php _e('Blog Post', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="template_report" title="<?php esc_attr_e('Report', 'wp-office-editor'); ?>">
                            <i class="fas fa-chart-bar"></i> <?php _e('Report', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="template_letter" title="<?php esc_attr_e('Business Letter', 'wp-office-editor'); ?>">
                            <i class="fas fa-envelope"></i> <?php _e('Business Letter', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="template_email" title="<?php esc_attr_e('Email', 'wp-office-editor'); ?>">
                            <i class="fas fa-mail-bulk"></i> <?php _e('Email', 'wp-office-editor'); ?>
                        </a>
                    </div>
                    
                    <div class="wpoe-ai-section">
                        <strong><?php _e('Code', 'wp-office-editor'); ?></strong>
                        <a href="#" class="wpoe-ai-action" data-action="code_explain" title="<?php esc_attr_e('Explain Code', 'wp-office-editor'); ?>">
                            <i class="fas fa-code"></i> <?php _e('Explain Code', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="code_generate" title="<?php esc_attr_e('Generate Code', 'wp-office-editor'); ?>">
                            <i class="fas fa-terminal"></i> <?php _e('Generate Code', 'wp-office-editor'); ?>
                        </a>
                        <a href="#" class="wpoe-ai-action" data-action="code_debug" title="<?php esc_attr_e('Debug Code', 'wp-office-editor'); ?>">
                            <i class="fas fa-bug"></i> <?php _e('Debug Code', 'wp-office-editor'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Collaboration Group (إذا كان مفعلاً) -->
        <?php
        $settings = get_option('wpoe_settings', []);
        if (!empty($settings['enable_realtime'])): ?>
        <div class="wpoe-toolbar-group">
            <button type="button" class="wpoe-toolbar-btn" id="wpoe-btn-share" title="<?php esc_attr_e('Share Document', 'wp-office-editor'); ?>">
                <i class="fas fa-share-alt"></i> <span><?php _e('Share', 'wp-office-editor'); ?></span>
            </button>
            
            <div class="wpoe-collaborators">
                <div class="wpoe-collaborator me" title="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                    <img src="<?php echo esc_url(get_avatar_url(get_current_user_id(), ['size' => 32])); ?>" alt="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                </div>
                <div class="wpoe-collaborator-list" id="wpoe-collaborator-list"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Help Group -->
        <div class="wpoe-toolbar-group">
            <button type="button" class="wpoe-toolbar-btn" id="wpoe-btn-help" title="<?php esc_attr_e('Help', 'wp-office-editor'); ?>">
                <i class="fas fa-question-circle"></i> <span><?php _e('Help', 'wp-office-editor'); ?></span>
            </button>
        </div>
    </div>
    
    <!-- شريط أدوات التنسيق (سيتم ملؤه بواسطة CKEditor) -->
    <div class="wpoe-formatting-toolbar" id="wpoe-format-toolbar">
        <!-- CKEditor سيضيف شريط الأدوات هنا -->
    </div>
    
    <!-- منطقة المحرر الرئيسية -->
    <div class="wpoe-editor-container">
        
        <!-- منطقة العنوان -->
        <div class="wpoe-document-title">
            <input type="text" 
                   id="wpoe-document-title" 
                   placeholder="<?php esc_attr_e('Document Title', 'wp-office-editor'); ?>" 
                   value="<?php echo esc_attr($document_title); ?>"
                   class="wpoe-title-input"
                   autocomplete="off">
        </div>
        
        <!-- منطقة المحتوى الرئيسية -->
        <div class="wpoe-editor-content">
            
            <!-- المحرر الرئيسي -->
            <div id="wpoe-editor-area"></div>
            
            <!-- لوحة الذكاء الاصطناعي الجانبية -->
            <div class="wpoe-ai-panel" id="wpoe-ai-panel" style="<?php echo $user_settings['show_ai_panel'] ? '' : 'display: none;'; ?>">
                <div class="wpoe-ai-header">
                    <h3><i class="fas fa-robot"></i> <?php _e('AI Assistant', 'wp-office-editor'); ?></h3>
                    <div class="wpoe-ai-header-actions">
                        <button type="button" class="wpoe-ai-clear" title="<?php esc_attr_e('Clear Chat', 'wp-office-editor'); ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button type="button" class="wpoe-ai-close" title="<?php esc_attr_e('Close Panel', 'wp-office-editor'); ?>">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="wpoe-ai-chat">
                    <div class="wpoe-ai-messages" id="wpoe-ai-messages">
                        <!-- الرسائل ستضاف هنا -->
                    </div>
                    
                    <div class="wpoe-ai-input">
                        <textarea id="wpoe-ai-prompt" 
                                  placeholder="<?php esc_attr_e('Ask AI to help with writing, editing, or generating content...', 'wp-office-editor'); ?>"
                                  rows="3"></textarea>
                        <div class="wpoe-ai-input-actions">
                            <button type="button" id="wpoe-ai-send" class="wpoe-ai-send-btn" title="<?php esc_attr_e('Send Message', 'wp-office-editor'); ?>">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button type="button" id="wpoe-ai-stop" class="wpoe-ai-stop-btn" title="<?php esc_attr_e('Stop Generating', 'wp-office-editor'); ?>" style="display: none;">
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- زر تبديل لوحة AI -->
            <button type="button" class="wpoe-ai-toggle" id="wpoe-ai-toggle" title="<?php esc_attr_e('Toggle AI Panel', 'wp-office-editor'); ?>">
                <i class="fas fa-robot"></i>
            </button>
            
        </div>
        
        <!-- منطقة الشورت كود -->
        <div class="wpoe-shortcode-section">
            <div class="wpoe-shortcode-header">
                <h4><i class="fas fa-code"></i> <?php _e('Document Shortcode', 'wp-office-editor'); ?></h4>
                <div class="wpoe-shortcode-actions">
                    <button type="button" class="button button-small" id="wpoe-copy-shortcode" title="<?php esc_attr_e('Copy Shortcode', 'wp-office-editor'); ?>">
                        <i class="fas fa-copy"></i> <span><?php _e('Copy', 'wp-office-editor'); ?></span>
                    </button>
                    <button type="button" class="button button-small" id="wpoe-insert-shortcode" title="<?php esc_attr_e('Insert into Post', 'wp-office-editor'); ?>">
                        <i class="fas fa-plus"></i> <span><?php _e('Insert', 'wp-office-editor'); ?></span>
                    </button>
                </div>
            </div>
            
            <div class="wpoe-shortcode-box">
                <code id="wpoe-shortcode-display">[wpoe_document id="<?php echo $document_id ? esc_attr($document_id) : 'new'; ?>"]</code>
            </div>
            
            <p class="description">
                <?php _e('Use this shortcode to embed this document anywhere on your site.', 'wp-office-editor'); ?>
                <?php if ($document_id): ?>
                <br>
                <?php _e('Preview:', 'wp-office-editor'); ?> 
                <a href="<?php echo esc_url(get_permalink($document_id)); ?>" target="_blank">
                    <?php echo esc_url(get_permalink($document_id)); ?>
                </a>
                <?php endif; ?>
            </p>
        </div>
        
    </div>
    
    <!-- شريط الحالة -->
    <div class="wpoe-editor-status">
        <div class="wpoe-status-left">
            <span class="wpoe-word-count" title="<?php esc_attr_e('Word Count', 'wp-office-editor'); ?>">
                <i class="fas fa-font"></i> 
                <span id="wpoe-word-count">0</span> <?php _e('words', 'wp-office-editor'); ?>
            </span>
            
            <span class="wpoe-char-count" title="<?php esc_attr_e('Character Count', 'wp-office-editor'); ?>">
                <i class="fas fa-text-width"></i> 
                <span id="wpoe-char-count">0</span> <?php _e('characters', 'wp-office-editor'); ?>
            </span>
            
            <span class="wpoe-page-count" title="<?php esc_attr_e('Page Count', 'wp-office-editor'); ?>">
                <i class="fas fa-file"></i> 
                <span id="wpoe-page-count">1</span> <?php _e('page', 'wp-office-editor'); ?>
            </span>
            
            <span class="wpoe-reading-time" title="<?php esc_attr_e('Reading Time', 'wp-office-editor'); ?>">
                <i class="fas fa-clock"></i> 
                <span id="wpoe-reading-time"><?php _e('Less than a minute', 'wp-office-editor'); ?></span>
            </span>
        </div>
        
        <div class="wpoe-status-right">
            <button type="button" class="button" id="wpoe-btn-draft" title="<?php esc_attr_e('Save as Draft', 'wp-office-editor'); ?>">
                <i class="fas fa-file"></i> <span><?php _e('Save Draft', 'wp-office-editor'); ?></span>
            </button>
            
            <button type="button" class="button button-primary button-large" id="wpoe-btn-publish" title="<?php esc_attr_e('Publish as Post', 'wp-office-editor'); ?>">
                <i class="fas fa-paper-plane"></i> <span><?php _e('Publish as Post', 'wp-office-editor'); ?></span>
            </button>
        </div>
    </div>
    
</div>

<!-- نافذة مشاركة المستند -->
<div class="wpoe-modal" id="wpoe-share-modal">
    <div class="wpoe-modal-content">
        <div class="wpoe-modal-header">
            <h3><i class="fas fa-share-alt"></i> <?php _e('Share Document', 'wp-office-editor'); ?></h3>
            <button type="button" class="wpoe-modal-close" title="<?php esc_attr_e('Close', 'wp-office-editor'); ?>">&times;</button>
        </div>
        
        <div class="wpoe-modal-body">
            <div class="wpoe-share-link">
                <label><?php _e('Shareable Link:', 'wp-office-editor'); ?></label>
                <div class="wpoe-link-box">
                    <input type="text" id="wpoe-share-url" readonly>
                    <button type="button" class="button" id="wpoe-copy-link" title="<?php esc_attr_e('Copy Link', 'wp-office-editor'); ?>">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="description">
                    <?php _e('Anyone with this link can view and edit this document.', 'wp-office-editor'); ?>
                </p>
            </div>
            
            <div class="wpoe-share-users">
                <label><?php _e('Add Users:', 'wp-office-editor'); ?></label>
                <select id="wpoe-user-select" multiple style="width: 100%;" class="wpoe-user-select2">
                    <?php
                    $users = get_users([
                        'fields' => ['ID', 'display_name', 'user_email'],
                        'exclude' => [get_current_user_id()]
                    ]);
                    
                    $shared_users = $document_id ? get_post_meta($document_id, '_wpoe_shared_users', true) : [];
                    $shared_users = is_array($shared_users) ? $shared_users : [];
                    
                    foreach ($users as $user) {
                        $selected = in_array($user->ID, $shared_users) ? 'selected' : '';
                        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>';
                        echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
                        echo '</option>';
                    }
                    ?>
                </select>
                <p class="description">
                    <?php _e('Selected users will be able to edit this document.', 'wp-office-editor'); ?>
                </p>
            </div>
            
            <div class="wpoe-share-permissions">
                <label><?php _e('Permissions:', 'wp-office-editor'); ?></label>
                <div class="wpoe-permissions-options">
                    <label>
                        <input type="radio" name="wpoe_share_permission" value="view" checked>
                        <?php _e('View only', 'wp-office-editor'); ?>
                    </label>
                    <label>
                        <input type="radio" name="wpoe_share_permission" value="edit">
                        <?php _e('Can edit', 'wp-office-editor'); ?>
                    </label>
                    <label>
                        <input type="radio" name="wpoe_share_permission" value="comment">
                        <?php _e('Can comment', 'wp-office-editor'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="wpoe-modal-footer">
            <button type="button" class="button button-secondary" id="wpoe-reset-sharing">
                <?php _e('Reset Sharing', 'wp-office-editor'); ?>
            </button>
            <button type="button" class="button button-primary" id="wpoe-save-sharing">
                <i class="fas fa-save"></i> <?php _e('Save Sharing Settings', 'wp-office-editor'); ?>
            </button>
        </div>
    </div>
</div>

<!-- نافذة مستعرض المستندات -->
<div class="wpoe-modal" id="wpoe-documents-modal">
    <div class="wpoe-modal-content wpoe-documents-modal">
        <div class="wpoe-modal-header">
            <h3><i class="fas fa-folder-open"></i> <?php _e('Open Document', 'wp-office-editor'); ?></h3>
            <button type="button" class="wpoe-modal-close">&times;</button>
        </div>
        
        <div class="wpoe-modal-body">
            <div class="wpoe-documents-search">
                <input type="text" id="wpoe-documents-search" placeholder="<?php esc_attr_e('Search documents...', 'wp-office-editor'); ?>">
                <button type="button" id="wpoe-documents-search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="wpoe-documents-list" id="wpoe-documents-list">
                <!-- قائمة المستندات ستظهر هنا -->
                <div class="wpoe-loading">
                    <i class="fas fa-spinner fa-spin"></i> <?php _e('Loading documents...', 'wp-office-editor'); ?>
                </div>
            </div>
            
            <div class="wpoe-documents-pagination" id="wpoe-documents-pagination">
                <!-- ترقيم الصفحات -->
            </div>
        </div>
        
        <div class="wpoe-modal-footer">
            <button type="button" class="button button-primary" id="wpoe-open-selected">
                <i class="fas fa-folder-open"></i> <?php _e('Open Selected', 'wp-office-editor'); ?>
            </button>
        </div>
    </div>
</div>

<!-- نافذة قائمة الألسنة -->
<div class="wpoe-modal" id="wpoe-tabs-list-modal">
    <div class="wpoe-modal-content">
        <div class="wpoe-modal-header">
            <h3><i class="fas fa-list"></i> <?php _e('All Tabs', 'wp-office-editor'); ?></h3>
            <button type="button" class="wpoe-modal-close">&times;</button>
        </div>
        
        <div class="wpoe-modal-body">
            <div class="wpoe-tabs-list-search">
                <input type="text" id="wpoe-tabs-search" placeholder="<?php esc_attr_e('Search tabs...', 'wp-office-editor'); ?>">
                <button type="button" id="wpoe-tabs-search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="wpoe-tabs-list" id="wpoe-tabs-list">
                <!-- قائمة الألسنة ستظهر هنا -->
            </div>
            
            <div class="wpoe-tabs-stats">
                <div class="wpoe-tab-stat">
                    <span class="wpoe-tab-stat-label"><?php _e('Total Tabs:', 'wp-office-editor'); ?></span>
                    <span class="wpoe-tab-stat-value" id="wpoe-total-tabs">0</span>
                </div>
                <div class="wpoe-tab-stat">
                    <span class="wpoe-tab-stat-label"><?php _e('Unsaved:', 'wp-office-editor'); ?></span>
                    <span class="wpoe-tab-stat-value" id="wpoe-unsaved-tabs">0</span>
                </div>
                <div class="wpoe-tab-stat">
                    <span class="wpoe-tab-stat-label"><?php _e('Pinned:', 'wp-office-editor'); ?></span>
                    <span class="wpoe-tab-stat-value" id="wpoe-pinned-tabs">0</span>
                </div>
            </div>
        </div>
        
        <div class="wpoe-modal-footer">
            <button type="button" class="button button-secondary" id="wpoe-sort-tabs" data-sort="last_modified">
                <i class="fas fa-sort-amount-down"></i> <?php _e('Sort by Last Modified', 'wp-office-editor'); ?>
            </button>
            <button type="button" class="button button-primary" id="wpoe-switch-selected-tab">
                <i class="fas fa-exchange-alt"></i> <?php _e('Switch to Selected', 'wp-office-editor'); ?>
            </button>
        </div>
    </div>
</div>

<!-- نافذة استيراد الألسنة -->
<div class="wpoe-modal" id="wpoe-import-tabs-modal">
    <div class="wpoe-modal-content">
        <div class="wpoe-modal-header">
            <h3><i class="fas fa-upload"></i> <?php _e('Import Tabs', 'wp-office-editor'); ?></h3>
            <button type="button" class="wpoe-modal-close">&times;</button>
        </div>
        
        <div class="wpoe-modal-body">
            <div class="wpoe-import-options">
                <div class="wpoe-import-option">
                    <label>
                        <input type="radio" name="import_mode" value="merge" checked>
                        <?php _e('Merge with existing tabs', 'wp-office-editor'); ?>
                    </label>
                </div>
                <div class="wpoe-import-option">
                    <label>
                        <input type="radio" name="import_mode" value="replace">
                        <?php _e('Replace all tabs', 'wp-office-editor'); ?>
                    </label>
                </div>
                <div class="wpoe-import-option">
                    <label>
                        <input type="radio" name="import_mode" value="new">
                        <?php _e('Open in new window', 'wp-office-editor'); ?>
                    </label>
                </div>
            </div>
            
            <div class="wpoe-import-file">
                <label for="wpoe-import-file-input"><?php _e('Select JSON file:', 'wp-office-editor'); ?></label>
                <input type="file" id="wpoe-import-file-input" accept=".json,application/json">
                <p class="description">
                    <?php _e('Select a JSON file exported from WP Office Editor.', 'wp-office-editor'); ?>
                </p>
            </div>
            
            <div class="wpoe-import-preview" id="wpoe-import-preview" style="display: none;">
                <h4><?php _e('Preview:', 'wp-office-editor'); ?></h4>
                <div class="wpoe-import-preview-content"></div>
            </div>
        </div>
        
        <div class="wpoe-modal-footer">
            <button type="button" class="button button-secondary" id="wpoe-cancel-import">
                <?php _e('Cancel', 'wp-office-editor'); ?>
            </button>
            <button type="button" class="button button-primary" id="wpoe-confirm-import" disabled>
                <i class="fas fa-upload"></i> <?php _e('Import Tabs', 'wp-office-editor'); ?>
            </button>
        </div>
    </div>
</div>

<!-- بيانات المستند -->
<script type="application/json" id="wpoe-document-data">
{
    "id": <?php echo $document_id ?: 'null'; ?>,
    "title": "<?php echo esc_js($document_title); ?>",
    "is_new": <?php echo $is_new ? 'true' : 'false'; ?>,
    "share_token": "<?php echo esc_js($share_token); ?>",
    "user_settings": <?php echo json_encode($user_settings); ?>,
    "auto_save_interval": <?php echo isset($settings['auto_save_interval']) ? (int)$settings['auto_save_interval'] : 30; ?>
}
</script>

<?php
// تحميل Select2 لمتصفح المستخدمين
if (!wp_script_is('select2', 'enqueued')) {
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13');
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
}
?>

<script>
jQuery(document).ready(function($) {
    // تهيئة Select2
    if ($.fn.select2) {
        $('.wpoe-user-select2').select2({
            placeholder: '<?php echo esc_js(__('Select users...', 'wp-office-editor')); ?>',
            width: '100%',
            allowClear: true
        });
    }
});
</script>
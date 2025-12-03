<?php
if (!current_user_can('edit_posts')) {
    wp_die(__('You do not have permission to access this page.', 'wp-office-editor'));
}

$ai = new WP_Office_Editor_AI();
$templates = $ai->get_templates();
$writing_styles = $ai->get_writing_styles();
$available_models = $ai->get_available_models();

// الحصول على إحصائيات الاستخدام
$usage_stats = $ai->get_usage_stats('month', get_current_user_id());
$user_stats = get_user_meta(get_current_user_id(), 'wpoe_ai_stats', true);
?>

<div class="wrap wpoe-ai-templates-wrap">
    <h1 class="wp-heading-inline">
        <i class="fas fa-robot"></i> <?php _e('AI Templates', 'wp-office-editor'); ?>
    </h1>
    
    <div class="wpoe-ai-stats-overview">
        <div class="wpoe-ai-stat-card">
            <div class="wpoe-ai-stat-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="wpoe-ai-stat-content">
                <div class="wpoe-ai-stat-value"><?php echo esc_html($user_stats['total_requests'] ?? 0); ?></div>
                <div class="wpoe-ai-stat-label"><?php _e('Total Requests', 'wp-office-editor'); ?></div>
            </div>
        </div>
        
        <div class="wpoe-ai-stat-card">
            <div class="wpoe-ai-stat-icon">
                <i class="fas fa-keyboard"></i>
            </div>
            <div class="wpoe-ai-stat-content">
                <div class="wpoe-ai-stat-value"><?php echo number_format($user_stats['total_tokens'] ?? 0); ?></div>
                <div class="wpoe-ai-stat-label"><?php _e('Tokens Used', 'wp-office-editor'); ?></div>
            </div>
        </div>
        
        <div class="wpoe-ai-stat-card">
            <div class="wpoe-ai-stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="wpoe-ai-stat-content">
                <div class="wpoe-ai-stat-value">$<?php echo number_format($user_stats['total_cost'] ?? 0, 6); ?></div>
                <div class="wpoe-ai-stat-label"><?php _e('Estimated Cost', 'wp-office-editor'); ?></div>
            </div>
        </div>
        
        <div class="wpoe-ai-stat-card">
            <div class="wpoe-ai-stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="wpoe-ai-stat-content">
                <div class="wpoe-ai-stat-value"><?php echo round(($usage_stats['total_tokens'] ?? 0) / max(1, ($usage_stats['total_requests'] ?? 1))); ?></div>
                <div class="wpoe-ai-stat-label"><?php _e('Avg Tokens/Request', 'wp-office-editor'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="wpoe-ai-templates-container">
        <div class="wpoe-ai-sidebar">
            <div class="wpoe-ai-sidebar-section">
                <h3><i class="fas fa-sliders-h"></i> <?php _e('AI Settings', 'wp-office-editor'); ?></h3>
                
                <div class="wpoe-ai-setting">
                    <label for="wpoe-ai-default-model"><?php _e('Default Model:', 'wp-office-editor'); ?></label>
                    <select id="wpoe-ai-default-model" class="wpoe-ai-model-select">
                        <?php foreach ($available_models as $model_id => $model_name): ?>
                            <option value="<?php echo esc_attr($model_id); ?>">
                                <?php echo esc_html($model_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="wpoe-ai-setting">
                    <label for="wpoe-ai-max-tokens"><?php _e('Max Tokens:', 'wp-office-editor'); ?></label>
                    <input type="range" id="wpoe-ai-max-tokens" min="100" max="4000" step="100" value="2000">
                    <span id="wpoe-ai-max-tokens-value">2000</span>
                </div>
                
                <div class="wpoe-ai-setting">
                    <label for="wpoe-ai-temperature"><?php _e('Temperature:', 'wp-office-editor'); ?></label>
                    <input type="range" id="wpoe-ai-temperature" min="0" max="1" step="0.1" value="0.7">
                    <span id="wpoe-ai-temperature-value">0.7</span>
                </div>
                
                <button type="button" id="wpoe-ai-test-api" class="button button-secondary">
                    <i class="fas fa-plug"></i> <?php _e('Test API Connection', 'wp-office-editor'); ?>
                </button>
            </div>
            
            <div class="wpoe-ai-sidebar-section">
                <h3><i class="fas fa-history"></i> <?php _e('Recent Activity', 'wp-office-editor'); ?></h3>
                <div id="wpoe-ai-recent-activity">
                    <?php if (!empty($usage_stats['top_actions'])): ?>
                        <ul class="wpoe-ai-activity-list">
                            <?php foreach ($usage_stats['top_actions'] as $activity): ?>
                                <li>
                                    <span class="wpoe-ai-activity-action"><?php echo esc_html($activity->action); ?></span>
                                    <span class="wpoe-ai-activity-count"><?php echo esc_html($activity->count); ?> uses</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="description"><?php _e('No AI activity yet.', 'wp-office-editor'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="wpoe-ai-main-content">
            <div class="wpoe-ai-templates-grid">
                <?php foreach ($templates as $template_id => $template): ?>
                    <div class="wpoe-ai-template-card" data-template-id="<?php echo esc_attr($template_id); ?>">
                        <div class="wpoe-ai-template-icon">
                            <i class="fas <?php echo $template_id === 'blog_post' ? 'fa-blog' : 
                                                ($template_id === 'report' ? 'fa-chart-bar' : 
                                                ($template_id === 'business_letter' ? 'fa-envelope' : 
                                                ($template_id === 'email' ? 'fa-mail-bulk' : 
                                                ($template_id === 'social_media' ? 'fa-share-alt' : 
                                                ($template_id === 'product_description' ? 'fa-tag' : 
                                                ($template_id === 'seo_article' ? 'fa-search' : 'fa-file-alt')))))); ?>"></i>
                        </div>
                        <div class="wpoe-ai-template-content">
                            <h3><?php echo esc_html($template['name']); ?></h3>
                            <p class="wpoe-ai-template-description"><?php echo esc_html($template['description']); ?></p>
                            <div class="wpoe-ai-template-meta">
                                <span class="wpoe-ai-template-tokens">
                                    <i class="fas fa-keyboard"></i> ~500-1000 tokens
                                </span>
                            </div>
                        </div>
                        <button type="button" class="button button-primary wpoe-ai-use-template">
                            <i class="fas fa-magic"></i> <?php _e('Use Template', 'wp-office-editor'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="wpoe-ai-custom-prompt">
                <h3><i class="fas fa-comment-dots"></i> <?php _e('Custom AI Prompt', 'wp-office-editor'); ?></h3>
                <div class="wpoe-ai-custom-form">
                    <div class="wpoe-ai-form-row">
                        <label for="wpoe-ai-custom-instruction"><?php _e('Instructions:', 'wp-office-editor'); ?></label>
                        <textarea id="wpoe-ai-custom-instruction" rows="4" 
                                  placeholder="<?php esc_attr_e('Describe what you want AI to generate...', 'wp-office-editor'); ?>"></textarea>
                    </div>
                    
                    <div class="wpoe-ai-form-row">
                        <label for="wpoe-ai-writing-style"><?php _e('Writing Style:', 'wp-office-editor'); ?></label>
                        <select id="wpoe-ai-writing-style">
                            <option value=""><?php _e('Default', 'wp-office-editor'); ?></option>
                            <?php foreach ($writing_styles as $style_id => $style_name): ?>
                                <option value="<?php echo esc_attr($style_id); ?>">
                                    <?php echo esc_html($style_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="wpoe-ai-form-row">
                        <label for="wpoe-ai-context"><?php _e('Context (Optional):', 'wp-office-editor'); ?></label>
                        <textarea id="wpoe-ai-context" rows="3" 
                                  placeholder="<?php esc_attr_e('Add any context or reference text...', 'wp-office-editor'); ?>"></textarea>
                    </div>
                    
                    <div class="wpoe-ai-form-actions">
                        <button type="button" id="wpoe-ai-generate-custom" class="button button-primary button-large">
                            <i class="fas fa-bolt"></i> <?php _e('Generate Content', 'wp-office-editor'); ?>
                        </button>
                        <button type="button" id="wpoe-ai-save-template" class="button button-secondary">
                            <i class="fas fa-save"></i> <?php _e('Save as Template', 'wp-office-editor'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="wpoe-ai-tools">
                <h3><i class="fas fa-tools"></i> <?php _e('AI Tools', 'wp-office-editor'); ?></h3>
                <div class="wpoe-ai-tools-grid">
                    <div class="wpoe-ai-tool-card" data-tool="improve">
                        <div class="wpoe-ai-tool-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <h4><?php _e('Improve Writing', 'wp-office-editor'); ?></h4>
                        <p><?php _e('Enhance grammar, style, and clarity', 'wp-office-editor'); ?></p>
                    </div>
                    
                    <div class="wpoe-ai-tool-card" data-tool="summarize">
                        <div class="wpoe-ai-tool-icon">
                            <i class="fas fa-compress"></i>
                        </div>
                        <h4><?php _e('Summarize', 'wp-office-editor'); ?></h4>
                        <p><?php _e('Create concise summaries', 'wp-office-editor'); ?></p>
                    </div>
                    
                    <div class="wpoe-ai-tool-card" data-tool="translate">
                        <div class="wpoe-ai-tool-icon">
                            <i class="fas fa-language"></i>
                        </div>
                        <h4><?php _e('Translate', 'wp-office-editor'); ?></h4>
                        <p><?php _e('Translate between languages', 'wp-office-editor'); ?></p>
                    </div>
                    
                    <div class="wpoe-ai-tool-card" data-tool="expand">
                        <div class="wpoe-ai-tool-icon">
                            <i class="fas fa-expand"></i>
                        </div>
                        <h4><?php _e('Expand', 'wp-office-editor'); ?></h4>
                        <p><?php _e('Add details and explanations', 'wp-office-editor'); ?></p>
                    </div>
                    
                    <div class="wpoe-ai-tool-card" data-tool="seo_analyze">
                        <div class="wpoe-ai-tool-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4><?php _e('SEO Analyze', 'wp-office-editor'); ?></h4>
                        <p><?php _e('Optimize for search engines', 'wp-office-editor'); ?></p>
                    </div>
                    
                    <div class="wpoe-ai-tool-card" data-tool="generate_faq">
                        <div class="wpoe-ai-tool-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h4><?php _e('Generate FAQ', 'wp-office-editor'); ?></h4>
                        <p><?php _e('Create questions and answers', 'wp-office-editor'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة القالب -->
<div class="wpoe-modal" id="wpoe-ai-template-modal">
    <div class="wpoe-modal-content">
        <div class="wpoe-modal-header">
            <h3 id="wpoe-ai-template-modal-title"></h3>
            <button type="button" class="wpoe-modal-close">&times;</button>
        </div>
        <div class="wpoe-modal-body" id="wpoe-ai-template-modal-body">
            <!-- محتوى القالب سيتم تحميله هنا -->
        </div>
        <div class="wpoe-modal-footer">
            <button type="button" class="button button-secondary" id="wpoe-ai-template-cancel">
                <?php _e('Cancel', 'wp-office-editor'); ?>
            </button>
            <button type="button" class="button button-primary" id="wpoe-ai-template-generate">
                <i class="fas fa-bolt"></i> <?php _e('Generate', 'wp-office-editor'); ?>
            </button>
        </div>
    </div>
</div>

<!-- نافذة النتيجة -->
<div class="wpoe-modal" id="wpoe-ai-result-modal">
    <div class="wpoe-modal-content wpoe-ai-result-modal">
        <div class="wpoe-modal-header">
            <h3><i class="fas fa-robot"></i> <?php _e('AI Generated Content', 'wp-office-editor'); ?></h3>
            <button type="button" class="wpoe-modal-close">&times;</button>
        </div>
        <div class="wpoe-modal-body">
            <div class="wpoe-ai-result-stats">
                <div class="wpoe-ai-result-stat">
                    <span class="stat-label"><?php _e('Tokens:', 'wp-office-editor'); ?></span>
                    <span class="stat-value" id="wpoe-ai-result-tokens">0</span>
                </div>
                <div class="wpoe-ai-result-stat">
                    <span class="stat-label"><?php _e('Model:', 'wp-office-editor'); ?></span>
                    <span class="stat-value" id="wpoe-ai-result-model">GPT-3.5</span>
                </div>
                <div class="wpoe-ai-result-stat">
                    <span class="stat-label"><?php _e('Cost:', 'wp-office-editor'); ?></span>
                    <span class="stat-value" id="wpoe-ai-result-cost">$0.000</span>
                </div>
            </div>
            
            <div class="wpoe-ai-result-content">
                <textarea id="wpoe-ai-result-text" rows="10" readonly></textarea>
            </div>
            
            <div class="wpoe-ai-result-actions">
                <button type="button" class="button" id="wpoe-ai-result-copy">
                    <i class="fas fa-copy"></i> <?php _e('Copy', 'wp-office-editor'); ?>
                </button>
                <button type="button" class="button" id="wpoe-ai-result-insert">
                    <i class="fas fa-plus"></i> <?php _e('Insert into Editor', 'wp-office-editor'); ?>
                </button>
                <button type="button" class="button button-primary" id="wpoe-ai-result-open">
                    <i class="fas fa-external-link-alt"></i> <?php _e('Open in New Tab', 'wp-office-editor'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.wpoe-ai-templates-wrap {
    background: #fff;
    padding: 20px;
    min-height: calc(100vh - 32px);
}

.wpoe-ai-stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.wpoe-ai-stat-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
}

.wpoe-ai-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #764ba2;
}

.wpoe-ai-stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.wpoe-ai-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    line-height: 1;
}

.wpoe-ai-stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.wpoe-ai-templates-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    margin-top: 30px;
}

.wpoe-ai-sidebar {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e0e0e0;
}

.wpoe-ai-sidebar-section {
    margin-bottom: 30px;
}

.wpoe-ai-sidebar-section h3 {
    margin-top: 0;
    font-size: 16px;
    color: #333;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wpoe-ai-setting {
    margin-bottom: 15px;
}

.wpoe-ai-setting label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.wpoe-ai-setting select,
.wpoe-ai-setting input[type="range"] {
    width: 100%;
}

.wpoe-ai-activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.wpoe-ai-activity-list li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wpoe-ai-activity-list li:last-child {
    border-bottom: none;
}

.wpoe-ai-activity-action {
    font-size: 13px;
    color: #333;
}

.wpoe-ai-activity-count {
    font-size: 12px;
    color: #666;
    background: #eee;
    padding: 2px 8px;
    border-radius: 10px;
}

.wpoe-ai-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.wpoe-ai-template-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.wpoe-ai-template-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #764ba2;
}

.wpoe-ai-template-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    margin-bottom: 15px;
}

.wpoe-ai-template-content h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #333;
}

.wpoe-ai-template-description {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 15px;
}

.wpoe-ai-template-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 12px;
    color: #888;
    margin-bottom: 15px;
}

.wpoe-ai-use-template {
    width: 100%;
}

.wpoe-ai-custom-prompt {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.wpoe-ai-custom-prompt h3 {
    margin-top: 0;
    font-size: 18px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.wpoe-ai-form-row {
    margin-bottom: 15px;
}

.wpoe-ai-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.wpoe-ai-form-row textarea,
.wpoe-ai-form-row select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.wpoe-ai-form-row textarea {
    resize: vertical;
}

.wpoe-ai-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.wpoe-ai-tools {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
}

.wpoe-ai-tools h3 {
    margin-top: 0;
    font-size: 18px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.wpoe-ai-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.wpoe-ai-tool-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.wpoe-ai-tool-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #764ba2;
    background: white;
}

.wpoe-ai-tool-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    margin: 0 auto 10px;
}

.wpoe-ai-tool-card h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #333;
}

.wpoe-ai-tool-card p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.wpoe-ai-result-modal .wpoe-modal-content {
    max-width: 800px;
}

.wpoe-ai-result-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.wpoe-ai-result-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 14px;
    font-weight: bold;
    color: #333;
}

.wpoe-ai-result-content textarea {
    width: 100%;
    min-height: 300px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14px;
    line-height: 1.6;
    resize: vertical;
}

.wpoe-ai-result-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    justify-content: center;
}

@media (max-width: 1024px) {
    .wpoe-ai-templates-container {
        grid-template-columns: 1fr;
    }
    
    .wpoe-ai-sidebar {
        order: 2;
    }
    
    .wpoe-ai-main-content {
        order: 1;
    }
}

@media (max-width: 768px) {
    .wpoe-ai-stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .wpoe-ai-templates-grid {
        grid-template-columns: 1fr;
    }
    
    .wpoe-ai-tools-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // تحديث قيم المدخلات المنزلقة
    $('#wpoe-ai-max-tokens').on('input', function() {
        $('#wpoe-ai-max-tokens-value').text($(this).val());
    });
    
    $('#wpoe-ai-temperature').on('input', function() {
        $('#wpoe-ai-temperature-value').text($(this).val());
    });
    
    // اختبار اتصال API
    $('#wpoe-ai-test-api').on('click', function() {
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpoe_test_ai_api',
                nonce: wpoe_data.nonce
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                alert('✅ ' + response.message);
            } else {
                alert('❌ ' + response.message);
            }
        })
        .fail(function() {
            alert('❌ Connection test failed');
        })
        .always(function() {
            $button.prop('disabled', false).html(originalText);
        });
    });
    
    // استخدام القوالب
    $('.wpoe-ai-use-template').on('click', function() {
        const templateId = $(this).closest('.wpoe-ai-template-card').data('template-id');
        openTemplateModal(templateId);
    });
    
    // أدوات الذكاء الاصطناعي
    $('.wpoe-ai-tool-card').on('click', function() {
        const tool = $(this).data('tool');
        openToolModal(tool);
    });
    
    // توليد محتوى مخصص
    $('#wpoe-ai-generate-custom').on('click', function() {
        generateCustomContent();
    });
    
    // حفظ كقالب
    $('#wpoe-ai-save-template').on('click', function() {
        saveAsTemplate();
    });
    
    // فتح نافذة القالب
    function openTemplateModal(templateId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpoe_get_template_form',
                nonce: wpoe_data.nonce,
                template_id: templateId
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                $('#wpoe-ai-template-modal-title').text(response.data.title);
                $('#wpoe-ai-template-modal-body').html(response.data.form);
                $('#wpoe-ai-template-modal').addClass('active');
                
                // إضافة مستمعي الأحداث للنموذج
                $('#wpoe-ai-template-generate').off('click').on('click', function() {
                    generateFromTemplate(templateId);
                });
                
                $('#wpoe-ai-template-cancel, .wpoe-modal-close').off('click').on('click', function() {
                    $('#wpoe-ai-template-modal').removeClass('active');
                });
            }
        })
        .fail(function() {
            alert('Error loading template');
        });
    }
    
    // فتح نافذة الأداة
    function openToolModal(tool) {
        let title = '';
        let formHTML = '';
        
        switch(tool) {
            case 'improve':
                title = 'Improve Writing';
                formHTML = `
                    <div class="wpoe-ai-tool-form">
                        <div class="form-field">
                            <label for="improve-text">Text to improve:</label>
                            <textarea id="improve-text" rows="6" placeholder="Paste the text you want to improve..."></textarea>
                        </div>
                        <div class="form-field">
                            <label for="improve-instructions">Specific instructions (optional):</label>
                            <textarea id="improve-instructions" rows="3" placeholder="E.g., Make it more formal, fix grammar, improve flow..."></textarea>
                        </div>
                    </div>
                `;
                break;
                
            case 'summarize':
                title = 'Summarize Text';
                formHTML = `
                    <div class="wpoe-ai-tool-form">
                        <div class="form-field">
                            <label for="summarize-text">Text to summarize:</label>
                            <textarea id="summarize-text" rows="6" placeholder="Paste the text you want to summarize..."></textarea>
                        </div>
                        <div class="form-field">
                            <label for="summary-length">Summary length:</label>
                            <select id="summary-length">
                                <option value="short">Short (1-2 sentences)</option>
                                <option value="medium" selected>Medium (paragraph)</option>
                                <option value="detailed">Detailed (multiple paragraphs)</option>
                            </select>
                        </div>
                    </div>
                `;
                break;
                
            case 'translate':
                title = 'Translate Text';
                formHTML = `
                    <div class="wpoe-ai-tool-form">
                        <div class="form-field">
                            <label for="translate-text">Text to translate:</label>
                            <textarea id="translate-text" rows="6" placeholder="Paste the text you want to translate..."></textarea>
                        </div>
                        <div class="form-field">
                            <label for="target-language">Target language:</label>
                            <select id="target-language">
                                <option value="english">English</option>
                                <option value="arabic">Arabic</option>
                                <option value="spanish">Spanish</option>
                                <option value="french">French</option>
                                <option value="german">German</option>
                                <option value="chinese">Chinese</option>
                                <option value="japanese">Japanese</option>
                            </select>
                        </div>
                    </div>
                `;
                break;
                
            case 'seo_analyze':
                title = 'SEO Analysis';
                formHTML = `
                    <div class="wpoe-ai-tool-form">
                        <div class="form-field">
                            <label for="seo-text">Content to analyze:</label>
                            <textarea id="seo-text" rows="6" placeholder="Paste the content for SEO analysis..."></textarea>
                        </div>
                        <div class="form-field">
                            <label for="target-keyword">Target keyword (optional):</label>
                            <input type="text" id="target-keyword" placeholder="Main keyword">
                        </div>
                    </div>
                `;
                break;
        }
        
        $('#wpoe-ai-template-modal-title').text(title);
        $('#wpoe-ai-template-modal-body').html(formHTML);
        $('#wpoe-ai-template-modal').addClass('active');
        
        // إضافة مستمعي الأحداث للنموذج
        $('#wpoe-ai-template-generate').off('click').on('click', function() {
            generateFromTool(tool);
        });
        
        $('#wpoe-ai-template-cancel, .wpoe-modal-close').off('click').on('click', function() {
            $('#wpoe-ai-template-modal').removeClass('active');
        });
    }
    
    // توليد من القالب
    function generateFromTemplate(templateId) {
        const formData = {};
        $(`#wpoe-ai-template-modal-body .form-field`).each(function() {
            const $field = $(this);
            const name = $field.find('input, select, textarea').attr('id');
            const value = $field.find('input, select, textarea').val();
            if (name && value) {
                formData[name.replace('template-', '')] = value;
            }
        });
        
        generateAI(templateId, formData);
    }
    
    // توليد من الأداة
    function generateFromTool(tool) {
        const formData = {};
        
        switch(tool) {
            case 'improve':
                formData.text = $('#improve-text').val();
                formData.instructions = $('#improve-instructions').val();
                break;
                
            case 'summarize':
                formData.text = $('#summarize-text').val();
                formData.length = $('#summary-length').val();
                break;
                
            case 'translate':
                formData.text = $('#translate-text').val();
                formData.language = $('#target-language').val();
                break;
                
            case 'seo_analyze':
                formData.text = $('#seo-text').val();
                formData.keyword = $('#target-keyword').val();
                break;
        }
        
        generateAI(tool, formData);
    }
    
    // توليد محتوى مخصص
    function generateCustomContent() {
        const instruction = $('#wpoe-ai-custom-instruction').val();
        const style = $('#wpoe-ai-writing-style').val();
        const context = $('#wpoe-ai-context').val();
        
        if (!instruction.trim()) {
            alert('Please enter instructions for AI');
            return;
        }
        
        const options = {
            style: style,
            context: context,
            model: $('#wpoe-ai-default-model').val(),
            max_tokens: $('#wpoe-ai-max-tokens').val(),
            temperature: $('#wpoe-ai-temperature').val()
        };
        
        generateAI('custom', { instruction: instruction, ...options });
    }
    
    // دالة توليد AI عامة
    function generateAI(action, data) {
        const $generateBtn = $('#wpoe-ai-template-generate');
        const originalText = $generateBtn.html();
        
        $generateBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpoe_ai_generate',
                nonce: wpoe_data.nonce,
                action_type: action,
                data: JSON.stringify(data)
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                showResultModal(response.data);
                $('#wpoe-ai-template-modal').removeClass('active');
            } else {
                alert('Error: ' + response.message);
            }
        })
        .fail(function() {
            alert('Generation failed. Please try again.');
        })
        .always(function() {
            $generateBtn.prop('disabled', false).html(originalText);
        });
    }
    
    // عرض نافذة النتيجة
    function showResultModal(result) {
        $('#wpoe-ai-result-tokens').text(result.tokens_used);
        $('#wpoe-ai-result-model').text(result.model);
        $('#wpoe-ai-result-cost').text('$' + result.cost.toFixed(6));
        $('#wpoe-ai-result-text').val(result.content);
        
        $('#wpoe-ai-result-modal').addClass('active');
        
        // إضافة مستمعي الأحداث للأزرار
        $('#wpoe-ai-result-copy').off('click').on('click', function() {
            navigator.clipboard.writeText(result.content).then(function() {
                alert('Content copied to clipboard!');
            });
        });
        
        $('#wpoe-ai-result-insert').off('click').on('click', function() {
            // هذا يتطلب تكاملاً مع صفحة المحرر الرئيسية
            window.opener?.WPOfficeEditor?.applyToEditor?.(result.content, 'insert');
            $('#wpoe-ai-result-modal').removeClass('active');
        });
        
        $('#wpoe-ai-result-open').off('click').on('click', function() {
            const newWindow = window.open('', '_blank');
            newWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>AI Generated Content</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                        .content { white-space: pre-wrap; background: #f5f5f5; padding: 20px; border-radius: 5px; }
                    </style>
                </head>
                <body>
                    <h1>AI Generated Content</h1>
                    <div class="content">${result.content.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
                </body>
                </html>
            `);
        });
        
        $('.wpoe-modal-close').off('click').on('click', function() {
            $('#wpoe-ai-result-modal').removeClass('active');
        });
    }
    
    // حفظ كقالب
    function saveAsTemplate() {
        const name = prompt('Enter template name:');
        if (!name) return;
        
        const instruction = $('#wpoe-ai-custom-instruction').val();
        const style = $('#wpoe-ai-writing-style').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpoe_save_ai_template',
                nonce: wpoe_data.nonce,
                name: name,
                instruction: instruction,
                style: style
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                alert('Template saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        })
        .fail(function() {
            alert('Failed to save template');
        });
    }
});
</script>
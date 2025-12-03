<?php
class WP_Office_Editor_Templates {
    
    private $templates;
    private $categories;
    private $template_dir;
    
    public function __construct() {
        $this->template_dir = WPOE_PLUGIN_DIR . 'templates/';
        
        // إنشاء مجلد القوالب إذا لم يكن موجوداً
        if (!file_exists($this->template_dir)) {
            wp_mkdir_p($this->template_dir);
        }
        
        $this->load_templates();
        $this->load_categories();
        $this->init_hooks();
    }
    
    /**
     * تهيئة الروابط
     */
    private function init_hooks() {
        // نقاط نهاية AJAX للقوالب
        add_action('wp_ajax_wpoe_get_templates', [$this, 'ajax_get_templates']);
        add_action('wp_ajax_wpoe_get_template_categories', [$this, 'ajax_get_categories']);
        add_action('wp_ajax_wpoe_create_from_template', [$this, 'ajax_create_from_template']);
        add_action('wp_ajax_wpoe_save_as_template', [$this, 'ajax_save_as_template']);
        add_action('wp_ajax_wpoe_import_template', [$this, 'ajax_import_template']);
        add_action('wp_ajax_wpoe_export_template', [$this, 'ajax_export_template']);
        add_action('wp_ajax_wpoe_delete_template', [$this, 'ajax_delete_template']);
        
        // معالجة رفع القوالب
        add_action('wp_ajax_wpoe_upload_template_file', [$this, 'ajax_upload_template_file']);
        
        // نقاط نهاية REST API
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        
        // قصر القوالب في واجهة المستخدم
        add_action('admin_menu', [$this, 'add_templates_menu']);
    }
    
    /**
     * تحميل القوالب
     */
    private function load_templates() {
        $this->templates = [
            // قوالب الأعمال
            'business_letter' => [
                'id' => 'business_letter',
                'name' => __('Business Letter', 'wp-office-editor'),
                'description' => __('Professional business letter template', 'wp-office-editor'),
                'category' => 'business',
                'type' => 'document',
                'icon' => 'fa-envelope',
                'color' => '#3498db',
                'content' => $this->get_business_letter_template(),
                'preview' => '',
                'tags' => ['business', 'letter', 'professional'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 95,
                'rating' => 4.5
            ],
            
            'invoice' => [
                'id' => 'invoice',
                'name' => __('Invoice', 'wp-office-editor'),
                'description' => __('Professional invoice template', 'wp-office-editor'),
                'category' => 'business',
                'type' => 'document',
                'icon' => 'fa-file-invoice-dollar',
                'color' => '#2ecc71',
                'content' => $this->get_invoice_template(),
                'preview' => '',
                'tags' => ['business', 'invoice', 'finance'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 85,
                'rating' => 4.3
            ],
            
            'report' => [
                'id' => 'report',
                'name' => __('Business Report', 'wp-office-editor'),
                'description' => __('Formal business report template', 'wp-office-editor'),
                'category' => 'business',
                'type' => 'document',
                'icon' => 'fa-chart-bar',
                'color' => '#9b59b6',
                'content' => $this->get_report_template(),
                'preview' => '',
                'tags' => ['business', 'report', 'analysis'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 80,
                'rating' => 4.2
            ],
            
            // قوالب التعليم
            'research_paper' => [
                'id' => 'research_paper',
                'name' => __('Research Paper', 'wp-office-editor'),
                'description' => __('Academic research paper template', 'wp-office-editor'),
                'category' => 'academic',
                'type' => 'document',
                'icon' => 'fa-graduation-cap',
                'color' => '#e74c3c',
                'content' => $this->get_research_paper_template(),
                'preview' => '',
                'tags' => ['academic', 'research', 'paper'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 75,
                'rating' => 4.4
            ],
            
            'lesson_plan' => [
                'id' => 'lesson_plan',
                'name' => __('Lesson Plan', 'wp-office-editor'),
                'description' => __('Educational lesson plan template', 'wp-office-editor'),
                'category' => 'academic',
                'type' => 'document',
                'icon' => 'fa-book',
                'color' => '#1abc9c',
                'content' => $this->get_lesson_plan_template(),
                'preview' => '',
                'tags' => ['academic', 'education', 'lesson'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 70,
                'rating' => 4.1
            ],
            
            // قوالب التسويق
            'blog_post' => [
                'id' => 'blog_post',
                'name' => __('Blog Post', 'wp-office-editor'),
                'description' => __('Professional blog post template', 'wp-office-editor'),
                'category' => 'marketing',
                'type' => 'document',
                'icon' => 'fa-blog',
                'color' => '#f39c12',
                'content' => $this->get_blog_post_template(),
                'preview' => '',
                'tags' => ['marketing', 'blog', 'content'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 90,
                'rating' => 4.6
            ],
            
            'social_media_plan' => [
                'id' => 'social_media_plan',
                'name' => __('Social Media Plan', 'wp-office-editor'),
                'description' => __('Social media content plan template', 'wp-office-editor'),
                'category' => 'marketing',
                'type' => 'document',
                'icon' => 'fa-share-alt',
                'color' => '#34495e',
                'content' => $this->get_social_media_plan_template(),
                'preview' => '',
                'tags' => ['marketing', 'social', 'plan'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 78,
                'rating' => 4.3
            ],
            
            // قوالب التطوير
            'project_proposal' => [
                'id' => 'project_proposal',
                'name' => __('Project Proposal', 'wp-office-editor'),
                'description' => __('Detailed project proposal template', 'wp-office-editor'),
                'category' => 'development',
                'type' => 'document',
                'icon' => 'fa-project-diagram',
                'color' => '#e67e22',
                'content' => $this->get_project_proposal_template(),
                'preview' => '',
                'tags' => ['development', 'project', 'proposal'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 82,
                'rating' => 4.4
            ],
            
            'meeting_agenda' => [
                'id' => 'meeting_agenda',
                'name' => __('Meeting Agenda', 'wp-office-editor'),
                'description' => __('Professional meeting agenda template', 'wp-office-editor'),
                'category' => 'business',
                'type' => 'document',
                'icon' => 'fa-calendar-alt',
                'color' => '#16a085',
                'content' => $this->get_meeting_agenda_template(),
                'preview' => '',
                'tags' => ['business', 'meeting', 'agenda'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 88,
                'rating' => 4.5
            ],
            
            'resume' => [
                'id' => 'resume',
                'name' => __('Professional Resume', 'wp-office-editor'),
                'description' => __('Modern professional resume template', 'wp-office-editor'),
                'category' => 'personal',
                'type' => 'document',
                'icon' => 'fa-user-tie',
                'color' => '#8e44ad',
                'content' => $this->get_resume_template(),
                'preview' => '',
                'tags' => ['personal', 'resume', 'cv'],
                'created_at' => current_time('mysql'),
                'modified_at' => current_time('mysql'),
                'author' => 'system',
                'popularity' => 92,
                'rating' => 4.7
            ]
        ];
        
        // تحميل القوالب المخصصة من الملفات
        $this->load_custom_templates();
    }
    
    /**
     * تحميل القوالب المخصصة من الملفات
     */
    private function load_custom_templates() {
        $template_files = glob($this->template_dir . '*.json');
        
        if (!$template_files) {
            return;
        }
        
        foreach ($template_files as $file) {
            $template_data = json_decode(file_get_contents($file), true);
            
            if ($template_data && isset($template_data['id'])) {
                $this->templates[$template_data['id']] = array_merge([
                    'type' => 'custom',
                    'file' => basename($file),
                    'created_at' => filemtime($file) ? date('Y-m-d H:i:s', filemtime($file)) : current_time('mysql'),
                    'modified_at' => filemtime($file) ? date('Y-m-d H:i:s', filemtime($file)) : current_time('mysql')
                ], $template_data);
            }
        }
    }
    
    /**
     * تحميل الفئات
     */
    private function load_categories() {
        $this->categories = [
            'business' => [
                'name' => __('Business', 'wp-office-editor'),
                'description' => __('Business and professional documents', 'wp-office-editor'),
                'icon' => 'fa-briefcase',
                'color' => '#3498db',
                'count' => 0
            ],
            'academic' => [
                'name' => __('Academic', 'wp-office-editor'),
                'description' => __('Educational and academic documents', 'wp-office-editor'),
                'icon' => 'fa-graduation-cap',
                'color' => '#e74c3c',
                'count' => 0
            ],
            'marketing' => [
                'name' => __('Marketing', 'wp-office-editor'),
                'description' => __('Marketing and advertising documents', 'wp-office-editor'),
                'icon' => 'fa-bullhorn',
                'color' => '#f39c12',
                'count' => 0
            ],
            'development' => [
                'name' => __('Development', 'wp-office-editor'),
                'description' => __('Project and development documents', 'wp-office-editor'),
                'icon' => 'fa-code',
                'color' => '#2ecc71',
                'count' => 0
            ],
            'personal' => [
                'name' => __('Personal', 'wp-office-editor'),
                'description' => __('Personal and lifestyle documents', 'wp-office-editor'),
                'icon' => 'fa-user',
                'color' => '#9b59b6',
                'count' => 0
            ],
            'creative' => [
                'name' => __('Creative', 'wp-office-editor'),
                'description' => __('Creative writing and design documents', 'wp-office-editor'),
                'icon' => 'fa-palette',
                'color' => '#1abc9c',
                'count' => 0
            ],
            'legal' => [
                'name' => __('Legal', 'wp-office-editor'),
                'description' => __('Legal and official documents', 'wp-office-editor'),
                'icon' => 'fa-balance-scale',
                'color' => '#34495e',
                'count' => 0
            ],
            'medical' => [
                'name' => __('Medical', 'wp-office-editor'),
                'description' => __('Medical and healthcare documents', 'wp-office-editor'),
                'icon' => 'fa-heartbeat',
                'color' => '#e67e22',
                'count' => 0
            ]
        ];
        
        // حساب عدد القوالب في كل فئة
        foreach ($this->templates as $template) {
            if (isset($template['category']) && isset($this->categories[$template['category']])) {
                $this->categories[$template['category']]['count']++;
            }
        }
    }
    
    /**
     * الحصول على قالب رسالة الأعمال
     */
    private function get_business_letter_template() {
        return <<<HTML
<div style="font-family: 'Calibri', sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto;">
    <!-- Header -->
    <div style="text-align: right; margin-bottom: 40px;">
        <div style="font-size: 12px; color: #666;">
            [Your Company Name]<br>
            [Your Address]<br>
            [City, State, ZIP Code]<br>
            [Phone Number]<br>
            [Email Address]<br>
            [Website]
        </div>
    </div>
    
    <!-- Date -->
    <div style="margin-bottom: 30px;">
        [Date]
    </div>
    
    <!-- Recipient -->
    <div style="margin-bottom: 30px;">
        [Recipient Name]<br>
        [Recipient Position]<br>
        [Company Name]<br>
        [Company Address]<br>
        [City, State, ZIP Code]
    </div>
    
    <!-- Salutation -->
    <div style="margin-bottom: 20px;">
        Dear [Recipient Name],
    </div>
    
    <!-- Body -->
    <div style="margin-bottom: 30px;">
        <p>I am writing to you today regarding [Subject of Letter]. This letter serves to [Purpose of Letter].</p>
        
        <p>[First Paragraph - Introduction and main purpose]</p>
        
        <p>[Second Paragraph - Details and supporting information]</p>
        
        <p>[Third Paragraph - Additional information or requests]</p>
        
        <p>[Fourth Paragraph - Conclusion and call to action]</p>
    </div>
    
    <!-- Closing -->
    <div style="margin-bottom: 40px;">
        <p>Sincerely,</p>
        <br><br>
        <p>[Your Name]<br>
        [Your Position]<br>
        [Your Company Name]</p>
    </div>
    
    <!-- Footer -->
    <div style="font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; margin-top: 40px;">
        This document was created using WP Office Editor Template System
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب الفاتورة
     */
    private function get_invoice_template() {
        return <<<HTML
<div style="font-family: 'Arial', sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 2px solid #3498db; padding-bottom: 20px;">
        <div>
            <h1 style="color: #3498db; margin: 0; font-size: 28px;">INVOICE</h1>
            <div style="color: #666; font-size: 14px;">
                #INV-[Invoice Number]<br>
                Date: [Invoice Date]<br>
                Due Date: [Due Date]
            </div>
        </div>
        <div style="text-align: right;">
            <h2 style="margin: 0; color: #333;">[Your Company Name]</h2>
            <div style="color: #666; font-size: 14px;">
                [Your Address]<br>
                [City, State, ZIP]<br>
                Phone: [Phone]<br>
                Email: [Email]
            </div>
        </div>
    </div>
    
    <!-- Billing Info -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
        <div>
            <h3 style="color: #333; margin-bottom: 10px;">Bill To:</h3>
            <div style="color: #666;">
                [Client Name]<br>
                [Client Company]<br>
                [Client Address]<br>
                [City, State, ZIP]
            </div>
        </div>
        <div>
            <h3 style="color: #333; margin-bottom: 10px;">Ship To:</h3>
            <div style="color: #666;">
                [Shipping Name]<br>
                [Shipping Company]<br>
                [Shipping Address]<br>
                [City, State, ZIP]
            </div>
        </div>
    </div>
    
    <!-- Items Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th style="text-align: left; padding: 12px; border: 1px solid #ddd;">Description</th>
                <th style="text-align: center; padding: 12px; border: 1px solid #ddd;">Quantity</th>
                <th style="text-align: right; padding: 12px; border: 1px solid #ddd;">Unit Price</th>
                <th style="text-align: right; padding: 12px; border: 1px solid #ddd;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 12px; border: 1px solid #ddd;">[Item Description 1]</td>
                <td style="text-align: center; padding: 12px; border: 1px solid #ddd;">[Qty]</td>
                <td style="text-align: right; padding: 12px; border: 1px solid #ddd;">$[Price]</td>
                <td style="text-align: right; padding: 12px; border: 1px solid #ddd;">$[Total]</td>
            </tr>
            <tr>
                <td style="padding: 12px; border: 1px solid #ddd;">[Item Description 2]</td>
                <td style="text-align: center; padding: 12px; border: 1px solid #ddd;">[Qty]</td>
                <td style="text-align: right; padding: 12px; border: 1px solid #ddd;">$[Price]</td>
                <td style="text-align: right; padding: 12px; border: 1px solid #ddd;">$[Total]</td>
            </tr>
            <!-- Add more rows as needed -->
        </tbody>
    </table>
    
    <!-- Totals -->
    <div style="float: right; width: 300px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; text-align: right;">Subtotal:</td>
                <td style="padding: 8px; text-align: right;">$[Subtotal]</td>
            </tr>
            <tr>
                <td style="padding: 8px; text-align: right;">Tax ([Tax Rate]%):</td>
                <td style="padding: 8px; text-align: right;">$[Tax Amount]</td>
            </tr>
            <tr>
                <td style="padding: 8px; text-align: right;">Shipping:</td>
                <td style="padding: 8px; text-align: right;">$[Shipping]</td>
            </tr>
            <tr style="font-weight: bold; font-size: 18px; border-top: 2px solid #3498db;">
                <td style="padding: 12px; text-align: right;">Total:</td>
                <td style="padding: 12px; text-align: right;">$[Total Amount]</td>
            </tr>
        </table>
    </div>
    
    <div style="clear: both;"></div>
    
    <!-- Payment Info -->
    <div style="margin-top: 50px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
        <h3 style="color: #333; margin-bottom: 10px;">Payment Information</h3>
        <p>Please make payment to:</p>
        <p><strong>Bank:</strong> [Bank Name]<br>
        <strong>Account Name:</strong> [Account Name]<br>
        <strong>Account Number:</strong> [Account Number]<br>
        <strong>SWIFT/BIC:</strong> [SWIFT Code]</p>
    </div>
    
    <!-- Terms -->
    <div style="margin-top: 30px; font-size: 12px; color: #666;">
        <p><strong>Terms & Conditions:</strong> Payment is due within [Number] days. Late payments are subject to a [Percentage]% monthly fee.</p>
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب التقرير
     */
    private function get_report_template() {
        return <<<HTML
<div style="font-family: 'Times New Roman', serif; line-height: 1.8; max-width: 800px; margin: 0 auto;">
    <!-- Title Page -->
    <div style="text-align: center; margin-top: 100px;">
        <h1 style="font-size: 36px; margin-bottom: 20px;">[Report Title]</h1>
        <h2 style="font-size: 24px; color: #666; margin-bottom: 40px;">[Report Subtitle]</h2>
        
        <div style="margin-bottom: 60px;">
            <p>Prepared for:</p>
            <p><strong>[Client/Organization Name]</strong></p>
        </div>
        
        <div style="margin-bottom: 60px;">
            <p>Prepared by:</p>
            <p><strong>[Your Name/Company]</strong><br>
            [Your Position]<br>
            [Date]</p>
        </div>
    </div>
    
    <!-- Table of Contents -->
    <div style="page-break-before: always; margin-top: 50px;">
        <h2 style="border-bottom: 2px solid #333; padding-bottom: 10px;">Table of Contents</h2>
        <ul style="list-style: none; padding-left: 0;">
            <li style="margin-bottom: 8px;">1.0 Executive Summary ................................................................. 2</li>
            <li style="margin-bottom: 8px;">2.0 Introduction ........................................................................... 3</li>
            <li style="margin-bottom: 8px;">3.0 Methodology .......................................................................... 4</li>
            <li style="margin-bottom: 8px;">4.0 Findings ............................................................................... 5</li>
            <li style="margin-bottom: 8px;">5.0 Analysis ............................................................................... 6</li>
            <li style="margin-bottom: 8px;">6.0 Recommendations ................................................................... 7</li>
            <li style="margin-bottom: 8px;">7.0 Conclusion ............................................................................ 8</li>
            <li style="margin-bottom: 8px;">Appendices ................................................................................ 9</li>
        </ul>
    </div>
    
    <!-- Executive Summary -->
    <div style="page-break-before: always; margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">1.0 Executive Summary</h2>
        <p>This report provides an overview of [report subject]. The key findings include:</p>
        <ul>
            <li>[Key finding 1]</li>
            <li>[Key finding 2]</li>
            <li>[Key finding 3]</li>
        </ul>
        <p>The report concludes with recommendations for [action items].</p>
    </div>
    
    <!-- Introduction -->
    <div style="margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">2.0 Introduction</h2>
        <h3>2.1 Background</h3>
        <p>[Background information and context]</p>
        
        <h3>2.2 Objectives</h3>
        <p>The main objectives of this report are:</p>
        <ol>
            <li>[Objective 1]</li>
            <li>[Objective 2]</li>
            <li>[Objective 3]</li>
        </ol>
        
        <h3>2.3 Scope</h3>
        <p>[Scope and limitations of the report]</p>
    </div>
    
    <!-- Methodology -->
    <div style="margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">3.0 Methodology</h2>
        <p>[Description of research methods, data collection, and analysis techniques]</p>
    </div>
    
    <!-- Findings -->
    <div style="margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">4.0 Findings</h2>
        <p>[Presentation of research findings with supporting data]</p>
        
        <h3>4.1 [Finding Category 1]</h3>
        <p>[Details of finding 1]</p>
        
        <h3>4.2 [Finding Category 2]</h3>
        <p>[Details of finding 2]</p>
    </div>
    
    <!-- Analysis -->
    <div style="margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">5.0 Analysis</h2>
        <p>[Analysis and interpretation of findings]</p>
    </div>
    
    <!-- Recommendations -->
    <div style="margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">6.0 Recommendations</h2>
        <p>Based on the analysis, the following recommendations are proposed:</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Recommendation</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Priority</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Timeline</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Recommendation 1]</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">High</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Timeline]</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Recommendation 2]</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">Medium</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Timeline]</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Conclusion -->
    <div style="margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">7.0 Conclusion</h2>
        <p>[Summary of report and final thoughts]</p>
    </div>
    
    <!-- Appendices -->
    <div style="margin-top: 50px;">
        <h2 style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">Appendices</h2>
        <h3>Appendix A: [Appendix Title]</h3>
        <p>[Appendix content]</p>
    </div>
    
    <!-- Footer -->
    <div style="margin-top: 100px; font-size: 11px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
        Confidential - For internal use only<br>
        Generated using WP Office Editor Professional Report Template
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب ورقة البحث
     */
    private function get_research_paper_template() {
        return <<<HTML
<div style="font-family: 'Times New Roman', serif; line-height: 2.0; max-width: 800px; margin: 0 auto;">
    <!-- Title -->
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 10px;">[Research Paper Title]</h1>
        <h2 style="font-size: 18px; color: #666; font-weight: normal; margin-bottom: 30px;">[Subtitle or Additional Information]</h2>
        
        <div style="margin-bottom: 20px;">
            <p><strong>[Author Name]</strong><br>
            [Author Affiliation]<br>
            [Email Address]</p>
        </div>
        
        <div>
            <p><em>Submitted: [Submission Date]<br>
            Revised: [Revision Date]<br>
            Accepted: [Acceptance Date]</em></p>
        </div>
    </div>
    
    <!-- Abstract -->
    <div style="margin-bottom: 40px; padding: 20px; background: #f8f9fa; border-left: 4px solid #3498db;">
        <h2 style="font-size: 16px; margin-bottom: 10px;">Abstract</h2>
        <p style="text-align: justify;">[Abstract content - Approximately 150-250 words summarizing the research problem, methodology, findings, and conclusions.]</p>
        
        <p style="margin-top: 10px;"><strong>Keywords:</strong> [Keyword 1], [Keyword 2], [Keyword 3], [Keyword 4], [Keyword 5]</p>
    </div>
    
    <!-- Introduction -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">1. Introduction</h2>
        <p style="text-align: justify;">[Introduction paragraph 1: Background and context of the research]</p>
        <p style="text-align: justify;">[Introduction paragraph 2: Problem statement and research gap]</p>
        <p style="text-align: justify;">[Introduction paragraph 3: Research objectives and questions]</p>
        <p style="text-align: justify;">[Introduction paragraph 4: Significance of the study]</p>
        <p style="text-align: justify;">[Introduction paragraph 5: Structure of the paper]</p>
    </div>
    
    <!-- Literature Review -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">2. Literature Review</h2>
        <h3 style="font-size: 16px; margin-bottom: 10px;">2.1 [Theme/Topic 1]</h3>
        <p style="text-align: justify;">[Review of relevant literature on theme 1]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">2.2 [Theme/Topic 2]</h3>
        <p style="text-align: justify;">[Review of relevant literature on theme 2]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">2.3 Theoretical Framework</h3>
        <p style="text-align: justify;">[Description of theoretical framework guiding the research]</p>
    </div>
    
    <!-- Methodology -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">3. Methodology</h2>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">3.1 Research Design</h3>
        <p style="text-align: justify;">[Description of research design - quantitative, qualitative, or mixed methods]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">3.2 Participants/Sample</h3>
        <p style="text-align: justify;">[Description of study participants or sample selection]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">3.3 Data Collection</h3>
        <p style="text-align: justify;">[Description of data collection methods and instruments]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">3.4 Data Analysis</h3>
        <p style="text-align: justify;">[Description of data analysis techniques]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">3.5 Ethical Considerations</h3>
        <p style="text-align: justify;">[Discussion of ethical issues and how they were addressed]</p>
    </div>
    
    <!-- Results -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">4. Results</h2>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">4.1 [Result Category 1]</h3>
        <p style="text-align: justify;">[Presentation of results for category 1]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">4.2 [Result Category 2]</h3>
        <p style="text-align: justify;">[Presentation of results for category 2]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">4.3 [Result Category 3]</h3>
        <p style="text-align: justify;">[Presentation of results for category 3]</p>
        
        <!-- Example Table -->
        <div style="margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <caption style="margin-bottom: 10px; font-weight: bold;">Table 1: [Table Title]</caption>
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Variable</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: center;">Mean</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: center;">SD</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: center;">t-value</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: center;">p-value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">[Variable 1]</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">[Value]</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">[Value]</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">[Value]</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">[Value]</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Discussion -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">5. Discussion</h2>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">5.1 Interpretation of Findings</h3>
        <p style="text-align: justify;">[Interpretation of key findings in relation to research questions]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">5.2 Comparison with Existing Literature</h3>
        <p style="text-align: justify;">[Comparison of findings with previous research]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">5.3 Theoretical Implications</h3>
        <p style="text-align: justify;">[Discussion of theoretical implications]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">5.4 Practical Implications</h3>
        <p style="text-align: justify;">[Discussion of practical implications]</p>
    </div>
    
    <!-- Conclusion -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">6. Conclusion</h2>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">6.1 Summary of Findings</h3>
        <p style="text-align: justify;">[Summary of main findings]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">6.2 Limitations</h3>
        <p style="text-align: justify;">[Discussion of study limitations]</p>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">6.3 Suggestions for Future Research</h3>
        <p style="text-align: justify;">[Recommendations for future research]</p>
    </div>
    
    <!-- References -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">References</h2>
        
        <div style="font-size: 14px;">
            <p style="margin-bottom: 10px; text-indent: -20px; padding-left: 20px;">Author, A. A. (Year). <em>Title of work</em>. Publisher.</p>
            <p style="margin-bottom: 10px; text-indent: -20px; padding-left: 20px;">Author, B. B., & Author, C. C. (Year). Title of article. <em>Journal Name, Volume</em>(Issue), Page range. DOI</p>
            <p style="margin-bottom: 10px; text-indent: -20px; padding-left: 20px;">Author, D. D. (Year, Month Day). Title of webpage. <em>Website Name</em>. URL</p>
        </div>
    </div>
    
    <!-- Appendices -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 18px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 15px;">Appendices</h2>
        
        <h3 style="font-size: 16px; margin-bottom: 10px;">Appendix A: [Appendix Title]</h3>
        <p style="text-align: justify;">[Appendix content]</p>
    </div>
    
    <!-- Footer -->
    <div style="margin-top: 60px; padding-top: 20px; border-top: 1px solid #eee; font-size: 11px; color: #999; text-align: center;">
        Word count: [Word Count] | Pages: [Page Count]<br>
        Academic Paper Template - WP Office Editor
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب خطة الدرس
     */
    private function get_lesson_plan_template() {
        return <<<HTML
<div style="font-family: 'Arial', sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); color: white; padding: 30px; border-radius: 8px;">
        <h1 style="margin: 0 0 10px 0; font-size: 32px;">Lesson Plan</h1>
        <h2 style="margin: 0; font-size: 20px; font-weight: normal;">[Subject/Course Name]</h2>
    </div>
    
    <!-- Basic Information -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Teacher Information</h3>
            <div style="color: #666;">
                <p><strong>Teacher:</strong> [Teacher Name]</p>
                <p><strong>Grade Level:</strong> [Grade Level]</p>
                <p><strong>Subject:</strong> [Subject]</p>
                <p><strong>Date:</strong> [Date]</p>
                <p><strong>Time:</strong> [Time]</p>
                <p><strong>Duration:</strong> [Duration] minutes</p>
            </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Class Information</h3>
            <div style="color: #666;">
                <p><strong>Class:</strong> [Class Name]</p>
                <p><strong>Room:</strong> [Room Number]</p>
                <p><strong>Number of Students:</strong> [Number]</p>
                <p><strong>Prerequisites:</strong> [Prerequisites]</p>
            </div>
        </div>
    </div>
    
    <!-- Learning Objectives -->
    <div style="margin-bottom: 30px; padding: 20px; background: #e8f4fc; border-radius: 5px; border-left: 4px solid #3498db;">
        <h2 style="color: #3498db; margin: 0 0 15px 0; font-size: 20px;">Learning Objectives</h2>
        <p>By the end of this lesson, students will be able to:</p>
        <ul style="margin-left: 20px;">
            <li>[Objective 1]</li>
            <li>[Objective 2]</li>
            <li>[Objective 3]</li>
            <li>[Objective 4]</li>
        </ul>
        <p><strong>Standards Alignment:</strong> [Relevant Standards]</p>
    </div>
    
    <!-- Materials and Resources -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #333; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; font-size: 20px;">Materials and Resources</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 15px;">
            <div>
                <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">Teacher Materials</h3>
                <ul>
                    <li>[Material 1]</li>
                    <li>[Material 2]</li>
                    <li>[Material 3]</li>
                    <li>[Material 4]</li>
                </ul>
            </div>
            
            <div>
                <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">Student Materials</h3>
                <ul>
                    <li>[Material 1]</li>
                    <li>[Material 2]</li>
                    <li>[Material 3]</li>
                    <li>[Material 4]</li>
                </ul>
            </div>
        </div>
        
        <div style="margin-top: 15px;">
            <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">Technology Requirements</h3>
            <ul>
                <li>[Technology 1]</li>
                <li>[Technology 2]</li>
                <li>[Technology 3]</li>
            </ul>
        </div>
    </div>
    
    <!-- Lesson Procedure -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #333; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; font-size: 20px;">Lesson Procedure</h2>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <thead>
                <tr style="background: #2c3e50; color: white;">
                    <th style="padding: 12px; text-align: left; width: 20%;">Time</th>
                    <th style="padding: 12px; text-align: left; width: 30%;">Activity</th>
                    <th style="padding: 12px; text-align: left; width: 50%;">Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">[5-10 min]</td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;"><strong>Warm-up / Hook</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">
                        <p>[Engage students and activate prior knowledge]</p>
                        <p><em>Teacher Actions:</em> [What the teacher will do]</p>
                        <p><em>Student Actions:</em> [What students will do]</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">[15-20 min]</td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;"><strong>Direct Instruction</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">
                        <p>[Introduce new concepts and skills]</p>
                        <p><em>Teacher Actions:</em> [What the teacher will do]</p>
                        <p><em>Student Actions:</em> [What students will do]</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">[20-25 min]</td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;"><strong>Guided Practice</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">
                        <p>[Students practice with teacher support]</p>
                        <p><em>Teacher Actions:</em> [What the teacher will do]</p>
                        <p><em>Student Actions:</em> [What students will do]</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">[15-20 min]</td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;"><strong>Independent Practice</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">
                        <p>[Students work independently]</p>
                        <p><em>Teacher Actions:</em> [What the teacher will do]</p>
                        <p><em>Student Actions:</em> [What students will do]</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">[5-10 min]</td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;"><strong>Closure / Assessment</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd; vertical-align: top;">
                        <p>[Review and assess learning]</p>
                        <p><em>Teacher Actions:</em> [What the teacher will do]</p>
                        <p><em>Student Actions:</em> [What students will do]</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Differentiation -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #333; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; font-size: 20px;">Differentiation Strategies</h2>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 15px;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">For Struggling Students</h3>
                <ul>
                    <li>[Strategy 1]</li>
                    <li>[Strategy 2]</li>
                    <li>[Strategy 3]</li>
                </ul>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">For Advanced Students</h3>
                <ul>
                    <li>[Strategy 1]</li>
                    <li>[Strategy 2]</li>
                    <li>[Strategy 3]</li>
                </ul>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">For English Learners</h3>
                <ul>
                    <li>[Strategy 1]</li>
                    <li>[Strategy 2]</li>
                    <li>[Strategy 3]</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Assessment -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #333; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; font-size: 20px;">Assessment</h2>
        
        <div style="margin-top: 15px;">
            <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">Formative Assessment</h3>
            <ul>
                <li>[Assessment method 1]</li>
                <li>[Assessment method 2]</li>
                <li>[Assessment method 3]</li>
            </ul>
            
            <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px; margin-top: 15px;">Summative Assessment</h3>
            <ul>
                <li>[Assessment method 1]</li>
                <li>[Assessment method 2]</li>
            </ul>
            
            <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px; margin-top: 15px;">Rubric</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Criteria</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Excellent (4)</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Good (3)</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Satisfactory (2)</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Needs Improvement (1)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">[Criterion 1]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">[Criterion 2]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">[Description]</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Homework/Extensions -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #333; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; font-size: 20px;">Homework & Extensions</h2>
        
        <div style="margin-top: 15px;">
            <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px;">Homework Assignment</h3>
            <p>[Homework description and instructions]</p>
            
            <h3 style="color: #2c3e50; font-size: 16px; margin-bottom: 10px; margin-top: 15px;">Extension Activities</h3>
            <ul>
                <li>[Activity 1]</li>
                <li>[Activity 2]</li>
                <li>[Activity 3]</li>
            </ul>
        </div>
    </div>
    
    <!-- Reflection -->
    <div style="margin-bottom: 30px; padding: 20px; background: #fff8e1; border-radius: 5px; border-left: 4px solid #f39c12;">
        <h2 style="color: #f39c12; margin: 0 0 15px 0; font-size: 20px;">Teacher Reflection</h2>
        <p><strong>What worked well:</strong> [Reflection on successful aspects]</p>
        <p><strong>Challenges:</strong> [Reflection on challenges faced]</p>
        <p><strong>Adjustments for next time:</strong> [Planned adjustments]</p>
        <p><strong>Student Feedback:</strong> [Notes on student responses]</p>
    </div>
    
    <!-- Footer -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center;">
        <p>Lesson Plan Template - WP Office Editor</p>
        <p>Total Time: [Total Minutes] minutes | Prepared by: [Teacher Name] | Last Updated: [Date]</p>
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب مقالة المدونة
     */
    private function get_blog_post_template() {
        return <<<HTML
<div style="font-family: 'Georgia', serif; line-height: 1.8; max-width: 700px; margin: 0 auto; color: #333;">
    <!-- Title Section -->
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 36px; line-height: 1.2; margin-bottom: 20px; color: #2c3e50;">[Blog Post Title - Catchy and SEO-Friendly]</h1>
        
        <div style="display: flex; justify-content: center; align-items: center; gap: 20px; color: #7f8c8d; font-size: 14px; margin-bottom: 30px;">
            <span style="display: flex; align-items: center; gap: 5px;">
                <i class="fa fa-user"></i> [Author Name]
            </span>
            <span style="display: flex; align-items: center; gap: 5px;">
                <i class="fa fa-calendar"></i> [Publication Date]
            </span>
            <span style="display: flex; align-items: center; gap: 5px;">
                <i class="fa fa-clock"></i> [Reading Time] min read
            </span>
            <span style="display: flex; align-items: center; gap: 5px;">
                <i class="fa fa-tag"></i> [Primary Category]
            </span>
        </div>
        
        <!-- Featured Image -->
        <div style="margin-bottom: 30px;">
            <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px;">
                [Featured Image Placeholder]
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 14px; margin-top: 10px;">Recommended size: 1200×628px</div>
                </div>
            </div>
            <p style="font-size: 14px; color: #7f8c8d; margin-top: 10px; text-align: center;"><em>[Caption for featured image]</em></p>
        </div>
    </div>
    
    <!-- Introduction -->
    <div style="margin-bottom: 40px;">
        <p style="font-size: 20px; line-height: 1.6; color: #2c3e50; font-weight: 500; margin-bottom: 20px;">
            [Hook sentence that grabs attention]. [Brief overview of what the post will cover and why it matters to the reader].
        </p>
        
        <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #3498db; margin-top: 20px;">
            <p style="margin: 0; font-style: italic; color: #2c3e50;">
                <strong>TL;DR:</strong> [Quick summary for readers who want the main points fast]
            </p>
        </div>
    </div>
    
    <!-- Table of Contents -->
    <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 40px;">
        <h2 style="color: #2c3e50; margin-top: 0; margin-bottom: 15px; font-size: 22px;">📋 What You'll Learn in This Article</h2>
        <ul style="margin: 0; padding-left: 20px;">
            <li>[Key point 1 that readers will learn]</li>
            <li>[Key point 2 that readers will learn]</li>
            <li>[Key point 3 that readers will learn]</li>
            <li>[Key point 4 that readers will learn]</li>
            <li>[Key point 5 that readers will learn]</li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">[First Main Section Heading]</h2>
        
        <p>[First paragraph - Develop the main idea with supporting details]</p>
        
        <div style="background: #e8f4fc; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="color: #3498db; margin-top: 0; margin-bottom: 10px; font-size: 18px;">💡 Pro Tip</h3>
            <p style="margin: 0;">[Helpful tip or insight related to the content]</p>
        </div>
        
        <p>[Second paragraph - Continue developing the idea with examples or data]</p>
        
        <!-- Example List -->
        <div style="margin: 20px 0;">
            <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">[Subheading for List]</h3>
            <ul>
                <li><strong>[List item 1]:</strong> [Description]</li>
                <li><strong>[List item 2]:</strong> [Description]</li>
                <li><strong>[List item 3]:</strong> [Description]</li>
                <li><strong>[List item 4]:</strong> [Description]</li>
            </ul>
        </div>
        
        <p>[Third paragraph - Additional information or transition to next section]</p>
    </div>
    
    <!-- Second Section -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">[Second Main Section Heading]</h2>
        
        <p>[First paragraph of second section]</p>
        
        <!-- Blockquote -->
        <div style="border-left: 4px solid #2ecc71; padding-left: 20px; margin: 20px 0; font-style: italic; color: #555;">
            <p>"[Relevant quote or important statement that supports your point]"</p>
            <p style="font-style: normal; font-size: 14px; color: #7f8c8d; margin-top: 10px;">— [Source of quote]</p>
        </div>
        
        <p>[Continue with more content...]</p>
        
        <!-- Comparison Table -->
        <div style="margin: 30px 0;">
            <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">Comparison Table</h3>
            <table style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background: #2c3e50; color: white;">
                        <th style="padding: 15px; text-align: left;">Feature</th>
                        <th style="padding: 15px; text-align: left;">Option A</th>
                        <th style="padding: 15px; text-align: left;">Option B</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 15px; border: 1px solid #eee;">[Feature 1]</td>
                        <td style="padding: 15px; border: 1px solid #eee;">[Description]</td>
                        <td style="padding: 15px; border: 1px solid #eee;">[Description]</td>
                    </tr>
                    <tr style="background: #f8f9fa;">
                        <td style="padding: 15px; border: 1px solid #eee;">[Feature 2]</td>
                        <td style="padding: 15px; border: 1px solid #eee;">[Description]</td>
                        <td style="padding: 15px; border: 1px solid #eee;">[Description]</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Step-by-Step Guide -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">Step-by-Step Guide</h2>
        
        <div style="counter-reset: step-counter; margin-left: 20px;">
            <div style="position: relative; margin-bottom: 30px; padding-left: 40px;">
                <div style="position: absolute; left: 0; top: 0; width: 30px; height: 30px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    1
                </div>
                <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 10px; font-size: 20px;">[Step 1: Clear Action]</h3>
                <p>[Detailed instructions for step 1]</p>
            </div>
            
            <div style="position: relative; margin-bottom: 30px; padding-left: 40px;">
                <div style="position: absolute; left: 0; top: 0; width: 30px; height: 30px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    2
                </div>
                <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 10px; font-size: 20px;">[Step 2: Clear Action]</h3>
                <p>[Detailed instructions for step 2]</p>
            </div>
        </div>
    </div>
    
    <!-- Conclusion -->
    <div style="margin-bottom: 40px; padding: 30px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px;">
        <h2 style="color: #2c3e50; margin-top: 0; margin-bottom: 20px; font-size: 28px;">Conclusion</h2>
        
        <p style="font-size: 18px; margin-bottom: 20px;">[Summarize the main points of the article. Reinforce the key takeaways.]</p>
        
        <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px;">
            <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 15px; font-size: 20px;">🔑 Key Takeaways</h3>
            <ul style="margin: 0; padding-left: 20px;">
                <li>[Takeaway 1]</li>
                <li>[Takeaway 2]</li>
                <li>[Takeaway 3]</li>
            </ul>
        </div>
        
        <p style="margin-top: 20px;">[Final thought or call to action. What should readers do next?]</p>
    </div>
    
    <!-- FAQ Section -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">Frequently Asked Questions</h2>
        
        <div style="margin-bottom: 20px;">
            <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 18px;">Q: [Common question 1]</h3>
            <p>A: [Clear, concise answer to question 1]</p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 18px;">Q: [Common question 2]</h3>
            <p>A: [Clear, concise answer to question 2]</p>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div style="text-align: center; padding: 40px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); color: white; border-radius: 8px; margin-bottom: 40px;">
        <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 28px;">[Call to Action Headline]</h2>
        <p style="font-size: 18px; margin-bottom: 30px;">[Persuasive text encouraging readers to take action]</p>
        <button style="background: white; color: #3498db; border: none; padding: 15px 40px; font-size: 18px; border-radius: 50px; cursor: pointer; font-weight: bold;">
            [Action Button Text]
        </button>
    </div>
    
    <!-- About Author -->
    <div style="display: flex; gap: 20px; padding: 30px; background: #f8f9fa; border-radius: 8px; margin-bottom: 40px;">
        <div style="flex-shrink: 0;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
        </div>
        <div>
            <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 10px; font-size: 22px;">About the Author</h3>
            <p><strong>[Author Name]</strong> is [author's credentials and background]. [Brief bio highlighting expertise].</p>
            <div style="display: flex; gap: 15px; margin-top: 15px;">
                <a href="#" style="color: #3498db; text-decoration: none;">Twitter</a>
                <a href="#" style="color: #3498db; text-decoration: none;">LinkedIn</a>
                <a href="#" style="color: #3498db; text-decoration: none;">Website</a>
            </div>
        </div>
    </div>
    
    <!-- Related Posts -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">📚 You Might Also Like</h2>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="height: 150px; background: #f8f9fa;"></div>
                <div style="padding: 15px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 16px;">[Related Post Title 1]</h4>
                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 14px;">Read More →</a>
                </div>
            </div>
            
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="height: 150px; background: #f8f9fa;"></div>
                <div style="padding: 15px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 16px;">[Related Post Title 2]</h4>
                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 14px;">Read More →</a>
                </div>
            </div>
            
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="height: 150px; background: #f8f9fa;"></div>
                <div style="padding: 15px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 16px;">[Related Post Title 3]</h4>
                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 14px;">Read More →</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding-top: 40px; border-top: 1px solid #eee; color: #7f8c8d; font-size: 14px;">
        <p>Published on [Publication Date] | Last updated: [Update Date] | Word count: [Word Count]</p>
        <p style="margin-top: 10px;">
            <a href="#" style="color: #3498db; margin: 0 10px;">Share on Twitter</a> •
            <a href="#" style="color: #3498db; margin: 0 10px;">Share on Facebook</a> •
            <a href="#" style="color: #3498db; margin: 0 10px;">Share on LinkedIn</a>
        </p>
        <p style="margin-top: 20px; font-size: 12px;">© [Year] [Blog Name]. All rights reserved.</p>
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب خطة وسائل التواصل الاجتماعي
     */
    private function get_social_media_plan_template() {
        return <<<HTML
<div style="font-family: 'Arial', sans-serif; line-height: 1.6; max-width: 900px; margin: 0 auto; color: #333;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 40px; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px;">
        <h1 style="margin: 0 0 10px 0; font-size: 36px;">Social Media Content Plan</h1>
        <h2 style="margin: 0; font-size: 22px; font-weight: normal;">[Campaign/Project Name]</h2>
        <div style="display: flex; justify-content: center; gap: 30px; margin-top: 20px; font-size: 16px;">
            <div>
                <strong>Period:</strong> [Start Date] - [End Date]
            </div>
            <div>
                <strong>Prepared by:</strong> [Your Name]
            </div>
            <div>
                <strong>Version:</strong> [Version Number]
            </div>
        </div>
    </div>
    
    <!-- Executive Summary -->
    <div style="margin-bottom: 40px; padding: 25px; background: #f0f7ff; border-radius: 8px; border-left: 4px solid #3498db;">
        <h2 style="color: #3498db; margin-top: 0; margin-bottom: 15px; font-size: 24px;">📊 Executive Summary</h2>
        <p><strong>Campaign Objective:</strong> [Primary goal of the social media campaign]</p>
        <p><strong>Target Audience:</strong> [Description of target audience]</p>
        <p><strong>Key Messages:</strong> [Core messages to communicate]</p>
        <p><strong>Success Metrics:</strong> [How success will be measured]</p>
    </div>
    
    <!-- Platform Strategy -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">🌐 Platform Strategy</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
            <!-- Facebook -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3b5998;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: #3b5998; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        F
                    </div>
                    <h3 style="margin: 0; color: #3b5998; font-size: 20px;">Facebook</h3>
                </div>
                <p><strong>Purpose:</strong> [Primary use for this platform]</p>
                <p><strong>Posting Frequency:</strong> [Times per week/day]</p>
                <p><strong>Content Mix:</strong> [Percentage of different content types]</p>
                <p><strong>Goals:</strong> [Specific platform goals]</p>
            </div>
            
            <!-- Instagram -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #E1306C;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(45deg, #405DE6, #E1306C, #FFDC80); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        IG
                    </div>
                    <h3 style="margin: 0; color: #E1306C; font-size: 20px;">Instagram</h3>
                </div>
                <p><strong>Purpose:</strong> [Primary use for this platform]</p>
                <p><strong>Posting Frequency:</strong> [Times per week/day]</p>
                <p><strong>Content Mix:</strong> [Percentage of different content types]</p>
                <p><strong>Goals:</strong> [Specific platform goals]</p>
            </div>
            
            <!-- Twitter -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #1DA1F2;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: #1DA1F2; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        🐦
                    </div>
                    <h3 style="margin: 0; color: #1DA1F2; font-size: 20px;">Twitter</h3>
                </div>
                <p><strong>Purpose:</strong> [Primary use for this platform]</p>
                <p><strong>Posting Frequency:</strong> [Times per week/day]</p>
                <p><strong>Content Mix:</strong> [Percentage of different content types]</p>
                <p><strong>Goals:</strong> [Specific platform goals]</p>
            </div>
            
            <!-- LinkedIn -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0077B5;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: #0077B5; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        in
                    </div>
                    <h3 style="margin: 0; color: #0077B5; font-size: 20px;">LinkedIn</h3>
                </div>
                <p><strong>Purpose:</strong> [Primary use for this platform]</p>
                <p><strong>Posting Frequency:</strong> [Times per week/day]</p>
                <p><strong>Content Mix:</strong> [Percentage of different content types]</p>
                <p><strong>Goals:</strong> [Specific platform goals]</p>
            </div>
        </div>
    </div>
    
    <!-- Content Calendar -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">📅 Weekly Content Calendar</h2>
        
        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <thead>
                <tr style="background: #2c3e50; color: white;">
                    <th style="padding: 12px; text-align: left; width: 10%;">Day</th>
                    <th style="padding: 12px; text-align: left; width: 15%;">Platform</th>
                    <th style="padding: 12px; text-align: left; width: 25%;">Content Type</th>
                    <th style="padding: 12px; text-align: left; width: 30%;">Content Description</th>
                    <th style="padding: 12px; text-align: left; width: 20%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <!-- Week 1 -->
                <tr style="background: #f8f9fa;">
                    <td colspan="5" style="padding: 10px; font-weight: bold; color: #2c3e50;">Week 1: [Theme/Topic]</td>
                </tr>
                
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;">Monday</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="color: #3b5998; font-weight: bold;">Facebook</span><br>
                        <span style="font-size: 12px; color: #666;">9:00 AM</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Educational</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <strong>[Post Title]</strong><br>
                        [Brief description of content]
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: #ffebee; color: #d32f2f; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Pending</span>
                    </td>
                </tr>
                
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;">Tuesday</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="color: #E1306C; font-weight: bold;">Instagram</span><br>
                        <span style="font-size: 12px; color: #666;">11:00 AM</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: #f3e5f5; color: #7b1fa2; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Visual</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <strong>[Post Title]</strong><br>
                        [Brief description of content]
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: #fff3e0; color: #f57c00; padding: 4px 8px; border-radius: 4px; font-size: 12px;">In Progress</span>
                    </td>
                </tr>
                
                <!-- Add more days as needed -->
                
            </tbody>
        </table>
    </div>
    
    <!-- Content Pillars -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">🎯 Content Pillars</h2>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <div style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; margin-bottom: 10px;">📚</div>
                <h3 style="margin: 0 0 10px 0; font-size: 18px;">Educational</h3>
                <p style="margin: 0; font-size: 14px;">[Percentage]% of content</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; margin-bottom: 10px;">🎉</div>
                <h3 style="margin: 0 0 10px 0; font-size: 18px;">Entertainment</h3>
                <p style="margin: 0; font-size: 14px;">[Percentage]% of content</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; margin-bottom: 10px;">💬</div>
                <h3 style="margin: 0 0 10px 0; font-size: 18px;">Conversational</h3>
                <p style="margin: 0; font-size: 14px;">[Percentage]% of content</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; margin-bottom: 10px;">🎯</div>
                <h3 style="margin: 0 0 10px 0; font-size: 18px;">Promotional</h3>
                <p style="margin: 0; font-size: 14px;">[Percentage]% of content</p>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 20px;">Content Pillar Breakdown</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Pillar</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Purpose</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Example Topics</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">Educational</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">[Purpose description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <ul style="margin: 0; padding-left: 15px;">
                                <li>[Topic 1]</li>
                                <li>[Topic 2]</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">Entertainment</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">[Purpose description]</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <ul style="margin: 0; padding-left: 15px;">
                                <li>[Topic 1]</li>
                                <li>[Topic 2]</li>
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Campaign Timeline -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">⏰ Campaign Timeline</h2>
        
        <div style="position: relative; padding-left: 30px; border-left: 2px solid #3498db; margin-left: 15px;">
            <!-- Phase 1 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -40px; top: 0; width: 30px; height: 30px; background: #3498db; border-radius: 50%;"></div>
                <div style="background: #e8f4fc; padding: 20px; border-radius: 8px;">
                    <h3 style="color: #3498db; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 1: Planning & Preparation</h3>
                    <p><strong>Duration:</strong> [Start Date] - [End Date]</p>
                    <p><strong>Key Activities:</strong></p>
                    <ul>
                        <li>[Activity 1]</li>
                        <li>[Activity 2]</li>
                        <li>[Activity 3]</li>
                    </ul>
                </div>
            </div>
            
            <!-- Phase 2 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -40px; top: 0; width: 30px; height: 30px; background: #2ecc71; border-radius: 50%;"></div>
                <div style="background: #e8f6f3; padding: 20px; border-radius: 8px;">
                    <h3 style="color: #2ecc71; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 2: Content Creation</h3>
                    <p><strong>Duration:</strong> [Start Date] - [End Date]</p>
                    <p><strong>Key Activities:</strong></p>
                    <ul>
                        <li>[Activity 1]</li>
                        <li>[Activity 2]</li>
                        <li>[Activity 3]</li>
                    </ul>
                </div>
            </div>
            
            <!-- Phase 3 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -40px; top: 0; width: 30px; height: 30px; background: #9b59b6; border-radius: 50%;"></div>
                <div style="background: #f3e5f5; padding: 20px; border-radius: 8px;">
                    <h3 style="color: #9b59b6; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 3: Execution</h3>
                    <p><strong>Duration:</strong> [Start Date] - [End Date]</p>
                    <p><strong>Key Activities:</strong></p>
                    <ul>
                        <li>[Activity 1]</li>
                        <li>[Activity 2]</li>
                        <li>[Activity 3]</li>
                    </ul>
                </div>
            </div>
            
            <!-- Phase 4 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -40px; top: 0; width: 30px; height: 30px; background: #e74c3c; border-radius: 50%;"></div>
                <div style="background: #ffebee; padding: 20px; border-radius: 8px;">
                    <h3 style="color: #e74c3c; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 4: Analysis & Reporting</h3>
                    <p><strong>Duration:</strong> [Start Date] - [End Date]</p>
                    <p><strong>Key Activities:</strong></p>
                    <ul>
                        <li>[Activity 1]</li>
                        <li>[Activity 2]</li>
                        <li>[Activity 3]</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- KPIs and Metrics -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">📈 KPIs & Success Metrics</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; color: #3498db; margin-bottom: 10px;">🎯</div>
                <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px;">Engagement Rate</h3>
                <p style="margin: 0; font-size: 14px; color: #666;">Target: [Target percentage]%</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Current: [Current percentage]%</p>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; color: #2ecc71; margin-bottom: 10px;">👥</div>
                <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px;">Follower Growth</h3>
                <p style="margin: 0; font-size: 14px; color: #666;">Target: [Target number]</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Current: [Current number]</p>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; color: #9b59b6; margin-bottom: 10px;">🔗</div>
                <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px;">Click-through Rate</h3>
                <p style="margin: 0; font-size: 14px; color: #666;">Target: [Target percentage]%</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Current: [Current percentage]%</p>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; color: #e74c3c; margin-bottom: 10px;">💬</div>
                <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px;">Comments/Shares</h3>
                <p style="margin: 0; font-size: 14px; color: #666;">Target: [Target number]</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Current: [Current number]</p>
            </div>
        </div>
        
        <div>
            <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 20px;">Measurement Tools</h3>
            <ul>
                <li><strong>[Tool 1]:</strong> [What it measures]</li>
                <li><strong>[Tool 2]:</strong> [What it measures]</li>
                <li><strong>[Tool 3]:</strong> [What it measures]</li>
            </ul>
        </div>
    </div>
    
    <!-- Budget and Resources -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">💰 Budget & Resources</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 20px;">Budget Allocation</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Category</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Amount</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">Content Creation</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">$[Amount]</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">[Percentage]%</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">Paid Advertising</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">$[Amount]</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">[Percentage]%</td>
                        </tr>
                        <tr style="font-weight: bold; background: #f8f9fa;">
                            <td style="padding: 10px; border: 1px solid #ddd;">Total Budget</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">$[Total Amount]</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 20px;">Team Resources</h3>
                <ul>
                    <li><strong>[Role 1]:</strong> [Name] - [Responsibilities]</li>
                    <li><strong>[Role 2]:</strong> [Name] - [Responsibilities]</li>
                    <li><strong>[Role 3]:</strong> [Name] - [Responsibilities]</li>
                </ul>
                
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 20px; margin-top: 20px;">Tools & Software</h3>
                <ul>
                    <li>[Tool 1] - [Purpose]</li>
                    <li>[Tool 2] - [Purpose]</li>
                    <li>[Tool 3] - [Purpose]</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Risk Management -->
    <div style="margin-bottom: 40px; padding: 25px; background: #fff8e1; border-radius: 8px; border-left: 4px solid #f39c12;">
        <h2 style="color: #f39c12; margin-top: 0; margin-bottom: 15px; font-size: 24px;">⚠️ Risk Management</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #fff3cd;">
                    <th style="padding: 10px; border: 1px solid #f39c12; text-align: left;">Risk</th>
                    <th style="padding: 10px; border: 1px solid #f39c12; text-align: left;">Impact</th>
                    <th style="padding: 10px; border: 1px solid #f39c12; text-align: left;">Mitigation Strategy</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border: 1px solid #f39c12;">[Risk 1 - e.g., Negative comments]</td>
                    <td style="padding: 10px; border: 1px solid #f39c12;">[Impact level - High/Medium/Low]</td>
                    <td style="padding: 10px; border: 1px solid #f39c12;">[How to mitigate this risk]</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #f39c12;">[Risk 2 - e.g., Platform algorithm changes]</td>
                    <td style="padding: 10px; border: 1px solid #f39c12;">[Impact level - High/Medium/Low]</td>
                    <td style="padding: 10px; border: 1px solid #f39c12;">[How to mitigate this risk]</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Approval -->
    <div style="margin-bottom: 40px; padding: 25px; background: #e8f6f3; border-radius: 8px; border: 1px solid #2ecc71;">
        <h2 style="color: #2ecc71; margin-top: 0; margin-bottom: 20px; font-size: 24px;">✅ Approval & Sign-off</h2>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
            <div>
                <p><strong>Prepared by:</strong></p>
                <div style="height: 2px; background: #2ecc71; margin: 20px 0;"></div>
                <p>[Name]<br>[Position]<br>[Date]</p>
            </div>
            
            <div>
                <p><strong>Reviewed by:</strong></p>
                <div style="height: 2px; background: #2ecc71; margin: 20px 0;"></div>
                <p>[Name]<br>[Position]<br>[Date]</p>
            </div>
            
            <div>
                <p><strong>Approved by:</strong></p>
                <div style="height: 2px; background: #2ecc71; margin: 20px 0;"></div>
                <p>[Name]<br>[Position]<br>[Date]</p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding-top: 40px; border-top: 1px solid #eee; color: #7f8c8d; font-size: 14px;">
        <p><strong>Confidential Document - For Internal Use Only</strong></p>
        <p>Social Media Plan Template v1.0 | WP Office Editor</p>
        <p>Generated on: [Generation Date] | Last Updated: [Last Updated Date]</p>
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب عرض المشروع
     */
    private function get_project_proposal_template() {
        return <<<HTML
<div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; max-width: 850px; margin: 0 auto; color: #333;">
    <!-- Cover Page -->
    <div style="text-align: center; padding: 60px 0; border-bottom: 1px solid #eee; margin-bottom: 40px;">
        <div style="margin-bottom: 40px;">
            <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); margin: 0 auto 30px; border-radius: 8px;"></div>
            <h1 style="font-size: 42px; color: #2c3e50; margin-bottom: 10px;">PROJECT PROPOSAL</h1>
            <h2 style="font-size: 28px; color: #3498db; font-weight: normal; margin-bottom: 30px;">[Project Name]</h2>
        </div>
        
        <div style="display: flex; justify-content: center; gap: 40px; font-size: 16px; color: #666;">
            <div>
                <p><strong>Prepared for:</strong><br>
                [Client/Organization Name]<br>
                [Client Position/Department]</p>
            </div>
            <div>
                <p><strong>Prepared by:</strong><br>
                [Your Company/Name]<br>
                [Your Position]</p>
            </div>
        </div>
        
        <div style="margin-top: 40px;">
            <p><strong>Submission Date:</strong> [Date]<br>
            <strong>Proposal Valid Until:</strong> [Expiry Date]</p>
        </div>
    </div>
    
    <!-- Executive Summary -->
    <div style="margin-bottom: 40px; page-break-before: always;">
        <div style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; padding: 25px; border-radius: 8px 8px 0 0;">
            <h2 style="margin: 0; font-size: 24px;">EXECUTIVE SUMMARY</h2>
        </div>
        <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px;">
            <p style="font-size: 18px; line-height: 1.8; color: #2c3e50;">
                [A concise overview of the entire proposal. This should be 2-3 paragraphs summarizing the problem, solution, benefits, and key recommendations.]
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px;">
                <div style="text-align: center;">
                    <div style="font-size: 32px; color: #3498db; margin-bottom: 10px;">🎯</div>
                    <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 18px;">Objective</h3>
                    <p style="color: #666; font-size: 14px; margin: 0;">[Primary goal in one sentence]</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 32px; color: #2ecc71; margin-bottom: 10px;">💰</div>
                    <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 18px;">Investment</h3>
                    <p style="color: #666; font-size: 14px; margin: 0;">$[Total Budget]</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 32px; color: #9b59b6; margin-bottom: 10px;">⏰</div>
                    <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 18px;">Timeline</h3>
                    <p style="color: #666; font-size: 14px; margin: 0;">[Start Date] - [End Date]</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Table of Contents -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">TABLE OF CONTENTS</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div>
                <p style="margin: 8px 0;"><strong>1.0 Introduction</strong> ........................................................................ 3</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;1.1 Background ................................................................... 3</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;1.2 Problem Statement ......................................................... 4</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;1.3 Project Objectives .......................................................... 5</p>
            </div>
            <div>
                <p style="margin: 8px 0;"><strong>2.0 Proposed Solution</strong> ............................................................ 6</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;2.1 Solution Overview ........................................................ 6</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;2.2 Key Features ............................................................... 7</p>
            </div>
            <div>
                <p style="margin: 8px 0;"><strong>3.0 Project Scope</strong> ................................................................ 8</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;3.1 In Scope ................................................................... 8</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;3.2 Out of Scope ............................................................... 9</p>
            </div>
            <div>
                <p style="margin: 8px 0;"><strong>4.0 Methodology</strong> ................................................................. 10</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;4.1 Project Approach ........................................................ 10</p>
                <p style="margin: 8px 0;">&nbsp;&nbsp;4.2 Phases & Timeline ........................................................ 11</p>
            </div>
            <div>
                <p style="margin: 8px 0;"><strong>5.0 Team & Resources</strong> ............................................................ 12</p>
                <p style="margin: 8px 0;"><strong>6.0 Budget & Pricing</strong> ............................................................. 13</p>
            </div>
            <div>
                <p style="margin: 8px 0;"><strong>7.0 Risk Analysis</strong> ................................................................ 14</p>
                <p style="margin: 8px 0;"><strong>8.0 Success Metrics</strong> ............................................................. 15</p>
            </div>
        </div>
    </div>
    
    <!-- 1.0 Introduction -->
    <div style="margin-bottom: 40px; page-break-before: always;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">1.0 Introduction</h2>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">1.1 Background</h3>
        <p>[Provide context about the current situation, industry trends, and why this project is necessary.]</p>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px; margin-top: 25px;">1.2 Problem Statement</h3>
        <div style="background: #ffebee; padding: 20px; border-radius: 8px; border-left: 4px solid #e74c3c;">
            <p style="margin: 0; color: #c0392b; font-weight: 500;">Currently, [describe the problem]. This results in [negative impacts]. Without intervention, [future consequences].</p>
        </div>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px; margin-top: 25px;">1.3 Project Objectives</h3>
        <div style="background: #e8f4fc; padding: 20px; border-radius: 8px;">
            <p style="margin: 0 0 15px 0; font-weight: 500;">The primary objectives of this project are:</p>
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ddd;">
                            <strong>Objective 1:</strong> [Specific, measurable objective]
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;">
                            <span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px;">Priority: High</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ddd;">
                            <strong>Objective 2:</strong> [Specific, measurable objective]
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;">
                            <span style="background: #2ecc71; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px;">Priority: Medium</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ddd;">
                            <strong>Objective 3:</strong> [Specific, measurable objective]
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;">
                            <span style="background: #f39c12; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px;">Priority: Low</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- 2.0 Proposed Solution -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2ecc71; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">2.0 Proposed Solution</h2>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">2.1 Solution Overview</h3>
        <p>[Describe the proposed solution in detail. How does it address the problem statement?]</p>
        
        <div style="background: #e8f6f3; padding: 25px; border-radius: 8px; margin: 20px 0;">
            <h4 style="color: #27ae60; margin-top: 0; margin-bottom: 15px;">Solution Architecture</h4>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center;">
                <div style="background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div style="font-size: 24px; color: #3498db; margin-bottom: 10px;">🔧</div>
                    <p style="margin: 0; font-weight: 500;">[Component 1]</p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Brief description]</p>
                </div>
                <div style="background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div style="font-size: 24px; color: #2ecc71; margin-bottom: 10px;">🔄</div>
                    <p style="margin: 0; font-weight: 500;">[Component 2]</p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Brief description]</p>
                </div>
                <div style="background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div style="font-size: 24px; color: #9b59b6; margin-bottom: 10px;">📊</div>
                    <p style="margin: 0; font-weight: 500;">[Component 3]</p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Brief description]</p>
                </div>
            </div>
        </div>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px; margin-top: 25px;">2.2 Key Features</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        1
                    </div>
                    <h4 style="margin: 0; color: #2c3e50;">[Feature 1]</h4>
                </div>
                <p style="margin: 0; color: #666;">[Detailed description of feature 1 and its benefits]</p>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: #2ecc71; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        2
                    </div>
                    <h4 style="margin: 0; color: #2c3e50;">[Feature 2]</h4>
                </div>
                <p style="margin: 0; color: #666;">[Detailed description of feature 2 and its benefits]</p>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: #9b59b6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        3
                    </div>
                    <h4 style="margin: 0; color: #2c3e50;">[Feature 3]</h4>
                </div>
                <p style="margin: 0; color: #666;">[Detailed description of feature 3 and its benefits]</p>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="width: 40px; height: 40px; background: #f39c12; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        4
                    </div>
                    <h4 style="margin: 0; color: #2c3e50;">[Feature 4]</h4>
                </div>
                <p style="margin: 0; color: #666;">[Detailed description of feature 4 and its benefits]</p>
            </div>
        </div>
    </div>
    
    <!-- 3.0 Project Scope -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #f39c12; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">3.0 Project Scope</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-bottom: 30px;">
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">3.1 In Scope</h3>
                <div style="background: #e8f6f3; padding: 20px; border-radius: 8px; height: 100%;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>[Scope item 1]</li>
                        <li>[Scope item 2]</li>
                        <li>[Scope item 3]</li>
                        <li>[Scope item 4]</li>
                        <li>[Scope item 5]</li>
                    </ul>
                </div>
            </div>
            
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">3.2 Out of Scope</h3>
                <div style="background: #ffebee; padding: 20px; border-radius: 8px; height: 100%;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>[Out of scope item 1]</li>
                        <li>[Out of scope item 2]</li>
                        <li>[Out of scope item 3]</li>
                        <li>[Out of scope item 4]</li>
                        <li>[Out of scope item 5]</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">3.3 Assumptions & Dependencies</h3>
        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Assumption/Dependency</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Impact if Not Met</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Assumption 1]</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Impact description]</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Assumption 2]</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">[Impact description]</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- 4.0 Methodology -->
    <div style="margin-bottom: 40px; page-break-before: always;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #9b59b6; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">4.0 Methodology</h2>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">4.1 Project Approach</h3>
        <p>[Describe the methodology - Agile, Waterfall, Hybrid, etc.]</p>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px; margin-top: 25px;">4.2 Phases & Timeline</h3>
        
        <div style="position: relative; padding-left: 40px; margin-left: 20px; border-left: 3px solid #3498db;">
            <!-- Phase 1 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -48px; top: 0; width: 30px; height: 30px; background: #3498db; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 3px #3498db;"></div>
                <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                    <h4 style="color: #3498db; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 1: Discovery & Planning</h4>
                    <div style="display: flex; justify-content: space-between; color: #666; margin-bottom: 15px;">
                        <span><strong>Duration:</strong> [Weeks] weeks</span>
                        <span><strong>Start:</strong> [Date]</span>
                        <span><strong>End:</strong> [Date]</span>
                    </div>
                    <p><strong>Key Deliverables:</strong></p>
                    <ul>
                        <li>[Deliverable 1]</li>
                        <li>[Deliverable 2]</li>
                        <li>[Deliverable 3]</li>
                    </ul>
                </div>
            </div>
            
            <!-- Phase 2 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -48px; top: 0; width: 30px; height: 30px; background: #2ecc71; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 3px #2ecc71;"></div>
                <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                    <h4 style="color: #2ecc71; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 2: Design & Development</h4>
                    <div style="display: flex; justify-content: space-between; color: #666; margin-bottom: 15px;">
                        <span><strong>Duration:</strong> [Weeks] weeks</span>
                        <span><strong>Start:</strong> [Date]</span>
                        <span><strong>End:</strong> [Date]</span>
                    </div>
                    <p><strong>Key Deliverables:</strong></p>
                    <ul>
                        <li>[Deliverable 1]</li>
                        <li>[Deliverable 2]</li>
                        <li>[Deliverable 3]</li>
                    </ul>
                </div>
            </div>
            
            <!-- Phase 3 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -48px; top: 0; width: 30px; height: 30px; background: #f39c12; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 3px #f39c12;"></div>
                <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                    <h4 style="color: #f39c12; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 3: Testing & Quality Assurance</h4>
                    <div style="display: flex; justify-content: space-between; color: #666; margin-bottom: 15px;">
                        <span><strong>Duration:</strong> [Weeks] weeks</span>
                        <span><strong>Start:</strong> [Date]</span>
                        <span><strong>End:</strong> [Date]</span>
                    </div>
                    <p><strong>Key Deliverables:</strong></p>
                    <ul>
                        <li>[Deliverable 1]</li>
                        <li>[Deliverable 2]</li>
                        <li>[Deliverable 3]</li>
                    </ul>
                </div>
            </div>
            
            <!-- Phase 4 -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -48px; top: 0; width: 30px; height: 30px; background: #9b59b6; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 3px #9b59b6;"></div>
                <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                    <h4 style="color: #9b59b6; margin-top: 0; margin-bottom: 10px; font-size: 20px;">Phase 4: Deployment & Training</h4>
                    <div style="display: flex; justify-content: space-between; color: #666; margin-bottom: 15px;">
                        <span><strong>Duration:</strong> [Weeks] weeks</span>
                        <span><strong>Start:</strong> [Date]</span>
                        <span><strong>End:</strong> [Date]</span>
                    </div>
                    <p><strong>Key Deliverables:</strong></p>
                    <ul>
                        <li>[Deliverable 1]</li>
                        <li>[Deliverable 2]</li>
                        <li>[Deliverable 3]</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 5.0 Team & Resources -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">5.0 Team & Resources</h2>
        
        <div style="margin-bottom: 30px;">
            <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">Core Team Members</h3>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border-radius: 50%;"></div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: #2c3e50;">[Team Member Name]</h4>
                            <p style="margin: 0; color: #3498db; font-weight: 500;">[Role/Position]</p>
                        </div>
                    </div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><strong>Responsibilities:</strong> [Brief description of responsibilities]</p>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); border-radius: 50%;"></div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: #2c3e50;">[Team Member Name]</h4>
                            <p style="margin: 0; color: #2ecc71; font-weight: 500;">[Role/Position]</p>
                        </div>
                    </div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><strong>Responsibilities:</strong> [Brief description of responsibilities]</p>
                </div>
            </div>
        </div>
        
        <div>
            <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">Resource Requirements</h3>
            <table style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Resource Type</th>
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Specification</th>
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Provider</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #ddd;">Hardware</td>
                        <td style="padding: 12px; border: 1px solid #ddd;">[Specifications]</td>
                        <td style="padding: 12px; border: 1px solid #ddd;">[Provider]</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #ddd;">Software</td>
                        <td style="padding: 12px; border: 1px solid #ddd;">[Licenses/tools needed]</td>
                        <td style="padding: 12px; border: 1px solid #ddd;">[Provider]</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #ddd;">Facilities</td>
                        <td style="padding: 12px; border: 1px solid #ddd;">[Space/equipment requirements]</td>
                        <td style="padding: 12px; border: 1px solid #ddd;">[Provider]</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- 6.0 Budget & Pricing -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #16a085; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">6.0 Budget & Pricing</h2>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #16a085 0%, #1abc9c 100%); color: white;">
                        <th style="padding: 15px; text-align: left;">Item</th>
                        <th style="padding: 15px; text-align: left;">Description</th>
                        <th style="padding: 15px; text-align: right;">Quantity</th>
                        <th style="padding: 15px; text-align: right;">Unit Cost</th>
                        <th style="padding: 15px; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Labor -->
                    <tr style="background: #f8f9fa;">
                        <td colspan="5" style="padding: 12px; font-weight: bold; color: #2c3e50;">Labor Costs</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #eee;">[Role 1]</td>
                        <td style="padding: 12px; border: 1px solid #eee;">[Description]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">[Hours] hours</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Rate]/hour</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Total]</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #eee;">[Role 2]</td>
                        <td style="padding: 12px; border: 1px solid #eee;">[Description]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">[Hours] hours</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Rate]/hour</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Total]</td>
                    </tr>
                    
                    <!-- Materials -->
                    <tr style="background: #f8f9fa;">
                        <td colspan="5" style="padding: 12px; font-weight: bold; color: #2c3e50;">Materials & Equipment</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #eee;">[Item 1]</td>
                        <td style="padding: 12px; border: 1px solid #eee;">[Description]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">[Quantity]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Cost]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Total]</td>
                    </tr>
                    
                    <!-- Software -->
                    <tr style="background: #f8f9fa;">
                        <td colspan="5" style="padding: 12px; font-weight: bold; color: #2c3e50;">Software & Licensing</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #eee;">[Software 1]</td>
                        <td style="padding: 12px; border: 1px solid #eee;">[License type]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">[Licenses]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Cost]</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Total]</td>
                    </tr>
                    
                    <!-- Subtotal -->
                    <tr>
                        <td colspan="4" style="padding: 12px; border: 1px solid #eee; text-align: right; font-weight: bold;">Subtotal</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right; font-weight: bold;">$[Subtotal]</td>
                    </tr>
                    
                    <!-- Taxes -->
                    <tr>
                        <td colspan="4" style="padding: 12px; border: 1px solid #eee; text-align: right;">Tax ([Tax Rate]%)</td>
                        <td style="padding: 12px; border: 1px solid #eee; text-align: right;">$[Tax Amount]</td>
                    </tr>
                    
                    <!-- Total -->
                    <tr style="background: #e8f6f3; font-weight: bold;">
                        <td colspan="4" style="padding: 15px; border: 1px solid #16a085; text-align: right; font-size: 18px;">TOTAL PROJECT COST</td>
                        <td style="padding: 15px; border: 1px solid #16a085; text-align: right; font-size: 18px; color: #16a085;">$[Total Cost]</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">Payment Schedule</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Milestone</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Amount</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">Project Kick-off</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">$[Amount]</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">[Date]</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">Phase 1 Completion</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">$[Amount]</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">[Date]</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">Project Completion</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">$[Amount]</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">[Date]</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">ROI Analysis</h3>
                <div style="background: #e8f4fc; padding: 20px; border-radius: 8px; height: 100%;">
                    <p><strong>Expected Benefits:</strong></p>
                    <ul style="margin-top: 10px;">
                        <li>[Benefit 1: e.g., Cost savings of $X annually]</li>
                        <li>[Benefit 2: e.g., Revenue increase of X%]</li>
                        <li>[Benefit 3: e.g., Time savings of X hours/week]</li>
                    </ul>
                    <p style="margin-top: 15px;"><strong>Payback Period:</strong> [Number] months</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 7.0 Risk Analysis -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">7.0 Risk Analysis</h2>
        
        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white;">
                    <th style="padding: 12px; text-align: left;">Risk Category</th>
                    <th style="padding: 12px; text-align: left;">Potential Risk</th>
                    <th style="padding: 12px; text-align: center;">Probability</th>
                    <th style="padding: 12px; text-align: center;">Impact</th>
                    <th style="padding: 12px; text-align: left;">Mitigation Strategy</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px; border: 1px solid #eee;">Technical</td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Risk description]</td>
                    <td style="padding: 12px; border: 1px solid #eee; text-align: center;">
                        <span style="background: #ffebee; color: #d32f2f; padding: 4px 8px; border-radius: 4px; font-size: 12px;">High</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee; text-align: center;">
                        <span style="background: #fff3e0; color: #f57c00; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Medium</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Mitigation steps]</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #eee;">Schedule</td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Risk description]</td>
                    <td style="padding: 12px; border: 1px solid #eee; text-align: center;">
                        <span style="background: #fff3e0; color: #f57c00; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Medium</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee; text-align: center;">
                        <span style="background: #ffebee; color: #d32f2f; padding: 4px 8px; border-radius: 4px; font-size: 12px;">High</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Mitigation steps]</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #eee;">Budget</td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Risk description]</td>
                    <td style="padding: 12px; border: 1px solid #eee; text-align: center;">
                        <span style="background: #e8f5e9; color: #388e3c; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Low</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee; text-align: center;">
                        <span style="background: #fff3e0; color: #f57c00; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Medium</span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Mitigation steps]</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- 8.0 Success Metrics -->
    <div style="margin-bottom: 40px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2ecc71; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">8.0 Success Metrics</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                        📈
                    </div>
                    <div>
                        <h3 style="margin: 0; color: #2c3e50;">Key Performance Indicators</h3>
                    </div>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>[KPI 1]:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">[Target value]</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>[KPI 2]:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">[Target value]</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0;"><strong>[KPI 3]:</strong></td>
                            <td style="padding: 8px 0; text-align: right;">[Target value]</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                        ✅
                    </div>
                    <div>
                        <h3 style="margin: 0; color: #2c3e50;">Acceptance Criteria</h3>
                    </div>
                </div>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>[Criterion 1]</li>
                    <li>[Criterion 2]</li>
                    <li>[Criterion 3]</li>
                    <li>[Criterion 4]</li>
                </ul>
            </div>
        </div>
        
        <div style="background: #e8f6f3; padding: 25px; border-radius: 8px;">
            <h3 style="color: #27ae60; margin-top: 0; margin-bottom: 15px; font-size: 22px;">Measurement & Reporting</h3>
            <p><strong>Reporting Frequency:</strong> [Weekly/Monthly/Quarterly]</p>
            <p><strong>Reporting Format:</strong> [Format description]</p>
            <p><strong>Stakeholder Review Meetings:</strong> [Schedule]</p>
        </div>
    </div>
    
    <!-- Conclusion -->
    <div style="margin-bottom: 40px; padding: 30px; background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; border-radius: 8px;">
        <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 28px; text-align: center;">Conclusion</h2>
        
        <p style="font-size: 18px; line-height: 1.8; text-align: center;">
            [Summarize why this proposal represents the best solution. Reinforce key benefits and value proposition. End with a strong call to action.]
        </p>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 20px; margin-bottom: 20px;"><strong>Ready to move forward with this project?</strong></p>
            <p>Contact: [Your Name] | [Your Email] | [Your Phone]</p>
        </div>
    </div>
    
    <!-- Appendix -->
    <div style="margin-bottom: 40px; page-break-before: always;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #9b59b6; padding-bottom: 10px; margin-bottom: 20px; font-size: 28px;">Appendix</h2>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px;">A. References</h3>
        <ul>
            <li>[Reference 1]</li>
            <li>[Reference 2]</li>
            <li>[Reference 3]</li>
        </ul>
        
        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 22px; margin-top: 25px;">B. Glossary</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Term</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Definition</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Term 1]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Definition]</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Term 2]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Definition]</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Sign-off Page -->
    <div style="margin-bottom: 40px; padding: 40px; border: 2px solid #2c3e50; border-radius: 8px; text-align: center; page-break-before: always;">
        <h2 style="color: #2c3e50; margin-top: 0; margin-bottom: 40px; font-size: 32px;">PROPOSAL ACCEPTANCE</h2>
        
        <div style="margin-bottom: 50px;">
            <p style="font-size: 18px; margin-bottom: 30px;">
                By signing below, the parties acknowledge acceptance of this proposal and agree to proceed with the project as outlined.
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-bottom: 60px;">
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 30px; font-size: 22px;">Client Authorization</h3>
                <div style="margin-bottom: 20px;">
                    <p style="margin-bottom: 5px;"><strong>Organization:</strong></p>
                    <div style="height: 2px; background: #2c3e50; width: 80%; margin: 0 auto;"></div>
                </div>
                <div style="margin-bottom: 20px;">
                    <p style="margin-bottom: 5px;"><strong>Authorized Signature:</strong></p>
                    <div style="height: 2px; background: #2c3e50; width: 80%; margin: 0 auto;"></div>
                </div>
                <div style="margin-bottom: 20px;">
                    <p style="margin-bottom: 5px;"><strong>Name:</strong></p>
                    <div style="height: 2px; background: #2c3e50; width: 80%; margin: 0 auto;"></div>
                </div>
                <div>
                    <p style="margin-bottom: 5px;"><strong>Date:</strong></p>
                    <div style="height: 2px; background: #2c3e50; width: 80%; margin: 0 auto;"></div>
                </div>
            </div>
            
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 30px; font-size: 22px;">Service Provider Authorization</h3>
                <div style="margin-bottom: 20px;">
                    <p style="margin-bottom: 5px;"><strong>Organization:</strong></p>
                    <div style="height: 2px; background: #3498db; width: 80%; margin: 0 auto;"></div>
                </div>
                <div style="margin-bottom: 20px;">
                    <p style="margin-bottom: 5px;"><strong>Authorized Signature:</strong></p>
                    <div style="height: 2px; background: #3498db; width: 80%; margin: 0 auto;"></div>
                </div>
                <div style="margin-bottom: 20px;">
                    <p style="margin-bottom: 5px;"><strong>Name:</strong></p>
                    <div style="height: 2px; background: #3498db; width: 80%; margin: 0 auto;"></div>
                </div>
                <div>
                    <p style="margin-bottom: 5px;"><strong>Date:</strong></p>
                    <div style="height: 2px; background: #3498db; width: 80%; margin: 0 auto;"></div>
                </div>
            </div>
        </div>
        
        <div style="font-size: 14px; color: #666;">
            <p><strong>Note:</strong> This proposal is valid for 30 days from the date of submission.</p>
            <p>Project commencement is contingent upon signed acceptance and initial payment.</p>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding-top: 40px; border-top: 1px solid #eee; color: #7f8c8d; font-size: 14px;">
        <p><strong>CONFIDENTIAL</strong> - This proposal contains proprietary information of [Your Company].</p>
        <p>Project Proposal Template v1.0 | WP Office Editor</p>
        <p>Generated on: [Date] | Document ID: [Document ID]</p>
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب جدول أعمال الاجتماع
     */
    private function get_meeting_agenda_template() {
        return <<<HTML
<div style="font-family: 'Arial', sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; color: #333;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 30px; padding: 30px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); color: white; border-radius: 8px;">
        <h1 style="margin: 0 0 10px 0; font-size: 36px;">Meeting Agenda</h1>
        <h2 style="margin: 0; font-size: 22px; font-weight: normal;">[Meeting Topic/Title]</h2>
    </div>
    
    <!-- Meeting Details -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 15px; font-size: 18px;">📅 Meeting Information</h3>
            <table style="width: 100%;">
                <tbody>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Date:</strong></td>
                        <td style="padding: 5px 0;">[Date]</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Time:</strong></td>
                        <td style="padding: 5px 0;">[Start Time] - [End Time] ([Time Zone])</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Duration:</strong></td>
                        <td style="padding: 5px 0;">[Duration] minutes</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Location:</strong></td>
                        <td style="padding: 5px 0;">[Meeting Room/Virtual Link]</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 15px; font-size: 18px;">👥 Participants</h3>
            <table style="width: 100%;">
                <tbody>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Chair:</strong></td>
                        <td style="padding: 5px 0;">[Chairperson Name]</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Facilitator:</strong></td>
                        <td style="padding: 5px 0;">[Facilitator Name]</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Notetaker:</strong></td>
                        <td style="padding: 5px 0;">[Notetaker Name]</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Attendees:</strong></td>
                        <td style="padding: 5px 0;">[Number] persons</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Meeting Objectives -->
    <div style="margin-bottom: 30px; padding: 20px; background: #e8f4fc; border-radius: 8px; border-left: 4px solid #3498db;">
        <h2 style="color: #3498db; margin-top: 0; margin-bottom: 15px; font-size: 22px;">🎯 Meeting Objectives</h2>
        <p>The purpose of this meeting is to:</p>
        <ul style="margin: 10px 0 0 20px;">
            <li>[Objective 1]</li>
            <li>[Objective 2]</li>
            <li>[Objective 3]</li>
            <li>[Objective 4]</li>
        </ul>
    </div>
    
    <!-- Pre-Meeting Preparation -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px;">📋 Pre-Meeting Preparation</h2>
        <p>To ensure a productive meeting, please review the following before attending:</p>
        <ul>
            <li>[Document 1 to review]</li>
            <li>[Document 2 to review]</li>
            <li>[Information to prepare]</li>
        </ul>
    </div>
    
    <!-- Agenda Items -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px;">📝 Agenda</h2>
        
        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: #2c3e50; color: white;">
                    <th style="padding: 12px; text-align: left; width: 10%;">Time</th>
                    <th style="padding: 12px; text-align: left; width: 25%;">Topic</th>
                    <th style="padding: 12px; text-align: left; width: 25%;">Presenter</th>
                    <th style="padding: 12px; text-align: left; width: 40%;">Discussion Points</th>
                </tr>
            </thead>
            <tbody>
                <!-- Welcome & Introductions -->
                <tr>
                    <td style="padding: 12px; border: 1px solid #eee;">[5 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>Welcome & Introductions</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Chairperson]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <ul style="margin: 0; padding-left: 15px;">
                            <li>Welcome participants</li>
                            <li>Review meeting objectives</li>
                            <li>Introduce new attendees</li>
                        </ul>
                    </td>
                </tr>
                
                <!-- Review of Previous Minutes -->
                <tr style="background: #f8f9fa;">
                    <td style="padding: 12px; border: 1px solid #eee;">[10 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>Review of Previous Minutes</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Notetaker]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <ul style="margin: 0; padding-left: 15px;">
                            <li>Review action items from last meeting</li>
                            <li>Approve previous meeting minutes</li>
                        </ul>
                    </td>
                </tr>
                
                <!-- Agenda Item 1 -->
                <tr>
                    <td style="padding: 12px; border: 1px solid #eee;">[15 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>[Agenda Item 1 Title]</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Presenter Name]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <ul style="margin: 0; padding-left: 15px;">
                            <li>[Discussion point 1]</li>
                            <li>[Discussion point 2]</li>
                            <li>[Decision needed: Yes/No]</li>
                        </ul>
                    </td>
                </tr>
                
                <!-- Agenda Item 2 -->
                <tr style="background: #f8f9fa;">
                    <td style="padding: 12px; border: 1px solid #eee;">[20 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>[Agenda Item 2 Title]</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Presenter Name]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <ul style="margin: 0; padding-left: 15px;">
                            <li>[Discussion point 1]</li>
                            <li>[Discussion point 2]</li>
                            <li>[Decision needed: Yes/No]</li>
                        </ul>
                    </td>
                </tr>
                
                <!-- Break -->
                <tr>
                    <td style="padding: 12px; border: 1px solid #eee;">[10 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>Coffee Break</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">-</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        Networking and refreshments
                    </td>
                </tr>
                
                <!-- Agenda Item 3 -->
                <tr style="background: #f8f9fa;">
                    <td style="padding: 12px; border: 1px solid #eee;">[25 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>[Agenda Item 3 Title]</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Presenter Name]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <ul style="margin: 0; padding-left: 15px;">
                            <li>[Discussion point 1]</li>
                            <li>[Discussion point 2]</li>
                            <li>[Decision needed: Yes/No]</li>
                        </ul>
                    </td>
                </tr>
                
                <!-- Open Discussion -->
                <tr>
                    <td style="padding: 12px; border: 1px solid #eee;">[10 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>Open Discussion</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">All</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <ul style="margin: 0; padding-left: 15px;">
                            <li>Other business</li>
                            <li>Questions and comments</li>
                        </ul>
                    </td>
                </tr>
                
                <!-- Wrap-up & Next Steps -->
                <tr style="background: #f8f9fa;">
                    <td style="padding: 12px; border: 1px solid #eee;">[15 min]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <strong>Wrap-up & Next Steps</strong>
                    </td>
                    <td style="padding: 12px; border: 1px solid #eee;">[Chairperson]</td>
                    <td style="padding: 12px; border: 1px solid #eee;">
                        <ul style="margin: 0; padding-left: 15px;">
                            <li>Summarize decisions made</li>
                            <li>Assign action items</li>
                            <li>Set next meeting date</li>
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Materials & Resources -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px;">📎 Materials & Resources</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 18px;">Documents to Review</h3>
                <ul>
                    <li><a href="#" style="color: #3498db; text-decoration: none;">[Document 1 Name]</a></li>
                    <li><a href="#" style="color: #3498db; text-decoration: none;">[Document 2 Name]</a></li>
                    <li><a href="#" style="color: #3498db; text-decoration: none;">[Document 3 Name]</a></li>
                </ul>
            </div>
            
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 10px; font-size: 18px;">Presentation Files</h3>
                <ul>
                    <li><a href="#" style="color: #3498db; text-decoration: none;">[Presentation 1]</a></li>
                    <li><a href="#" style="color: #3498db; text-decoration: none;">[Presentation 2]</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Action Items from Previous Meeting -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px;">✅ Action Items from Previous Meeting</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Action Item</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Assigned To</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Due Date</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Action item 1 description]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Person Name]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Date]</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                        <span style="background: #2ecc71; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Completed</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Action item 2 description]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Person Name]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Date]</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                        <span style="background: #f39c12; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">In Progress</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Action item 3 description]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Person Name]</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">[Date]</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                        <span style="background: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Pending</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Ground Rules -->
    <div style="margin-bottom: 30px; padding: 20px; background: #fff8e1; border-radius: 8px; border-left: 4px solid #f39c12;">
        <h2 style="color: #f39c12; margin-top: 0; margin-bottom: 15px; font-size: 22px;">📜 Meeting Ground Rules</h2>
        <ul style="margin: 0;">
            <li>Arrive on time and stay for the entire meeting</li>
            <li>Silence mobile phones</li>
            <li>One person speaks at a time</li>
            <li>Respect different opinions</li>
            <li>Stay on topic and respect time limits</li>
            <li>Participate actively and constructively</li>
        </ul>
    </div>
    
    <!-- Notes Section -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px;">📝 Notes</h2>
        <div style="min-height: 200px; border: 2px dashed #ddd; border-radius: 8px; padding: 15px; color: #999;">
            <p><em>Space for taking notes during the meeting...</em></p>
        </div>
    </div>
    
    <!-- Post-Meeting Actions -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px;">🚀 Post-Meeting Actions</h2>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 24px;">
                    📧
                </div>
                <p style="margin: 0; font-weight: 500;">Minutes Distribution</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Within 24 hours</p>
            </div>
            
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #2ecc71; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 24px;">
                    ✅
                </div>
                <p style="margin: 0; font-weight: 500;">Action Item Follow-up</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Follow-up Date]</p>
            </div>
            
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #9b59b6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 24px;">
                    📅
                </div>
                <p style="margin: 0; font-weight: 500;">Next Meeting</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Date] at [Time]</p>
            </div>
        </div>
    </div>
    
    <!-- Attendee List -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 15px; font-size: 22px;">👥 Attendee List</h2>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="width: 40px; height: 40px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        JD
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 500;">John Doe</p>
                        <p style="margin: 0; font-size: 12px; color: #666;">Chairperson</p>
                    </div>
                </div>
                <p style="margin: 0; font-size: 14px;"><strong>Email:</strong> john@example.com</p>
                <p style="margin: 0; font-size: 14px;"><strong>Attendance:</strong> 
                    <span style="color: #2ecc71;">●</span> Confirmed
                </p>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="width: 40px; height: 40px; background: #2ecc71; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        JS
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 500;">Jane Smith</p>
                        <p style="margin: 0; font-size: 12px; color: #666;">Facilitator</p>
                    </div>
                </div>
                <p style="margin: 0; font-size: 14px;"><strong>Email:</strong> jane@example.com</p>
                <p style="margin: 0; font-size: 14px;"><strong>Attendance:</strong> 
                    <span style="color: #2ecc71;">●</span> Confirmed
                </p>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="width: 40px; height: 40px; background: #9b59b6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        RJ
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 500;">Robert Johnson</p>
                        <p style="margin: 0; font-size: 12px; color: #666;">Notetaker</p>
                    </div>
                </div>
                <p style="margin: 0; font-size: 14px;"><strong>Email:</strong> robert@example.com</p>
                <p style="margin: 0; font-size: 14px;"><strong>Attendance:</strong> 
                    <span style="color: #f39c12;">●</span> Tentative
                </p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding-top: 30px; border-top: 1px solid #eee; color: #7f8c8d; font-size: 14px;">
        <p><strong>Meeting Agenda Prepared by:</strong> [Your Name] | [Your Position]</p>
        <p><strong>Last Updated:</strong> [Date] | <strong>Version:</strong> 1.0</p>
        <p style="margin-top: 10px;">Meeting Agenda Template - WP Office Editor</p>
    </div>
</div>
HTML;
    }
    
    /**
     * الحصول على قالب السيرة الذاتية
     */
    private function get_resume_template() {
        return <<<HTML
<div style="font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; color: #2c3e50;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 3px solid #3498db;">
        <h1 style="font-size: 42px; margin: 0 0 10px 0; color: #2c3e50; font-weight: 300;">[First Name] [Last Name]</h1>
        <h2 style="font-size: 22px; margin: 0 0 20px 0; color: #3498db; font-weight: 400;">[Professional Title]</h2>
        
        <div style="display: flex; justify-content: center; gap: 30px; font-size: 16px; color: #7f8c8d;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #3498db;">📧</span> [Email Address]
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #3498db;">📱</span> [Phone Number]
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #3498db;">📍</span> [City, State]
            </div>
        </div>
        
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
            <a href="#" style="color: #3498db; text-decoration: none;">LinkedIn</a>
            <a href="#" style="color: #3498db; text-decoration: none;">GitHub</a>
            <a href="#" style="color: #3498db; text-decoration: none;">Portfolio</a>
            <a href="#" style="color: #3498db; text-decoration: none;">Twitter</a>
        </div>
    </div>
    
    <!-- Professional Summary -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 40px; height: 40px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                👤
            </div>
            <h2 style="margin: 0; font-size: 24px; color: #2c3e50;">Professional Summary</h2>
        </div>
        
        <p style="font-size: 18px; line-height: 1.8; color: #34495e;">
            [Dynamic and results-oriented [Your Profession] with [Number] years of experience in [Your Industry/Field]. Proven track record of [Key Achievement 1], [Key Achievement 2], and [Key Achievement 3]. Seeking to leverage expertise in [Key Skill 1], [Key Skill 2], and [Key Skill 3] to contribute to [Company Name]'s success.]
        </p>
    </div>
    
    <!-- Work Experience -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 40px; height: 40px; background: #2ecc71; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                💼
            </div>
            <h2 style="margin: 0; font-size: 24px; color: #2c3e50;">Work Experience</h2>
        </div>
        
        <!-- Job 1 -->
        <div style="margin-bottom: 30px; padding-left: 55px; position: relative;">
            <div style="position: absolute; left: 0; top: 0; width: 40px; height: 40px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #2ecc71; font-size: 20px;">
                1
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div>
                    <h3 style="margin: 0 0 5px 0; font-size: 20px; color: #2c3e50;">[Job Title]</h3>
                    <p style="margin: 0 0 5px 0; font-size: 18px; color: #3498db; font-weight: 500;">[Company Name]</p>
                    <p style="margin: 0; color: #7f8c8d; font-size: 14px;">[City, State]</p>
                </div>
                <div style="text-align: right;">
                    <p style="margin: 0; color: #2ecc71; font-weight: 500;">[Start Date] - [End Date]</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 14px;">[Duration]</p>
                </div>
            </div>
            
            <ul style="margin: 15px 0 0 0; padding-left: 20px;">
                <li>[Responsibility/Achievement 1 - Use action verbs and quantify results]</li>
                <li>[Responsibility/Achievement 2 - Increased efficiency by X%, reduced costs by $Y]</li>
                <li>[Responsibility/Achievement 3 - Led team of Z people to achieve specific result]</li>
                <li>[Responsibility/Achievement 4 - Implemented new system/process that improved outcomes]</li>
            </ul>
            
            <div style="margin-top: 15px;">
                <p style="margin: 0 0 5px 0; font-weight: 500;">Key Technologies:</p>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <span style="background: #e8f4fc; color: #3498db; padding: 4px 12px; border-radius: 20px; font-size: 14px;">[Technology 1]</span>
                    <span style="background: #e8f4fc; color: #3498db; padding: 4px 12px; border-radius: 20px; font-size: 14px;">[Technology 2]</span>
                    <span style="background: #e8f4fc; color: #3498db; padding: 4px 12px; border-radius: 20px; font-size: 14px;">[Technology 3]</span>
                </div>
            </div>
        </div>
        
        <!-- Job 2 -->
        <div style="margin-bottom: 30px; padding-left: 55px; position: relative;">
            <div style="position: absolute; left: 0; top: 0; width: 40px; height: 40px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #2ecc71; font-size: 20px;">
                2
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div>
                    <h3 style="margin: 0 0 5px 0; font-size: 20px; color: #2c3e50;">[Job Title]</h3>
                    <p style="margin: 0 0 5px 0; font-size: 18px; color: #3498db; font-weight: 500;">[Company Name]</p>
                    <p style="margin: 0; color: #7f8c8d; font-size: 14px;">[City, State]</p>
                </div>
                <div style="text-align: right;">
                    <p style="margin: 0; color: #2ecc71; font-weight: 500;">[Start Date] - [End Date]</p>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 14px;">[Duration]</p>
                </div>
            </div>
            
            <ul style="margin: 15px 0 0 0; padding-left: 20px;">
                <li>[Responsibility/Achievement 1]</li>
                <li>[Responsibility/Achievement 2]</li>
                <li>[Responsibility/Achievement 3]</li>
            </ul>
        </div>
    </div>
    
    <!-- Skills & Expertise -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 40px; height: 40px; background: #f39c12; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                ⚡
            </div>
            <h2 style="margin: 0; font-size: 24px; color: #2c3e50;">Skills & Expertise</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 18px; border-bottom: 2px solid #f39c12; padding-bottom: 5px;">Technical Skills</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <span style="background: #3498db; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 1]</span>
                    <span style="background: #3498db; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 2]</span>
                    <span style="background: #3498db; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 3]</span>
                    <span style="background: #3498db; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 4]</span>
                    <span style="background: #3498db; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 5]</span>
                    <span style="background: #3498db; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 6]</span>
                </div>
            </div>
            
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 18px; border-bottom: 2px solid #f39c12; padding-bottom: 5px;">Soft Skills</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <span style="background: #2ecc71; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 1]</span>
                    <span style="background: #2ecc71; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 2]</span>
                    <span style="background: #2ecc71; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 3]</span>
                    <span style="background: #2ecc71; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Skill 4]</span>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 18px; border-bottom: 2px solid #f39c12; padding-bottom: 5px;">Tools & Technologies</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <span style="background: #9b59b6; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Tool 1]</span>
                <span style="background: #9b59b6; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Tool 2]</span>
                <span style="background: #9b59b6; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Tool 3]</span>
                <span style="background: #9b59b6; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Tool 4]</span>
                <span style="background: #9b59b6; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px;">[Tool 5]</span>
            </div>
        </div>
    </div>
    
    <!-- Education -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 40px; height: 40px; background: #9b59b6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🎓
            </div>
            <h2 style="margin: 0; font-size: 24px; color: #2c3e50;">Education</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
            <!-- Degree 1 -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; font-size: 20px; color: #2c3e50;">[Degree Name]</h3>
                <p style="margin: 0 0 10px 0; color: #3498db; font-weight: 500;">[University Name]</p>
                <p style="margin: 0 0 10px 0; color: #7f8c8d;">[City, State]</p>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #9b59b6; font-weight: 500;">[Graduation Date]</span>
                    <span style="background: #9b59b6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px;">GPA: [GPA]</span>
                </div>
                
                <div style="margin-top: 15px;">
                    <p style="margin: 0 0 8px 0; font-weight: 500;">Relevant Coursework:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <span style="background: white; color: #9b59b6; padding: 4px 10px; border-radius: 4px; font-size: 13px;">[Course 1]</span>
                        <span style="background: white; color: #9b59b6; padding: 4px 10px; border-radius: 4px; font-size: 13px;">[Course 2]</span>
                        <span style="background: white; color: #9b59b6; padding: 4px 10px; border-radius: 4px; font-size: 13px;">[Course 3]</span>
                    </div>
                </div>
            </div>
            
            <!-- Degree 2 -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; font-size: 20px; color: #2c3e50;">[Degree Name]</h3>
                <p style="margin: 0 0 10px 0; color: #3498db; font-weight: 500;">[University Name]</p>
                <p style="margin: 0 0 10px 0; color: #7f8c8d;">[City, State]</p>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #9b59b6; font-weight: 500;">[Graduation Date]</span>
                    <span style="background: #9b59b6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px;">GPA: [GPA]</span>
                </div>
                
                <div style="margin-top: 15px;">
                    <p style="margin: 0 0 8px 0; font-weight: 500;">Honors:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <span style="background: white; color: #9b59b6; padding: 4px 10px; border-radius: 4px; font-size: 13px;">[Honor 1]</span>
                        <span style="background: white; color: #9b59b6; padding: 4px 10px; border-radius: 4px; font-size: 13px;">[Honor 2]</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Certifications -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 40px; height: 40px; background: #e74c3c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                📜
            </div>
            <h2 style="margin: 0; font-size: 24px; color: #2c3e50;">Certifications</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #e74c3c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 24px;">
                    🏆
                </div>
                <p style="margin: 0; font-weight: 500;">[Certification 1]</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Issuing Organization]</p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #e74c3c;">[Year]</p>
            </div>
            
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #e74c3c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 24px;">
                    🏆
                </div>
                <p style="margin: 0; font-weight: 500;">[Certification 2]</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Issuing Organization]</p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #e74c3c;">[Year]</p>
            </div>
            
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #e74c3c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 24px;">
                    🏆
                </div>
                <p style="margin: 0; font-weight: 500;">[Certification 3]</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">[Issuing Organization]</p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #e74c3c;">[Year]</p>
            </div>
        </div>
    </div>
    
    <!-- Projects -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 40px; height: 40px; background: #1abc9c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🚀
            </div>
            <h2 style="margin: 0; font-size: 24px; color: #2c3e50;">Key Projects</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <!-- Project 1 -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #2c3e50;">[Project Name 1]</h3>
                <p style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 14px;">[Project Role] | [Date]</p>
                <p style="margin: 0 0 15px 0;">[Brief project description highlighting impact and achievements]</p>
                
                <div>
                    <p style="margin: 0 0 8px 0; font-weight: 500;">Technologies Used:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <span style="background: #1abc9c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">[Tech 1]</span>
                        <span style="background: #1abc9c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">[Tech 2]</span>
                        <span style="background: #1abc9c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">[Tech 3]</span>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 14px;">View Project →</a>
                </div>
            </div>
            
            <!-- Project 2 -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #2c3e50;">[Project Name 2]</h3>
                <p style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 14px;">[Project Role] | [Date]</p>
                <p style="margin: 0 0 15px 0;">[Brief project description highlighting impact and achievements]</p>
                
                <div>
                    <p style="margin: 0 0 8px 0; font-weight: 500;">Technologies Used:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <span style="background: #1abc9c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">[Tech 1]</span>
                        <span style="background: #1abc9c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">[Tech 2]</span>
                        <span style="background: #1abc9c; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px;">[Tech 3]</span>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 14px;">View Project →</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Languages -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 40px; height: 40px; background: #34495e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🌐
            </div>
            <h2 style="margin: 0; font-size: 24px; color: #2c3e50;">Languages</h2>
        </div>
        
        <div style="display: flex; gap: 30px;">
            <div>
                <p style="margin: 0 0 10px 0; font-weight: 500;">[Language 1]</p>
                <div style="display: flex; gap: 5px;">
                    <span style="width: 30px; height: 8px; background: #3498db; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #3498db; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #3498db; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #3498db; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #eee; border-radius: 4px;"></span>
                </div>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Fluent</p>
            </div>
            
            <div>
                <p style="margin: 0 0 10px 0; font-weight: 500;">[Language 2]</p>
                <div style="display: flex; gap: 5px;">
                    <span style="width: 30px; height: 8px; background: #2ecc71; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #2ecc71; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #2ecc71; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #eee; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #eee; border-radius: 4px;"></span>
                </div>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Intermediate</p>
            </div>
            
            <div>
                <p style="margin: 0 0 10px 0; font-weight: 500;">[Language 3]</p>
                <div style="display: flex; gap: 5px;">
                    <span style="width: 30px; height: 8px; background: #9b59b6; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #9b59b6; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #eee; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #eee; border-radius: 4px;"></span>
                    <span style="width: 30px; height: 8px; background: #eee; border-radius: 4px;"></span>
                </div>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Basic</p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding-top: 40px; border-top: 1px solid #eee; color: #7f8c8d; font-size: 14px;">
        <p><strong>References available upon request</strong></p>
        <p>Professional Resume Template | WP Office Editor</p>
        <p>Last Updated: [Date] | Page 1 of 1</p>
    </div>
</div>
HTML;
    }
    
    /**
     * إضافة قائمة القوالب
     */
    public function add_templates_menu() {
        add_submenu_page(
            'wp-office-editor',
            __('Templates', 'wp-office-editor'),
            __('Templates', 'wp-office-editor'),
            'edit_posts',
            'wp-office-editor-templates',
            [$this, 'render_templates_page']
        );
    }
    
    /**
     * عرض صفحة القوالب
     */
    public function render_templates_page() {
        ?>
        <div class="wrap wpoe-templates-page">
            <h1><?php _e('Document Templates', 'wp-office-editor'); ?></h1>
            
            <div id="wpoe-templates-container">
                <!-- سيتم تحميل واجهة القوالب عبر JavaScript -->
                <div class="wpoe-loading">
                    <p><?php _e('Loading templates...', 'wp-office-editor'); ?></p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // تحميل واجهة القوالب
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wpoe_get_templates'
                },
                success: function(response) {
                    $('#wpoe-templates-container').html(response);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: الحصول على القوالب
     */
    public function ajax_get_templates() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'all';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'popularity';
        
        // تصفية القوالب
        $filtered_templates = $this->filter_templates($category, $search, $sort);
        
        ob_start();
        ?>
        <div class="wpoe-templates-wrapper">
            <!-- شريط البحث والتصفية -->
            <div class="wpoe-templates-header">
                <div class="wpoe-search-box">
                    <input type="text" id="wpoe-template-search" placeholder="<?php _e('Search templates...', 'wp-office-editor'); ?>">
                    <button class="button button-primary wpoe-search-button">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
                
                <div class="wpoe-filter-controls">
                    <select id="wpoe-template-category">
                        <option value="all"><?php _e('All Categories', 'wp-office-editor'); ?></option>
                        <?php foreach ($this->categories as $cat_id => $category_data): ?>
                            <option value="<?php echo esc_attr($cat_id); ?>">
                                <?php echo esc_html($category_data['name']); ?> (<?php echo $category_data['count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="wpoe-template-sort">
                        <option value="popularity"><?php _e('Most Popular', 'wp-office-editor'); ?></option>
                        <option value="name"><?php _e('Name (A-Z)', 'wp-office-editor'); ?></option>
                        <option value="rating"><?php _e('Highest Rated', 'wp-office-editor'); ?></option>
                        <option value="date"><?php _e('Newest First', 'wp-office-editor'); ?></option>
                    </select>
                    
                    <button class="button button-secondary" id="wpoe-upload-template">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Upload Template', 'wp-office-editor'); ?>
                    </button>
                    
                    <button class="button button-primary" id="wpoe-save-as-template">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Save Current as Template', 'wp-office-editor'); ?>
                    </button>
                </div>
            </div>
            
            <!-- عرض القوالب -->
            <div class="wpoe-templates-grid">
                <?php foreach ($filtered_templates as $template): ?>
                    <div class="wpoe-template-card" data-id="<?php echo esc_attr($template['id']); ?>">
                        <div class="wpoe-template-preview">
                            <div class="wpoe-template-icon" style="background: <?php echo esc_attr($template['color']); ?>;">
                                <span class="dashicons dashicons-<?php echo esc_attr($template['icon']); ?>"></span>
                            </div>
                            <div class="wpoe-template-meta">
                                <span class="wpoe-template-category">
                                    <?php echo esc_html($this->categories[$template['category']]['name']); ?>
                                </span>
                                <span class="wpoe-template-rating">
                                    <?php echo esc_html($template['rating']); ?> ★
                                </span>
                            </div>
                        </div>
                        
                        <div class="wpoe-template-content">
                            <h3><?php echo esc_html($template['name']); ?></h3>
                            <p><?php echo esc_html($template['description']); ?></p>
                            
                            <div class="wpoe-template-tags">
                                <?php foreach ($template['tags'] as $tag): ?>
                                    <span class="wpoe-template-tag"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="wpoe-template-actions">
                                <button class="button button-primary wpoe-use-template" 
                                        data-id="<?php echo esc_attr($template['id']); ?>">
                                    <?php _e('Use Template', 'wp-office-editor'); ?>
                                </button>
                                
                                <div class="wpoe-template-more-actions">
                                    <button class="button button-link wpoe-preview-template"
                                            data-id="<?php echo esc_attr($template['id']); ?>">
                                        <?php _e('Preview', 'wp-office-editor'); ?>
                                    </button>
                                    
                                    <?php if ($template['type'] === 'custom'): ?>
                                        <button class="button button-link wpoe-export-template"
                                                data-id="<?php echo esc_attr($template['id']); ?>">
                                            <?php _e('Export', 'wp-office-editor'); ?>
                                        </button>
                                        
                                        <button class="button button-link wpoe-delete-template"
                                                data-id="<?php echo esc_attr($template['id']); ?>"
                                                style="color: #dc3232;">
                                            <?php _e('Delete', 'wp-office-editor'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- نافذة المعاينة -->
        <div id="wpoe-template-preview-modal" class="wpoe-modal">
            <div class="wpoe-modal-content">
                <div class="wpoe-modal-header">
                    <h2 id="wpoe-preview-title"></h2>
                    <button class="wpoe-modal-close">&times;</button>
                </div>
                <div class="wpoe-modal-body">
                    <div id="wpoe-preview-content"></div>
                </div>
                <div class="wpoe-modal-footer">
                    <button class="button button-primary wpoe-use-from-preview">
                        <?php _e('Use This Template', 'wp-office-editor'); ?>
                    </button>
                    <button class="button button-secondary wpoe-modal-close">
                        <?php _e('Cancel', 'wp-office-editor'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- نافذة رفع القالب -->
        <div id="wpoe-upload-modal" class="wpoe-modal">
            <div class="wpoe-modal-content">
                <div class="wpoe-modal-header">
                    <h2><?php _e('Upload Template', 'wp-office-editor'); ?></h2>
                    <button class="wpoe-modal-close">&times;</button>
                </div>
                <div class="wpoe-modal-body">
                    <form id="wpoe-upload-form" enctype="multipart/form-data">
                        <div class="wpoe-form-group">
                            <label for="template-file"><?php _e('Template File', 'wp-office-editor'); ?></label>
                            <input type="file" id="template-file" name="template_file" accept=".json" required>
                            <p class="description">
                                <?php _e('Upload a JSON file containing your template.', 'wp-office-editor'); ?>
                            </p>
                        </div>
                        
                        <div class="wpoe-form-group">
                            <label for="template-name"><?php _e('Template Name', 'wp-office-editor'); ?></label>
                            <input type="text" id="template-name" name="template_name" required>
                        </div>
                        
                        <div class="wpoe-form-group">
                            <label for="template-description"><?php _e('Description', 'wp-office-editor'); ?></label>
                            <textarea id="template-description" name="template_description" rows="3"></textarea>
                        </div>
                        
                        <div class="wpoe-form-group">
                            <label for="template-category"><?php _e('Category', 'wp-office-editor'); ?></label>
                            <select id="template-category" name="template_category">
                                <?php foreach ($this->categories as $cat_id => $category_data): ?>
                                    <option value="<?php echo esc_attr($cat_id); ?>">
                                        <?php echo esc_html($category_data['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="wpoe-form-group">
                            <label for="template-tags"><?php _e('Tags (comma-separated)', 'wp-office-editor'); ?></label>
                            <input type="text" id="template-tags" name="template_tags">
                        </div>
                    </form>
                </div>
                <div class="wpoe-modal-footer">
                    <button class="button button-primary" id="wpoe-upload-submit">
                        <?php _e('Upload Template', 'wp-office-editor'); ?>
                    </button>
                    <button class="button button-secondary wpoe-modal-close">
                        <?php _e('Cancel', 'wp-office-editor'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .wpoe-templates-wrapper {
            margin-top: 20px;
        }
        
        .wpoe-templates-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .wpoe-search-box {
            display: flex;
            gap: 10px;
        }
        
        .wpoe-search-box input {
            min-width: 300px;
        }
        
        .wpoe-filter-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .wpoe-templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .wpoe-template-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .wpoe-template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .wpoe-template-preview {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .wpoe-template-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 32px;
        }
        
        .wpoe-template-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
        }
        
        .wpoe-template-content {
            padding: 20px;
        }
        
        .wpoe-template-content h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .wpoe-template-content p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .wpoe-template-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .wpoe-template-tag {
            background: #e8f4fc;
            color: #3498db;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .wpoe-template-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wpoe-template-more-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Modal Styles */
        .wpoe-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        .wpoe-modal-content {
            background: white;
            margin: 50px auto;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        
        .wpoe-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wpoe-modal-header h2 {
            margin: 0;
        }
        
        .wpoe-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .wpoe-modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        
        .wpoe-modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .wpoe-form-group {
            margin-bottom: 20px;
        }
        
        .wpoe-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .wpoe-form-group input,
        .wpoe-form-group select,
        .wpoe-form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .wpoe-templates-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .wpoe-search-box input {
                min-width: auto;
                flex: 1;
            }
            
            .wpoe-filter-controls {
                flex-wrap: wrap;
            }
            
            .wpoe-templates-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var currentTemplateId = null;
            
            // البحث والتصفية
            $('#wpoe-template-search, #wpoe-template-category, #wpoe-template-sort').on('change keyup', function() {
                var search = $('#wpoe-template-search').val();
                var category = $('#wpoe-template-category').val();
                var sort = $('#wpoe-template-sort').val();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'wpoe_get_templates',
                        category: category,
                        search: search,
                        sort: sort,
                        nonce: '<?php echo wp_create_nonce('wpoe_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#wpoe-templates-container').html(response);
                        initTemplateActions();
                    }
                });
            });
            
            // استخدام القالب
            $(document).on('click', '.wpoe-use-template', function() {
                var templateId = $(this).data('id');
                useTemplate(templateId);
            });
            
            // معاينة القالب
            $(document).on('click', '.wpoe-preview-template', function() {
                var templateId = $(this).data('id');
                previewTemplate(templateId);
            });
            
            // تصدير القالب
            $(document).on('click', '.wpoe-export-template', function() {
                var templateId = $(this).data('id');
                exportTemplate(templateId);
            });
            
            // حذف القالب
            $(document).on('click', '.wpoe-delete-template', function() {
                var templateId = $(this).data('id');
                if (confirm('<?php _e('Are you sure you want to delete this template?', 'wp-office-editor'); ?>')) {
                    deleteTemplate(templateId);
                }
            });
            
            // فتح نافذة الرفع
            $('#wpoe-upload-template').on('click', function() {
                $('#wpoe-upload-modal').show();
            });
            
            // حفظ القالب الحالي كقالب
            $('#wpoe-save-as-template').on('click', function() {
                // هذه الوظيفة ستتم من خلال المحرر الرئيسي
                if (typeof window.wpoeEditor !== 'undefined') {
                    window.wpoeEditor.saveAsTemplate();
                } else {
                    alert('<?php _e('Please open the editor first', 'wp-office-editor'); ?>');
                }
            });
            
            // إرسال نموذج الرفع
            $('#wpoe-upload-submit').on('click', function() {
                var formData = new FormData($('#wpoe-upload-form')[0]);
                formData.append('action', 'wpoe_upload_template_file');
                formData.append('nonce', '<?php echo wp_create_nonce('wpoe_nonce'); ?>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });
            
            // إغلاق النوافذ
            $('.wpoe-modal-close').on('click', function() {
                $(this).closest('.wpoe-modal').hide();
            });
            
            // استخدام القالب من المعاينة
            $('.wpoe-use-from-preview').on('click', function() {
                if (currentTemplateId) {
                    useTemplate(currentTemplateId);
                    $('#wpoe-template-preview-modal').hide();
                }
            });
            
            // تهيئة الأحداث بعد تحميل القوالب
            function initTemplateActions() {
                // إعادة ربط الأحداث
            }
            
            // استخدام القالب
            function useTemplate(templateId) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'wpoe_create_from_template',
                        template_id: templateId,
                        nonce: '<?php echo wp_create_nonce('wpoe_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // فتح المحرر مع القالب
                            window.location.href = '<?php echo admin_url('admin.php?page=wp-office-editor'); ?>&content=' + encodeURIComponent(response.data.content);
                        } else {
                            alert(response.data);
                        }
                    }
                });
            }
            
            // معاينة القالب
            function previewTemplate(templateId) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'wpoe_get_template_preview',
                        template_id: templateId,
                        nonce: '<?php echo wp_create_nonce('wpoe_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            currentTemplateId = templateId;
                            $('#wpoe-preview-title').text(response.data.name);
                            $('#wpoe-preview-content').html(response.data.preview);
                            $('#wpoe-template-preview-modal').show();
                        }
                    }
                });
            }
            
            // تصدير القالب
            function exportTemplate(templateId) {
                window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=wpoe_export_template&template_id=' + templateId + '&nonce=<?php echo wp_create_nonce("wpoe_nonce"); ?>';
            }
            
            // حذف القالب
            function deleteTemplate(templateId) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'wpoe_delete_template',
                        template_id: templateId,
                        nonce: '<?php echo wp_create_nonce('wpoe_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    }
                });
            }
            
            // تهيئة أولية
            initTemplateActions();
        });
        </script>
        <?php
        
        wp_die();
    }
    
    /**
     * AJAX: إنشاء مستند من قالب
     */
    public function ajax_create_from_template() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        
        if (empty($template_id) || !isset($this->templates[$template_id])) {
            wp_send_json_error(__('Template not found', 'wp-office-editor'));
        }
        
        $template = $this->templates[$template_id];
        
        wp_send_json_success([
            'content' => $template['content'],
            'name' => $template['name']
        ]);
    }
    
    /**
     * AJAX: حفظ كقالب
     */
    public function ajax_save_as_template() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'personal';
        $tags = isset($_POST['tags']) ? array_map('trim', explode(',', sanitize_text_field($_POST['tags']))) : [];
        
        if (empty($name) || empty($content)) {
            wp_send_json_error(__('Name and content are required', 'wp-office-editor'));
        }
        
        // إنشاء معرف فريد للقالب
        $template_id = 'custom_' . uniqid();
        
        $template_data = [
            'id' => $template_id,
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'type' => 'custom',
            'icon' => 'fa-file-alt',
            'color' => $this->categories[$category]['color'],
            'content' => $content,
            'tags' => $tags,
            'created_at' => current_time('mysql'),
            'modified_at' => current_time('mysql'),
            'author' => get_current_user_id(),
            'popularity' => 0,
            'rating' => 0
        ];
        
        // حفظ القالب في ملف
        $filename = $template_id . '.json';
        $filepath = $this->template_dir . $filename;
        
        if (file_put_contents($filepath, json_encode($template_data, JSON_PRETTY_PRINT))) {
            // إضافة القالب للمصفوفة
            $this->templates[$template_id] = $template_data;
            wp_send_json_success(__('Template saved successfully', 'wp-office-editor'));
        } else {
            wp_send_json_error(__('Failed to save template', 'wp-office-editor'));
        }
    }
    
    /**
     * AJAX: استيراد قالب
     */
    public function ajax_import_template() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        wp_send_json_success(__('Template imported successfully', 'wp-office-editor'));
    }
    
    /**
     * AJAX: تصدير قالب
     */
    public function ajax_export_template() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        $template_id = isset($_GET['template_id']) ? sanitize_text_field($_GET['template_id']) : '';
        
        if (empty($template_id) || !isset($this->templates[$template_id])) {
            wp_die(__('Template not found', 'wp-office-editor'));
        }
        
        $template = $this->templates[$template_id];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $template['id'] . '.json"');
        echo json_encode($template, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * AJAX: حذف قالب
     */
    public function ajax_delete_template() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        
        if (empty($template_id) || !isset($this->templates[$template_id])) {
            wp_send_json_error(__('Template not found', 'wp-office-editor'));
        }
        
        $template = $this->templates[$template_id];
        
        // حذف ملف القالب إذا كان مخصصاً
        if (isset($template['file'])) {
            $filepath = $this->template_dir . $template['file'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // حذف القالب من المصفوفة
        unset($this->templates[$template_id]);
        
        wp_send_json_success(__('Template deleted successfully', 'wp-office-editor'));
    }
    
    /**
     * AJAX: رفع ملف القالب
     */
    public function ajax_upload_template_file() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('You do not have permission to upload files', 'wp-office-editor'));
        }
        
        if (!isset($_FILES['template_file'])) {
            wp_send_json_error(__('No file uploaded', 'wp-office-editor'));
        }
        
        $file = $_FILES['template_file'];
        
        // التحقق من نوع الملف
        $filetype = wp_check_filetype($file['name']);
        if ($filetype['ext'] !== 'json') {
            wp_send_json_error(__('Only JSON files are allowed', 'wp-office-editor'));
        }
        
        // قراءة محتوى الملف
        $content = file_get_contents($file['tmp_name']);
        $template_data = json_decode($content, true);
        
        if (!$template_data || !isset($template_data['id'])) {
            wp_send_json_error(__('Invalid template file', 'wp-office-editor'));
        }
        
        // تحديث البيانات إذا تم تقديمها
        if (isset($_POST['template_name'])) {
            $template_data['name'] = sanitize_text_field($_POST['template_name']);
        }
        
        if (isset($_POST['template_description'])) {
            $template_data['description'] = sanitize_text_field($_POST['template_description']);
        }
        
        if (isset($_POST['template_category'])) {
            $template_data['category'] = sanitize_text_field($_POST['template_category']);
        }
        
        if (isset($_POST['template_tags'])) {
            $tags = array_map('trim', explode(',', sanitize_text_field($_POST['template_tags'])));
            $template_data['tags'] = $tags;
        }
        
        // إضافة بيانات إضافية
        $template_data['type'] = 'custom';
        $template_data['file'] = basename($file['name']);
        $template_data['created_at'] = current_time('mysql');
        $template_data['modified_at'] = current_time('mysql');
        $template_data['author'] = get_current_user_id();
        
        if (!isset($template_data['icon'])) {
            $template_data['icon'] = 'fa-file-alt';
        }
        
        if (!isset($template_data['color'])) {
            $template_data['color'] = $this->categories[$template_data['category']]['color'];
        }
        
        // حفظ الملف
        $filename = $template_data['id'] . '.json';
        $filepath = $this->template_dir . $filename;
        
        if (file_put_contents($filepath, json_encode($template_data, JSON_PRETTY_PRINT))) {
            wp_send_json_success(__('Template uploaded successfully', 'wp-office-editor'));
        } else {
            wp_send_json_error(__('Failed to save template', 'wp-office-editor'));
        }
    }
    
    /**
     * AJAX: الحصول على فئات القوالب
     */
    public function ajax_get_categories() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        wp_send_json_success($this->categories);
    }
    
    /**
     * AJAX: الحصول على معاينة القالب
     */
    public function ajax_get_template_preview() {
        check_ajax_referer('wpoe_nonce', 'nonce');
        
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        
        if (empty($template_id) || !isset($this->templates[$template_id])) {
            wp_send_json_error(__('Template not found', 'wp-office-editor'));
        }
        
        $template = $this->templates[$template_id];
        
        wp_send_json_success([
            'name' => $template['name'],
            'preview' => $template['content']
        ]);
    }
    
    /**
     * تسجيل نقاط نهاية REST API
     */
    public function register_rest_endpoints() {
        register_rest_route('wpoe/v1', '/templates', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_templates'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        register_rest_route('wpoe/v1', '/templates/(?P<id>\w+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_template'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }
    
    /**
     * REST API: الحصول على القوالب
     */
    public function rest_get_templates(WP_REST_Request $request) {
        $category = $request->get_param('category') ?: 'all';
        $search = $request->get_param('search') ?: '';
        $sort = $request->get_param('sort') ?: 'popularity';
        
        $filtered_templates = $this->filter_templates($category, $search, $sort);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => array_values($filtered_templates)
        ], 200);
    }
    
    /**
     * REST API: الحصول على قالب محدد
     */
    public function rest_get_template(WP_REST_Request $request) {
        $template_id = $request->get_param('id');
        
        if (!isset($this->templates[$template_id])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $this->templates[$template_id]
        ], 200);
    }
    
    /**
     * تصفية القوالب
     */
    private function filter_templates($category = 'all', $search = '', $sort = 'popularity') {
        $filtered = $this->templates;
        
        // التصفية حسب الفئة
        if ($category !== 'all') {
            $filtered = array_filter($filtered, function($template) use ($category) {
                return isset($template['category']) && $template['category'] === $category;
            });
        }
        
        // البحث
        if (!empty($search)) {
            $filtered = array_filter($filtered, function($template) use ($search) {
                $search = strtolower($search);
                $name = strtolower($template['name']);
                $description = strtolower($template['description']);
                $tags = isset($template['tags']) ? array_map('strtolower', $template['tags']) : [];
                
                return strpos($name, $search) !== false || 
                       strpos($description, $search) !== false ||
                       in_array($search, $tags) ||
                       array_filter($tags, function($tag) use ($search) {
                           return strpos($tag, $search) !== false;
                       });
            });
        }
        
        // الترتيب
        switch ($sort) {
            case 'name':
                usort($filtered, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                break;
                
            case 'rating':
                usort($filtered, function($a, $b) {
                    return $b['rating'] <=> $a['rating'];
                });
                break;
                
            case 'date':
                usort($filtered, function($a, $b) {
                    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
                });
                break;
                
            case 'popularity':
            default:
                usort($filtered, function($a, $b) {
                    return $b['popularity'] <=> $a['popularity'];
                });
                break;
        }
        
        return $filtered;
    }
    
    /**
     * الحصول على كل القوالب
     */
    public function get_all_templates() {
        return $this->templates;
    }
    
    /**
     * الحصول على الفئات
     */
    public function get_categories() {
        return $this->categories;
    }
    
    /**
     * الحصول على قالب محدد
     */
    public function get_template($template_id) {
        return isset($this->templates[$template_id]) ? $this->templates[$template_id] : null;
    }
}
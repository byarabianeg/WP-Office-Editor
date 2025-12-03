<?php
class WP_Office_Editor_AI {
    
    private $api_key;
    private $api_base_url = 'https://api.openai.com/v1';
    private $available_models = [
        'gpt-4' => 'GPT-4',
        'gpt-4-turbo-preview' => 'GPT-4 Turbo',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        'gpt-3.5-turbo-instruct' => 'GPT-3.5 Turbo Instruct'
    ];
    
    private $templates = [
        'blog_post' => [
            'name' => 'Blog Post',
            'description' => 'Generate a professional blog post',
            'prompt_template' => 'Write a blog post about {topic} with the following requirements:\n\n- Title: {title}\n- Tone: {tone}\n- Target audience: {audience}\n- Length: {words} words\n\nInclude an introduction, main points, and conclusion.'
        ],
        'report' => [
            'name' => 'Report',
            'description' => 'Generate a formal report',
            'prompt_template' => 'Write a formal report about {topic} with the following structure:\n\n1. Executive Summary\n2. Introduction\n3. Methodology\n4. Findings\n5. Recommendations\n6. Conclusion\n\nMake it professional and data-driven.'
        ],
        'business_letter' => [
            'name' => 'Business Letter',
            'description' => 'Generate a business letter',
            'prompt_template' => 'Write a business letter with the following details:\n\n- Recipient: {recipient}\n- Subject: {subject}\n- Purpose: {purpose}\n- Tone: {tone}\n- Key points: {key_points}\n\nInclude proper salutation and closing.'
        ],
        'email' => [
            'name' => 'Email',
            'description' => 'Generate a professional email',
            'prompt_template' => 'Write a professional email with the following details:\n\n- To: {recipient}\n- Subject: {subject}\n- Purpose: {purpose}\n- Tone: {tone}\n- Key information: {key_info}\n\nKeep it concise and clear.'
        ],
        'social_media' => [
            'name' => 'Social Media Post',
            'description' => 'Generate social media content',
            'prompt_template' => 'Create {platform} post about {topic} with the following requirements:\n\n- Tone: {tone}\n- Hashtags: {hashtags}\n- Call to action: {cta}\n- Length: {characters} characters\n\nMake it engaging and shareable.'
        ],
        'product_description' => [
            'name' => 'Product Description',
            'description' => 'Generate product descriptions',
            'prompt_template' => 'Write a product description for {product_name} with the following features:\n\n- Key features: {features}\n- Target audience: {audience}\n- Benefits: {benefits}\n- Price point: {price}\n\nMake it persuasive and highlight unique selling points.'
        ],
        'seo_article' => [
            'name' => 'SEO Article',
            'description' => 'Generate SEO-optimized articles',
            'prompt_template' => 'Write an SEO-optimized article about {topic} with the following requirements:\n\n- Primary keyword: {keyword}\n- Secondary keywords: {secondary_keywords}\n- Word count: {words} words\n- Target audience: {audience}\n- Tone: {tone}\n\nInclude meta description, headings with keywords, and internal linking suggestions.'
        ]
    ];
    
    private $writing_styles = [
        'formal' => 'Formal and professional',
        'casual' => 'Casual and friendly',
        'persuasive' => 'Persuasive and convincing',
        'academic' => 'Academic and scholarly',
        'creative' => 'Creative and imaginative',
        'technical' => 'Technical and detailed'
    ];
    
    public function __construct() {
        $settings = get_option('wpoe_settings', []);
        $this->api_key = isset($settings['ai_api_key']) ? $settings['ai_api_key'] : '';
    }
    
    /**
     * توليد محتوى باستخدام الذكاء الاصطناعي
     */
    public function generate_content($prompt, $context = '', $action = 'generate', $options = []) {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => __('API key is not configured. Please add your OpenAI API key in settings.', 'wp-office-editor')
            ];
        }
        
        // معالجة الإجراءات المختلفة
        $processed_prompt = $this->process_action($prompt, $context, $action, $options);
        
        if (empty($processed_prompt)) {
            return [
                'success' => false,
                'message' => __('Invalid prompt or action.', 'wp-office-editor')
            ];
        }
        
        // إعداد البيانات للطلب
        $request_data = $this->prepare_request_data($processed_prompt, $options);
        
        // إرسال الطلب إلى OpenAI
        $response = $this->make_api_request($request_data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'content' => $this->clean_response($response['content']),
                'tokens_used' => $response['tokens_used'],
                'model' => $response['model'],
                'cost' => $this->calculate_cost($response['tokens_used'], $response['model'])
            ];
        }
        
        return $response;
    }
    
    /**
     * معالجة الإجراءات المختلفة
     */
    private function process_action($prompt, $context, $action, $options) {
        $final_prompt = '';
        
        switch ($action) {
            case 'improve':
                $final_prompt = "Please improve the following text for better clarity, grammar, and style while preserving its original meaning:\n\n" . 
                              "Original text: " . $context . "\n\n" .
                              "Improvement instructions: " . $prompt . "\n\n" .
                              "Provide only the improved text without additional explanations.";
                break;
                
            case 'summarize':
                $final_prompt = "Please summarize the following text in a clear and concise manner:\n\n" . 
                              "Text to summarize: " . $context . "\n\n" .
                              "Summary instructions: " . $prompt . "\n\n" .
                              "Provide only the summary without additional text.";
                break;
                
            case 'translate':
                $target_language = isset($options['target_language']) ? $options['target_language'] : 'English';
                $final_prompt = "Please translate the following text to " . $target_language . ":\n\n" . 
                              "Original text: " . $context . "\n\n" .
                              "Translation instructions: " . $prompt . "\n\n" .
                              "Provide only the translation without additional text.";
                break;
                
            case 'expand':
                $final_prompt = "Please expand on the following text, adding more details, examples, and explanations:\n\n" . 
                              "Original text: " . $context . "\n\n" .
                              "Expansion instructions: " . $prompt . "\n\n" .
                              "Provide only the expanded text without additional explanations.";
                break;
                
            case 'simplify':
                $final_prompt = "Please simplify the following text to make it easier to understand for a general audience:\n\n" . 
                              "Original text: " . $context . "\n\n" .
                              "Simplify to reading level: " . (isset($options['reading_level']) ? $options['reading_level'] : '8th grade') . "\n\n" .
                              "Provide only the simplified text without additional explanations.";
                break;
                
            case 'code_explain':
                $final_prompt = "Please explain the following code:\n\n" . 
                              "Code: " . $context . "\n\n" .
                              "Explanation instructions: " . $prompt . "\n\n" .
                              "Provide a detailed explanation including:\n1. What the code does\n2. How it works\n3. Key functions/variables\n4. Any potential issues or improvements";
                break;
                
            case 'code_generate':
                $final_prompt = "Please generate code with the following requirements:\n\n" . 
                              "Programming language: " . (isset($options['language']) ? $options['language'] : 'PHP') . "\n" .
                              "Requirements: " . $prompt . "\n\n" .
                              "Provide complete, working code with comments explaining key parts.";
                break;
                
            case 'code_debug':
                $final_prompt = "Please help debug the following code:\n\n" . 
                              "Code: " . $context . "\n\n" .
                              "Error or issue: " . $prompt . "\n\n" .
                              "Provide:\n1. Identification of the issue\n2. Explanation of why it's happening\n3. Fixed code\n4. Suggestions to prevent similar issues";
                break;
                
            case 'template_blog':
                $template_data = $this->templates['blog_post'];
                $final_prompt = $this->fill_template($template_data['prompt_template'], $options);
                break;
                
            case 'template_report':
                $template_data = $this->templates['report'];
                $final_prompt = $this->fill_template($template_data['prompt_template'], $options);
                break;
                
            case 'template_letter':
                $template_data = $this->templates['business_letter'];
                $final_prompt = $this->fill_template($template_data['prompt_template'], $options);
                break;
                
            case 'template_email':
                $template_data = $this->templates['email'];
                $final_prompt = $this->fill_template($template_data['prompt_template'], $options);
                break;
                
            case 'template_social':
                $template_data = $this->templates['social_media'];
                $final_prompt = $this->fill_template($template_data['prompt_template'], $options);
                break;
                
            case 'template_product':
                $template_data = $this->templates['product_description'];
                $final_prompt = $this->fill_template($template_data['prompt_template'], $options);
                break;
                
            case 'template_seo':
                $template_data = $this->templates['seo_article'];
                $final_prompt = $this->fill_template($template_data['prompt_template'], $options);
                break;
                
            default:
                // إجراء افتراضي - توليد محتوى عادي
                if (!empty($context)) {
                    $final_prompt = "Context: " . $context . "\n\n" .
                                  "Based on the above context, please: " . $prompt . "\n\n" .
                                  "Provide only the requested content without additional explanations.";
                } else {
                    $final_prompt = $prompt;
                }
                break;
        }
        
        return $final_prompt;
    }
    
    /**
     * ملء القالب بالبيانات
     */
    private function fill_template($template, $data) {
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $template = str_replace($placeholder, $value, $template);
        }
        
        // استبدال العناصر الفارغة بالقيم الافتراضية
        $defaults = [
            '{topic}' => 'the subject',
            '{title}' => 'Untitled',
            '{tone}' => 'professional',
            '{audience}' => 'general audience',
            '{words}' => '500',
            '{recipient}' => 'the recipient',
            '{subject}' => 'Subject',
            '{purpose}' => 'the purpose',
            '{key_points}' => 'main points',
            '{platform}' => 'social media',
            '{hashtags}' => '#content #post',
            '{cta}' => 'Learn more',
            '{characters}' => '280',
            '{product_name}' => 'the product',
            '{features}' => 'key features',
            '{benefits}' => 'main benefits',
            '{price}' => 'competitive price',
            '{keyword}' => 'main keyword',
            '{secondary_keywords}' => 'related keywords'
        ];
        
        foreach ($defaults as $placeholder => $default_value) {
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $default_value, $template);
            }
        }
        
        return $template;
    }
    
    /**
     * إعداد بيانات الطلب
     */
    private function prepare_request_data($prompt, $options) {
        $model = isset($options['model']) && array_key_exists($options['model'], $this->available_models) 
                ? $options['model'] 
                : 'gpt-3.5-turbo';
        
        $max_tokens = isset($options['max_tokens']) ? intval($options['max_tokens']) : 2000;
        $temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.7;
        $presence_penalty = isset($options['presence_penalty']) ? floatval($options['presence_penalty']) : 0.0;
        $frequency_penalty = isset($options['frequency_penalty']) ? floatval($options['frequency_penalty']) : 0.0;
        
        $messages = [];
        
        // إضافة تعليمات النظام إذا كانت متوفرة
        if (isset($options['system_instruction']) && !empty($options['system_instruction'])) {
            $messages[] = [
                'role' => 'system',
                'content' => $options['system_instruction']
            ];
        } else {
            // تعليمات النظام الافتراضية
            $messages[] = [
                'role' => 'system',
                'content' => 'You are a helpful writing assistant. Provide clear, concise, and professional responses. Format responses appropriately for the context.'
            ];
        }
        
        // إضافة رسالة المستخدم
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
        
        return [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'presence_penalty' => $presence_penalty,
            'frequency_penalty' => $frequency_penalty,
            'top_p' => 1.0,
            'stream' => false
        ];
    }
    
    /**
     * إرسال طلب API
     */
    private function make_api_request($data) {
        $url = $this->api_base_url . '/chat/completions';
        
        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ];
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => __('API request failed: ', 'wp-office-editor') . $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return [
                'success' => false,
                'message' => __('API error: ', 'wp-office-editor') . $data['error']['message']
            ];
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'message' => __('Invalid API response format.', 'wp-office-editor')
            ];
        }
        
        return [
            'success' => true,
            'content' => $data['choices'][0]['message']['content'],
            'tokens_used' => $data['usage']['total_tokens'],
            'model' => $data['model'],
            'finish_reason' => $data['choices'][0]['finish_reason']
        ];
    }
    
    /**
     * تنظيف الاستجابة
     */
    private function clean_response($content) {
        // إزالة علامات الاقتباس الزائدة
        $content = trim($content, "\"'\n\r\t ");
        
        // إزالة بادئات مثل "الإجابة:" أو "Answer:"
        $patterns = [
            '/^(الإجابة|الجواب|النتيجة|المحتوى|Content|Answer|Response|Result):\s*/iu',
            '/^"(.*)"$/s',
            '/^\'(.*)\'$/s'
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '$1', $content);
        }
        
        // تأكد من أن المحتوى لا يبدأ أو ينتهي بمسافات بيضاء
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * حساب التكلفة
     */
    private function calculate_cost($tokens, $model) {
        $cost_per_1k_tokens = [
            'gpt-4' => 0.06,
            'gpt-4-turbo-preview' => 0.03,
            'gpt-3.5-turbo' => 0.002,
            'gpt-3.5-turbo-instruct' => 0.0015
        ];
        
        $model_cost = isset($cost_per_1k_tokens[$model]) ? $cost_per_1k_tokens[$model] : 0.002;
        $cost = ($tokens / 1000) * $model_cost;
        
        return round($cost, 6);
    }
    
    /**
     * الحصول على القوالب المتاحة
     */
    public function get_templates() {
        return $this->templates;
    }
    
    /**
     * الحصول على أنماط الكتابة
     */
    public function get_writing_styles() {
        return $this->writing_styles;
    }
    
    /**
     * الحصول على النماذج المتاحة
     */
    public function get_available_models() {
        return $this->available_models;
    }
    
    /**
     * التحقق من صحة مفتاح API
     */
    public function validate_api_key($api_key = null) {
        $key_to_test = $api_key ?: $this->api_key;
        
        if (empty($key_to_test)) {
            return [
                'success' => false,
                'message' => __('API key is empty.', 'wp-office-editor')
            ];
        }
        
        $url = $this->api_base_url . '/models';
        
        $args = [
            'method' => 'GET',
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $key_to_test
            ]
        ];
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => __('Connection failed: ', 'wp-office-editor') . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return [
                'success' => true,
                'message' => __('API key is valid.', 'wp-office-editor')
            ];
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            $error_message = isset($data['error']['message']) 
                           ? $data['error']['message'] 
                           : __('Invalid API key (Status: ', 'wp-office-editor') . $status_code . ')';
            
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
    }
    
    /**
     * توليد أفكار للمحتوى
     */
    public function generate_ideas($topic, $count = 5, $options = []) {
        $prompt = "Generate " . $count . " creative ideas or angles for content about: " . $topic . "\n\n";
        
        if (isset($options['content_type'])) {
            $prompt .= "Content type: " . $options['content_type'] . "\n";
        }
        
        if (isset($options['target_audience'])) {
            $prompt .= "Target audience: " . $options['target_audience'] . "\n";
        }
        
        if (isset($options['tone'])) {
            $prompt .= "Tone: " . $options['tone'] . "\n";
        }
        
        $prompt .= "\nProvide each idea with:\n1. A compelling title\n2. Brief description (1-2 sentences)\n3. Key points to cover\n\nFormat as a numbered list.";
        
        return $this->generate_content($prompt, '', 'generate', $options);
    }
    
    /**
     * تحليل وتحسين SEO
     */
    public function analyze_seo($content, $options = []) {
        $prompt = "Analyze the following content for SEO and provide recommendations:\n\n" .
                 "Content to analyze:\n" . substr($content, 0, 3000) . "\n\n" .
                 "Provide analysis in these areas:\n" .
                 "1. Keyword optimization\n" .
                 "2. Readability and structure\n" .
                 "3. Meta suggestions (title, description)\n" .
                 "4. Internal/external linking opportunities\n" .
                 "5. Content gaps and improvements\n\n" .
                 "Be specific and actionable.";
        
        return $this->generate_content($prompt, '', 'generate', $options);
    }
    
    /**
     * إنشاء أسئلة وأجوبة
     */
    public function generate_faq($topic, $count = 5, $options = []) {
        $prompt = "Generate " . $count . " frequently asked questions (FAQ) about: " . $topic . "\n\n" .
                 "For each FAQ, provide:\n" .
                 "1. The question (common and relevant)\n" .
                 "2. A detailed, helpful answer\n\n" .
                 "Format as:\n" .
                 "Q: [Question]\n" .
                 "A: [Answer]\n\n" .
                 "Make the answers accurate and valuable.";
        
        return $this->generate_content($prompt, '', 'generate', $options);
    }
    
    /**
     * تحويل النص إلى نقاط رئيسية
     */
    public function text_to_bullets($content, $options = []) {
        $prompt = "Convert the following text into clear, concise bullet points:\n\n" .
                 "Text:\n" . $content . "\n\n" .
                 "Requirements:\n" .
                 "- Extract key information\n" .
                 "- Use clear, actionable language\n" .
                 "- Organize logically\n" .
                 "- Keep each bullet point concise\n\n" .
                 "Provide only the bullet points without additional text.";
        
        return $this->generate_content($prompt, '', 'generate', $options);
    }
    
    /**
     * إنشاء أوصاف ميتا
     */
    public function generate_meta_description($content, $options = []) {
        $prompt = "Generate 3 compelling meta descriptions (150-160 characters each) for the following content:\n\n" .
                 "Content:\n" . substr($content, 0, 2000) . "\n\n" .
                 "Requirements for each meta description:\n" .
                 "1. Include primary keyword naturally\n" .
                 "2. Create curiosity and encourage clicks\n" .
                 "3. Accurate representation of content\n" .
                 "4. Clear call-to-action\n\n" .
                 "Format as:\n" .
                 "Option 1: [description]\n" .
                 "Option 2: [description]\n" .
                 "Option 3: [description]";
        
        return $this->generate_content($prompt, '', 'generate', $options);
    }
    
    /**
     * تسجيل استخدام AI للإحصائيات
     */
    public function log_usage($user_id, $action, $tokens_used, $model, $cost = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpoe_ai_usage';
        
        // إنشاء الجدول إذا لم يكن موجوداً
        $this->create_usage_table();
        
        $data = [
            'user_id' => $user_id,
            'action' => $action,
            'tokens_used' => $tokens_used,
            'model' => $model,
            'estimated_cost' => $cost,
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert($table_name, $data);
        
        // تحديم إحصائيات المستخدم
        $user_stats = get_user_meta($user_id, 'wpoe_ai_stats', true);
        if (empty($user_stats)) {
            $user_stats = [
                'total_tokens' => 0,
                'total_cost' => 0,
                'total_requests' => 0,
                'last_used' => current_time('mysql')
            ];
        }
        
        $user_stats['total_tokens'] += $tokens_used;
        $user_stats['total_cost'] += $cost;
        $user_stats['total_requests']++;
        $user_stats['last_used'] = current_time('mysql');
        
        update_user_meta($user_id, 'wpoe_ai_stats', $user_stats);
        
        return true;
    }
    
    /**
     * إنشاء جدول استخدام AI
     */
    private function create_usage_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpoe_ai_usage';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            tokens_used int(11) NOT NULL,
            model varchar(50) NOT NULL,
            estimated_cost decimal(10,6) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * الحصول على إحصائيات استخدام AI
     */
    public function get_usage_stats($period = 'month', $user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpoe_ai_usage';
        
        $where_clauses = [];
        $params = [];
        
        if ($user_id) {
            $where_clauses[] = 'user_id = %d';
            $params[] = $user_id;
        }
        
        // تحديد الفترة الزمنية
        $date_condition = '';
        switch ($period) {
            case 'day':
                $date_condition = 'created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
                break;
            case 'week':
                $date_condition = 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case 'month':
                $date_condition = 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case 'year':
                $date_condition = 'created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)';
                break;
            default:
                $date_condition = '1=1';
        }
        
        $where_clauses[] = $date_condition;
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        // استعلام الإحصائيات
        $stats_query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_requests,
                SUM(tokens_used) as total_tokens,
                SUM(estimated_cost) as total_cost,
                AVG(tokens_used) as avg_tokens,
                model
             FROM $table_name
             $where_sql
             GROUP BY model",
            $params
        );
        
        $stats = $wpdb->get_results($stats_query);
        
        // استعلام الاستخدام اليومي
        $daily_query = $wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(tokens_used) as tokens
             FROM $table_name
             $where_sql
             GROUP BY DATE(created_at)
             ORDER BY date DESC
             LIMIT 30",
            $params
        );
        
        $daily_usage = $wpdb->get_results($daily_query);
        
        // استعلام أكثر الإجراءات استخداماً
        $actions_query = $wpdb->prepare(
            "SELECT 
                action,
                COUNT(*) as count,
                SUM(tokens_used) as tokens
             FROM $table_name
             $where_sql
             GROUP BY action
             ORDER BY count DESC
             LIMIT 10",
            $params
        );
        
        $top_actions = $wpdb->get_results($actions_query);
        
        return [
            'period' => $period,
            'stats' => $stats,
            'daily_usage' => $daily_usage,
            'top_actions' => $top_actions,
            'total_requests' => array_sum(array_column($stats, 'total_requests')),
            'total_tokens' => array_sum(array_column($stats, 'total_tokens')),
            'total_cost' => array_sum(array_column($stats, 'total_cost'))
        ];
    }
    
    /**
     * الحصول على اقتراحات بناءً على المحتوى
     */
    public function get_content_suggestions($content, $type = 'improvements') {
        $prompt = '';
        
        switch ($type) {
            case 'improvements':
                $prompt = "Analyze the following text and suggest specific improvements:\n\n" .
                         "Text:\n" . $content . "\n\n" .
                         "Provide suggestions in these areas:\n" .
                         "1. Grammar and spelling\n" .
                         "2. Clarity and readability\n" .
                         "3. Structure and flow\n" .
                         "4. Word choice and tone\n" .
                         "5. Overall effectiveness\n\n" .
                         "Be specific and provide examples where possible.";
                break;
                
            case 'headings':
                $prompt = "Suggest better headings and subheadings for the following content:\n\n" .
                         "Content:\n" . $content . "\n\n" .
                         "Provide:\n" .
                         "1. A compelling main title\n" .
                         "2. 3-5 section headings\n" .
                         "3. Brief description of what each section should cover\n\n" .
                         "Make headings engaging and SEO-friendly.";
                break;
                
            case 'cta':
                $prompt = "Generate 5 effective call-to-action (CTA) statements for the following content:\n\n" .
                         "Content:\n" . $content . "\n\n" .
                         "CTAs should be:\n" .
                         "1. Clear and actionable\n" .
                         "2. Relevant to the content\n" .
                         "3. Varied in approach (some direct, some subtle)\n" .
                         "4. Engaging and persuasive\n\n" .
                         "Format each CTA on a new line.";
                break;
                
            case 'keywords':
                $prompt = "Suggest relevant keywords and key phrases for the following content:\n\n" .
                         "Content:\n" . $content . "\n\n" .
                         "Provide:\n" .
                         "1. 3-5 primary keywords\n" .
                         "2. 5-10 secondary keywords\n" .
                         "3. Search intent for each primary keyword\n" .
                         "4. Difficulty level estimate (low/medium/high)\n\n" .
                         "Format clearly with categories.";
                break;
        }
        
        return $this->generate_content($prompt, '', 'generate');
    }
    
    /**
     * التحقق من إمكانية الوصول إلى API
     */
    public function check_api_availability() {
        if (empty($this->api_key)) {
            return [
                'available' => false,
                'message' => __('API key not configured', 'wp-office-editor')
            ];
        }
        
        $validation = $this->validate_api_key();
        
        return [
            'available' => $validation['success'],
            'message' => $validation['message'],
            'models' => $validation['success'] ? $this->available_models : []
        ];
    }
    
    /**
     * إنشاء اقتراحات بناءً على التاريخ
     */
    public function generate_from_history($user_id, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpoe_ai_usage';
        
        $query = $wpdb->prepare(
            "SELECT action, model, created_at 
             FROM $table_name 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id, $limit
        );
        
        $history = $wpdb->get_results($query);
        
        if (empty($history)) {
            return null;
        }
        
        // تحليل التاريخ لتقديم اقتراحات مخصصة
        $actions = array_count_values(array_column($history, 'action'));
        arsort($actions);
        
        $most_used_action = key($actions);
        $action_count = current($actions);
        
        $suggestions = [];
        
        // اقتراحات بناءً على الإجراء الأكثر استخداماً
        switch ($most_used_action) {
            case 'improve':
                $suggestions[] = [
                    'title' => __('Try Advanced Improvement', 'wp-office-editor'),
                    'description' => __('You frequently use text improvement. Try our advanced grammar and style checker.', 'wp-office-editor'),
                    'action' => 'improve_advanced',
                    'icon' => 'magic'
                ];
                break;
                
            case 'summarize':
                $suggestions[] = [
                    'title' => __('Create Executive Summary', 'wp-office-editor'),
                    'description' => __('Based on your summarizing history, try creating executive summaries for reports.', 'wp-office-editor'),
                    'action' => 'template_report',
                    'icon' => 'file-alt'
                ];
                break;
                
            case 'translate':
                $suggestions[] = [
                    'title' => __('Translate Entire Document', 'wp-office-editor'),
                    'description' => __('You translate often. Try our batch translation feature for longer documents.', 'wp-office-editor'),
                    'action' => 'translate_batch',
                    'icon' => 'language'
                ];
                break;
        }
        
        // اقتراحات عامة
        $general_suggestions = [
            [
                'title' => __('Generate Blog Post Outline', 'wp-office-editor'),
                'description' => __('Create a structured outline for your next blog post.', 'wp-office-editor'),
                'action' => 'template_blog',
                'icon' => 'blog'
            ],
            [
                'title' => __('Improve SEO', 'wp-office-editor'),
                'description' => __('Optimize your content for better search engine ranking.', 'wp-office-editor'),
                'action' => 'analyze_seo',
                'icon' => 'search'
            ],
            [
                'title' => __('Create Social Media Posts', 'wp-office-editor'),
                'description' => __('Generate engaging posts for your social media channels.', 'wp-office-editor'),
                'action' => 'template_social',
                'icon' => 'share-alt'
            ]
        ];
        
        // إضافة اقتراحات عامة إذا لم يكن هناك ما يكفي
        while (count($suggestions) < 3) {
            $random_suggestion = $general_suggestions[array_rand($general_suggestions)];
            if (!in_array($random_suggestion, $suggestions)) {
                $suggestions[] = $random_suggestion;
            }
        }
        
        return [
            'suggestions' => $suggestions,
            'stats' => [
                'total_actions' => count($history),
                'most_used_action' => $most_used_action,
                'action_count' => $action_count
            ]
        ];
    }
}
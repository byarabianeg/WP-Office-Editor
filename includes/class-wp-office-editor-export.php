<?php
class WP_Office_Editor_Export {
    
    private $temp_dir;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/wpoe-export/';
        
        // إنشاء المجلد إذا لم يكن موجوداً
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
    }
    
    /**
     * تصدير المستند
     */
    public function export_document($document_id, $format = 'docx') {
        $document = get_post($document_id);
        
        if (!$document) {
            return [
                'success' => false,
                'message' => __('Document not found.', 'wp-office-editor')
            ];
        }
        
        // تنظيف العنوان للاستخدام في اسم الملف
        $clean_title = sanitize_title($document->post_title);
        $filename = $clean_title . '-' . date('Y-m-d-H-i-s') . '.' . $format;
        $filepath = $this->temp_dir . $filename;
        
        switch ($format) {
            case 'docx':
                $result = $this->export_to_docx($document, $filepath);
                break;
                
            case 'pdf':
                $result = $this->export_to_pdf($document, $filepath);
                break;
                
            case 'odt':
                $result = $this->export_to_odt($document, $filepath);
                break;
                
            case 'html':
                $result = $this->export_to_html($document, $filepath);
                break;
                
            default:
                return [
                    'success' => false,
                    'message' => __('Unsupported export format.', 'wp-office-editor')
                ];
        }
        
        if ($result['success']) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => str_replace(ABSPATH, site_url('/'), $filepath)
            ];
        }
        
        return $result;
    }
    
    /**
     * التصدير إلى Word (DOCX)
     */
    private function export_to_docx($document, $filepath) {
        try {
            // استخدام مكتبة PHPWord
            if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
                return [
                    'success' => false,
                    'message' => __('PHPWord library is not available.', 'wp-office-editor')
                ];
            }
            
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // إضافة قسم جديد
            $section = $phpWord->addSection();
            
            // إضافة العنوان
            $section->addTitle($document->post_title, 1);
            
            // إضافة التاريخ
            $section->addText(
                __('Created: ', 'wp-office-editor') . get_the_date('', $document->ID),
                ['size' => 9, 'color' => '666666']
            );
            
            // إضافة المحتوى
            $content = $this->clean_html_for_export($document->post_content);
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $content, false, false);
            
            // حفظ الملف
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filepath);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * التصدير إلى PDF
     */
    private function export_to_pdf($document, $filepath) {
        try {
            // استخدام مكتبة DomPDF
            if (!class_exists('Dompdf\Dompdf')) {
                return [
                    'success' => false,
                    'message' => __('DomPDF library is not available.', 'wp-office-editor')
                ];
            }
            
            // إنشاء HTML للمستند
            $html = $this->generate_pdf_html($document);
            
            // إنشاء PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // حفظ الملف
            file_put_contents($filepath, $dompdf->output());
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * التصدير إلى OpenDocument (ODT)
     */
    private function export_to_odt($document, $filepath) {
        try {
            // استخدام مكتبة PHPWord
            if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
                return [
                    'success' => false,
                    'message' => __('PHPWord library is not available.', 'wp-office-editor')
                ];
            }
            
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // إضافة قسم جديد
            $section = $phpWord->addSection();
            
            // إضافة العنوان
            $section->addTitle($document->post_title, 1);
            
            // إضافة المحتوى
            $content = $this->clean_html_for_export($document->post_content);
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $content, false, false);
            
            // حفظ كـ ODT
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'ODText');
            $objWriter->save($filepath);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * التصدير إلى HTML
     */
    private function export_to_html($document, $filepath) {
        try {
            $html = $this->generate_html_document($document);
            file_put_contents($filepath, $html);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * توليد HTML لملف PDF
     */
    private function generate_pdf_html($document) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($document->post_title); ?></title>
            <style>
                body {
                    font-family: 'DejaVu Sans', Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 20px;
                }
                h1 {
                    color: #2c3e50;
                    border-bottom: 2px solid #3498db;
                    padding-bottom: 10px;
                }
                h2 {
                    color: #34495e;
                    margin-top: 30px;
                }
                h3 {
                    color: #7f8c8d;
                }
                p {
                    margin: 10px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                table, th, td {
                    border: 1px solid #ddd;
                }
                th, td {
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
                img {
                    max-width: 100%;
                    height: auto;
                }
                .document-meta {
                    font-size: 12px;
                    color: #666;
                    margin-bottom: 30px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }
            </style>
        </head>
        <body>
            <div class="document-meta">
                <strong><?php _e('Title:', 'wp-office-editor'); ?></strong> <?php echo esc_html($document->post_title); ?><br>
                <strong><?php _e('Created:', 'wp-office-editor'); ?></strong> <?php echo get_the_date('', $document->ID); ?><br>
                <strong><?php _e('Author:', 'wp-office-editor'); ?></strong> <?php echo get_the_author_meta('display_name', $document->post_author); ?>
            </div>
            
            <div class="document-content">
                <?php echo $this->clean_html_for_export($document->post_content); ?>
            </div>
            
            <div class="document-footer">
                <hr>
                <p style="font-size: 10px; color: #999; text-align: center;">
                    <?php _e('Generated by WP Office Editor - ', 'wp-office-editor'); ?>
                    <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * توليد HTML كامل للمستند
     */
    private function generate_html_document($document) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($document->post_title); ?> - WP Office Editor</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    line-height: 1.8;
                    color: #333;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    background: #f9f9f9;
                }
                .document-container {
                    background: white;
                    padding: 40px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #2c3e50;
                    margin-bottom: 10px;
                    padding-bottom: 15px;
                    border-bottom: 3px solid #3498db;
                }
                .document-meta {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 30px;
                    font-size: 14px;
                    color: #666;
                }
                .document-content {
                    font-size: 16px;
                }
                .document-content h2 {
                    color: #34495e;
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                }
                .document-content h3 {
                    color: #7f8c8d;
                    margin-top: 30px;
                }
                .document-content img {
                    max-width: 100%;
                    height: auto;
                    border-radius: 4px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                .document-content table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .document-content table th {
                    background: #3498db;
                    color: white;
                    font-weight: 600;
                }
                .document-content table th,
                .document-content table td {
                    padding: 12px 15px;
                    border: 1px solid #ddd;
                    text-align: left;
                }
                .document-content blockquote {
                    border-left: 4px solid #3498db;
                    margin: 20px 0;
                    padding: 15px 20px;
                    background: #f8f9fa;
                    font-style: italic;
                }
                .document-footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                    font-size: 12px;
                    color: #999;
                    text-align: center;
                }
                @media print {
                    body {
                        background: white;
                        padding: 0;
                    }
                    .document-container {
                        box-shadow: none;
                        padding: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="document-container">
                <h1><?php echo esc_html($document->post_title); ?></h1>
                
                <div class="document-meta">
                    <strong><?php _e('Author:', 'wp-office-editor'); ?></strong> 
                    <?php echo get_the_author_meta('display_name', $document->post_author); ?> |
                    
                    <strong><?php _e('Created:', 'wp-office-editor'); ?></strong> 
                    <?php echo get_the_date('', $document->ID); ?> |
                    
                    <strong><?php _e('Modified:', 'wp-office-editor'); ?></strong> 
                    <?php echo get_the_modified_date('', $document->ID); ?>
                </div>
                
                <div class="document-content">
                    <?php echo $this->clean_html_for_export($document->post_content); ?>
                </div>
                
                <div class="document-footer">
                    <p>
                        <?php _e('Document generated by WP Office Editor', 'wp-office-editor'); ?> | 
                        <?php echo date('Y-m-d H:i:s'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * تنظيف HTML للتصدير
     */
    private function clean_html_for_export($html) {
        // إزالة السكريبتات والأنماط غير الآمنة
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/on\w+=\'[^\']*\'/i', '', $html);
        
        // استبدال الصور بروابطها إذا كان src موجود
        $html = preg_replace_callback('/<img[^>]+src="([^"]+)"[^>]*>/i', function($matches) {
            $src = esc_url($matches[1]);
            $alt = preg_match('/alt="([^"]*)"/i', $matches[0], $alt_match) ? $alt_match[1] : '';
            return '<p><a href="' . $src . '">' . ($alt ?: __('Image', 'wp-office-editor')) . '</a></p>';
        }, $html);
        
        // تنظيف السمات غير الآمنة
        $allowed_attributes = [
            'href', 'title', 'target', 'src', 'alt', 'width', 'height',
            'border', 'cellpadding', 'cellspacing', 'colspan', 'rowspan',
            'align', 'valign', 'class', 'id', 'style'
        ];
        
        $html = preg_replace_callback('/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i', function($matches) use ($allowed_attributes) {
            $tag = $matches[1];
            $self_closing = $matches[2];
            
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'])) {
                return '';
            }
            
            return '<' . $tag . $self_closing . '>';
        }, $html);
        
        return $html;
    }
    
    /**
     * تنظيف الملفات المؤقتة القديمة
     */
    public function cleanup_temp_files($hours = 24) {
        if (!is_dir($this->temp_dir)) {
            return;
        }
        
        $files = glob($this->temp_dir . '*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $hours * 3600) {
                    unlink($file);
                }
            }
        }
    }
}
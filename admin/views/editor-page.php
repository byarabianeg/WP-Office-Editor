<?php
/**
 * Admin page: Office Editor screen.
 *
 * @package WP_Office_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div class="wrap wp-office-editor-wrap">

    <h1 class="wp-heading-inline">
        <?php echo esc_html__( 'Office Editor', 'wp-office-editor' ); ?>
    </h1>

    <hr class="wp-header-end">

    <div id="oe-status-message" style="margin-top: 10px;"></div>

    <!-- Document Form -->
    <form id="oe-editor-form" method="post">

        <!-- Document Title -->
        <input type="text"
               id="oe-document-title"
               name="title"
               class="regular-text"
               placeholder="عنوان المستند"
               style="width:100%; margin-bottom: 15px; font-size:18px; padding:8px;"
        />

        <!-- Hidden Post ID -->
        <input type="hidden" id="oe-post-id" name="post_id" value="0">

        <!-- CKEditor Toolbar Container -->
        <div id="oe-toolbar-container" style="margin-bottom: 10px;"></div>

        <!-- CKEditor Content Area -->
        <div id="oe-editor"
             style="
                background: #ffffff;
                border: 1px solid #ccd0d4;
                min-height: 400px;
                padding: 15px;
             "
        >
            <p>ابدأ الكتابة هنا...</p>
        </div>

        <!-- Save Button -->
        <p style="margin-top: 20px;">
            <button id="oe-save-button"
                    class="button button-primary button-hero">
                حفظ المستند
            </button>
        </p>

    </form>
</div>

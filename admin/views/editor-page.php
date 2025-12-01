<?php
/**
 * Editor Page View – Multi-tab CKEditor interface
 */
?>

<div class="wrap">
    <h1>Office Editor</h1>

    <div id="oe-status-message" style="margin-top:15px;"></div>

    <!-- Tabs Bar -->
    <div id="oe-tabs-bar" class="oe-tabs-bar">
        <!-- Tabs are added here dynamically -->
    </div>

    <!-- Button: New Tab -->
    <button id="oe-add-tab" class="button button-primary" style="margin:15px 0;">
        تبويب جديد +
    </button>

    <!-- Editors container -->
    <div id="oe-editors-container" class="oe-editors-container">
        <!-- Editor cards appear here dynamically -->
    </div>
</div>

<!-- Template: Tab -->
<template id="oe-tab-template">
    <div class="oe-tab">
        <span class="oe-tab-title">عنوان</span>

        <span class="oe-shortcode" title="اضغط للنسخ"></span>

        <button class="oe-open-window dashicons dashicons-external" title="فتح في نافذة جديدة"></button>
        <button class="oe-close-tab dashicons dashicons-no-alt" title="إغلاق"></button>
    </div>
</template>

<!-- Template: Editor Card -->
<template id="oe-editor-template">
    <div class="oe-editor-card">

        <input type="text" class="oe-title-input" placeholder="عنوان المستند" />

        <div class="oe-toolbar-container"></div>

        <div class="oe-editor-area"></div>

        <div class="oe-actions">
            <button class="button button-primary oe-save-button">حفظ</button>
            <button class="button oe-save-draft-button">حفظ كمسودة (محلي)</button>
        </div>

    </div>
</template>

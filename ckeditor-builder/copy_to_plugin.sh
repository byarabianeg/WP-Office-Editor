#!/bin/bash
PLUGIN_VENDOR_PATH="../wp-office-editor/assets/vendor/ckeditor5"
mkdir -p "$PLUGIN_VENDOR_PATH"
cp build/ckeditor.js "$PLUGIN_VENDOR_PATH/ckeditor.js"
echo "Copied build/ckeditor.js to $PLUGIN_VENDOR_PATH"

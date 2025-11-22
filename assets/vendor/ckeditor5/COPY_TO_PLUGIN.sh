#!/bin/bash
# After building, run this to copy the ckeditor build into the WordPress plugin
PLUGIN_PATH="../wp-office-editor/assets/vendor/ckeditor5"
mkdir -p "$PLUGIN_PATH"
cp build/ckeditor.js "$PLUGIN_PATH/ckeditor.js"
echo "Copied build/ckeditor.js to $PLUGIN_PATH"

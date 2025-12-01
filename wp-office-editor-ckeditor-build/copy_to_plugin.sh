#!/bin/bash

# مسارات الملفات
BUILD_FILE="./build/ckeditor.js"
PLUGIN_PATH="../assets/vendor/ckeditor5"

# تأكد أن ملف البناء موجود
if [ ! -f "$BUILD_FILE" ]; then
    echo "❌ لم يتم العثور على ملف البناء ckeditor.js داخل مجلد build/"
    exit 1
fi

# تأكد أن مسار البلجن موجود
if [ ! -d "$PLUGIN_PATH" ]; then
    echo "❌ مسار البلجن غير موجود: $PLUGIN_PATH"
    exit 1
fi

# نسخ الملف
cp "$BUILD_FILE" "$PLUGIN_PATH"

echo "✅ تم نسخ ckeditor.js بنجاح إلى:"
echo "   $PLUGIN_PATH"

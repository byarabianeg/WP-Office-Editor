import DecoupledEditor from '@ckeditor/ckeditor5-editor-decoupled/src/decouplededitor';

import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials';
import Paragraph from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import Bold from '@ckeditor/ckeditor5-basic-styles/src/bold';
import Italic from '@ckeditor/ckeditor5-basic-styles/src/italic';
import Underline from '@ckeditor/ckeditor5-basic-styles/src/underline';
import Strikethrough from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';
import Code from '@ckeditor/ckeditor5-basic-styles/src/code';
import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote';

import Heading from '@ckeditor/ckeditor5-heading/src/heading';

import Font from '@ckeditor/ckeditor5-font/src/font';

import Link from '@ckeditor/ckeditor5-link/src/link';

import List from '@ckeditor/ckeditor5-list/src/list';

import Indent from '@ckeditor/ckeditor5-indent/src/indent';
import IndentBlock from '@ckeditor/ckeditor5-indent/src/indentblock';

import Image from '@ckeditor/ckeditor5-image/src/image';
import ImageToolbar from '@ckeditor/ckeditor5-image/src/imagetoolbar';
import ImageCaption from '@ckeditor/ckeditor5-image/src/imagecaption';
import ImageStyle from '@ckeditor/ckeditor5-image/src/imagestyle';
import ImageUpload from '@ckeditor/ckeditor5-image/src/imageupload';
import PictureEditing from '@ckeditor/ckeditor5-image/src/pictureediting';

import Table from '@ckeditor/ckeditor5-table/src/table';
import TableToolbar from '@ckeditor/ckeditor5-table/src/tabletoolbar';

import MediaEmbed from '@ckeditor/ckeditor5-media-embed/src/mediaembed';

import RemoveFormat from '@ckeditor/ckeditor5-remove-format/src/removeformat';

import PasteFromOffice from '@ckeditor/ckeditor5-paste-from-office/src/pastefromoffice';

import PageBreak from '@ckeditor/ckeditor5-page-break/src/pagebreak';

import WordCount from '@ckeditor/ckeditor5-word-count/src/wordcount';

import HtmlEmbed from '@ckeditor/ckeditor5-html-embed/src/htmlembed';

// ----- FIXED LANGUAGE IMPORTS -----
import TextPartLanguage from '@ckeditor/ckeditor5-language/src/textpartlanguage';
import TextPartLanguageUI from '@ckeditor/ckeditor5-language/src/textpartlanguageui';
import TextPartLanguageEditing from '@ckeditor/ckeditor5-language/src/textpartlanguageediting';

class WP_DecoupledEditor extends DecoupledEditor {}

WP_DecoupledEditor.builtinPlugins = [
    Essentials,
    Paragraph,
    Bold,
    Italic,
    Underline,
    Strikethrough,
    Code,
    BlockQuote,
    Heading,
    Font,
    Link,
    List,
    Indent,
    IndentBlock,
    Image,
    ImageToolbar,
    ImageCaption,
    ImageStyle,
    ImageUpload,
    PictureEditing,
    Table,
    TableToolbar,
    MediaEmbed,
    RemoveFormat,
    PasteFromOffice,
    PageBreak,
    WordCount,
    HtmlEmbed,

    // Language support
    TextPartLanguage,
    TextPartLanguageUI,
    TextPartLanguageEditing,
];

WP_DecoupledEditor.defaultConfig = {
    toolbar: {
        items: [
            'undo', 'redo',
            '|',
            'heading',
            '|',
            'bold', 'italic', 'underline', 'strikethrough', 'code',
            '|',
            'link',
            'bulletedList', 'numberedList',
            '|',
            'indent', 'outdent',
            '|',
            'imageUpload',
            'mediaEmbed',
            'insertTable',
            '|',
            'pageBreak',
            'htmlEmbed',
            'removeFormat',
        ]
    },
    image: {
        toolbar: [
            'imageStyle:alignLeft', 'imageStyle:alignCenter', 'imageStyle:alignRight',
            '|',
            'toggleImageCaption', 'imageTextAlternative'
        ]
    },
    table: {
        contentToolbar: [
            'tableColumn', 'tableRow', 'mergeTableCells'
        ]
    },
    language: {
        ui: 'ar',
        content: 'ar'
    }
};

export default WP_DecoupledEditor;

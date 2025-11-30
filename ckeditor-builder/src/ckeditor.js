import DecoupledEditor from '@ckeditor/ckeditor5-editor-decoupled/src/decouplededitor';

import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials';
import Autoformat from '@ckeditor/ckeditor5-autoformat/src/autoformat';
import Bold from '@ckeditor/ckeditor5-basic-styles/src/bold';
import Italic from '@ckeditor/ckeditor5-basic-styles/src/italic';
import Underline from '@ckeditor/ckeditor5-basic-styles/src/underline';
import Strikethrough from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';

import Paragraph from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import Heading from '@ckeditor/ckeditor5-heading/src/heading';

import Link from '@ckeditor/ckeditor5-link/src/link';

import List from '@ckeditor/ckeditor5-list/src/list';
import ListProperties from '@ckeditor/ckeditor5-list/src/listproperties';

import Image from '@ckeditor/ckeditor5-image/src/image';
import ImageToolbar from '@ckeditor/ckeditor5-image/src/imagetoolbar';
import ImageResize from '@ckeditor/ckeditor5-image/src/imageresize';
import ImageStyle from '@ckeditor/ckeditor5-image/src/imagestyle';
import ImageUpload from '@ckeditor/ckeditor5-image/src/imageupload';

import Table from '@ckeditor/ckeditor5-table/src/table';
import TableToolbar from '@ckeditor/ckeditor5-table/src/tabletoolbar';
import TableProperties from '@ckeditor/ckeditor5-table/src/tableproperties';
import TableCellProperties from '@ckeditor/ckeditor5-table/src/tablecellproperties';

import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote';

import Font from '@ckeditor/ckeditor5-font/src/font';

import Indent from '@ckeditor/ckeditor5-indent/src/indent';
import IndentBlock from '@ckeditor/ckeditor5-indent/src/indentblock';

import Alignment from '@ckeditor/ckeditor5-alignment/src/alignment';

import RemoveFormat from '@ckeditor/ckeditor5-remove-format/src/removeformat';

import PageBreak from '@ckeditor/ckeditor5-page-break/src/pagebreak';

import PasteFromOffice from '@ckeditor/ckeditor5-paste-from-office/src/pastefromoffice';

import MediaEmbed from '@ckeditor/ckeditor5-media-embed/src/mediaembed';

import WordCount from '@ckeditor/ckeditor5-word-count/src/wordcount';

import HtmlEmbed from '@ckeditor/ckeditor5-html-embed/src/htmlembed';

import Language from '@ckeditor/ckeditor5-language/src/language';

export default class WPOfficeEditor extends DecoupledEditor {}

WPOfficeEditor.builtinPlugins = [
    Essentials,
    Autoformat,

    // Text styling
    Bold,
    Italic,
    Underline,
    Strikethrough,

    Paragraph,
    Heading,

    // Links
    Link,

    // Lists
    List,
    ListProperties,

    // Images
    Image,
    ImageToolbar,
    ImageResize,
    ImageStyle,
    ImageUpload,

    // Table
    Table,
    TableToolbar,
    TableProperties,
    TableCellProperties,

    // Block Quote
    BlockQuote,

    // Font controls
    Font,

    // Indentation
    Indent,
    IndentBlock,

    // Alignment
    Alignment,

    // Remove Formatting
    RemoveFormat,

    // Page break
    PageBreak,

    // Paste from Office
    PasteFromOffice,

    // Media
    MediaEmbed,

    // Word count
    WordCount,

    // HTML embed
    HtmlEmbed,

    // Language (RTL – Arabic)
    Language
];

WPOfficeEditor.defaultConfig = {
    toolbar: {
        items: [
            'heading',
            '|',
            'bold', 'italic', 'underline', 'strikethrough',
            '|',
            'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor',
            '|',
            'alignment',
            '|',
            'link',
            'bulletedList', 'numberedList',
            '|',
            'outdent', 'indent',
            '|',
            'insertTable',
            'uploadImage',
            'mediaEmbed',
            '|',
            'blockQuote',
            'pageBreak',
            '|',
            'removeFormat',
            '|',
            'undo', 'redo'
        ]
    },

    language: {
        content: 'ar',
        ui: 'ar'
    },

    image: {
        resizeUnit: '%',
        toolbar: [
            'imageStyle:inline',
            'imageStyle:block',
            'imageStyle:side',
            '|',
            'toggleImageCaption',
            'imageTextAlternative',
            '|',
            'resizeImage'
        ]
    },

    table: {
        contentToolbar: [
            'tableColumn',
            'tableRow',
            'mergeTableCells',
            'tableProperties',
            'tableCellProperties'
        ]
    },

    mediaEmbed: {
        previewsInData: true
    },

    htmlEmbed: {
        showPreviews: true
    }
};

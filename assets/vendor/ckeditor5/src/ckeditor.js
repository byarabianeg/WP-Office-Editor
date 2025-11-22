/**
 * src/ckeditor.js
 * Entry file for custom Decoupled CKEditor build.
 *
 * IMPORTANT:
 * - Install the packages listed in package.json via `npm install`.
 * - Edit this file to add/remove plugins you need.
 *
 * Example: create a DecoupledEditor build with common plugins.
 */

import DecoupledEditorBase from '@ckeditor/ckeditor5-editor-decoupled/src/decouplededitor';
import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials';
import Bold from '@ckeditor/ckeditor5-basic-styles/src/bold';
import Italic from '@ckeditor/ckeditor5-basic-styles/src/italic';
import Underline from '@ckeditor/ckeditor5-basic-styles/src/underline';
import Strikethrough from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';
import Paragraph from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import Heading from '@ckeditor/ckeditor5-heading/src/heading';
import Link from '@ckeditor/ckeditor5-link/src/link';
import List from '@ckeditor/ckeditor5-list/src/list';
import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote';
import Table from '@ckeditor/ckeditor5-table/src/table';
import TableToolbar from '@ckeditor/ckeditor5-table/src/tabletoolbar';
import Image from '@ckeditor/ckeditor5-image/src/image';
import ImageUpload from '@ckeditor/ckeditor5-image/src/imageupload';
import SimpleUploadAdapter from '@ckeditor/ckeditor5-upload/src/adapters/simpleuploadadapter';
import Font from '@ckeditor/ckeditor5-font/src/font';
import Alignment from '@ckeditor/ckeditor5-alignment/src/alignment';
import HorizontalLine from '@ckeditor/ckeditor5-horizontal-line/src/horizontalline';
import Indent from '@ckeditor/ckeditor5-indent/src/indent';
import Undo from '@ckeditor/ckeditor5-undo/src/undo';

class DecoupledEditor extends DecoupledEditorBase {}

DecoupledEditor.builtinPlugins = [
  Essentials,
  Bold,
  Italic,
  Underline,
  Strikethrough,
  Paragraph,
  Heading,
  Link,
  List,
  BlockQuote,
  Table,
  TableToolbar,
  Image,
  ImageUpload,
  SimpleUploadAdapter,
  Font,
  Alignment,
  HorizontalLine,
  Indent,
  Undo
];

DecoupledEditor.defaultConfig = {
  language: 'ar',
  toolbar: {
    items: [
      'heading', '|',
      'bold', 'italic', 'underline', 'strikethrough', '|',
      'link', 'bulletedList', 'numberedList', '|',
      'insertTable', 'imageUpload', '|',
      'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
      'alignment', 'outdent', 'indent', '|',
      'undo', 'redo'
    ]
  },
  image: {
    toolbar: [ 'imageTextAlternative', 'imageStyle:full', 'imageStyle:side' ]
  },
  table: {
    contentToolbar: [ 'tableColumn', 'tableRow', 'mergeTableCells' ]
  }
};

export default DecoupledEditor;

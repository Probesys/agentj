import { Controller } from "@hotwired/stimulus"
import { ClassicEditor, Essentials, Bold, Italic,Underline,Strikethrough,RemoveFormat, Font, Paragraph, SourceEditing } from 'ckeditor5';
import { Image, ImageCaption, ImageToolbar, ImageInsert, ImageInsertViaUrl, ImageStyle, ImageResize } from 'ckeditor5';

import { Table, TableToolbar, TableLayout, GeneralHtmlSupport } from 'ckeditor5';
import { Link, HorizontalLine, Fullscreen } from 'ckeditor5';
import { List, Indent } from 'ckeditor5';
import { Heading } from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';

import translationsFr from 'ckeditor5/translations/fr.js'
import translationsEn from 'ckeditor5/translations/en.js'

const AVAILABLE_TRANSLATIONS = {
    fr: translationsFr,
    en: translationsEn,
};

export default class extends Controller {
    connect() {
        const language = AVAILABLE_TRANSLATIONS[document.documentElement.lang] ? document.documentElement.lang : 'en';

        ClassicEditor
            .create({
                attachTo: this.element,
                licenseKey: 'GPL',
                translations: [
                    AVAILABLE_TRANSLATIONS[language]
                ],
                language: language,
                plugins: [
                    Bold,
                    Essentials,
                    Font,
                    Fullscreen,
                    GeneralHtmlSupport,
                    Heading,
                    HorizontalLine,
                    Image,
                    ImageCaption,
                    ImageInsert,
                    ImageInsertViaUrl,
                    ImageResize,
                    ImageStyle,
                    ImageToolbar,
                    Indent,
                    Italic,
                    Link,
                    List,
                    Paragraph,
                    RemoveFormat,
                    SourceEditing,
                    Strikethrough,
                    Table,
                    TableLayout,
                    TableToolbar,
                    Underline
                ],
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                },
                image: {
                    insert: {
                        integrations: ['url']
                    },
                    resizeOptions: [
                        {
                            name: 'resizeImage:original',
                            label: 'Default image width',
                            value: null
                        },
                        {
                            name: 'resizeImage:50',
                            label: '50% page width',
                            value: '50'
                        },
                        {
                            name: 'resizeImage:75',
                            label: '75% page width',
                            value: '75'
                        }
                    ],
                    toolbar: [
                        'imageTextAlternative',
                        'toggleImageCaption',
                        '|',
                        'imageStyle:inline',
                        'imageStyle:wrapText',
                        'imageStyle:breakText',
                        '|',
                        'resizeImage'
                    ]
                },
                htmlSupport: {
                    allow: [
                        {
                            name: /.*/,
                            attributes: true,
                            classes: true,
                            styles: true
                        }
                    ]
                },
                toolbar: [
                    'undo', 'redo', '|',
                    'heading', '|',
                    'link', 'unlink', '|',
                    'insertImage', '|',
                    'insertTable', 'tableColumn', 'horizontalLine', 'fullscreen', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'removeFormat', '|',
                    'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                    'sourceEditing'
                ],
            })
            .then(editor => {
                this.editor = editor;
                editor.editing.view.document.on('focus', () => {
                    window.editor = editor;
                });
            })
            .catch(error => {
                console.error(error);
            });
    }

    disconnect() {
        if (this.editor) {
            if (window.editor === this.editor) {
                window.editor = null;
            }
            this.editor.destroy();
            this.editor = null;
        }
    }
}

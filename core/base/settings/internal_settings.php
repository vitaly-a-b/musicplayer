<?php

defined('VG_ACCESS') or die('Access denied');

const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/view/';
const UPLOAD_DIR = 'files/';

const COOKIE_VERSION = '1.0.1';
const CRYPT_KEY = 'mX50TzMm30QEBkw9UxppprA81N05BF1/7Y0FH0LPeO+llCyB46w4FiKj7VDKwjko';
const COOKIE_TIME = 18000;
const BLOCK_TIME = 3;

const END_SLASH = '/';


const _QTY = 36;

const QTY_LINKS = 3;

const BASE_CSS_JS = [
    'style' => [],
    'scripts' => ['core/base/assets/js/frameworkfunctions.js', 'core/base/assets/js/showMessage.js']
];

const ADMIN_CSS_JS = [
    'styles' => ['css/choices.min.css', 'css/decoration.css', 'css/loader.css', 'css/crop_image.css', 'css/style.css'],
    'scripts' => [
        'js/app.js',
        'js/choices.min.js',
        'js/jsColorPicker.min.js',
        'js/frameworkfunctions.js',
        'js/main.js',
        'js/showMessage.js',
        'js/tinymce/tinymce.min.js',
        'js/tinymce/tinymce_init.js',
        'js/crop-image.js'
    ]
];

const USER_CSS_JS = [
    'styles' => [
        'assets/css/styles.css',
        'assets/css/modals.css',

    ],
    'scripts' => [
        'assets/js/script.js',
        'assets/js/send_form.js',

    ]
];

use core\base\exceptions\RouteException;

function autoloadMainClasses($class_name){

    $file_name = str_replace('\\', '/', $class_name);

    @include_once $_SERVER['DOCUMENT_ROOT'] . PATH . $file_name . '.php';

}

spl_autoload_register('autoloadMainClasses');
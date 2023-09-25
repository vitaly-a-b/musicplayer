<?php

defined('VG_ACCESS') or die('Access denied');

const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/view/';
const UPLOAD_DIR = 'userfiles/';

const COOKIE_VERSION = '1.0.0';
const CRYPT_KEY = 'SDF*999HT5DrsDFsdf098098*&&^$%&^sdf54F873fDSFSF-SDF*9879790SDDFsdf098098*&&^$%&^sdfsddsfsfDSFdd511';
const COOKIE_TIME = 6000;
const BLOCK_TIME = 3;

const END_SLASH = '/';

const _QTY = 15;
const QTY_LINKS = 3;

const CART = 'cookie';
const CART_COOKIE_TIME = 4; //значение в днях

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
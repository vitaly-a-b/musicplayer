<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 31.01.2019
 * Time: 16:01
 */

namespace core\base\settings;


use core\base\controller\Singleton;

class Settings
{

    use BaseSettings;

    // прописываются маршруты  1-й пар.адр.строки => контроллер/входящий метод
    private $routes = [
        'user' => [
            'routes' => [
                //'information' => 'news/information',

            ]
        ]
    ];

    private $projectTables = [
        'settings' => ['name' => 'Настройки системы'],
        'visitors' => ['name' => 'Пользователи'],
        'track' => ['name' => 'Музыкальные треки'],
        'playlists' => ['name' => 'Плэйлисты пользователей'],
        'artist' => ['name' => 'Исполнители'],
    ];

    private $templateArr = [
        'text' => [
            'external_alias_faq', 'first_title', 'first_short_content', 'first_external_alias', 'second_title', 'second_short_content', 'second_external_alias',
            'video_title', 'global_sale_period', 'dop_content_title'
        ],
        'text_disabled' => [],
        'date' => [],
        'textarea' => ['video_text', 'video_content', 'global_sale', 'delivery_information', 'dop_content', 'text'],
        'file' => ['link'],
        'gallery_img' => ['payments_gallery_img'],
        'img' => ['first_img', 'second_img', 'dop_img', 'video_img'],
        'radio' => ['show_full_screen', 'first_inner_text', 'second_inner_text', 'template_type', 'clear_cache'],
        'select' => ['style_id'],
        'checkboxlist' => ['track'],  //если указать ключь с именем таблицы (sales => goods), то шаблонизироваться будет только для этой таблицы
        'colorpicker' => [],
        'gps' => [],
        'addedlist' => ['tizers_links'],
        'addedlist_img' => ['tizers_recomendations'],
    ];

    private $translate = [
//        'site_url' => ['Ссылка на сайт'],
        'link' => ['Звуковой файл'],
        'style_id' => ['Стиль музыки'],
        'text' => ['Текст песни'],

    ];

    private $radio = [

    ];

    private $blockNeedle = [
//        'grid' => [],
//        'full' => []
    ];

    //максимальная глубина вложенности, 0 - вложить только в корневой уровень, 1 - вложить максимум только в первый уровень
    private $deepLevel = [

    ];

    private $rootItems = [
        'style_id' => '---',
        'tables' => ['track']
    ];

    private $manyToMany = [
        'playlists_track' => ['track', 'playlists']
    ];


}
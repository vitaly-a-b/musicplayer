<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 07.03.2019
 * Time: 14:48
 */

namespace core\user\controller;

use core\base\exceptions\CacheException;
use core\base\helpers\CacheHelper;
use core\user\helpers\SeoHelper;
use core\user\model\Model;
use core\base\controller\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use core\user\projectHelpers\ProjectHelper;
use libraries\TextModify;

class BaseUser extends BaseController
{

    use ProjectHelper;
    use SeoHelper;
    use CacheHelper;

    protected $model;

    protected $userModel;

    protected $projectTables;

    protected $set;
    protected $menu;
    protected $table;

    protected $title;
    protected $keywords;
    protected $description;
    protected $h1;
    protected $metaData;

    protected $breadcrumbs;

    protected $data;


    /*Проектные свойства*/

    /**
     * @var ссылки в футере и подвале сайта
     */

    protected $modals;
    protected $search;
    protected $sidebar;

    /**
     * @var array hit, sale, week_sale, new
     */

    /*Проектные свойства*/

    protected function inputData(){

        if (!$this->isHttpRequest()){
            header("HTTP/1.1 404 Not Found", true, 404);
            header ('Status: 404 Not Found');
            exit();
        }

        $this->init();

        $this->model = Model::instance();

        $this->checkAuth();

        $this->projectTables = Settings::get('projectTables');

        $this->set = $this->model->get('settings', [
            'order' => 'id',
            'limit' => 1,
            'single' => true
        ]);

        define('QTY', $this->set['catalog_qty'] ?? _QTY);

        if(method_exists($this, 'beforeInput')){
            $this->beforeInput();
        }

        try {
            $this->dynamicCache = false;
            // проверяем наличие кеша
            !$this->debugMode && $this->debugMode = !empty($this->set['clear_cache']);
            $this->checkCache();


        }catch (CacheException $e){


        }

    }



    protected function outputData(){

        $args = func_get_arg(0);
        $vars = $args ?: [];
        $crumbs = null;

        // если есть дополнительные не закешированные переменные в $vars, то их нужно склеить с закешированными
        $this->addNoCacheVar($vars);

        if (empty($vars['ext_crumbs']) && method_exists($this, 'createChainElements')){
            $crumbs = $this->createChainElements($vars);
        }

        if(method_exists($this, 'beforeOutput')){
            $this->beforeOutput($vars);
        }

        if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . TEMPLATE . 'include/modals.php')){
            $this->modals = $this->render(TEMPLATE. 'include/modals', $vars);
        }

        if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . TEMPLATE . 'include/search.php')){
            $this->search = $this->render(TEMPLATE. 'include/search', $vars);
        }

        if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . TEMPLATE . 'include/sidebar.php')){
            $this->sidebar = $this->render(TEMPLATE. 'include/sidebar', $vars);
        }

        if(!$this->content){

            if(!$this->breadcrumbs){

                if(file_exists($_SERVER['DOCUMENT_ROOT']) . PATH . TEMPLATE . 'include/breadcrumbs.php'){

                    if (!empty($crumbs)){

                        $this->breadcrumbs = $this->render(TEMPLATE . 'include/breadcrumbs', ['crumbs' => $crumbs]);


                    }else{

                        $this->createBreadcrumbs($vars['data'] ?? [], $vars['ext_crumbs'] ?? []);
                    }

                    $crumbs && $vars['ext_crumbs'] = $crumbs;

                }



            }

            !$this->h1 && $this->h1 = !empty($vars['data']['h1']) ? $vars['data']['h1'] : (!empty($vars['data']['name']) ? $vars['data']['name'] : (!empty($crumbs) ? $crumbs[count($crumbs) - 1]['name'] : ''));
            !$this->title && $this->title = !empty($vars['data']['title']) ? $vars['data']['title'] : ($this->metaData['title'] ?? (!empty($vars['data']['name']) ? $vars['data']['name'] : ''));

            if(!$this->title){
                $this->title = !empty($this->metaData['title']) ? $this->metaData['title'] : ($this->h1 ?? $this->metaData['name']);
            }


            !$this->keywords && $this->keywords = !empty($vars['data']['keywords']) ? $vars['data']['keywords'] : (!empty($this->metaData['keywords']) ? $this->metaData['keywords'] : '');
            !$this->description && $this->description = !empty($vars['data']['description']) ? $vars['data']['description'] : (!empty($this->metaData['description']) ? $this->metaData['description'] : '');


            $this->content = $this->render($this->template, $vars);
        }

        if(!$this->title) $this->title = $this->set['title'] ?? $this->set['name'];
        if(!$this->keywords) $this->keywords = $this->set['keywords'] ?? '';
        if(!$this->description) $this->description = $this->set['description'] ?? '';
        if(!$this->h1) $this->h1 = $this->set['h1'] ?? $this->set['name'];

        $this->header = $this->render(TEMPLATE . 'include/header', $vars);

        $this->footer = $this->render(TEMPLATE . 'include/footer', $vars);

        if(method_exists($this, 'afterOutput')){
            $this->afterOutput($vars);
        }

        try {

            // если нужно кешируем данные
            $this->createCache($vars);

        }catch (CacheException $e){

        }

        return $this->render(TEMPLATE . 'layout/default');

    }




// магический метод. Вызывается при попытки получить данные у несуществующего свойства.
// Это свойство будет заполняться данными из одноименной таблицы, либо из таблицы с определенным шаблонным названием
// Так как смысовые части названия таблицы мы разделяем _, а в наименовании свойств вместо _ начинаем вторую часть с заглавной буквы
// например, таблица в БД client_services, то свойство будет clientServices
// Также можне в имени свойства передать имя колонки таблицы по значениям которой будут формироваться ключи массива при инициализации данного свойства.
// например, свойство clientServicesMenuPosition будет инициализированно данными из таблице client_services, где ключами массива будут выступать значение
// из колонки menu_position данной таблицы
    public function __get($property){

        // сохраняем $property, т.к далее будем его преобразовывать для поиска таблицы
        $baseProperty = $property;

        $limit = null;

        if(preg_match('/\d+$/', $property, $matches)){

            $property = preg_replace('/\d+$/', '', $property);

            $limit = $matches[0];

        }
        // если в свойстве есть заглавная буква после прописной, то между ними добавляем _
        $property = strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1_$2', $property));

        //получаем все таблицы в БД
        $tables = $this->model->showTables();

        // если в БД таблицы с названием $property нет, то возможно в имени свойства, по которому ищем таблицу,
        // дополнительно передано имя колонки таблицы по значениям которой нужно сформировать ключи массива.
        if(!in_array($property, $tables)){

            $propertyArr = preg_split('/_/', $property);

            if(count($propertyArr) === 1) return null;

            $property = '';

            $part = '';

            foreach ($propertyArr as $key => $item){

                $part .= !$part ? $item : '_' . $item;

                unset($propertyArr[$key]);

                if(in_array($part, $tables)){

                    $property = $part;

                    break;

                }

            }

        }
        // инициализируем свойство класса данными из БД
        if($property){

            $columns = $this->model->showColumns($property);

            $order = null;

            $orderDirection = null;

            $where = null;

            if(!empty($columns['menu_position'])){

                $order = 'menu_position';

            }elseif (!empty($columns['date'])){

                $order = 'date';

                $orderDirection = 'DESC';

            }

            if(!empty($columns['visible'])){

                $where['visible'] = 1;

            }

            $this->$baseProperty = $this->model->get($property, [
                'where' => $where,
                'order' => $order,
                'order_direction' => $orderDirection,
                'limit' => $limit
            ]) ?: [];

            //если есть колонка содержащая внешнюю ссылку, то ее формат нужно проверить
            if(!empty($columns['external_alias']) && !empty($this->$baseProperty)){

                foreach ($this->$baseProperty as $key => $item){

                    if(!empty($item['external_alias'])){

                        if(!preg_match('/^\s*http/i', $item['external_alias'])){

                            if(preg_match('/^\s*[^\/]+\./i', $item['external_alias'])){

                                $this->$baseProperty[$key]['external_alias'] = 'http://' . $item['external_alias'];

                            }elseif (!preg_match('/^\s*\//i', $item['external_alias'])){

                                $this->$baseProperty[$key]['external_alias'] = '/' . $item['external_alias'];

                            }

                        }

                    }

                }

            }

            // если после того как таблица была найденна и в $propertyArr остались данные, в которых
            // есть имя какой-либо колонки найденной таблицы, то ключи массива в инициализируемом свойстве $this->$baseProperty заменяем значениями из этой колонки
            if(!empty($propertyArr)){

                $row = '';

                foreach ($propertyArr as $item){

                    $row .= $row ? '_' . $item : $item;

                }

                if(!empty($columns[$row])){

                    foreach ($this->$baseProperty as $key => $item){

                        if(!empty($item[$row])){

                            unset($this->$baseProperty[$key]);

                            $this->$baseProperty[$item[$row]] = $item;

                        }

                    }

                }

            }

            return $this->$baseProperty;

        }

        return null;

    }






// метод создает текстовый элемент в БД. Текст из шаблона через этот метод добавляется в БД в качестве элемента страницы.
// Далее можно через БД редактировать этот элемент не трогая сам шаблон.
// $alias - текст введенный в шаблоне. $elName - название колонки в таблице с элементами, которая если она заполненна будет выводится в итоге на страницу
    protected function translateEl($alias, $elName = 'el_name'){

        // получаем все текстовые элементы, которые на текущий момент есть в БД
        $data = $this->translateElementsAlias;

        $translateData = false;

        //если $alias пришел кириллицей
        if(preg_match('/[а-яё]/ui', $alias)){

            $translateData = $alias;

            $alias = (new TextModify())->translit($alias);

        }else{

            $alias = strtolower($alias);

        }

        // если элемент уже есть в БД, то возвращаем содержимое колонки $elName (если она не пуста) или колонки 'name'
        if(!empty($data[$alias])){

            return $data[$alias][$elName] ?: $data[$alias]['name'];

        }
        // если элемента еще нет, то добавляем его в БД и возвращаем то значение которое пришло изначально в виде параметра
        if($translateData && in_array('translate_elements', $this->model->showTables())){

            if($this->model->add('translate_elements', [
                'fields' => ['name' => $translateData, 'alias' => $alias]
            ])){

                return $this->translateElementsAlias[$alias]['name'] = $translateData;

            }

        }

        return null;

    }






    protected function textCut($str, $length = false){

        if($str){

            if(!$length || mb_strlen($str) > $length){

                $res = (new TextModify())->textCutting($str, $length);

                if(!empty($res[0])) $str = $res[0] . '...';

            }

        }

        return $str;

    }





    protected function getMetaData($table_name = false){

        $table = explode('controller', strtolower((new \ReflectionClass($this))->getShortName()))[0];

        $tables = $this->model->showTables();

        if(in_array('metadata', $tables)){

            if(!$table_name){

                if($this->table){

                    $table_name = $this->table;

                }else{

                    if(in_array($table, $tables)){

                        $table_name = $table;

                    }

                }

            }

            $this->metaData = $this->model->get('metadata');

            if($this->metaData){

                foreach ($this->metaData as $key => $item){

                    unset($this->metaData[$key]);

                    $this->metaData[$item['table_name']] = $item;

                }

            }

        }

        return $this->metaData;

    }





    protected function createBreadcrumbs($data = false, $ext_crumbs = []){

        if($this->table || $ext_crumbs){
            $crumbs[0]['name'] = !empty($_COOKIE['language']) ? 'Main page' : 'Главная';
            $crumbs[0]['alias'] = PATH;

            if(!$ext_crumbs){
                $crumbs[1]['name'] =  !empty($_COOKIE['language']) ? Settings::get('projectTables')[$this->table]['translate'] ?? '' : Settings::get('projectTables')[$this->table]['name'] ?? '';
                $crumbs[1]['alias'] = PATH . $this->table;

                if($this->parameters['alias']){
                    $data = $data ?: $this->data;

                    if(!empty($data['name'])){

                        $crumbs[2]['name'] = $data['name'];
                        $crumbs[2]['alias'] = PATH . $this->table . '/' .$this->parameters['alias'];

                    }
                }
            }else{

                ksort($ext_crumbs);

                for($i = 0; $i < count($ext_crumbs); $i++){
                    if($ext_crumbs[$i]['name'])
                        $crumbs[$i + 1] = $ext_crumbs[$i];
                }
            }

            $this->breadcrumbs = $this->render(TEMPLATE . 'include/breadcrumbs', ['crumbs' => $crumbs]);
        }
    }





    protected function img($img = '', $tag = false, $set = []){

        if(!$img && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . 'default_images')){

            $dir = scandir($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . 'default_images');

            $img = preg_grep('/'.$this->getController().'\./i', $dir) ?: preg_grep('/default\./i', $dir);

            $img && $img = array_shift($img);

        }

        if($img){

            $path = PATH . UPLOAD_DIR . $img;

            $class = isset($set['class']) && $set['class'] ?
                ' class="' . (is_array($set['class']) ? implode(' ', $set['class']) : $set['class']) . '" ' : '';

            $alt = isset($set['alt']) && $set['alt'] ? ' alt="' . $set['alt'] . '" ' : '';

            $title = isset($set['title']) && $set['title'] ? ' title="' . $set['title'] . '" ' : '';

            $style = isset($set['style']) && $set['style'] ?
                ' style="' . (is_array($set['style']) ? implode(';', $set['style']) : $set['style']) . '" ' : '';

            $data = '';

            if(isset($set['data']) && $set['data']){

                if(is_array($set['data'])){

                    foreach($set['data'] as $key => $item){

                        if(stripos($key, 'data-') === false)
                            $data .= 'data-';

                        $data .= $key . '="' . $item . '"';

                    }

                }else{

                    if(!preg_match('/^\s*data[^=]+=/i', $set['data']))
                        $data = 'data-attribute="' . $set['data'] . '"';
                    else $data = $set['data'];

                }

            }

            if(!$tag)
                return $path;

            echo '<img src="' . $path . '"' . $alt . $title . $class . $style . ' ' . $data . ' >';

        }

        return '';

    }





    protected function alias($alias = '', $queryString = ''){

        $str = '';

        if($queryString){

            if(is_array($queryString)){

                foreach ($queryString as $key => $item){

                    if(is_array($item)){

                        $key .= '[]';

                        foreach ($item as $v) $str .= (!$str ? '?' : '&') . $key . '=' . $v;

                    }else{

                        $str .= (!$str ? '?' : '&') . $key . '=' . $item;

                    }


                }

            }else{

                if(strpos($queryString, '?') === false) $str .= '?' . $queryString;
                    else $str .= $queryString;

            }

        }

        if(is_array($alias)) {

            $aliasStr = '';

            foreach ($alias as $key => $item) {

                if (!is_numeric($key) && $item) {

                    $aliasStr .= $key . '/' . $item . '/';

                } elseif (is_numeric($key) && $item) {

                    $aliasStr .= $item . '/';

                }

            }

            $alias = trim($aliasStr, '/');

        }

        if(!$alias || $alias === '/') return PATH . $str;

        if(preg_match('/^https?:\/\//', $alias))
            return $alias . $str;

        return preg_replace('/\/{2,}/', '/', PATH . $alias . END_SLASH . $str);

    }






    protected function getVideo($video){

        if(!$video) return '';

        if(preg_match('/<iframe/', $video)){

            $content = $video;

        }else{

            if(strpos($video, 'embed') === false){

                $arr = explode('/', $video);

                $video = 'https://youtube.com/embed/' . $arr[count($arr) - 1];

            }

            $content = '<iframe src="' . $video . '"
                                                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen></iframe>';

        }

        return $content;

    }





    protected function checkAlias(){

        if(!isset($this->parameters['alias']) || !$this->parameters['alias'])
            throw new RouteException('Отсутствует алиас страницы при запросе контроллера ' . __CLASS__);

    }






    protected function setError($table = false, $alias = false){

        !$table && $table = $this->table ?: $this->getController();

        !$alias && $alias = !empty($this->parameters['alias']) ? $this->parameters['alias'] : false;

        $message = 'Не найдены записи в ' . $table;

        if($alias){

            $message .= ' по ссылке ' . $this->parameters['alias'];

        }

        throw new RouteException($message);

    }





    protected function wordsForCounter($counter, $arrElement = 'products'){

        $arr = [
            'products' => [
                'Товаров',
                'Товар',
                'Товара'
            ],
            'duration' => [
                'дней',
                'день',
                'дня'
            ],
        ];

        if(is_array($arrElement)){

            $arr = $arrElement;

        }else{

            $arr = $arr[$arrElement] ?? array_shift($arr);

        }

        $char = (int)substr($counter, -1);

        $counter = (int)substr($counter, -2);

        if(($counter >= 10 && $counter <= 20) || ($char >= 5 && $char <= 9) || $char === 0) return $arr[0];
        elseif ($char === 1) return $arr[1];
        else return $arr[2];

    }


// метод вылидации данных пришедших из поля colorpicker таблицы фильтров и правильного задания цвета для стилей.
// #B39494;rgb(162, 63, 3),
    protected function createColors($data, $degrees = 90){

        if($data){
            // делим пришедшую строку на массив по ;. Если элемент один, то в стили нужно просто передать цвет в нужном формате. Для более 1-го эл. linear-gradient
            $colorArr = preg_split('/\s*;\s*/', $data, 0 , PREG_SPLIT_NO_EMPTY);
            $countColors = count($colorArr);

            if($countColors === 1)
                return $this->setColorType($colorArr[0]);

            $resultColor = "linear-gradient({$degrees}deg";
            $percent = 100 / ($countColors -1);

            foreach ($colorArr as $key => $item){

                $key *= $percent;

                if($key > 100)
                    $key = 100;

                $resultColor .= ', ' . $this->setColorType($item) . ' ' . $key . '%';

            }

            $data = $resultColor . ');';

        }

        return $data;
    }


// вспомогательный метод для проверки правильного формата задания цвета, #B39494, rgb(162, 63, 3), rgba
// Данные могут прийте для hex без #, для rgb без rgb
    protected function setColorType($color){

        //убираем пробелы если есть
        $colorNew = preg_replace('/\s+/', '', $color);

        //если формат верный возвращаем данные
        if(preg_match('/^(#)|(rgb)/i', $colorNew)){
            return $color;
        }

        // убираем скобки (rgb) если есть, делим строку на массив (разделитель ,), считаем сколько элементов получилось
        // если один то это hex и просто добавляем #. Если 3 то rgb, 4 rgba
        $colorNew = preg_replace('/[\(\)]/', '', $colorNew);
        $colorArr = preg_split('/,/', $colorNew, 0, PREG_SPLIT_NO_EMPTY);
        $count = count($colorArr);

        switch ($count){
            case 1:
                $colorNew = '#' . $color;
                break;

            case 3:
                $colorNew = 'rgb(' . implode(',', $colorArr) . ')';
                break;

            case 4:
                $colorNew = 'rgba(' . implode(',', $colorArr) . ')';
                break;

            default:
                $colorNew = $color;
        }

        return $colorNew;

    }


    protected function setFormValues($key, $property = null, $arr = []){

        if(!$arr){
            $arr = $_SESSION['res'] ?? [];
        }

        if(!empty($arr[$key])){
            return $arr[$key];

        }elseif ($property && !empty($this->$property[$key])){
            return $this->$property[$key];
        }

        return '';

    }



    protected function addDeleteTrackToPlaylist($trackId = null, $playListId = null, $action = 'add'){

        $trackId = $this->clearNum($trackId ?? ($this->ajaxData['id'] ?? 0));
        $playListId = $this->clearNum($playListId ?? ($this->ajaxData['playListId'] ?? 0));

        if (!$trackId || !$playListId){
            return ['error' => 1, 'message' => $this->translateEl('Не балуйтесь')];
        }

        $where = ['track_id' => $trackId, 'playlists_id' => $playListId];

        $res = $this->model->get('playlists_track', [
            'where' => $where,
            'single' => true
        ]);


        if ($action === 'delete'){

            if (!$res){
                return ['error' => 1, 'message' => $this->translateEl('Данного трека нет в этом плейлисте')];
            }

            $delete = $this->model->delete('playlists_track', [
                'where' => $where
            ]);

            if (!$delete){
                return ['error' => 1, 'message' => $this->translateEl('Ошибка при удалении записи')];
            }

        }else{

            if ($res){
                return ['error' => 1, 'message' => $this->translateEl('Этот трек уже есть в этом плейлисте')];
            }

            $fields = $where;

            $add = $this->model->add('playlists_track', [
                'fields' => $fields
            ]);

            if (!$add){
                return ['error' => 1, 'message' => $this->translateEl('Ошибка при добавлении записи')];
            }

        }

        return ['success' => 1];
    }





    protected function addToPlaylist($namePlaylist = null){

        $namePlaylist = $this->clearStr($namePlaylist ?: ($this->ajaxData['namePlaylist'] ?: 'Новый плейлист'));

        if (!$this->userData){
            return ['error' => 1, 'message' => $this->translateEl('Не авторизован пользователь')];
        }

        $NewPlaylistId = $this->model->add('playlists', [
            'fields' => ['name' => $namePlaylist, 'visitors_id' => $this->userData['id']],
            'return_id' => true
        ]);

        if (!$NewPlaylistId){
            return ['error' => 1, 'message' => $this->translateEl('Ошибка при добавлении записи')];
        }

        return ['success' => 1, 'id' => $NewPlaylistId, 'name' => $namePlaylist];

    }




    protected function deletePlaylist($id = null){

        if (!$this->userData){
            return ['error' => 1, 'message' => $this->translateEl('Не авторизован пользователь')];
        }

        $id = $this->clearNum($id ?? ($this->ajaxData['id'] ?? 0));

        if (!$id){
            return ['error' => 1, 'message' => $this->translateEl('Не балуйтесь')];
        }

        $where = ['id' => $id, 'visitors_id' => $this->userData['id']];

        $res = $this->model->get('playlists', [
            'where' => $where,
            'single' => true
        ]);

        if (!$res){
            return ['error' => 1, 'message' => $this->translateEl('Плейлист не найден')];
        }

        $set = [];
        $set['where'] = $where;


        if ($this->model->get('playlists_track', [
            'where'=> ['playlists_id' => $id]
        ])){
            // удалятся записи в связанной таблице только при delete rule - cascade
            $set['join'] = [
                [
                    'table' => 'playlists_track',
                    'on' => ['id', 'playlists_id'],
                ]
            ];

        }


        $delete = $this->model->delete('playlists', $set);

        if (!$delete){
            return ['error' => 1, 'message' => $this->translateEl('Ошибка при удалении записи')];
        }

        return ['success' => 1];

    }




}





























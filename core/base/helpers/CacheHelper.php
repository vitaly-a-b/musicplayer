<?php

namespace core\base\helpers;

use core\base\exceptions\CacheException;
use core\base\exceptions\LowLevelException;
use core\base\settings\Settings;

trait CacheHelper
{

    // если в false то сбрасывается логика получения кеша
    protected $dynamicCache = true;

    // используемые таблицы
    protected $usingTables = [];

    // возможность проинициализировать дополнительные свойства
    protected $cacheInit = false;

    //по дефолту файл с кешем для данных из контроллера хранится в директории с именем контроллера
    // если нужно изменить директорию, то прописываем в $changeDirectory , например, для goodsController
    // $changeDirectory = ['goods' => 'директория']
    protected $changeDirectory = [];

    // таблицы на которые система сброса кеширования не будет работать
    protected $excludeTables = ['settings'];

    // контроллеры в которых не включаем кеширование
    protected $excludeControllers = ['cart', 'lk', 'ajax', 'send_mail'];

    // свойства исключаемые из кеширования (как правило свойства шаблонизации)
    protected $excludeProperties = ['content', 'header', 'footer', 'styles', 'scripts'];

    protected $cacheTimeSettings = 'set';

    // поля где искать время кеширования
    protected $cacheTimeFields = ['cache_time'];

    protected $cacheDefaultTime = 0;

    protected $cacheControlTable = 'cached_tables';

    // поле с именем закешированной таблицей
    protected $cacheControllTableName = 'name';

    protected $cacheHelperModel;

    //в true запрет кеширования
    protected $debugMode = false;

    protected $objectVars = [];




    protected function cacheInit($model = null, $properties = []){

        $model = $this->cacheHelperModel = $model;

        foreach ($properties as $name => $item){
            $this->$name = $item;
        }

        if(!$this->cacheHelperModel){

            $this->cacheHelperModel = $this->model;

        }

        $this->cacheInit = true;

    }



    // проверка кеша
    protected function checkCache($returnException = true){

        // если кеш отключен, ошибка в http запросе, или текущий контроллер есть в $this->excludeControllers, то прерываем дальнейшую работу логики кеширования
        if (!$this->isHttpRequest() || $this->debugMode || !$this->dynamicCache ||
            (!empty($this->excludeControllers) && in_array($this->getController(), $this->excludeControllers))){

            return $this->dynamicCache = false;

        }

        !$this->cacheInit && $this->cacheInit();

        // сначала проверка наличия самого файла с кешем, затем востановление данных из файла. Обязательно заполненное из кеша $this->usingTables для сравнение с тем что сейчас есть в БД
        if (!file_exists($this->getCacheFile()) || !($obj = $this->getCache()) || !$obj->usingTables){
            return $this->dynamicCache = false;
        }

        $cachedTables = [];

        // есле есть таблица в БД содержащая закешированные таблицы
        if (in_array($this->cacheControlTable, $this->cacheHelperModel->showTables())){

            // получаем из БД актуальные данные по закешированым таблицам для сравнения с теми данными которые закешированы
            if (($cached = $this->cacheHelperModel->get($this->cacheControlTable, ['where' => [$this->cacheControllTableName => array_keys($obj->usingTables)]]))){

                // для таблиц из стоки $item['date'](дата последнего обновление таблицы) формируем объект DateTime
                foreach ($cached as $item){
                    $item['date'] && $cachedTables[$item[$this->cacheControllTableName]] = new \DateTime($item['date']);
                }

            }

        }

        // текущая метка времени
        $dateTime = new \DateTime();

        $settings = $this->cacheTimeSettings;
        $setArr = &$this->$settings;

        if ($this->cacheTimeFields){

            foreach ($this->cacheTimeFields as $item){

                if (!empty($setArr[$item])){
                    $this->cacheDefaultTime = $this->clearNum($setArr[$item]);
                }
            }
        }

        // перебираем данные по закешированным таблицам, если таблица изменялась после кеширования или кеш устарел по времени ($this->cacheDefaultTime)
        // то устанавливаем $this->dynamicCache = false для того чтобы кеш создавался по новой
        foreach ($obj->usingTables as $table => $cacheDate){

            $date = new \DateTime($cacheDate);

            if (!in_array($table, $this->excludeTables) &&
                ((!empty($cachedTables[$table]) && $cachedTables[$table] > $date) ||
                    $this->cacheDefaultTime && $dateTime > $date->modify("+$this->cacheDefaultTime second"))){

                //unset($this->usingTables);

                return $this->dynamicCache = false;
            }
        }

        //перебираем все свойства полученного объекта и устанавливаем те которые нужно текущему объекту
        foreach ($obj as $key => $item){

            // заполняем данными текущий объект. Не заполняем свойства из $this->excludeProperties и те которые уже установлены
            if($key !== '___vars' && !in_array($key, $this->excludeProperties) && (!isset($this->$key) || (!$this->$key && $item))){

                $this->$key = $item;

            }

        }

        // получаем данные которые хранились в '___vars' (те которые возращает inputData())
        $this->objectVars = !empty($obj->___vars) ? $obj->___vars : [];



        // выброс исключения с целью пропуска участка скрипта который получает данные закешированные ранее
        if ($returnException){
            throw new LowLevelException();
        }

        return true;

    }




  // создание файла с кешем, $template - сериализованный шаблон с кешируемыми данными
    protected function createCache($args = [], $template = ''){

        // если кеш отключен, уже создан или ошибка подключения к БД, то уходим
        if (!$this->isHttpRequest() || $this->debugMode || $this->dynamicCache || empty($this->cacheHelperModel)){
            return false;
        }

        if (!$template){

            // формируем $this->usingTables с текущими метками времени для кеширования этих данных
            !$this->usingTables && $this->usingTables = $this->cacheHelperModel->getRealTables();

            $object = $this;
            $object->___vars = $args;

            // сериализуем объект перед сохранением в файл
            $template = serialize($object);

        }

        // сжимаем данные
        if (function_exists('gzdeflate')){
            $template = gzdeflate($template, 9);
        }

        // сохраняем в файл
        if (!file_put_contents($this->getCacheFile(), $template)){
            return false;
        }

        return true;

    }



    // проверка наличия кеша для данного контроллера и url. Заполнение текущего объекта данными из кеша если файл кеша успешно прочитан.
    protected function getCache(){

        if ($this->dynamicCache){

            // получаем из файла сжатые закешированные данные
            $obj = @file_get_contents($this->getCacheFile());

            // востанавливаем данные до строкового состояния
            if(function_exists('gzinflate')){
                $obj = @gzinflate($obj);
            }

            // десериализуем данные
            $obj = unserialize($obj);

            // если данные есть и они уже не строковые (десереализация успешна)
            if ($obj && !is_string($obj)){
                return $obj;
            }

        }

        return false;

    }



    //добавления к $vars закешированных данных.
    protected function addNoCacheVar(&$vars = []){

        // склеиваем закешированные данные с незакешированными
        !is_array($vars) && $vars = [];
        $vars = array_merge($this->objectVars, $vars);

        return true;

    }




    /* получение корневой директории с кешем
     * Корневой путь может быть задан в настройках в свойстве 'cachePath' или по дефолту  '/cache/имя сайта/'
     */
    protected function getCachePath(){

        static $cachePath = null;

        $settingsPath = Settings::get('cachePath');
        $settingsPath && preg_replace('/\/+\s*$/', '', $settingsPath);

        return $cachePath ?: $cachePath = ($settingsPath ?: 'cache') . '/' . $_SERVER['SERVER_NAME'] . '/';

    }



    // получение полного пути и имени файла кеша. Метод проверяет путь, если директорий нет то создает их.
    // Определяет каким должно быть имя файла с кешем для данной страницы, но не создает файл и не проверяет его наличие
    protected function getCacheFile(){

        static $cacheFile = null;

        if ($cacheFile){
            return $cacheFile;
        }

        // получаем директорию из имени текущего контроллера
        $dir = $this->getController();

        // если директория изменена в $this->changeDirectory
        if (!empty($this->changeDirectory[$dir])){
            $dir = $this->changeDirectory[$dir];
        }

        // формируем полный путь
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . PATH . $this->getCachePath() . $dir . $this->subDirectory();

        // если директории еще нет то создаем ее
        if(!is_dir($fullPath)){

            if(!mkdir($fullPath, 0777, true)){
                throw new CacheException();
            }

        }

        // возвращаем полный путь с именем файла
        return $cacheFile = $fullPath . $this->filename() . '.php';

    }



    //поддиректория хранения файла кеша. Формируется из параметра адресной строки
    protected function subDirectory(){

        static $subDirectory = null;

        if($subDirectory){
            return $subDirectory;
        }

        $matches = null;

        preg_match('/^\/?([^\/?]+\/[^a-z1-9?]*([a-z1-9]+))|([^a-z1-9]*([a-z1-9]+))/i', $_SERVER['REQUEST_URI'], $matches);

        $subDirectory = '/';

        if($matches){

            if (!empty($matches[4])){
                $subDirectory .= $matches[4] . '/';

            }elseif (!empty($matches[2])){
                $subDirectory .= $matches[2] . '/';
            }
        }

        return $subDirectory;

    }



    // формирование имени файла из адресной строки
    protected function fileName(){

        static $filename = null;

        if(!$filename){
            $filename = md5($_SERVER['REQUEST_URI']);
        }

        return $filename;
    }




    // очищение кеша. $cachePath - путь к директории с кешем
    protected function clearCache($cachePath = ''){

        // если путь не задан, то берем по дефолту
        !$cachePath && $cachePath = $this->getCachePath();

        // добавляем конечный слешь если его нет
        if (!preg_match('/\/\s*$/', $cachePath)){
            $cachePath .= '/';
        }

        // получаем массив каталогов и файлов
        $list = scandir($cachePath);

        if ($list){

            foreach ($list as $file){

                if ($file !== '.' && $file !== '..' ){

                    //если это директория, то рекурсивно очищаем то что есть в ней. Если файл то удаляем его
                    if (is_dir($cachePath . $file)){

                        $this->clearCache($cachePath . $file);
                        @rmdir($cachePath . $file);

                    }else{
                        @unlink($cachePath . $file);
                    }

                }
            }
        }

    }


}


























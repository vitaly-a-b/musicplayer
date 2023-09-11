<?php


namespace core\user\helpers;


use core\base\settings\Settings;
use core\user\model\Model;

trait SeoHelper
{

    /* цепочка родителей. $chainParents нужен для гибкого конфигурирования построения навигационной цепочки
     * Конфигурирование в $chainParents: ['имя таблицы' => [имя поля связывающее с другой таблицей 1, имя поля связывающее с другой таблицей 2, ...], ...]
     * например ['goods' => ['parent_id', 'другое связующее поле'], 'catalog' => [], 'другие таблицы' => []]
     * Скрипт от текущего элемента переходит по связям от одной таблицы к другой создовая цепочку. Дойдя до укзанной в $chainParents таблицы, например
     * 'goods' => ['parent_id'], связь со следующей таблицей (или другим полем этой таблицы) будет проверяться только по указанным в массиве полям и в том порядке в каком они следуют,
     *  в данном случае  'parent_id'. Цепочка продолжется от от этого поля если, конечно, оно является связующим.
     * Если указанно еще какое то поле, то закончив по первому полю, продолжится создание цепочки и по второму.
     * Если ключ таблицы в $chainParents есть, а поля не указаны, например 'goods' => [], то создание цепочки прервется на этой таблице ('goods'), несмотря на
     * то что могут быть у нее связи
    */
    protected $chainParents = [
        'goods' => ['information_id', 'parent_id'],
        //'special_information' => []
    ];

    // второй элемент навигационной цепочки после главной страницы
    protected $rootChainElement = [];

    protected $setFullChainData = false;

    // если установлена, то навигационная цепочка не будет строиться
    protected $skipChainBuilding = false;

    protected $columnName = 'name';
    protected $columnAlias = 'alias';


// построение навигационной цепочки. Вызывается в BaseUser метод outputData.
// построение идет по цепочке родителей от одного связанного поля к другому добавляя элементы указанные в этих полях
    protected function createChainElements($data){

        // если цепочку строить не нужно или нет данных
        if(empty($data) || $this->skipChainBuilding){
            return [];
        }

        $chaining = [];

        !$this->model && $this->model = Model::instance();

        // получаем текущую таблицу. Из this->table или из имени контролера или из имени входного метода, если он не дефолтный
        $currentTable = $this->getCurrentTable();

        if(!$currentTable){
            return false;
        }

        // колонки текущей таблицы
        $currentColumns = $this->model->showColumns($currentTable);

        // выборка данных из текущей таблицы
        $currentData = !empty($data['data'][$currentColumns['id_row']]) ? $data['data'] :
            (!empty($data[$currentColumns['id_row']]) ? $data : null);

        // если данные о текущем элементе, для которого нужно построить нав.цепочку, есть,
        if($currentData){

            // устанавливаем элемент в нав.цепочку
            $this->setChaining($currentData, $currentTable, $chaining);

            // устанавливаем метаданные
            $this->setMeta($currentData);

            // получаем массив с полями таблицы содержащими внешние ключи ($fields[0] = 'parent_id')
            $fields = $this->getTableForeignColumns($currentTable);

            // выбираем ключ текущего элемента
            $key = key($fields);

            // значение текщего элемента $fields
            $item = isset($key) ? $fields[$key] : null;

            $table = $currentTable;
            $previousTable = null;
            $currentElement = $currentData;
            $nextElement = null;

            // создаем цепочку для данного элемента по внешним связям данной таблицы. Цыкл пока есть поле с внешним ключем
            while ($item){

                // получаем данные на какую конкретно таблицу и колонку ссылается $item ('parent_id')
                $foreignData = $this->model->showForeignKeys($table, $item);

                // если установленной связи нет, но $item === 'parent_id', тогда принимаем то что 'parent_id' указывает на первичный ключ (id) своей же таблицы
                if (!$foreignData && $item === 'parent_id'){

                    $foreignData = [];

                    $foreignData[0]['REFERENCED_TABLE_NAME'] = $table;
                    $foreignData[0]['REFERENCED_COLUMN_NAME'] = $this->model->showColumns($table)['id_row'];

                }

                // если внешние данные есть то определяем следующий элемент на который ссылался текущий
                if($foreignData){

                    // внешняя таблица, где хранится следующий элемент
                    $table =  $foreignData[0]['REFERENCED_TABLE_NAME'];

                    //
                    if ($table !== $previousTable || $nextElement){

                        $previousTable = $table;

                        // получаем поле во внешней, которое связанно с полем в $item текущей таблицы
                        $idRow =  $foreignData[0]['REFERENCED_COLUMN_NAME'];

                        // массив с внешними ключами таблицы
                        $tempFields = $this->getTableForeignColumns($table);

                        // объеденяем массивы с внешнеми ключами таблиц
                        array_splice($fields, $key + 1, 0, $tempFields);

                        // array_splice сбрасывает внутренний указатель массива на 1-й эл. Поэтому востанавливаем указатель
                        for ($i = 0; $i < $key; $i++){
                            next($fields);
                        }

                        // получаем следующий элемент на который ссылался текущий по полю с внешним ключем.
                        $nextElement = $this->getForeignElement($currentElement[$item] ?? null, $table, $idRow);

                        // если элемент найден, то добавляем в цепочку
                        if ($nextElement){
                            $this->setChaining($nextElement, $table, $chaining);

                            //если в $this->chainParents $table = пустому массиву, т.е. останавливаем раскручивать цепочку по связям этой таблицы
                            if (is_array($this->chainParents) && array_key_exists($table, $this->chainParents) && empty($this->chainParents[$table])){

                                // возвращаем значение таблици на текущую
                                $table = $currentTable;
                                $currentElement = $currentData;

                            }else{
                                $currentElement = $nextElement;
                            }

                        }else{

                            $currentElement= $currentData;
                            $table = $currentTable;

                            if($key === count($fields) -1){
                                prev($fields);

                            }


                        }


                    }else{
                        $table = $currentTable;

                    }

                }else{
                    $table = $currentTable;

                }

                // перемещаем указатель массива с внешнеми ключами вперед и сотрим есть ли еще поля
                next($fields);
                $key = key($fields);
                $item = isset($key) ? $fields[$key] : null;

            }

        }

        return $this->setCorrectPrepareChaining($chaining, $currentTable);

    }



    // корректировка цепочки. Добавление главной страницы и корневого элемента
    private function setCorrectPrepareChaining($chaining, $table){

        if($chaining){

            $this->checkMetaData($chaining);

            if (isset($this->rootChainElement) && (!isset($this->chainParents[$table]) || !empty($this->chainParents[$table]))){

                $name = null;

                // алиас по названию таблицы, если не будет переопределен
                $alias = $table;

                // если корневой элемент задан в $this->rootChainElement (2-й элемент после главной страницы), то берем данные оттуда
                if (!empty($this->rootChainElement)){

                    if (is_array($this->rootChainElement)){

                        $alias = key($this->rootChainElement);
                        $name = $this->rootChainElement[$alias];

                    }else{
                        $alias = $this->rootChainElement;

                    }
                }

                !$name && $name = !empty($this->metaData[$table]['name']) ? $this->metaData[$table]['name'] :
                    (Settings::get('projectTables')['name'] ?? null);

                if ($name){

                    // второй элемент цепочки
                    $chaining[] = [
                        'name' => $name,
                        'alias' => $alias
                    ];

                }

            }

            // первый элемент цепочки (главная страница
            $chaining[] = [
                'name' => $this->translateEl('Главная'),
                'alias' => $this->alias()
            ];

        }

        // переворачиваем цепочку задом на перед
        $chaining && is_array($chaining) && $chaining = array_reverse($chaining);

        if (property_exists($this, 'chaining')){
            $this->chaining = $chaining;
        }

        return $chaining;

    }




    // установка в навигационную цепочку
    private function setChaining($data, $table, array &$chaining){

        // число элементов в цепочке
        $c = count($chaining);

        // сохранять все данные или только имя и алиас
        if(!$this->setFullChainData){

            $chaining[$c][$this->columnName] = $data[$this->columnName] ?? '';
            $chaining[$c][$this->columnAlias] = $data[$this->columnAlias] ?? '';

        }else{

            $chaining[$c] = $data;
        }

        $chaining[$c]['table'] = $table;

    }


    // метод для получения внешнего элемента
    // $id - номер элемента, $field - поле в котором искать $id
    private function getForeignElement($id, $table, $field){

        $columns = $this->model->showColumns($table);
        $where = [$field => $id];

        if(!empty($columns['visible'])){
            $where['visible'] = 1;
        }

        $result = $this->model->get($table, [
            'where' =>$where,
            'limit' => 1,
            'single' => true
        ]);

        if($result){
            $this->setMeta($result);
        }

        return $result;

    }


    // внешние ключи конкретной таблицы. Возвращаем массив элементов в котором сохранено имя колонки(ок), которая связазана с внешней таблицей.
    // либо пустой массив если связи нет ()
    protected function getTableForeignColumns($currentTable){

        $chainParents = $this->chainParents;

        if(!is_array($chainParents)){
            $chainParents = [];
        }

        // если в свойстве $this->chainParents есть ключ с именем данной таблицы, то значение по этому ключу и вернем
        // там либо поля для внешней связи или пустой массив, сообщающий что даже если внешние ключи есть использовать их не нужно
        if(!isset($chainParents[$currentTable])){

            $chainParents[$currentTable] = [];
            $parentRow = null;

            // если есть поле 'parent_id' в текущей таблице, то берем в качестве внешней связи его
            // если такого нет, то берем первую найденную внешнюю связь с помощью showForeignKeys
            if(!empty($this->model->showColumns($currentTable)['parent_id'])){
                $parentRow = 'parent_id';

            }elseif (($currentTableForeignKeys = $this->model->showForeignKeys($currentTable))){

                $parentRow = $currentTableForeignKeys[0]['COLUMN_NAME'];
            }

            // если поля с внешним ключем нет возращаем пустой массив
            if(!$parentRow){
                return [];
            }

            // сохраняем имя колонки текущей таблицы, указывающей на внешнюю связь
            $chainParents[$currentTable][] = $parentRow;

        }

        return $chainParents[$currentTable];

    }


    //получение исходной таблицы
    private function getCurrentTable(){

        $currentTable = null;

        // если входной метод установлен не по дефолту, то возможно, имя таблицы это имя метода в camelCase
        if(!empty($this->inputMethod) && $this->inputMethod !== Settings::get('routes')['default']['inputMethod']){

            $inputMethod = strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1_$2', $this->inputMethod));

            if(in_array($inputMethod, $this->model->showTables())){
                $currentTable = $inputMethod;
            }

        }

        // если предыдущий метод не сработал, то имя таблице может быть в свойстве $this->table или в названии контроллера
        if(!$currentTable){

            $table = $this->table ?: $this->getController();

            if(in_array($table, $this->model->showTables())){
                $currentTable = $table;
            }

        }

        return $currentTable;

    }



    private function checkMetaData($chaining){

        $this->getMetaData();

        if($chaining && count($chaining) > 1){

            if ($this->metaData){

                foreach (array_unique(array_column($chaining, 'table')) as $table){

                    $this->setMeta($this->metaData[$table] ?? []);
                }
            }
        }

        $this->setMeta($this->set ?? []);

    }



    protected function getMetaData(){

        if(!$this->metaData){

            if(in_array('metadata', $this->model->showTables())){

                $metadata = $this->model->get('metadata');

                if ($metadata){

                    foreach ($metadata as $item){

                        $this->metaData[$item['table_name']] = $item;
                    }
                }
            }

        }

        return $this->metaData;

    }


    // установка правильной ссылки на элементе навигационной цепочки. Вызывается в шаблоне хлебных крошек.
    protected function getCrumbsAlias($data){

        return  !empty($data['alias']) ? $this->alias((!empty($data['table']) ? $data['table'] . '/' : '') .  $data['alias']) : '';
    }




    // установка значений метатегов в соответствующие свойства объекта класса
    private function setMeta($data){

        if($data){

            if (!$this->title){
                $this->title = $data['title'] ?? ($data['name'] ?? '');
            }

            if (!$this->h1){
                $this->h1 = $data['h1'] ?? ($data['name'] ?? '');
            }

            if (!$this->description){
                $this->description = $data['description'] ?? '';
            }

            if (!$this->keywords){
                $this->keywords = $data['keywords'] ?? '';
            }

        }

    }



    // вывод метатегов на странице ($this->getMeta() в head)
    protected function getMeta(){

        if($this->title){

            if(preg_match('/##/', $this->title)){

                if($this->h1)
                    $this->title = str_replace('##', $this->h1, $this->title);
                else
                    $this->title = preg_replace('/\s*##\s*/', '', $this->title);
            }

            echo '<title>' . $this->title . '</title>' . "\r\n";

        }

        if($this->description){

            if(preg_match('/##/', $this->description)){

                if($this->h1)
                    $this->description = str_replace('##', $this->h1, $this->description);
                else
                    $this->description = preg_replace('/\s*##\s*/', '', $this->description);


            }

            echo '<meta name="description" content="' . $this->description . '">'  . "\r\n";
        }

        if($this->keywords){

            if(preg_match('/##/', $this->keywords)){

                if($this->h1)
                    $this->keywords = str_replace('##', $this->h1, $this->keywords);
                else
                    $this->keywords = preg_replace('/\s*##\s*/', '', $this->keywords);


                $this->keywords = preg_replace('/\s*,\s*,\s*/', ', ', $this->keywords);

            }

            echo '<meta name="keywords" content="' . $this->keywords . '">'  . "\r\n";

        }

    }



}



















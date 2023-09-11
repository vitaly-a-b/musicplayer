<?php


namespace libraries\import1C;


use core\admin\controller\BaseAdmin;
use core\admin\model\Model;
use core\base\helpers\CacheHelper;
use libraries\FileEdit;
use libraries\TextModify;

// класс обрабатывает полученные файлы от 1с, парсит их структурируя данные и на ох основе обновляет БД
class Import1C extends BaseAdmin
{
    use CacheHelper;

    protected $directory;

    protected $goodsTable = 'goods';
    protected $offersTable = 'offers';
    protected $catalogTable = 'catalog';
    protected $filtersTable = 'filters';
    protected $filtersGoodsTable = 'filters_goods';
    protected $priceType = 0;
    protected $proceccedElements = [];



    protected function inputData()
    {

        // снимаем ограничение на выполнения скрипта
        set_time_limit(0);

        $this->model = Model::instance();

        //директория где искать файлы
        $dir = $this->setDirectory();

        // имена файлов
        $fileArr = ['import', 'offers', 'prices', 'rests'];

        foreach ($fileArr as $file){

            // поиск файлов с импортируемыми данными
            $fileExists = $this->searchFile($dir, $file);

            // перебираем все найденные файлы
            if ($fileExists){

                foreach ($fileExists as $importFile){

                    // получаем объект из xml-файла
                    $xml = simplexml_load_file($importFile);

                    if ($xml){

                        // получаем массив их xml-объекта
                        $data = json_decode(json_encode($xml), true);

                        if ($data){

                            switch ($file){

                                case 'import':
                                    $this->createImport($data, $importFile);
                                    break;

                                case 'offers':
                                    $this->createOffers($data);
                                    break;

                                case 'prices':
                                    $this->createPrices($data);
                                    break;

                                case 'rests':
                                    $this->createRests($data);
                                    break;
                            }

                        }

                    }
                }
            }

        }

        if ($this->proceccedElements){
            $this->clearCache();
        }

        $this->clearDir($dir);

        return $this->proceccedElements;
    }




    protected function createPrices($data){

        if (!empty($data['ПакетПредложений']['Предложения']['Предложение'])){

            $data = $data['ПакетПредложений']['Предложения']['Предложение'];

            if (!is_numeric(key($data))){
                $data = [$data];
            }

            foreach ($data as $item){

                if (!empty($item['Цены']['Цена'])){

                    $table = preg_match('/#/', $item['Ид']) ? $this->offersTable : $this->goodsTable;
                    $prices = $item['Цены']['Цена'];

                    if (!is_numeric(key($prices))){
                        $prices = [$prices];
                    }

                    if (!empty($prices[$this->priceType])){

                        $price = !empty($prices[$this->priceType]['ЦенаЗаЕдиницу']) ? $this->clearNum($prices[$this->priceType]['ЦенаЗаЕдиницу']) : 0;

                        $el = $this->model->get($table, [
                            'fields' => ['id'],
                            'where' => ['1c_id' => $item['Ид']],
                            'limit' => 1,
                            'single' => true
                        ]);

                        if ($el){

                            $this->model->edit($table, [
                               'fields' => ['price' => $price],
                                'where' => ['id' => $el['id']]
                            ]);
                        }

                    }

                }

            }

        }

    }



    protected function createRests($data){

        if (!empty($data['ПакетПредложений']['Предложения']['Предложение'])){

            $data = $data['ПакетПредложений']['Предложения']['Предложение'];

            if (!is_numeric(key($data))){
                $data = [$data];
            }

            foreach ($data as $item){

                if(!empty($item['Остатки']['Остаток'])){

                    $table = preg_match('/#/', $item['Ид']) ? $this->offersTable : $this->goodsTable;

                    $el = $this->model->get($table, [
                        'fields' => ['id'],
                        'where' => ['1c_id' => $item['Ид']],
                        'limit' => 1,
                        'single' => true
                    ]);

                    if (!$el){
                        continue;
                    }

                    $rests = $item['Остатки']['Остаток'];

                    if (!is_numeric(key($rests))){
                        $rests = [$rests];
                    }

                    $quantity = 0;

                    foreach ($rests as $store){

                        if (!empty($store['Склад']['Количество'])){
                            $quantity += $this->clearNum($store['Склад']['Количество']);
                        }

                        if (!empty($store['Количество'])){
                            $quantity += $this->clearNum($store['Количество']);
                        }

                    }

                    $this->model->edit($table, [
                       'fields' => ['quantity' => $quantity],
                        'where' => ['id' => $el['id']]
                    ]);

                }

            }



        }

    }





    protected function createOffers($data){

        if (!empty($data['ПакетПредложений']['Предложения']['Предложение'])){

            $data = $data['ПакетПредложений']['Предложения']['Предложение'];

            if (!is_numeric(key($data))){
                $data = [$data];
            }

            // название полей которые нужно трансформировать из формата 1с в формат БД
            $addFields = [
                'name' => 'Наименование',
                '1c_id' => 'Ид',
            ];

            foreach ($data as $item){

                $fields = $this->addFields($addFields, $item);

                if ($fields && !empty($fields['1c_id'])){

                    $idArr = preg_split('/#+/', $fields['1c_id'], 2, PREG_SPLIT_NO_EMPTY);

                    if(count($idArr) === 2){

                        $goods1CId = $idArr[0];

                        $goodsItem =$this->model->get($this->goodsTable, [
                            'fields' => ['id'],
                            'where' => ['1c_id' => $goods1CId],
                            'limit' => 1,
                            'single' => true
                        ]);

                        if (!$goodsItem){
                            continue;
                        }

                        $fields['parent_id'] = $goodsItem['id'];

                        $this->addElement($fields, $fields['parent_id'], $this->offersTable);

                    }

                }

            }

        }

    }







    //импортирование данных $data из файла $fileName в БД
    protected function createImport($data, $fileName){

        if (!$data){
            return;
        }

        // импортируем в БД элементы каталога
        if (!empty($data['Классификатор']['Группы']['Группа'])){
            $this->createCatalog($data['Классификатор']['Группы']['Группа']);
        }

        // импортируем в БД свойства
        if (!empty($data['Классификатор']['Свойства']['Свойство'])){
            $this->createProperties($data['Классификатор']['Свойства']['Свойство']);
        }

        // импортируем в БД товары
        if (!empty($data['Каталог']['Товары']['Товар'])){
            $this->createGoods($data['Каталог']['Товары']['Товар'], $fileName);
        }


    }



// импорт товаров в БД, $data - массив с товарами, $fileName - файл из которого добавляются товары. Нужен для импорта картинки,
// т.к. путь к файлам с указан относительно $fileName
    protected function createGoods($data, $fileName){

        if (!$data){
            return;
        }

        // название полей которые нужно трансформировать из формата 1с в формат БД
        $addFields = [
            'name' => 'Наименование',
            '1c_id' => 'Ид',
            'article' => 'Артикул',
            'content' => 'Описание'
        ];

        // таблица с товарами в БД
        $table = $this->goodsTable;

        if (!is_numeric(key($data))){
            $data = [$data];
        }

        // перебираем массив с товарами
        foreach ($data as $item){

            // поля таблицы БД значения которых будем добавлять-редактировать
            $fields = $this->addFields($addFields, $item);
            $fields['parent_id'] = null;
            $fields['visible'] = 1;

            // если есть данные по каталогу к которому относится этот товар
            if (!empty($item['Группы']['Ид'])){

                // ищем в БД каталог для данного товара
                $res = $this->model->get($this->catalogTable, [
                    'fields' => ['id', 'visible'],
                    'where' => ['1c_id' => $item['Группы']['Ид']],
                    'limit' => 1,
                    'single' => true
                ]);

                // устанавливаем для товара связанный каталог и его видимость
                if ($res){
                    $fields['parent_id'] = $res['id'];
                    $fields['visible']  = $res['visible'];
                }

            }

            // если есть изображения к товару
            if (!empty($item['Картинка'])){

                //информация о пути к файлу $fileName
                $fileInfo = pathinfo($fileName);

                if (!empty($fileInfo['dirname'])){

                    // добавляем файлы изображений в хранилище
                    $images = $this->createImg((array)$item['Картинка'], $fields['name'], $table, $fileInfo['dirname']);

                    if ($images){

                        // перебираем файлы изображений и добавляем их в $fields чтобы перед записью в БД полностью сфоррмировать данные о товаре
                        foreach ($images as $img){

                            if (empty($fields['img'])){
                                $fields['img'] = $img;

                            }else{
                                $fields['gallery_img'][] = $img;

                            }
                        }
                    }
                }
            }

            // добавляем товар в БД
            $id = $this->addElement($fields, $fields['parent_id'], $table);

            // привязываем свойства к товару
            if ($id){
                $this->addPropertiesToGoods($id, $item);
            }


        }


    }




    // привязка товара к его свойствам-фильтрам. $id - ид товара в БД, $element - данные о товаре из 1с
    protected function addPropertiesToGoods($id, $element){

        // если в выгрузке есть привязанные к товару свойства
        if (!empty($element['ЗначенияСвойств']['ЗначенияСвойства'])){

            $data = $element['ЗначенияСвойств']['ЗначенияСвойства'];

            if (!is_numeric(key($data))){
                $data = [$data];
            }

            // массив в котором будут собираться данные о связи товара со значением свойств
            $addArr = [];

            // перебираем все имеющиеся значения свойства у товара
            foreach ($data as $item){

                // ищем в БД свойство (корневое с 'parent_id' => null)
                $parent = $this->model->get($this->filtersTable, [
                   'where' => ['1c_id' => $item['Ид'], 'parent_id' => null],
                    'limit'=>1,
                    'single' => true
                ]);

                if (!$parent){
                    continue;
                }

                // ищем значение свойства в БД связанное с свойством найденным ранее
                $value = $this->model->get($this->filtersTable, [
                   'where' => ['(1c_id' => $item['Значение'], ')name' => $item['Значение'] , 'parent_id' => $parent['id']],
                    'condition' => ['OR', 'AND'],
                    'limit' => 1,
                    'single' => true
                ]);

                //если значение свойства найдено то формируем для дальнейшей записи в БД связи между товаром и значением свойства
                if ($value){
                    $addArr[] = ['goods_id' => $id, 'filters_id' => $value['id']];

                }else{

                    // если данного значения свойства для товара не найдено в БД, то запишим его туда
                    $menuPosition = ++$this->model->get($this->filtersTable, [
                        'fields' => ['COUNT(*) as count'],
                        'where' => ['parent_id' => $parent['id']],
                        'single' => true
                    ])['count'];

                    // записываем отстутствующее значение свойства в БД
                    $valueId =  $this->model->add($this->filtersTable, [
                       'fields' => ['name' => $item['Значение'], 'menu_position' => $menuPosition, 'parent_id' => $parent['id']],
                        'return_id' => true
                    ]);

                    if (!$valueId){
                        continue;
                    }

                    // сохраняем связь между товаром и значением свойства в массив
                    $addArr[] = ['goods_id' => $id, 'filters_id' => $valueId];

                }

            }

            // если связи для товара нужно обновить, то перед этим удаляем старые
            if ($addArr){

                $this->model->delete($this->filtersGoodsTable, [
                   'where' => ['goods_id' => $id]
                ]);

                $this->model->add($this->filtersGoodsTable, [
                   'fields' => $addArr
                ]);

            }

        }
    }






    protected function createImg($files, $newFileName, $table, $directory){

        // путь до директории где будет храниться файлы с картинками
        $dir = $_SERVER['DOCUMENT_ROOT']. PATH . UPLOAD_DIR . $table;

        // если нет в конце слеша то добавляем
        !preg_match('/\/$/', $directory) && $directory .= '/';

        $fileEdit = new FileEdit();
        $textModify = new TextModify();

        $fileEdit->setDirectory($table);

        $res = [];

        // перебираем массив файлов
        foreach ($files as $file){

            $name = preg_replace('/\/{2,}/', '/', $directory . $file);

            if ($file && file_exists($name)){

                $fileInfo = pathinfo($name);
                $fileName = $textModify->translit($newFileName);
                $fileName = $fileEdit->checkFile($fileName, $fileInfo['extension']);

                if (copy($name, $dir . '/' . $fileName)){
                    $res[] = $table . '/' . $fileName;
                }

            }

        }

        return $res;

    }



// импорт фильтров-свойств и их значений в БД
    protected function createProperties($data){

        if (!$data){
            return;
        }

        // название полей которые нужно трансформировать из формата 1с в формат БД
        // имя свойства (имя: цвет, значения: красный, белый и тд)
        $addNameFields = [
            'name' => 'Наименование',
            '1c_id' => 'Ид'
        ];

        // значения свойства
        $addValuesFields = [
            'name' => 'Значение',
            '1c_id' => 'ИдЗначения'
        ];

        if (!is_numeric(key($data))){
            $data = [$data];
        }

        foreach ($data as $item){

            $id = null;

            // трансформируем названия полей из формата 1с в формат БД
            $fields = $this->addFields($addNameFields, $item);

            // добавляем свойство
            $id = $this->addElement($fields, null, $this->filtersTable);

            // смотрим, есть ли значения добавленного свойства
            if ($id && !empty($item['ВариантыЗначений']['Справочник'])){

                $values = $item['ВариантыЗначений']['Справочник'];

                if (!is_numeric(key($values))){
                    $values = [$values];
                }

                // перебираем массив имеющихся значений добавленного свойства
                foreach ($values as $value){

                    $valueId = null;

                    // трансформируем названия полей из формата 1с в формат БД
                    $fields = $this->addFields($addValuesFields, $value);

                    // устанвливаем для текущего значения родительское свойство
                    $fields['parent_id'] = $id;

                    // добавляем текущее значение в БД и связываем его со свойством
                    $valueId = $this->addElement($fields, $id, $this->filtersTable);

                }

            }

        }

    }




    // добавление новых элементов и редактирование старых в таблице каталога, $data - массив с иерархией элементов каталога
    protected function createCatalog($data, $parentId = null){

        // название полей которые нужно трансформировать из формата 1с в формат БД
        $addFields = [
            'name' => 'Наименование',
            '1c_id' => 'Ид'
        ];

        // таблица каталога в БД
        $table = $this->catalogTable;

        if (!is_numeric(key($data))){
            $data = [$data];
        }

        // перебираем данные которые нужно добавить-редактировать
        foreach ($data as $item){

            $id = null;

            // поля таблицы БД значения которых будем добавить-редактировать
            $fields = $this->addFields($addFields, $item);

            //если в таблице БД есть поле 'parent_id'
            if (isset($this->model->showColumns($table)['parent_id'])){
                $fields['parent_id'] = $parentId;
            }

            //добавляем элемент
            $id = $this->addElement($fields, $parentId, $table);

            // если у добавленного элемента есть дочерние элементы, то вызываем рекурсивно этот метод с  $parentId = id родительского элемента
            if (!empty($item['Группы']['Группа'])){
                $this->createCatalog($item['Группы']['Группа'], $id);
            }

        }

    }



    // функция добавления элемента в БД. $fields - поля таблицы которые будут редактироваться, $parentId - указатель на связанный родительский элемент
    protected function addElement($fields, $parentId, $table){

        $this->table = $table;
        $this->columns = $this->model->showColumns($table);
        $id = null;

        // проверяем, есть ли в БД элемент с таким же '1c_id'
        $el = $this->model->get($table, [
           'where' => ['1c_id' => $fields['1c_id']],
           'single' => true,
           'limit' => 1
        ]);

        // если элемент есть, то его редактируем в БД
        if ($el){
            $id = $el['id'];

            $this->model->edit($table, [
               'fields' => $fields,
               'where' => ['id' => $id]
            ]);

            $dir = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;

            // если у элемента уже есть файл изображения и в импортируемом есть файл на замену, то старый файл удаляем
            if (!empty($fields['img']) && !empty($el['img'])){
                @unlink($dir . $el['img']);
            }

            // аналогично и с галереей изображений
            if (!empty($fields['gallery_img']) && !empty($el['gallery_img'])){

                foreach (json_decode($el['gallery_img'], true) as $img){
                    @unlink($dir . $img);
                }

            }

        }else{ // если элемент новый, то добавляем его в БД

            // корректно устанавливаем поле таблицы 'menu_position', если оно есть
            if (!empty($this->model->showColumns($table)['menu_position']) && !isset($fields['menu_position'])){

                $where = [];

                if ( isset($this->model->showColumns($table)['parent_id'])){
                    $where['parent_id'] = $parentId;
                }

                $fields['menu_position'] = ++$this->model->get($table, [
                    'fields' => ['COUNT(*) as count'],
                    'where' => $where,
                    'single' => true
                ])['count'];
            }

            // делаем видимым элемент после добавления
            if ( isset($this->model->showColumns($table)['visible']) && !isset($fields['visible'])){
                $fields['visible'] = 1;
            }

            // добавляем элемент в БД
            $id = $this->model->add($table, [
                'fields' => $fields,
                'return_id' => true
            ]);

            if (!$id){
                $this->writeLog('Ошибка добавления элемента' . "\r\n" . print_r($fields, true), 'log_import.txt');
                exit("failure\nОшибка добавления элемента");
            }

            $fields['id'] = $id;

            // формируем алиас для элемента
            if (isset($this->model->showColumns($table)['alias'])){
                $fields = $this->createAlias($id, $fields);

                // редактируем алиас добавленного элемента в БД
                $this->model->edit($table, [
                    'fields' => ['alias' => $fields['alias']],
                    'where' => ['id' => $id]
                ]);

            }

        }

        // добавляем к подсчету  чего сохранили сейчас
        $row = 'Товары';

        if ($table === $this->catalogTable){
            $row = 'Категории';
        }

        if (!isset($this->proceccedElements[$row])){
            $this->proceccedElements[$row] = 0;
        }

        $this->proceccedElements[$row]++;

        return $id;

    }



/*трансформируем названия полей из формата 1с в формат БД
 *    $addFields = [
            'name' => 'Наименование',
            '1c_id' => 'Ид',
            'article' => 'Артикул',
            'content' => 'Описание'
        ];
 */
    protected function addFields($addFields, $element){

        // поля таблицы БД значения которых будем добавить-редактировать
        $fields = [];

        if ($addFields && $element){

            // трансформируем названия полей из формата 1с в формат БД
            foreach ($addFields as $key => $value){

                if (isset($element[$value])){
                    $fields[$key] = $element[$value];
                }

            }
        }

        return $fields;

    }





    // поиск нужного файла в имени которого есть $fileName в директории $dir
    protected function searchFile($dir, $fileName){

        // массив файлов для обработки
        $searchRes = [];

        // добавляем слеш в конец, если его нет
        !preg_match('/\/$/', $dir) && $dir .= '/';

        //сканируем директорию
        $list = scandir($dir);

        // вложенные директории, которые нужно будет далее сканировать на наличия там нужных нам файлов
        $directories = [];

        if ($list){

            // перебираем все файлы и директории
            foreach ($list as $file){

                if ($file !== '.' && $file !== '..'){

                    // если директория, то сохраняем для дальнейшей проверки в ней файлов
                    if (is_dir($dir . $file)){
                        $directories[] = $dir . $file . '/';

                    }else{

                        // если файл, то ищем в его имени совпадение с $fileName
                        if (mb_strpos($file, $fileName) !== false){
                            $searchRes[] = $dir . $file;
                        }
                    }

                }
            }

        }

        // если были на пути директории, то проверяем их на наличие искомых файлов
        if ($directories){

            foreach ($directories as $item){

                if (($res = $this->searchFile($item, $fileName))){
                    $searchRes = array_merge($searchRes, $res);
                }
            }
        }

        return $searchRes;

    }




    // установка директории куда будут сохраняться полученные от 1с файлы
    public function setDirectory($dir = ''){

        if (!$this->directory){

            if ($dir){

                if (stripos($dir, $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR) === false){
                    $dir = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $dir . '/';
                }

                $dir = preg_replace('/\/{2,}/', '/', $dir);

            }else{

                $dir = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . '1c_import/';

            }

            $this->directory = $dir;

            if (!is_dir($dir)){

                if (!mkdir($this->directory, 0777, true)){
                    $this->directory = false;
                }
            }
        }

        return $this->directory;
    }



    protected function clearDir($dir){

        !preg_match('/\/$/', $dir) && $dir .= '/';

        $list = scandir($dir);

        if ($list){

            foreach ($list as $file){

                if ($file !== '.' && $file !== '..'){

                    if (is_dir($dir . $file)){

                        $this->clearDir($dir . $file);
                        @rmdir($dir . $file);

                    }else{

                        @unlink($dir . $file);

                    }
                }
            }
        }

    }


}























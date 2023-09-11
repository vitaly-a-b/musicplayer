<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 06.02.2019
 * Time: 13:50
 */

namespace core\base\controller;


trait BaseMethods
{

    public $_variable;



    protected function clearStr($str, $ecran = true){

        if(is_array($str)){
            foreach($str as $key => $item)
                $str[$key] = $this->clearStr($item, $ecran);

            return $str;
        }else{
            return $ecran ? str_replace(array("\\","\0","\n","\r","\x1a","'",'"'),array("\\\\","\\0","\\n","\\r","\Z","\'",'\"'), trim(strip_tags($str))) : trim(strip_tags($str));
        }

    }



    protected function clearNum($num){

        return (isset($num) && $num && preg_match('/\d/', $num)) ? preg_replace('/[^\d.]/', '', str_replace(',', '.', $num)) * 1 : 0;

    }



    protected function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }



    protected function isAjax(){
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }


    protected function isHttpRequest(){
        return empty($_SERVER['HTTP_ACCEPT']) || preg_match('/^((text\/html)|(\W+$))/i', $_SERVER['HTTP_ACCEPT']);
    }


    protected function redirect($http = false, $code = false){

        if($code){
            $codes = ['301' => 'HTTP/1.1 301 Move Permanently'];

            if($codes[$code]) header($codes[$code]);
        }

        if($http) $redirect = $http;
            else $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;

            header("Location: $redirect");

            exit;
    }



    protected function getStyles(){

        if($this->styles){
            foreach($this->styles as $style){

                echo !preg_match('/<\s*\/style\s*>/i', $style) ? '<link rel="stylesheet" href="' . $style . '">' : $style;

            }
        }

    }

    protected function getScripts(){

        if($this->scripts){
            foreach ($this->scripts as $script) {

                echo !preg_match('/<\s*\/script\s*>/i', $script) ? '<script src="' . $script . '"></script>' : $script;

            }
        }

    }





    protected function recursiveArr($arr, $deep = 0, $parent_id = null, $row_id = 'id', $row_parent_id = 'parent_id', $recursiveName = '', $start = true){

        $res_arr = [];

        if(!is_array($arr))
            return $arr;

        reset($arr);

        if(is_array($deep) && isset($deep['from'])){

            $deep['from']++;

        }else{

            $deep = ['from' => 0, 'to' => $deep];

        }

        while(($key = key($arr)) !== null){

            if(is_int($parent_id) && !empty($arr[$key][$row_parent_id]) && is_numeric($arr[$key][$row_parent_id])){
                $arr[$key][$row_parent_id] = (int)$arr[$key][$row_parent_id];
            }

            if(!array_key_exists($row_parent_id, $arr[$key]) || $arr[$key][$row_parent_id] === $parent_id){

                if(empty($arr[$key]['recursive_name'])){

                    $name = $arr[$key]['name'] ?? $arr[$key][$row_id];

                    $arr[$key]['recursive_name'] = $recursiveName ? $recursiveName . '->' . $name : $name;

                }

                $arr[$key]['depth_level'] = $deep['from'] ?? $deep;

                $res_arr[$arr[$key][$row_id]] = $arr[$key];

                unset($arr[$key]);

                reset($arr);

                continue;

            }

            if(isset($res_arr[$arr[$key][$row_parent_id]])){

                $res = $this->recursiveArr($arr, $deep, $arr[$key][$row_parent_id], $row_id, $row_parent_id, $res_arr[$arr[$key][$row_parent_id]]['recursive_name'], false);

                if($res['res_arr']){

                    if($deep && is_array($deep) && !empty($deep['to']) && $deep['from'] >= $deep['to']){

                        foreach($res['res_arr'] as $item){

                            $res_arr[$item[$row_id]] = $item;

                        }

                    }else{

                        $res_arr[$arr[$key][$row_parent_id]]['sub'] = $res['res_arr'];

                    }

                }

                if(isset($res['arr'])){

                    $arr = $res['arr'];
                    reset($arr);
                    continue;

                }

            }

            next($arr);

        }

        if($start && $arr){

            foreach ($arr as $item){

                if(empty($res_arr[$item[$row_id]])){

                    $res_arr[$item[$row_id]] = $item;

                    $res_arr[$item[$row_id]]['old_' . $row_parent_id] = $res_arr[$item[$row_id]][$row_parent_id];

                    $res_arr[$item[$row_id]][$row_parent_id] = $parent_id;

                }

            }

        }

        return $start ? $res_arr : compact('res_arr', 'arr');

    }





    protected function getChildren($category, $table, $idRow = null, $checkVisible = false){

        $columns = $this->model->showColumns($table);

        !$idRow && $idRow = $columns['id_row'];

        $id = is_array($category) ? $category[$columns['id_row']] : $category;

        if(empty($columns['parent_id']))
            return $id;

        static $catalogDb = [];

        if(empty($catalogDb[$table])){

            $catalogDb[$table] = $this->model->get($table, [
                'where' => $checkVisible ? ['visible' => 1] : [],
                'order' => 'parent_id',
                'order_direction' => 'DESC'
            ]);
        }

        $categories = $this->recursiveArr($catalogDb[$table], 1, $id, $idRow);

        $ids = [];

        $ids[] = $id;

        if($categories){

            foreach($categories as $item){

                if(!array_key_exists('old_parent_id', $item) && $item['parent_id'] === $id){

                    $ids[] = $item[$columns['id_row']];

                    if(!empty($item['sub'])){

                        foreach ($item['sub'] as $subId => $value){

                            $ids[] = $subId;

                        }

                    }

                }

            }

        }

        return $ids;

    }





    protected function getParents($ids, $table, $parentRow = 'parent_id'){

        if(!$ids) return [];

        if(!is_array($ids)) $ids = (array)$ids;

        $model = !empty($this->model) ? $this->model : $this;

        $columns = $model->showColumns($table);

        if(empty($columns[$parentRow]))
            return $ids;

        $whereIds = $ids;

        while ($whereIds){

            $data = $model->get($table, [
                'fields' => [$parentRow],
                'where' => [$columns['id_row'] => $whereIds],
                'no_check_credentials' => true,
                'group' => $parentRow,
            ]);

            if(!$data){

                $whereIds = null;

                continue;

            }

            $whereIds = array_column($data, $parentRow);

            $ids = array_merge($ids, $whereIds);

        }

        return array_unique($ids);

    }




/*
 * Метод для поиска элемента массива (который сам может быть массивом) как в одномерном так и в многомерных массивах
 * $foreignArr - массив в котором ищем
 * $set - то что ищем. Если приходит значение, а не массив, то ищем элемент по его ключу. Анологично если в массиве будет только $set['search']
 * Чтобы искать по значению, которое содержит искомый элемент, нужно передать в $set['searchValue'] ключ содержащий этот элемент, а в $set['search'] его значение
 * Например, чтобы найти элемент в который вложен элемент  'alias' => 'kurtki' нужно передать в функцию  $set['search' => 'kurtki', 'searchValue' => 'alias']
 * $callback - функция которую можно использовать для корректировки поиска. Например, нужно получить элемент, который будет родительским по отношению к найденному
 * $el = $this->recursiveSearch($foreignArr, ['search'=>'tolstovki', 'searchValue'=>'alias'], function (&$el, &$set){

            if(!isset($el['parent_id']))
                return true;

            $set['searchValue'] = 'id';
            $set['search'] = $el['parent_id'];
            $el = false;

        });

 * $set['innerRow'] - элемент содержащий вложения в которые будем проваливаться и искать элементы уже там, по умолчанию 'sub'
 */

    protected function recursiveSearch($foreignArr, $set = [], $callback = null){

        // если $set не массив, то будем искать элемент с ключем равным $set
        if(!is_array($set)){

            $search = $set;

            $set = [];

            $set['search'] = $search;
        }

        if(empty($foreignArr) || !is_array($foreignArr) || !array_key_exists('search', $set)){
            return null;
        }

        $set['innerRow'] = $set['innerRow'] ?? 'sub';

        $set['searchValue'] = $set['searchValue'] ?? false;

        $element = [];

        // сбрасываем указатель массива на 1 элемент
        reset($foreignArr);

        // цикл перебора всех элементов массива пока указатель не выйдет за пределы массива.
        while(($key = key($foreignArr)) !== null){

            // если ищем по ключам и искомый элемент в самом верху, то сохраняем и если необходимо прогоняем через колбэк
            if(!$set['searchValue'] && array_key_exists($set['search'], $foreignArr)){

                $element = $foreignArr[$set['search']];

            }

            if(!$element){
                // ищем нужное значение у текущего элемента, если поиск осуществляется не по ключу
                if($set['searchValue']){

                    //проверяем, тот ли это элемент который ищем. Если да то сохраняем его
                    if(array_key_exists($set['searchValue'], $foreignArr[$key]) && $foreignArr[$key][$set['searchValue']] === $set['search']){

                        $element = $foreignArr[$key];
                    }

                    // ищем по ключу у вложенных элементов
                }elseif (isset($foreignArr[$key][$set['innerRow']][$set['search']])){

                    $element = $foreignArr[$key][$set['innerRow']][$set['search']];
                }
            }

            //если элемент найден то прогоняем через колбэк если он пришел или возращаем результат
            if($element){

                if($callback && is_callable($callback)){

                    if($callback($element, $set, $foreignArr)){

                        return $element;
                    }

                }else{

                    return $element;
                }

            }

            // если у тек.элемента есть вложенные элементы, то эти вложенные элементы добовляем в конец массива на первом уровне
            if(!empty($foreignArr[$key][$set['innerRow']])){

                $foreignArr += $foreignArr[$key][$set['innerRow']];

                unset($foreignArr[$key][$set['innerRow']]);
            }

            // $element === false можем установить в колбэке для корректировки поиска
            if($element !== false){

                next($foreignArr);

            }else{

                $element = [];
                reset($foreignArr);
            }

        }

        return $element;

    }





    protected function dateFormat($date){

        if(!$date) return null;

        static $dateArr = [];

        if(isset($dateArr[$date]))
            return $dateArr[$date];

        $daysArr = [
            'Sunday' => 'Воскресенье',
            'Monday' => 'Понедельник',
            'Tuesday' => 'Вторник',
            'Wednesday' => 'Среда',
            'Thursday' => 'Четверг',
            'Friday' => 'Пятница',
            'Saturday' => 'Суббота',
        ];

        $monthesArr = [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
        ];

        $dateData = new \DateTime($date);

        $dateArr[$date]['year'] = $dateData->format('Y');

        $dateArr[$date]['month'] = $monthesArr[$this->clearNum($dateData->format('m'))];

        $dateArr[$date]['monthFormat'] = preg_match('/т$/u', $dateArr[$date]['month']) ? $dateArr[$date]['month'] . 'а' : preg_replace('/ь$/u', 'я', $dateArr[$date]['month']);

        $dateArr[$date]['weekDay'] = $daysArr[$dateData->format('l')];

        $dateArr[$date]['day'] = $dateData->format('d');

        $dateArr[$date]['time'] = $dateData->format('H:i:s');

        $dateArr[$date]['format'] = mb_strtolower($dateArr[$date]['day'] . ' ' .
            $dateArr[$date]['monthFormat'] . ' ' .
            $dateArr[$date]['year'] . ' года');

        return $dateArr[$date];

    }




    protected function checkTizers($data = null){

        static $tizers = [];

        if($tizers && !$data){

            $tempTizers = $tizers;

            $tizers = [];

            return $tempTizers;

        }

        if($data){

            !is_array($data) && $data = json_decode($data, true);

            if($data){

                $noEmpty = false;

                foreach ($data as $value){

                    foreach ((array)$value as $item){

                        if(!empty($item)){

                            $noEmpty = true;

                            break;

                        }

                    }

                }

                if($noEmpty){

                    $tizers = $data;

                }

            }

        }


        return $tizers;

    }





    protected function writeLog($message, $file = 'log.txt', $event = 'Fault', $rotateLogs = true){

        $dateTime = new \DateTime();

        if($event !== 0) $str = $event . ': ' . $dateTime->format('d-m-Y G:i:s') . ' - ' . $message . "\r\n";
        else $str = $message . "\r\n";

        $dir = $_SERVER['DOCUMENT_ROOT'] . PATH . 'log';

        if(!is_dir($dir)){

            mkdir($dir, 0777);

        }

        $fileArr = preg_split('/\./', $file, 0, PREG_SPLIT_NO_EMPTY);

        if(!empty($fileArr[count($fileArr) - 2])){

            $fileArr[count($fileArr) - 2] .= '_' . $dateTime->format('Y_m_d');

            $file = implode('.', $fileArr);

        }

        if($rotateLogs){

            $this->rotateLogs($dir);

        }

        file_put_contents($dir . '/' . $file, $str, FILE_APPEND);

    }






    protected function rotateLogs($dir, $day = 30){

        $list = scandir($dir);

        if($list){

            foreach ($list as $file){

                if($file !== '.' && $file !== '..' && !is_dir($dir . '/' . $file) && !is_link($dir . '/' . $file)){

                    if((new \DateTime(date('Y-m-d', filemtime($dir . '/' . $file)))) < (new \DateTime())->modify('-' . $day . ' day')){

                        @unlink($dir . '/' . $file);

                    }

                }

            }

        }

    }





    protected function onlyRootParents(&$arr){

        if($arr){

            foreach ($arr as $key => $item){

                if(isset($item['parent_id']) && $item['parent_id']){

                    unset($arr[$key]);

                }

            }

        }

    }




    protected function addSessionData(){
        if($this->isPost()){
            foreach ($_POST as $key => $value){
                $_SESSION['res'][$key] = $value;
            }
            $this->redirect();
        }
    }




    protected function emptyFields($value, $answer){
        if(empty($value)){
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . ' ' .$answer . '</div>';
            $this->addSessionData();
        }
    }




    protected function getController(){

        return $this->controller ?:
            $this->controller = preg_split('/_?controller/', strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1_$2', (new \ReflectionClass($this))->getShortName())), 0, PREG_SPLIT_NO_EMPTY)[0];

    }



    protected function checkToken(){

        if(empty($_REQUEST['token']) || empty($_SESSION['token']) || $_REQUEST['token'] !== $_SESSION['token']){
            $this->redirect();
        }

        unset($_REQUEST['token'], $_GET['token'], $_POST['token']);
    }





    protected function pagination($pages, $template = ''){
        /*Поиск пораметра Page в адресной строке*/

        $str = $_SERVER['REQUEST_URI'];

        if(preg_match("/(page=\d+)/ui", $str)){
            $str = preg_replace("/(page=\d+)/", '', $str);
        }

        if(preg_match("/(\?&)|(\?amp;)/ui", $str)){
            $str = preg_replace("/(\?&)|(\?amp;)/", '?', $str);
        }

        /*Поиск параметра Page в адресной строке*/

        $basePageStr = $str;

        if(preg_match('/\?(.)?/iu', $str, $matches)){

            if(!preg_match('/&$/', $str) && !empty($matches[1])){

                $basePageStr = $str;
                $str .= '&';

            }else{

                $basePageStr = preg_replace('/(\?$)|(&$)/i', '', $str);

            }

        }else{

            $basePageStr = $str;
            $str .= '?';

        }

        $str .= 'page=';

        $firstPageStr = !empty($pages['first']) ? ($pages['first'] == 1 ? $basePageStr : $str . $pages['first']) :'';
        $backPageStr = !empty($pages['back']) ? ($pages['back'] == 1 ? $basePageStr : $str . $pages['back']) : '';

        $template = $this->render(($template ?: TEMPLATE . 'include/pagination'));

        if($template){

            $templatesArr = ['first', 'back', 'previous', 'current', 'next', 'forward', 'last'];

            foreach ($templatesArr as $key => $element){

                $regExp = '/<\!\-\-' . $element . '\-\->(.+?)<\!\-\-' . $element . '\-\->/is';

                if(!empty($pages[$element]) && preg_match($regExp, $template, $matches)){

                    if(!empty($matches[1])){

                        $regExpLink = '/<a\s+[^>]*>(.+?)<\/a>/is';

                        if(preg_match($regExpLink, $matches[1], $links)){

                            $pages[$element] = (array)$pages[$element];

                            foreach ($pages[$element] as $value){

                                $href = '';

                                switch ($element){

                                    case 'first':
                                        $href = $firstPageStr;
                                        break;

                                    case 'back':
                                        $href = $backPageStr;
                                        break;

                                    case 'previous':
                                        $href = $value == 1 ? $basePageStr : $str . $value;
                                        break;

                                    case 'current':
                                        $href = '';
                                        break;

                                    default:

                                        $href = $str . $value;

                                }

                                if(preg_match('/href\s*=\s*[\'"](.*?)\1/', $links[0])){

                                    $link = preg_replace('/href\s*=\s*([\'"])(.*?)\1/', 'href=$1'. $href .'$1', $links[0]);

                                }else{

                                    $link = preg_replace('/<a\s/', '<a href="'. $href .'" ', $links[0]);

                                }

                                if($key > 1 && $key < 5){

                                    $link = preg_replace('/>.*?</is', '>' . $value . '<', $link);

                                }

                                $content = str_replace($links[0], $link, $matches[1]);

                                echo $content;


                            }

                        }

                    }

                }

            }


        }

    }

}
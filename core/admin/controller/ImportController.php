<?php


namespace core\admin\controller;


use libraries\FileEdit;
use libraries\MP3File;
use libraries\TextModify;

class ImportController extends BaseAdmin
{

    protected $redirect = true;
    protected $artistTable = 'artist';
    protected $trackTable = 'track';
    protected $styleTable = 'style';


    protected function inputData()
    {

        set_time_limit(0);

        if (empty($_FILES['import']['name'][0])){

            $_SESSION['res']['answer'] = '<div class="error">Нет загруженных файлов</div>';

            if ($this->redirect) {
                $this->redirect();
            }
        }

        parent::inputData();

        if (is_array($_FILES['import']['name'])){

            $artist = null;
            $checkArtist = null;
            $checkStyle = null;
            $flagArt = false;
            $flagStyle = false;

            //проверяем заданно ли вручную имя исполнителя
            if (!empty($_POST['artist'][1]) && $_POST['artist'][1] === 'artist' && !empty($_POST['artist'][0])) {

                $flagArt = true;
                $artist = mb_convert_case($this->clearStr($_POST['artist'][0]), MB_CASE_TITLE);
                $checkArtist = $this->checkAndAdd($artist, $this->artistTable);

                if (!$checkArtist){
                    return null;
                }

            }

            // //проверяем заданно ли вручную стиль
            if (!empty($_POST['style'][1]) && $_POST['style'][1] === 'style' && !empty($_POST['style'][0])) {

                $flagStyle = true;
                $style = mb_convert_case($this->clearStr($_POST['style'][0]), MB_CASE_TITLE);
                $checkStyle = $this->checkAndAdd($style, $this->styleTable);

                if (!$checkStyle){
                    return null;
                }

            }

            //добавляем файлы в хранилище, если они пришли в $_FilES
            $fileEdit = new FileEdit;
            //$this->fileArray = $fileEdit->addFile($this->trackTable.'/' . !empty($artist) ? (new TextModify())->translit($artist) : '');

            $fields = [];
            $i = -1;

            require_once $_SERVER['DOCUMENT_ROOT'] . PATH . 'libraries/getID3-1.9.21/getid3/getid3.php';
            $getID3 = new \getID3();

            // разбираем имена файлов
            foreach ($_FILES['import']['name'] as $item){
                $i++;

                //$FileInfo = $getID3->analyze($_SERVER['DOCUMENT_ROOT'] . '/' . UPLOAD_DIR . $this->fileArray['import'][$i]);
                $FileInfo = $getID3->analyze($_FILES['import']['tmp_name'][$i]);

                if ($FileInfo){

                    $name = !empty($FileInfo['id3v2']['comments']['title'][0]) ?  $FileInfo['id3v2']['comments']['title'][0] :
                        (!empty($FileInfo['id3v1']['title']) ? $FileInfo['id3v1']['title'] : null);

                    $name = $this->conversionCodStr($name);

                    $fields[$i]['name'] =  $name;

                    if (!$checkArtist){

                        $artist = !empty($FileInfo['id3v2']['comments']['artist'][0]) ?  mb_convert_case($FileInfo['id3v2']['comments']['artist'][0], MB_CASE_TITLE) :
                            (!empty($FileInfo['id3v1']['artist']) ? mb_convert_case($FileInfo['id3v1']['artist'], MB_CASE_TITLE) : null);

                        $artist = $this->conversionCodStr($artist);

                        if (isset($artist)){
                            $checkArtist = $this->checkAndAdd($artist, $this->artistTable);

                            if (!$checkArtist){
                                return null;
                            }
                        }
                    }

                    $album = !empty($FileInfo['id3v2']['comments']['album'][0]) ?  $FileInfo['id3v2']['comments']['album'][0] :
                        (!empty($FileInfo['id3v1']['album']) ? $FileInfo['id3v1']['album'] : null);

                    $album = $this->conversionCodStr($album);

                    $fields[$i]['album'] = $album;

                    //$fields[$i]['year'] = !empty($FileInfo['id3v2']['comments']['year'][0]) ?  $FileInfo['id3v2']['comments']['year'][0] :
                     //   (!empty($FileInfo['id3v1']['year']) ? $FileInfo['id3v1']['year'] : null);

                    $fields[$i]['alias'] = !empty($fields[$i]['name']) ? (new TextModify())->translit($fields[$i]['name']) : null;

                    if (!$checkStyle){

                        $style = !empty($FileInfo['id3v2']['comments']['genre'][0]) ?  $FileInfo['id3v2']['comments']['genre'][0] :
                            (!empty($FileInfo['id3v1']['genre']) ? $FileInfo['id3v1']['genre'] : null);

                        if (isset($style)){
                            $checkStyle = $this->checkAndAdd($style, $this->styleTable);

                            if (!$checkStyle){
                                return null;
                            }
                        }
                    }

                    $fields[$i]['duration'] = !empty($FileInfo['playtime_seconds']) ? round($FileInfo['playtime_seconds'])  : null;

                }

                if (empty($fields[$i]['name']) || !$checkArtist){

                    //отделяем расширение файла от его имени
                    $fileNameArr = explode('.', $item);

                    //разрегистрирование элемента массива с расширением файла
                    unset($fileNameArr[count($fileNameArr) - 1]);

                    // сборка имени файла без расширения
                    $fileName = implode('.', $fileNameArr);

                    // отделяем исполнителя от название трека
                    $arr = preg_split('/-/', $fileName, 2);

                    if (count($arr) === 2){

                        $arr['artist'] =  preg_replace('/(^[_\s]+)|([_\s]+$)/', '', $arr[0]);
                        $arr['track'] =  preg_replace('/(^[_\s]+)|([_\s]+$)/', '', $arr[1]);

                    }else{
                        $arr['track'] =  preg_replace('/(^[_\s]+)|([_\s]+$)/', '', $arr[0]);
                    }

                    if (!$fields[$i]['name']){

                        $fields[$i]['name'] = mb_convert_case($arr['track'], MB_CASE_TITLE);
                        $fields[$i]['alias'] = (new TextModify())->translit($arr['track']);

                    }

                }

                if (empty($fields[$i]['duration'])){
                    $fields[$i]['duration'] = (new MP3File($_FILES['import']['tmp_name'][$i]))->getDuration();
                }

                if (!$checkArtist && !empty($arr['artist'])){

                    $arr['artist'] = $artist = mb_convert_case($this->clearStr($arr['artist']), MB_CASE_TITLE);

                    $checkArtist = $this->checkAndAdd($arr['artist'], $this->artistTable);

                    if (!$checkArtist){
                        return null;
                    }

                }

                //добавляем файлы в хранилище, если они пришли в $_FilES
                $this->fileArray['import'][] = $fileEdit->addOneFile('import', $i ,$this->trackTable.'/' . (!empty($artist) ? (new TextModify())->translit($artist) : ''));

                $fields[$i]['link'] = $this->fileArray['import'][$i];
                $fields[$i]['style_id'] = $checkStyle;
                $fields[$i]['parent_id'] = $checkArtist;

                if (!$flagArt){
                    $checkArtist = null;
                }

                if (!$flagStyle){
                    $checkStyle = null;
                }

            }

            $res = $this->model->add($this->trackTable, [
                'fields' => $fields,
            ]);

            if (!$res){
                $_SESSION['res']['answer'] = '<div class="error">Ошибка при добавлении в БД</div>';

            }else{
                $_SESSION['res']['answer'] = '<div class="success">Данные успешно добавлены</div>';
            }

        }

        if ($this->redirect) {
            $this->redirect();
        }

        return $_SESSION['res']['answer'] ?? null;

    }




    private function checkAndAdd($param, $table){

        $check = $this->model->get($table, [
            'where' => ['name' => $param, 'alias' => $param],
            'condition' => ['OR'],
            'limit' => 1,
            'single' => true
        ]);

        if ($check){
            $check = $check[$this->model->showColumns($table)['id_row']];

        }else{

            $alias = (new TextModify())->translit($param ?? '');

            $check = $this->model->add($table, [
                'fields' => ['name' => $param, 'alias' => $alias],
                'return_id' => true
            ]);

            if (!$check){

                $_SESSION['res']['answer'] = '<div class="error">Ошибка</div>';

                if ($this->redirect) {
                    $this->redirect();
                }

                return null;
            }

        }

        return $check;

    }



    private function conversionCodStr(?string $str) : ?string{

        for ($i=0; $i < strlen($str); $i++){

            if (ord($str[$i]) > 127){
                $str = iconv('utf-8', 'iso-8859-15', $str);
                $str = iconv('cp1251', 'utf-8', $str);
                break;
            }
        }

        return $str;

    }


}





















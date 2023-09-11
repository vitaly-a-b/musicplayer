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

        if (empty($_FILES['import']['name'][0])){

            $_SESSION['res']['answer'] = '<div class="error">Нет загруженных файлов</div>';

            if ($this->redirect) {
                $this->redirect();
            }
        }

        parent::inputData();

        if (is_array($_FILES['import']['name'])){

            $artist = null;
            $style = null;
            $checkArtist = null;
            $checkStyle = null;

            //проверяем заданно ли вручную имя исполнителя
            if (!empty($_POST['artist'][1]) && $_POST['artist'][1] === 'artist') {

                $artist = mb_convert_case($this->clearStr($_POST['artist'][0]), MB_CASE_TITLE);

                $checkArtist = $this->model->get($this->artistTable, [
                    'where' => ['name' => $artist, 'alias' => $artist],
                    'condition' => ['OR'],
                    'limit' => 1,
                    'single' => true
                ]);

                if ($checkArtist){
                    $checkArtist = $checkArtist[$this->model->showColumns($this->artistTable)['id_row']];

                }else{

                    $aliasArtist = (new TextModify())->translit($artist);

                    $checkArtist = $this->model->add($this->artistTable, [
                        'fields' => ['name' => $artist, 'alias' => $aliasArtist],
                        'return_id' => true
                    ]);

                    if (!$checkArtist){

                        $_SESSION['res']['answer'] = '<div class="error">Ошибка</div>';

                        if ($this->redirect) {
                            $this->redirect();
                        }

                        return null;
                    }

                }

            }

            // //проверяем заданно ли вручную стиль
            if (!empty($_POST['style'][1]) && $_POST['style'][1] === 'style') {
                $style = mb_convert_case($this->clearStr($_POST['style'][0]), MB_CASE_TITLE);

                $checkStyle = $this->model->get($this->styleTable, [
                    'where' => ['name' => $style, 'alias' => $style],
                    'condition' => ['OR'],
                    'limit' => 1,
                    'single' => true
                ]);

                if ($checkStyle){
                    $checkStyle = $checkStyle[$this->model->showColumns($this->styleTable)['id_row']];

                }else{

                    $aliasStyle = (new TextModify())->translit($style);

                    $checkStyle = $this->model->add($this->styleTable, [
                        'fields' => ['name' => $style, 'alias' => $aliasStyle],
                        'return_id' => true
                    ]);

                    if (!$checkStyle){

                        $_SESSION['res']['answer'] = '<div class="error">Ошибка</div>';

                        if ($this->redirect) {
                            $this->redirect();
                        }

                        return null;
                    }

                }

            }

            //добавляем файлы в хранилище, если они пришли в $_FilES
            $fileEdit = new FileEdit;
            $this->fileArray = $fileEdit->addFile($this->trackTable.'/' .(!empty($aliasArtist) ? $aliasArtist : (!empty($artist) ?  (new TextModify())->translit($artist) : '')));

            $fields = [];
            $i = -1;

            // разбираем имена файлов
            foreach ($_FILES['import']['name'] as $item){
                $i++;

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

                $fields[$i]['name'] = mb_convert_case($arr['track'], MB_CASE_TITLE);
                $fields[$i]['alias'] = (new TextModify())->translit($arr['track']);
                $fields[$i]['link'] = $this->fileArray['import'][$i];
                $fields[$i]['style_id'] = $checkStyle;
                $fields[$i]['duration'] = (new MP3File($_SERVER['DOCUMENT_ROOT'] . '/' . UPLOAD_DIR . $this->fileArray['import'][$i]))->getDuration();

                if ($checkArtist){
                    $fields[$i]['parent_id'] = $checkArtist;

                }else{

                    if (!empty($arr['artist'])){

                        $arr['artist'] = mb_convert_case($this->clearStr($arr['artist']), MB_CASE_TITLE);

                        $checkA = $this->model->get($this->artistTable, [
                            'where' => ['name' => $arr['artist'], 'alias' => $arr['artist']],
                            'condition' => ['OR'],
                            'limit' => 1,
                            'single' => true
                        ]);

                        if ($checkA){
                            $checkA = $checkA[$this->model->showColumns($this->artistTable)['id_row']];

                        }else{

                            $aliasArtist = (new TextModify())->translit($arr['artist']);

                            $checkA = $this->model->add($this->artistTable, [
                                'fields' => ['name' => $arr['artist'], 'alias' => $aliasArtist],
                                'return_id' => true
                            ]);

                            if (!$checkA){

                                $_SESSION['res']['answer'] = '<div class="error">Ошибка на шаге ' . $i .'</div>';

                                if ($this->redirect) {
                                    $this->redirect();
                                }

                                return null;
                            }
                        }

                        $fields[$i]['parent_id'] = $checkA;

                    }else{
                        $fields[$i]['parent_id'] = null;

                    }

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


}





















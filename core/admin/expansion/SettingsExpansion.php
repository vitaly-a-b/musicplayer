<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 07.03.2019
 * Time: 13:13
 */

namespace core\admin\expansion;


use core\base\controller\BaseMethods;
use core\base\controller\Singleton;
use core\base\helpers\CacheHelper;

class SettingsExpansion
{

    use Singleton;
    use BaseMethods;
    use CacheHelper;

    public function expansion(){
        $no_add = true;
        $no_delete = true;

        // очистка кеша
        if($this->isPost()){

            if (!empty($_POST['clear_cache'])){

                $this->clearCache();

                $this->model->edit('settings', [
                    'fields' => ['clear_cache' => 0],
                    'where' => ['>=id' => 1]
                ]);
            }
        }

        /*$this->translate['img'] = ['Логотип компании'];
        $this->translate['gallery_img'] = ['Логотипы торговых марок'];
        $this->translate['background_img'] = ['Изображение заднего фона по умолчанию'];*/

        return compact('no_add', 'no_delete');
    }

}
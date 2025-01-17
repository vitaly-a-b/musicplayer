<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 19.02.2019
 * Time: 17:37
 */

namespace core\admin\controller;

use core\admin\helpers\ForegnHelper;
use core\admin\helpers\PrepareEditHelper;
use core\admin\helpers\PrepareFilesHelper;
use core\admin\helpers\PrepareShowHelper;
use core\admin\helpers\StartProjectHelper;
use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\exceptions\DbException;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;


abstract class BaseAdmin extends BaseController
{

    use ForegnHelper;

    use PrepareEditHelper;

    use PrepareFilesHelper;

    use PrepareShowHelper;

    use StartProjectHelper;

    protected $model;

    protected $table;
    protected $columns;

    protected $alias;
    protected $fileArray;

    protected $adminPath;
    protected $multiLanguage;

    protected $menu;

    protected $title;
    protected $messages;

    protected $settings;
    protected $translate;
    protected $templateArr;
    protected $defaultTemplatePath;
    protected $blocks = [];
    protected $blockNeedle = [];
    protected $buttons;

    protected $countElements = 50;
    protected $linksCounter = 4;




    protected function inputData(){

        if(!$this->model)
            $this->model = Model::instance();

        $this->checkAuth(true);

        !$this->userData && $this->redirect(PATH);

        $this->init(true);

        $this->title = 'VG engine';

        define('QTY', _QTY);

        if(!$this->menu)
            $this->menu = Settings::get('projectTables');

        if(!$this->table){
            if($this->parameters)
                $this->table = array_keys($this->parameters)[0];
            elseif ($this->isPost() && !empty($_POST['table']))
                $this->table = $_POST['table'];
            else
                $this->table = Settings::get('defaultTable');

        }

        if(!in_array($this->table, $this->model->showTables())){

            throw new RouteException('Попытка доступа к несуществующей таблице - ' . $this->table);

        }

        if($this->table === 'metadata')
            $this->model->checkMetaDataTable();

        $this->checkUserCredentials();

        if(method_exists($this, 'checkExistingProjectTables')){

            $this->checkExistingProjectTables();

        }

        if(method_exists($this, 'setTranslateScripts')){

            $this->setTranslateScripts();

        }

        if(!$this->adminPath) $this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';
        if(!$this->templateArr) $this->templateArr = Settings::get('templateArr');
        if(!$this->translate) $this->translate = Settings::get('translate');
        if(!$this->defaultTemplatePath) $this->defaultTemplatePath = Settings::get('defaultTemplatePath');
        if(!$this->multiLanguage) $this->multiLanguage = Settings::get('multiLanguage');
        if($this->multiLanguage) $this->blockNeedle = Settings::get('blockNeedle');

        if(!$this->messages) $this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') . 'informationMessages.php';

        $this->sendNoCacheHeaders();

    }




    protected function outputData(){

        $args = func_get_arg(0);
        $vars = $args ?? [];

        $this->buttons = $this->render(ADMIN_TEMPLATE . 'include/buttons_' . ($this->getController() === 'add' || $this->getController() === 'edit' ? 'add' : 'show'), $vars);

        if(!$this->content){

            $this->content = $this->render($this->template, $vars);
        }

        $this->header = $this->render(ADMIN_TEMPLATE . 'include/header');
        $this->footer = $this->render(ADMIN_TEMPLATE . 'include/footer');

        return $this->render(ADMIN_TEMPLATE . 'layout/default');
    }




    protected function checkUserCredentials(){

        $arr = ['add', 'show', 'edit', 'delete'];

        if($this->model->get('users', ['fields' => ['COUNT(*) as count'], 'no_concat' => true, 'no_check_credentials' => true])[0]['count'] > 0){

            $baseUser = $this->model->get('users', [
                'fields' => ['id'],
                'limit' => 1,
                'single' => true,
                'order' => 'id',
                'no_check_credentials' => true
            ]);

            if($baseUser['id'] === $this->userData['id']){

                $this->userData['ROOT'] = $this->model->userData['ROOT'] = true;

                return;

            }

            $this->userData['credentials'] = $this->userData['credentials'] ? json_decode($this->userData['credentials'], true) : [];

            $this->model->userData['credentials'] = $this->userData['credentials'];

            $crud = $this->getController();

            if(in_array($crud, $arr)){

                $blocking = false;

                if($crud === 'edit'){

                    if(($this->isPost() && !isset($this->userData['credentials'][$this->table]['edit'])) ||
                        !isset($this->userData['credentials'][$this->table]['show'])){

                        $blocking = true;

                    }else{

                        $blocking = $this->checkDataCreators();

                    }

                }elseif($crud === 'delete'){

                    $blocking = $this->checkDataCreators('delete');

                }elseif(!isset($this->userData['credentials'][$this->table][$crud])){

                    $blocking = true;

                    if($crud === 'show' && isset($this->userData['credentials'][$this->table]['add'])){

                        $blocking = false;

                    }

                }

                if($blocking){

                    if(!empty($this->userData['credentials']) && !$this->isPost() && !$this->parameters){

                        foreach ($this->userData['credentials'] as $table => $item){

                            if(!empty($item['show'])){

                                $this->redirect(PATH . Settings::get('routes')['admin']['alias'] . '/show/' . $table);

                            }

                        }

                    }

                    throw new RouteException('Попытка доступа к запрещенному для пользователя '
                        . $this->userData['name'] . ' ресурсу ' . $crud . ' таблицы ' . $this->table, 3);

                }


            }


            foreach ($this->menu as $key => $item){

                if(!isset($this->userData['credentials'][$key]['show']) && !isset($this->userData['credentials'][$key]['add'])){

                    unset($this->menu[$key]);

                }

            }

        }

    }





    protected function checkDataCreators($crud = 'edit'){

        if($crud === 'delete' && !empty($this->parameters) && count($this->parameters) > 1){

            $crud = 'edit';

        }

        if($crud === 'delete' && empty($this->userData['credentials'][$this->table][$crud])){

            return true;

        }

        if(!empty($this->userData['credentials'][$this->table][$crud]['properties']) &&
            in_array('data_creators', $this->model->showTables())){

            $id_row = $this->model->showColumns($this->table)['id_row'];

            if(!empty($_POST[$id_row]) || !empty($this->parameters[$this->table])){

                $id = !empty($_POST[$id_row]) ? $_POST[$id_row] : $this->parameters[$this->table];

                $res = $this->model->get('data_creators', [
                    'where' => ['creator_id' => $this->userData['id'], 'table' => $this->table, 'data_id' => $id],
                    'limit' => 1,
                    'no_check_credentials' => true
                ]);

                if(!$res){

                    if($crud === 'edit' && !$this->isPost() &&
                        !empty($this->userData['credentials'][$this->table]['show']) &&
                        empty($this->userData['credentials'][$this->table]['show']['properties'])){

                        return false;

                    }

                    if($this->isPost() || $crud === 'delete' || $this->model->get('data_creators', [
                        'where' => ['table' => $this->table, 'data_id' => $id],
                        'limit' => 1,
                        'no_check_credentials' => true
                    ])){

                        return true;

                    }

                }

            }

        }

        return false;

    }




    protected function showButtons($type = false){

        if(!empty($this->userData['ROOT'])){

            return true;

        }

        !$type && $type = $this->getController();

        static $dataCreators = null;

        if(!empty($this->userData['credentials'][$this->table][$type]['properties']) && in_array('data_creators', $this->model->showTables())){

            return !$this->data && $type === 'edit' ? !empty($this->userData['credentials'][$this->table]['add']) : ($dataCreators !== null ? $dataCreators : $dataCreators = $this->model->get('data_creators', [
                'fields' => ['data_id'],
                'where' => ['creator_id' => $this->userData['id'], 'table' => $this->table, 'data_id' => $this->data[$this->columns['id_row']]],
                'limit' => 1,
                'no_check_credentials' => true
            ]));

        }

        return !empty($this->userData['credentials'][$this->table][$type]);

    }




    protected function sendNoCacheHeaders(){
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        header("Cache-Control: post-check=0,pre-check=0");
    }



    protected function execBase(){
        self::inputData();
    }



    protected function expansion($args = [], $settings = false){

        $filename = explode('_', $this->table);
        $className = '';

        foreach($filename as $item) $className .= ucfirst($item);

        if(!$settings){
            $path = Settings::get('expansion');
        }elseif (is_object($settings)){
            $path = $settings::get('expansion');
        }else{
            $path = $settings;
        }

        $class = $path . $className . 'Expansion';

        if(is_readable($_SERVER['DOCUMENT_ROOT'] . PATH. $class . '.php')){

            $class = str_replace('/', '\\', $class);

            $exp = $class::instance();

            foreach($this as $name => $value){
                $exp->$name = &$this->$name;
            }

            return $exp->expansion($args, $this);

        }else{

            $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

            extract($args);

            if(is_readable($file)) return include $file;

        }

        return false;
    }





    protected function editData($returnId = false){

        $id = false;
        $method = 'add';

        $redirectPath = isset($_POST['add_new_element']) ? PATH . \core\base\settings\Settings::get('routes')['admin']['alias'] . '/add/' . $this->table : '';

        unset($_POST['add_new_element']);

        if(!empty($_POST['return_id'])) $returnId = true;

        $where = [];

        if(isset($_POST[$this->columns['id_row']]) && $_POST[$this->columns['id_row']]){
            $id = is_numeric($_POST[$this->columns['id_row']]) ?
                $this->clearNum($_POST[$this->columns['id_row']]) :
                $this->clearStr($_POST[$this->columns['id_row']]);

            if($id){

                $where = [$this->columns['id_row'] => $id];
                $method = 'edit';

            }
        }elseif (!empty(Settings::get('unique')[$this->table])){

            foreach (Settings::get('unique')[$this->table] as $name){

                if(!empty($_POST[$name])){

                    $where[$name] = $_POST[$name];

                }

            }

            if($where){

                $res = $this->model->get($this->table, [
                    'fields' => [$this->columns['id_row']],
                    'where' => $where,
                    'limit' => 1,
                    'single' => true
                ]);

                if($res){

                    $_POST[$this->columns['id_row']] = $id = $res[$this->columns['id_row']];

                    $where = [$this->columns['id_row'] => $id];
                    $method = 'edit';

                }

            }

        }

        foreach ($this->columns as $key => $item){

            if(is_array($item) &&
                ($item['Type'] === 'date' || $item['Type'] === 'datetime') &&
                (!isset($_POST[$key]) || !$_POST[$key])){

                $_POST[$key] = 'NOW()';

            }

        }

        $this->createFiles($id);

        $this->createAlias($id);

        $except = $this->checkExceptFields();

        $oldData = [];

        try {

            $id && $oldData = $this->model->get($this->table, [
                'where' => $where,
                'single' => true
            ]);

            $res_id = $this->model->$method($this->table, [
                'files' => $this->fileArray,
                'where' => $where,
                'return_id' => true,
                'except' => $except
            ]);

            if(!empty($oldData) && method_exists($this, 'checkGroupEdit')){

                $this->checkGroupEdit($oldData);

            }

        }catch (DbException $e){

            $_SESSION['res']['answer'] = '<div class="error">'. explode("\r\n", $e->getMessage())[1] . '</div>';
            $this->addSessionData();

        }


        if(!$id){
            $id = $_POST[$this->columns['id_row']] = $res_id;
            $answerSuccess = $this->messages['addSuccess'];
            $answerFail = $this->messages['addFail'];
        }else{
            $answerSuccess = $this->messages['editSuccess'];
            $answerFail = $this->messages['editFail'];
        }

        $this->updateMenuPosition($id, $oldData);

        $result = $this->checkAlias($_POST[$this->columns['id_row']]);

        if(method_exists($this, 'checkJsModifiedFiles')){

            $this->checkJsModifiedFiles();

        }

        if(!$res_id){

            $_SESSION['res']['answer'] = '<div class="error">'. $answerFail . '</div>';
            $this->redirect();

        }

        $_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>';

        $this->checkManyToMany();

        $this->expansion(get_defined_vars());

        if($returnId){

            return $_POST[$this->columns['id_row']];

        }

        !$redirectPath && $redirectPath = PATH . \core\base\settings\Settings::get('routes')['admin']['alias'] . '/edit/' . $this->table . '/' . $_POST[$this->columns['id_row']];

        $this->redirect($redirectPath);

        return null;

    }





    protected function checkExceptFields($arr = []){

        $except = [];

        if(!empty($this->columns['id_row'])) return $except[] = $this->columns['id_row'];

        return $except;

    }





    protected function recursiveOutput($data, $parent_id = null, $recursive_mode = false){

        foreach ($data as $item){

            if(!$parent_id){

                if(empty($item['sub'])){

                    echo '<a href="' . (!empty($item['alias']) ? $item['alias'] : $this->adminPath . 'edit/' . $this->table . '/' . $item['id']) . '" class="wq-goods__item">
                                    <div class="wq-goods__item-btn">
                                        <div class="wq-goods__item-text">' . $item['name'] . '</div>
                                    </div>';

                }else{

                    echo '<div class="wq-goods__item">
                                    <div class="wq-goods__item-btn">
                                        <a href="' . (!empty($item['alias']) ? $item['alias'] : $this->adminPath . 'edit/' . $this->table . '/' . $item['id']) . '" class="wq-goods__item-text">
                                            ' . $item['name'] . '
                                        </a>
                                        <div class="wq-goods__item-arrow">
                                            <div class="wq-goods__arrow _ibg">
                                                <picture><source srcset="' . PATH . ADMIN_TEMPLATE . 'img/icons/icon-arrow-goods.webp" type="image/webp">
                                                <img src="' . PATH . ADMIN_TEMPLATE . 'img/icons/icon-arrow-goods.png" alt="icon">
                                                </picture>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="wq-goods__item-inner">';



                    $this->recursiveOutput($item['sub'], $item['id']);

                }

            }else{

                if(!$recursive_mode){

                    echo '<div class="wq-goods__item-category wq-goods__item-category_flex wq-goods-category">
                            <div class="wq-goods-category__inner">';

                }

                echo '<a href="' . (!empty($item['alias']) ? $item['alias'] : $this->adminPath . 'edit/' . $this->table . '/' . $item['id']) . '" 
                        class="' . (!empty($item['sub']) ? 'wq-goods-category__title' : 'wq-goods__item-text') . '">
                                            ' . $item['name'] . '
                                        </a>';

                if(!empty($item['sub'])){

                    echo '<div class="wq-goods-category__sub-inner">
                            <ul class="wq-goods-category__list">
                                <li class="wq-goods-category__item">';

                    $this->recursiveOutput($item['sub'], $item['id'], true);

                    echo '</li></ul></div>';

                }

                if(!$recursive_mode){

                    echo '</div></div>';

                }

            }

            if(!$parent_id){

                if(empty($item['sub'])){

                    echo '</a>';

                }else{

                    echo '</div></div>';

                }

            }

        }

    }

}
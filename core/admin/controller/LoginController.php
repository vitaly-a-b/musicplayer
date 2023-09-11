<?php

namespace core\admin\controller;

use core\base\controller\BaseController;
use core\base\model\UserModel;
use core\base\settings\Settings;


class LoginController extends BaseController {

    protected $model;
    protected $redirect = true;
    protected $checkToken = true;


    protected function inputData(){

        if(isset($this->parameters['logout'])){

            $this->checkAuth(true);

            $user_log = 'Выход пользователя - ' . $this->userData['name'];

            $this->writeLog($user_log, 'log_user.txt', 'LogoutUser');

            UserModel::instance()->logout();

            $this->redirect(PATH);

        }

        $this->model = UserModel::instance();

        $this->model->setAdmin();

        if($this->isPost()){

            if ($this->checkToken){

                if(empty($_POST['token']) || $_POST['token'] !== $_SESSION['token']){

                    exit('Куку охибка!!!');

                }

            }


            $ip_user = filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP) ?:
                (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP) ?: @$_SERVER['REMOTE_ADDR']);

            $time_clean = new \DateTime();

            $time_clean->modify("-" . BLOCK_TIME . " hour");

            $this->model->delete($this->model->getBlockedTable(), [
                'where' => ['<time' => $time_clean->format("Y-m-d H:i:s")],
            ]);

            $trying = $this->model->get($this->model->getBlockedTable(), [
                'fields' => ['trying'],
                'where' => ['ip' => $ip_user],
                'limit' => '1',
                'single' => true
            ]);

            $trying = !empty($trying) ? $this->clearNum($trying['trying']) : 0;

            $success = 0;

            if($_POST['login'] && $_POST['password'] && $trying < 3){

                $login = $this->clearStr($_POST['login']);

                $password = md5($this->clearStr($_POST['password']));

                $userData = $this->model->get($this->model->getAdminTable(), [
                    'fields' => ['id', 'name'],
                    'where' => [
                        'login' => $login,
                        'password' => $password,
                    ],
                    'limit' => 1,
                    'single' => true
                ]);

                if(!$userData){

                    $method = 'add';

                    $where = [];

                    if($trying){

                        $method = 'edit';

                        $where['ip'] = $ip_user;

                    }

                    $this->model->$method($this->model->getBlockedTable(), [
                        'fields' => ['login' => $login, 'ip' => $ip_user, 'time' => 'NOW()', 'trying' => ++$trying],
                        'where' => $where
                    ]);

                    $error = "Некорректная попытка входа пользователя - ip адрес - ". $ip_user .
                        "\r\nЛогин - " . $_POST['login'];

                }else{

                    if(!$this->model->checkUser($userData['id'])){

                        $error = $this->model->getLastError();

                    }else{

                        $error = 'Вход пользователя - '.$login;

                        $success = 1;

                    }

                }

            }elseif($trying >= 3){

                $this->model->logout();

                $error = "Превышено максимальное количество попыток ввода пароля - " . $ip_user;

            }else{

                $error = "Заполните обязательные поля";

            }

            $_SESSION['res']['answer'] = $success ? '<div class="success">Добро пожаловать ' . $userData['name'] . '</div>' :
                '<div class="error">' . (preg_split('/\s*\-/', $error, 2, PREG_SPLIT_NO_EMPTY)[0]) . '</div>';

            $this->writeLog($error, 'log_user.txt', 'AccessUser');

            $path = null;

            $success && $path = PATH . Settings::get('routes')['admin']['alias'];

            if ($this->redirect){
                $this->redirect($path);
            }

            return $success;

        }

    }



    protected function outputData(){

        $this->init(true);

        return $this->render('', [
            'adminPath' => Settings::get('routes')['admin']['alias']
        ]);

    }


    // авто идентификация
    public function APIAuth(){

        // не проверяем чектокен и не редиректим
        $this->redirect = false;
        $this->checkToken = false;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST['login'] = $_SERVER['PHP_AUTH_USER'];
        $_POST['password'] = $_SERVER['PHP_AUTH_PW'];

        // запускаем входной метод для аутентификации
        return $this->inputData();

    }


}


























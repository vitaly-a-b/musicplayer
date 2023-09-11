<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 08.09.2019
 * Time: 15:23
 */

namespace core\base\controller;


use core\base\settings\Settings;

class BaseAjax extends BaseController
{

    public function route(){

        $route = Settings::get('routes');

        $controller = $route['user']['path'] . 'AjaxController';

        $data = $this->isPost() ? $_POST : $_GET;

        if(!empty($data['ajax']) && $data['ajax'] === 'token'){

            return $this->generateToken();

        }

        $httpReferer = str_replace('/', '\/', ($_SERVER['REQUEST_SCHEME'] ?? 'http') . 's?://' . $_SERVER['SERVER_NAME'] . PATH . $route['admin']['alias']);

        if(isset($data['ADMIN_MODE']) || preg_match('/^' . $httpReferer . '(\/?|$)/', $_SERVER['HTTP_REFERER'])){

            unset($data['ADMIN_MODE']);

            $controller = $route['admin']['path'] . 'AjaxController';

        }

        $controller = str_replace('/', '\\', $controller);

        $ajax = new $controller;

        $ajax->ajaxData = $data;

        $res = $ajax->ajax();

        if(is_array($res) || is_object($res))
            $res = json_encode($res, JSON_UNESCAPED_UNICODE);

        elseif (is_int($res))
            $res = (float)$res;

        return $res;

    }

    private function generateToken(){

        return $_SESSION['token'] = md5(mt_rand(0, 999999) . microtime());

    }


}
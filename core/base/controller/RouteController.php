<?php

namespace core\base\controller;

use core\base\exceptions\RouteException;
use core\base\settings\Settings;

class RouteController extends BaseController
{

    use BaseMethods;
    use Singleton;

    protected $routes;


    private function __construct()
    {

        $adress_str = $_SERVER['REQUEST_URI'];

        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));

        if($path === PATH){

            $this->routes = Settings::get('routes');

            if(!$this->routes) throw new RouteException('Отсутствуют маршруты в базовых настройках', 1);

            $url = preg_split('/(\/)|(\?.*)/', $adress_str, 0, PREG_SPLIT_NO_EMPTY);

            if(!empty($url[0]) && $url[0] === $this->routes['admin']['alias']){

                array_shift($url);

                if($url[0] && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0])){

                   $plugin = array_shift($url);

                   $pluginSettings = $this->routes['settings']['path'] . ucfirst($plugin . 'Settings');

                   if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . $pluginSettings . '.php')){
                       $pluginSettings = str_replace('/', '\\', $pluginSettings);
                       $this->routes = $pluginSettings::get('routes');
                   }

                   $dir = $this->routes['plugins']['dir'] ? '/' . $this->routes['plugins']['dir'] . '/' : '/';
                   $dir = str_replace('//', '/', $dir);

                   $this->controller = $this->routes['plugins']['path'] . $plugin . $dir;

                   $hrUrl = $this->routes['plugins']['hrUrl'];

                   $route = 'plugins';

                }else{

                    $this->controller = $this->routes['admin']['path'];

                    $hrUrl = $this->routes['admin']['hrUrl'];

                    $route = 'admin';
                }


            }else{

                if(!$this->isPost()){

                    $pattern = '';
                    $replacement = '';

                    if(END_SLASH){

                        if(!preg_match('/(\/\?)|(\/\s*$)/', $adress_str)){

                            $pattern = '/(^.*?)(\?.*)?$/';
                            $replacement = '$1/';

                        }

                    }else{

                        if(preg_match('/(\/\?)|(\/\s*$)/', $adress_str)){
                            $pattern = '/(^.*?)\/(\?.*)?$/';
                            $replacement = '$1';
                        }

                    }

                    if($pattern){

                        $adress_str = preg_replace($pattern, $replacement, $adress_str);

                        if(!empty($_SERVER['QUERY_STRING'])){
                            $adress_str .= '?' . $_SERVER['QUERY_STRING'];
                        }

                        $this->redirect($adress_str, 301);
                    }


                }

                $hrUrl = $this->routes['user']['hrUrl'];

                $this->controller = $this->routes['user']['path'];

                $route = 'user';
            }

            $this->createRoute($route, $url);

            if(!empty($url[1])){
                $count = count($url);
                $key = '';

                if(!$hrUrl){
                    $i = 1;
                }else{
                    $this->parameters['alias'] = $this->clearStr($url[1]);
                    $i = 2;
                }

                for( ; $i < $count; $i++){
                    if(!$key){
                       $key = $url[$i];
                       $this->parameters[$key] = '';
                    }else{
                        $this->parameters[$key] = $url[$i];
                        $key = '';
                    }
                }
            }

        }else{
            throw new RouteException('Не корректная директория сайта', 1);
        }
    }

    private function createRoute($var, &$arr){

        $route = [];

        if(!empty($arr[0])){

            if(Settings::get('landingMode') && $this->controller !== $this->routes['admin']['path'] && $this->controller !== $this->routes['plugins']['path']){

                $this->controller .= $this->routes['default']['controller'];

                $newArr = [];

                $i = 0;

                foreach ($arr as $item){

                    $newArr[++$i] = $item;

                }

                $arr = $newArr;

            }else{

                if(!empty($this->routes[$var]['routes'][$arr[0]])){

                    $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);

                    $this->controller .= preg_replace('/[-_]+/', '', ucwords($route[0], '-_')) . 'Controller';

                }else{

                    $this->controller .= preg_replace('/[-_]+/', '', ucwords($arr[0], '-_')) . 'Controller';;

                }

            }


        }else{
            $this->controller .= $this->routes['default']['controller'];
        }

        $this->inputMethod = !empty($route[1]) ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = !empty($route[2]) ? $route[2] : $this->routes['default']['outputMethod'];

        return;
    }

}
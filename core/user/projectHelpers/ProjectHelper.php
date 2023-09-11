<?php

namespace core\user\projectHelpers;

trait ProjectHelper
{
    protected $rootAlias;


    protected function beforeInput(){

        if($this->styles && file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . TEMPLATE. 'assets/css/' . $this->getController() . '.css')){

            if(!preg_grep('/'. preg_quote($this->getController() . '.css') .'/i', $this->styles)){
                $this->styles[] = PATH . TEMPLATE. 'assets/css/' . $this->getController() . '.css';
            }

            if($this->getController() === 'lk'){
                $this->styles[] = PATH . TEMPLATE . 'assets/css/catalog.css';
            }
        }



    }


    protected function beforeOutput(&$vars){

        if (empty($vars['playlists']) && !empty($this->userData)){

            $vars['playlists'] = $this->model->get('playlists', [
                    'where' => ['visitors_id' => $this->userData['id']]
                ]);

        }

        // если кешь есть то далее выполнять не нужно
        if ($this->dynamicCache){
            return;
        }



    }





}




























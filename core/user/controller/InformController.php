<?php


namespace core\user\controller;


class InformController extends BaseUser
{

    protected function inputData(){

        // читаем содержимое из потока
        $data = file_get_contents("php://input");

        if ($data){
            $data = (array) json_decode($data);

            foreach ($data as $key => $item){
                $_SESSION[$key] = $item;
            }

        }
        exit;
    }

}
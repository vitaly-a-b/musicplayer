<?php

namespace core\base\exceptions\view;


use core\base\controller\BaseController;

class ErrorController extends BaseController
{

    public function outputData($message){

        return $this->render($_SERVER['DOCUMENT_ROOT'] . PATH . 'core/base/exceptions/view/404', ['message' => $message]);

    }

}
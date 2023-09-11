<?php

namespace core\base\exceptions;


use core\base\controller\BaseMethods;
use core\base\exceptions\view\ErrorController;
use core\base\settings\Settings;

class RouteException extends \Exception
{

    protected $messages;

    use BaseMethods;

    public function __construct($message = "", $code = 0)
    {
        parent::__construct($message, $code);

        $this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') . 'exceptionMessages.php';

        $error = $this->getMessage() ? $this->getMessage() : $this->messages[$this->getCode()];

        $error .= "\r\n" . 'file ' . $this->getFile() . "\r\n" . 'In line ' . $this->getLine() . "\r\n";

        if($this->messages[$this->getCode()]) $this->message = $this->messages[$this->getCode()];

        $this->writeLog($error);

    }

    public function showMessage(){

        header("HTTP/1.1 404 Not Found", true, 404);
        header ('Status: 404 Not Found');

        return (new ErrorController())->outputData($this->message);

    }

}
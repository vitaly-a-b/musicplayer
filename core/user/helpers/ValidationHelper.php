<?php


namespace core\user\helpers;


trait ValidationHelper
{


    protected function emptyField($value, $answer){

        $value = $this->clearStr($value);

        if(empty($value)){
            $this->sendError('не заполненно поле' . $answer);
        }

        return $value;

    }


    protected function numericField($value, $answer, $count = null){

        $value = preg_replace('/\D/', '', $this->clearNum($value));

        if(!$value){
            $this->sendError('Некорректное поле' . $answer);
        }

        if($count){

            if(strlen($value) !== $count)
                $this->sendError('Длина поля' . $answer . ' должна содержать ' . $count . ' ' . $this->wordsForCounter($count, ['Символов', 'Символ', 'Символа']));

        }

        return $value;

    }



    protected function phoneField($value, $answer = null){

        $value = preg_replace('/\D/', '', $this->clearNum($value));

        if(strlen($value) === 11){

            $value = preg_replace('/^8/', '7', $value);
        }

        return $value;

    }


    protected function emailField($value, $answer){

        $value = $this->clearStr($value);

        if(!preg_match('/^[\w\-\.]+@[\w\-]+\.[\w\-]+/i', $value)){
            $this->sendError('не корректный формат поля' . $answer);
        }

        return $value;

    }




    protected function sendError($text, $logMessage = null, $class = 'error',  $fileName = 'order_error_log.txt'){

        $_SESSION['res']['answer'] = '<div class="' . $class . '">' . $text . '</div>';

        if($class === 'error'){

            if($logMessage){

                if(!is_string($logMessage)){
                    $logMessage = $text;
                }

                $this->writeLog($logMessage, $fileName);
            }

            $this->addSessionData();

            $this->redirect();
        }

    }


    protected function sendSuccess($text){

        $this->sendError($text, null , 'success');
    }


}


















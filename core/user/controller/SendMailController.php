<?php


namespace core\user\controller;


use core\user\helpers\ValidationHelper;
use libraries\SendMail;

class SendMailController extends BaseUser
{

    use ValidationHelper;


    // поля формы для валидации. Они должны совпадать с тем, что пришло в POST
    // поля со * это обязательные поля которые нужно валидировать на пустоту
    protected $translate = [
        'name' => 'Имя',
        'phone' => 'Телефон*',
        'email' => 'E-mail*',
        'comment' => 'Комментарий'
    ];

    // для разных типов форм свои поля
    protected $formTypes = [
       /* 'feedBack' => [
            'name' => 'Имя',
            'phone' => 'Телефон*',
            'email' => 'E-mail*',
            'comment' => 'Комментарий'
        ]*/
    ];



    protected function inputData()
    {
        parent::inputData(); // TODO: Change the autogenerated stub

        if($this->isPost()){

            $this->checkToken();

            $this->checkFormType();

            //  в этом массиве будет собираться тело письма
            $request = [];

            // перебираем все данные пришедшие с формы
            foreach ($_POST as $key => $item){

                $_POST[$key] = $this->clearStr($_POST[$key]);
                $translated = preg_replace('/\*/', '',$this->translate[$key]);

                // если в $this->translate[$key] есть "*" то значение нужно проверить на пустоту
                if(!empty($this->translate[$key]) && preg_match('/\*/', $this->translate[$key])){
                    $this->emptyField($item, $translated);
                }

                // если есть метод валидации с именем $key+'Field'. Например поле phone нужно проволидировать методом phoneField
                if(!empty($this->translate[$key])){

                    $tryMethod = $key . 'Field';

                    if(method_exists($this, $tryMethod)){
                        $item = $this->$tryMethod($item, $translated);
                    }

                    // добавляем полученные данные в целевой массив, если они есть
                    !empty($item) && $request[] = $translated . ': ' . $item;

                }

            }

            if(!$request){

                $_SESSION['res'] = $_POST;
                $_SESSION['res']['answer'] = '<div>' . $this->translateEl('Заполните данные для отправки') . '</div>';

            }else{

                // отправляем письмо и выводим сообщение
                $mail = new SendMail();
                $mail->setMailBody(implode('<br>', $request));
                $mail->send($this->set['email'], 'Заявка с сайта ' . $_SERVER['SERVER_NAME']);

                $this->sendSuccess($this->translateEl('Спасибо за заявкую. В ближайщее время свяжемся с Вами'));

            }

        }

        $this->redirect();

    }


   // проверка типа формы. Если $this->formTypes заполненно, то $this->translate будет переопределенно согласно данным из $this->formTypes.
    // Нужно, чтобы в POST пришел ключь 'formType' (добавлен скрытый инпут с name = 'formType') или в адресной строке задан алиас ($this->parameters['alias'])
    // это нужно для использования разных форм.
    protected function checkFormType(){

        if(!empty($this->formTypes)){

            $type = $_POST['formType'] ?? ($this->parameters['alias'] ?? null);

            if(empty($type) || !in_array($type, $this->formTypes) || empty($this->formTypes[$type])){ // проверить условие
                exit('Досвидания');
            }

            if(!empty($this->formTypes[$type]) && is_array($this->formTypes[$type])){
                $this->translate = $this->formTypes[$type];
            }

        }

    }




}


























<?php


namespace libraries;


class SendMail
{
    // для инициализации PHPMailer
    private $CharSet = 'utf-8';
    private $SMTPDebug = 0;
    private $Debugoutput = 'htmp';
    private $Host = 'smtp.yandex.ru';
    private $SMTPSecure = 'tls';
    private $port = 587;
    private $SMTPAuth = true;
    private $Username = 'webprojectvitaly@yandex.ru';
    private $Password = 'xukiussdlynrklcb';

    private $_FromEmail = 'webprojectvitaly@yandex.ru';
    private $_FromName = null;
    private $_address = [];
    private $_files = [];
    private $_subject = '';

    // тело письма
    private $_mailBody = '';

    // Путь к директории где хранятся шаблоны писем
    private $_templatePath = '';

    // последняя ошибка при отправки письма
    private $_lastError = '';


  // свойства с префиксом _ инициализируются из $option
    public function __construct($option = []){

        if($option && is_iterable($option)){

            foreach ($option as $name => $value){

                if(property_exists($this, '_' . $name)){
                    $name = '_' . $name;
                }

                $this->$name = $value;

            }
        }

    }


    // инициализация $this->_templatePath (Путь к директории где хранятся шаблоны писем). По дефолту 'include/emailTemplates'
    public function setTemplatesPath($templatePath = ''){

        if(!$templatePath){

            $this->_templatePath = $_SERVER['DOCUMENT_ROOT'] . PATH . TEMPLATE . 'include/emailTemplates';

        }else{

            $this->_templatePath = strpos($templatePath, $_SERVER['DOCUMENT_ROOT'] . PATH) !== false ? $templatePath :
                $_SERVER['DOCUMENT_ROOT'] . PATH . $templatePath;

        }

        $this->_templatePath = preg_replace('/\/{2,}/', '/', $this->_templatePath);

        !preg_match('/\/$/', $this->_templatePath) && $this->_templatePath .= '/';

    }


   // метод получения  содержимого файла шаблона. $templateFilePath - имя файла шаблона
    public function getTemplate($templateFilePath){

        $template = '';

        if($templateFilePath){

            // если путь к директории где хранятся шаблоны еще не установлен, то устанавливаем
            if(!$this->_templatePath){
                $this->setTemplatesPath();
            }

            // если в пути до файла шаблона два "/" и более, то заменяем на один
            $templateFilePath = preg_replace('/\/{2,}/', '/', $this->_templatePath . $templateFilePath);

            // если нет расширения у файла
            if(!preg_match('/\.[a-z]{1,5}$/', $templateFilePath)){
                $templateFilePath .= '.php';
            }

            // получение содержимого файла шаблона
            if(is_readable($templateFilePath)){
                $template = file_get_contents($templateFilePath);
            }

        }

        return $template;

    }


    // метод подстановки в шаблон нужных данных. В теле шаблона заглушка #$name# заменяется значением из $value
    // может передаваться или уже сам шаблон в $template или его имя в $templateFilePath
    // если $setMailBody = true, то заполненный шаблон добавится к  $this->_mailBody
    public function setTemplate($name, $value, $template = '', $templateFilePath = '', $setMailBody = true){

        $checkTemplate = $this->getTemplate($templateFilePath);

        $checkTemplate && $template = $checkTemplate;

        // подставляем данные в шаблон
        $template = preg_replace('/#'. preg_replace('/\-/', '\-', preg_quote($name)) . '#/', $value, $template);

        if($setMailBody){
            $this->setMailBody($template);
        }

        return $template;

    }


    // метод-обертка для setTemplate(), только данные заполняются из массива $arr содержащего $name => $value
    public function setTemplateFromArray($arr, $template = '', $templateFilePath = '', $setMailBody = true){

        if(is_iterable($arr)){

            foreach ($arr as $name => $value){

                if(!$template && ($checkTemplate = $this->getTemplate($templateFilePath))){
                    $template = $checkTemplate;
                    $templateFilePath = '';
                }

                $template = $this->setTemplate($name, $value, $template, $templateFilePath, false);

            }

            if($setMailBody){
                $this->setMailBody($template);
            }
        }

        return $template;

    }



    // добавление к телу письма заполненного шаблона $template
    public function setMailBody($template){
        $this->_mailBody .= $template;
    }


    public function addFile($path, $name){
        $this->_files[$path] = $name;
    }



// отправка email
    public function send($email = '', $subject = ''){

        // подключаем автозагрузчик
        require_once $_SERVER['DOCUMENT_ROOT'] . PATH . 'libraries/PHPMailer/PHPMailerAutoload.php';

        $sender = new \PHPMailer();

        $sender->isSMTP();

        if(!is_array($this->_address)){
            $this->_address = (array)$this->_address;
        }

        if($email){

            foreach ((array)$email as $item){

                $this->_address[] = $item;
            }
        }

        //инициализируем PHPMailer свойствами класса без префикса _
        foreach ($this as $name => $value){

            if(!preg_match('/^_/', $name)){
                $sender->$name = $value;
            }
        }

        // от кого
        $sender->setFrom($this->_FromEmail, $this->_FromName ?: $_SERVER['HTTP_HOST']);

        foreach ($this->_address as $item){
            $sender->addAddress($item);
        }

        // если есть прикрепленные файлы
        if(!empty($this->_files)){

            foreach ($this->_files as $path => $name){
                $sender->addAttachment($path, $name);
            }
        }

        // тема письма
        !empty($subject) ? $sender->Subject = $subject : $sender->Subject = $this->_subject;

        if(!preg_match('/<\/html>\s*$/', $this->_mailBody)){
            $this->_mailBody = '<html><body>' . $this->_mailBody . '</body></html>';
        }

        $sender->msgHTML($this->_mailBody);

        $this->_lastError = '';

        if($sender->send()){

            $this->_mailBody = '';
            return true;

        }

        $this->_lastError = $sender->ErrorInfo;

        return false;
    }



    public function getLastError(){
        return $this->_lastError;
    }


}
















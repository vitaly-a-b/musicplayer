<?php


namespace libraries\payments;


use core\base\controller\BaseMethods;
use core\user\model\Model;

class SBRF
{
    use BaseMethods;

    public $returnUrl = 'cart/checkpayments/system/SBRF';

    public function setPayment($data){

        $order = $data['order'] ?? null;
        $goods = $data['goods'] ?? null;
        $payment = $data['payment'] ?? null;

        if(empty($order['id'])){
            exit('Ошибка формирования онлайн оплаты. Отсутствует идетнтификатор заказа');
        }

        // проверяем наличие всех данных платежной системы необходимых для проведения платежа
        if(!$payment = $this->validatePayment($order, $payment)){
            return;
        }

        // сумма оплаты в копейках
        $sum = (int)($order['total_sum'] * 100);

        // массив, который будет передаваться далее в платежную систему
        $parameters = [];

        // идентификатор платежа
        $parameters['orderNumber'] = $order['id'] . 'vit';

        // если пришли товары, то сумму нужно будет пересчитать по новой
        if($goods){

            $cart = [];
            $sum = 0;
            $counter = 0;

            foreach ($goods as $item){

                $counter++;
                $price = (int)($item['price'] * 100);
                $amount = $item['qty'] * $price;

                // идентификатор товара. Это либо артикул, код или id из таблицы БД с товарами
                $code = $item[Model::instance()->goodsTable . '_id'] ?? '';
                $code = !empty($item['code']) ? $item['code'] : (!empty($item['article']) ? $item['article'] : $code);
                // если есть оффер то добавляем, чтобы не было дубля с товаром
                $code .= !empty($item[Model::instance()->offersTable . '_id']) ? '--' . $item[Model::instance()->offersTable . '_id'] : '';

                // в случае если кода нет, а он обязателен, то используем счетчик $counter
                !$code && $code = $counter;

                // формируем корзину из товаров как требует платежная система
                $cart[] = [
                  'positionId' => $counter,
                  'name' => $item['name'],
                  'quantity' => [
                      'value' => $item['qty'],
                      'measure' => $item['unit'] ?? 'шт',
                  ],
                  'itemAmount' => $amount,
                  'itemCode' => $code,
                  'tax' => [
                      'taxType' => 0,
                      'taxSum' => 0
                  ],
                    'itemPrice' => $price
                ];

                $sum += $amount;

            }

            // формируем специализированую ячейку с товарами
            $parameters['orderBundle'] = json_encode(
                [
                    'cartItems' =>[
                        'items' => $cart
                    ]
                ]
            , JSON_UNESCAPED_UNICODE);
        }

        $parameters['amount'] = $sum;
        $parameters['userName'] = trim($payment['api_username']);
        $parameters['password'] = trim($payment['api_password']);

        // куда будет перенаправлен после платежа  в данной системе оплаты
        if(!empty($payment['api_return_url'])){
            $this->returnUrl = trim($payment['api_return_url']);
        }

        if(!preg_match('/^https?:\/\//', $this->returnUrl)){
            $this->returnUrl = (!empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['SERVER_NAME'] .
                PATH  . ltrim($this->returnUrl, ' /');
        }

        $parameters['returnUrl'] = $this->returnUrl;

        // режим работы, если он нужен (для сбера нужен)
        $mode = array_key_exists('api_mode', $payment) ? (int)$payment['api_mode'] : 1;

        // отправляем запрос на регистрацию заказа, получаем идентификатор от платежной системы
        $res = $this->sendSbrfRequest($parameters, $mode);

        if(empty($_SESSION['res']['answer'])){
            $_SESSION['res']['answer'] = '';
        }

        // если идентификатор не получен
        if(!$res || !($result = json_decode($res, true))){

            $_SESSION['res']['answer'] .= '<br><div>Ошибка при формировании данных для онлайн оплаты</div>';
            $this->writeLog('Некорректный ответ от платежной системы - ' . $res, 'payments_error_log.txt');
            return;

        }

        // полученный от платежной системы зарегестрированный идентификатор и ссылку записываем в БД и перенаправляем пользователя по полученной ссылке
        if(!empty($result['formUrl']) && !empty($result['orderId'])){

            Model::instance()->edit('orders', [
               'fields' => ['external_payment_id' => $result['orderId']],
                'where' => ['id' => $order['id']]
            ]);

            $this->redirect($result['formUrl']);

        }else{

            $_SESSION['res']['answer'] .= '<br><div>Ошибка при формировании данных для онлайн оплаты</div>';
            $this->writeLog('Сообщение от платежной системы - ' . $result['errorMessage'], 'payments_error_log.txt');
        }



    }




    public function getPaymentStatus(){

        $extOrderId = $this->clearStr($_GET['orderId'] ?? '');

        if(!$extOrderId){
            exit('Досвидания');
        }

        $order = Model::instance()->get('orders', [
            'where' => ['external_payment_id' => $extOrderId, 'external_payment_status' => false],
            'single' => true,
            'limit' => 1
        ]);

        if(empty($order) || empty($order['payments_id'])){
            exit('Досвидания');
        }

        if(!($payment = $this->validatePayment($order))){

            $this->writeLog('Не корректные данные по системе оплаты для заказа - ' . $extOrderId, 'payments_error_log.txt');
            return;

        }

        $mode = array_key_exists('api_mode', $payment) ? (int)$payment['api_mode'] : 1;

        $parameters = [
            'orderId' => $extOrderId,
            'userName' => $payment['api_username'],
            'password' => $payment['api_password']
        ];


        $res = $this->sendSbrfRequest($parameters, $mode, 'statusAction');

        if(empty($_SESSION['res']['answer'])){
            $_SESSION['res']['answer'] = '';
        }

        if(!$res || !($result = json_decode($res, true))){

            $_SESSION['res']['answer'] .= '<br><div>Ошибка при получении статуса онлайн оплаты</div>';
            $this->writeLog('Получен некорректный результат от платежной системы - ' . $res, 'payments_error_log.txt');
            return;

        }else{

            $_SESSION['res']['answer'] .= '<br><div class="success">Статус оплаты - ' . $result['errorMessage'] .'</div>';

            Model::instance()->edit('orders', [
               'fields' => ['external_payment_status' => $result['errorMessage'], 'external_payment_date' => 'NOW()'],
                'where' => ['id' =>$order['id']]
            ]);

        }

        $path = !empty($order['payment_from_page']) ? $order['payment_from_page'] : PATH . 'cart' . END_SLASH;

        $this->redirect($path);

    }






    // отправка запроса в платежную систему. $action - действие по регистрации оплаты или получение ее статуса
    // может иметь два значения 'registerAction' или 'statusAction'
    protected function sendSbrfRequest($parameters, $workMode = 1, $action = 'registerAction'){

        $url = !$workMode ? 'https://3dsec.sberbank.ru/payment/rest/' : 'https://securepayments.sberbank.ru/payment/rest/';

        $registerAction = 'register.do';

        $statusAction = 'getOrderStatusExtended.do';

        $headers = [
            'Content-type: application/x-www-form-urlencoded'
        ];

        // формирование ссылки для запроса
        $link = $url . $$action . '?' . http_build_query($parameters);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;

    }



    // метод проверки наличия данных о платежной системе
    protected function validatePayment($order, $payment = null){

        if(empty($payment) && !empty($order['payments_id'])) {

            $payment = Model::instance()->get('payments', [
                'where' => ['id' => $this->clearNum($order['payments_id'])],
                'single' => true
            ]);
        }

        if(!$payment){
            $this->writeLog("Отсутствуют данные о системе оплаты для заказа \r\n" . print_r($order, true), 'payments_error_log.txt');
            return false;
        }

        if(empty($payment['api_username']) || empty($payment['api_password'])){
            $this->writeLog("Отсутствуют данные для подключения к платежной системе  \r\n" . print_r($payment, true), 'payments_error_log.txt');
            return false;
        }

        return $payment;

    }



}





















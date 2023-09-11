<?php
namespace core\base\model;

use core\base\controller\Singleton;

class Crypt{

    use Singleton;

    private $crypt_method = 'AES-128-CBC'; //Режим шифрования
    private $hache_algoritm = 'sha256';
    private $hache_length = 32;



    public function encrypt($str){ //Шифрование данных

        $ivlen = openssl_cipher_iv_length($this->crypt_method);

        $iv = openssl_random_pseudo_bytes($ivlen);

        /*Шифруем строку*/
        $crypt_text = openssl_encrypt($str, $this->crypt_method, CRYPT_KEY, $options=OPENSSL_RAW_DATA, $iv);

        $hmac = hash_hmac($this->hache_algoritm, $crypt_text, CRYPT_KEY, $as_binary = true);

        return $this->cryptCombine($crypt_text, $iv, $ivlen, $hmac);

        /*Шифруем строку*/

    }




    public function decrypt($str){  //расшифровка данных

        $ivlen = openssl_cipher_iv_length($this->crypt_method);

        $crypt_data = $this->cryptUnCombine($str, $ivlen);

        $original_plaintext = openssl_decrypt($crypt_data['str'], $this->crypt_method, CRYPT_KEY, $options=OPENSSL_RAW_DATA, $crypt_data['iv']);

        $calcmac = hash_hmac($this->hache_algoritm, $crypt_data['str'], CRYPT_KEY, $as_binary = true);

        if (hash_equals($crypt_data['hmac'], $calcmac))// с PHP 5.6+ сравнение, не подверженное атаке по времени
        {
            return $original_plaintext;
        }

        return false;

    }




    protected function cryptUnCombine($str, $ivlen){

        $crypt_data = [];

        $str = base64_decode($str);

        $hache_position = (int)ceil((strlen($str) / 2 - $this->hache_length / 2));

        $crypt_data['hmac'] = substr($str, $hache_position, $this->hache_length);

        $crypt_data['str'] = '';

        $crypt_data['iv'] = '';

        $str = str_replace($crypt_data['hmac'], '', $str);

        $counter = (int)ceil((strlen(CRYPT_KEY) / (strlen($str) - $ivlen + strlen($crypt_data['hmac']))));

        $progress = 2;

        for($i = 0; $i < strlen($str); $i++){

            if(($ivlen + strlen($crypt_data['str'])) < strlen($str)){

                if($i === $counter && strlen($crypt_data['iv']) < $ivlen){

                    $crypt_data['iv'] .= substr($str, $counter, 1);
                    $progress++;
                    $counter += $progress;

                }else{
                    $crypt_data['str'] .= substr($str, $i, 1);
                }

            }else{

                $crypt_str_len = strlen($crypt_data['str']);

                $crypt_data['str'] .= substr($str, $i, strlen($str) - $ivlen - $crypt_str_len);
                $crypt_data['iv'] .= substr($str, $i + (strlen($str) - $ivlen - $crypt_str_len));

                break;

            }

        }

        return $crypt_data;

    }




    protected function cryptCombine($str, $iv, $ivlen, $hmac){

        $new_str = '';

        $counter = (int)ceil((strlen(CRYPT_KEY) / (strlen($str) + strlen($hmac))));

        $progress = 1;

        if($counter >= strlen($str) || $counter < 0) $counter = 1;

        for($i = 0; $i < strlen($str); $i++){

            if($counter < strlen($str)){

                if($i === $counter){

                    $new_str .= substr($iv, $progress - 1, 1);
                    $progress++;
                    $counter += $progress;

                }

            }else{

                break;

            }

            $new_str .= substr($str, $i, 1);

        }

        $new_str .= substr($str, $i);
        $new_str .= substr($iv, $progress - 1);


        $new_str_arr[] = substr($new_str, 0, (int)ceil((strlen($new_str) / 2))) . $hmac;
        $new_str_arr[] = substr($new_str, (int)ceil((strlen($new_str) / 2)));

        return base64_encode($new_str_arr[0] . $new_str_arr[1]);

    }

}
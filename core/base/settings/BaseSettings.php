<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 28.06.2019
 * Time: 15:10
 */

namespace core\base\settings;

use core\base\controller\Singleton;
use core\base\settings\Settings;

trait BaseSettings
{

    use Singleton{
        instance as SingletonInstance;
    }

    private $baseSettings;


    static public function get($property){
        return self::instance()->$property ?? null;
    }


    static public function instance(){

        if(self::$_instance instanceof self){
            return self::$_instance;
        }

        self::SingletonInstance()->baseSettings = MainSettings::instance();

        if(!self::$_instance instanceof self::$_instance->baseSettings){

            $baseProperties = self::$_instance->baseSettings->clueProperties(get_class());
            self::$_instance->setProperty($baseProperties);

        }

        return self::$_instance;
    }


    protected function setProperty($properties){
        if($properties){
            foreach ($properties as $name => $property) {
                $this->$name = $property;
            }
        }
    }


    public function clueProperties($class){

        $baseProperties = [];

        foreach($this as $name => $item){
            $property = $class::get($name);

            if(is_array($property) && is_array($item)){

                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
                continue;
            }

            if(!$property) $baseProperties[$name] = $this->$name;
        }

        return $baseProperties;
    }


    public function arrayMergeRecursive(){

        $arrays = func_get_args();

        $base = array_shift($arrays);

        foreach($arrays as $array){
            foreach($array as $key => $value){
                if(is_array($value) && isset($base[$key]) && is_array($base[$key])){
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                }else{
                    if(is_int($key)){
                        if(!in_array($value, $base)) array_push($base, $value);
                        continue;
                    }
                    $base[$key] = $value;
                }
            }
        }

        return $base;

    }

}
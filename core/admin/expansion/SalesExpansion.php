<?php


namespace core\admin\expansion;


use core\base\controller\Singleton;


class SalesExpansion extends Expansion
{
    use Singleton;

    public function expansion($args = [], $obj = false){

        parent::expansion($args, $obj);

        if(($this->className === 'Add' || $this->className === 'Edit') && !$this->isPost()){

            if(isset($this->foreignData['catalog_id']['NULL']['name'])){

                $this->foreignData['catalog_id']['NULL']['name'] = 'Главная страница';

            }
            /*
            if(!empty($this->translate['catalog_id'])){

                $this->translate['catalog_id'][1] = 'Обязятельно указывать только для корневого раздела';
            }*/
        }

    }

}
<?php


namespace core\user\controller;


class ArtistController extends BaseUser
{

    protected function inputData()
    {
        parent::inputData();


        $artists = [];
        $where = [];

        if (!empty($this->model->showColumns('artist')['visible'])){
            $where['visible'] = 1;
        }

        $artists = $this->model->get('artist', [
            'where' => $where
        ]);

        return compact('artists');

    }

}
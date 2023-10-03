<?php


namespace core\user\controller;


class ArtistController extends BaseUser
{

    protected function inputData()
    {
        parent::inputData();


        $artists = [];
        $where = [];

        $pagination = $this->clearNum($_GET['page'] ?? 1) ?? 1;

        if (!empty($this->model->showColumns('artist')['visible'])){
            $where['visible'] = 1;
        }

        $artists = $this->model->get('artist', [
            'where' => $where,
            'pagination' => $pagination,
        ]);

        $pages = $this->model->getPagination();

        return compact('artists', 'pages');

    }

}
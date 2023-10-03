<?php


namespace core\user\controller;

class IndexController extends BaseUser
{

    protected function inputData(){

        parent::inputData();

        $tracks = [];
        $playlists = null;

        $pagination = $this->clearNum($_GET['page'] ?? 1) ?? 1;


        if (!empty($this->userData)){

            $playlists = $this->model->get('playlists', [
                'where' => ['visitors_id' => $this->userData['id']]
            ]);

            if (!empty($_GET['pl'])) {

                $_GET['pl'] = $this->clearNum($_GET['pl']);

                if ($playlists) {

                    foreach ($playlists as $key => $playlist) {

                        if ($playlist['id'] == $_GET['pl']) {
                            $playlists[$key]['active'] = true;
                        }
                    }


                    if (in_array($_GET['pl'], array_column($playlists, 'id'))) {

                        $data = $this->model->get('playlists_track', [
                            'where' => ['playlists_id' => $_GET['pl']],
                            'fields' => ['track_id']
                        ]);

                        if ($data) {

                            $data = array_column($data, 'track_id');

                            $tracks = $this->model->get('track', [
                                'where' => ['visible' => 1, '{IN}id' => $data],
                                'pagination' => $pagination,
                                'join' => [
                                    'artist' => [
                                        'fields' => ['name as artist_name'],
                                        'on' => ['parent_id' => 'id'],
                                    ]
                                ],
                            ]);
                        }

                    }
                }

            }

        }

        if (!empty($_GET['artist'])) {

            $_GET['artist'] = $this->clearStr($_GET['artist']);

            $where = [];

            if (!empty($this->model->showColumns('artist')['visible'])){
                $where['visible'] = 1;
            }

            $where['parent_id'] = $this->model->get('artist', [
                'fields' => ['id'],
                'where' => ['alias' => $_GET['artist']],
                'return_query' => true
            ]);

            $tracks = $this->model->get('track', [
                'where' => $where,
                'pagination' => $pagination,
                'join' => [
                    'artist' => [
                        'fields' => ['name as artist_name'],
                        'on' => ['parent_id' => 'id'],
                    ]
                ],
            ]);


        }

        if (!empty($_GET['genre'])) {

            $_GET['genre'] = $this->clearStr($_GET['genre']);

            $where = [];

            if (!empty($this->model->showColumns('style')['visible'])){
                $where['visible'] = 1;
            }

            $where['style_id'] = $this->model->get('style', [
                'fields' => ['id'],
                'where' => ['alias' => $_GET['genre']],
                'return_query' => true
            ]);

            $tracks = $this->model->get('track', [
                'where' => $where,
                'pagination' => $pagination,
                'join' => [
                    'artist' => [
                        'fields' => ['name as artist_name'],
                        'on' => ['parent_id' => 'id'],
                    ]
                ],
            ]);


        }


        if (!$tracks && (empty($this->userData) || (!empty($this->userData) && empty($_GET['pl'])))){

            $where = [];

            if (!empty($this->model->showColumns('track')['visible'])){
                $where['visible'] = 1;
            }

            $tracks = $this->model->get('track', [
                'where' => $where,
                'pagination' => $pagination,
                'join' => [
                    'artist' => [
                        'fields' => ['name as artist_name'],
                        'on' => ['parent_id' => 'id'],
                    ]
                ],
            ]);
        }

        $pages = $this->model->getPagination();

        return compact('tracks', 'playlists', 'pages');
    }

}
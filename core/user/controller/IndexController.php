<?php


namespace core\user\controller;

class IndexController extends BaseUser
{

    protected function inputData(){

        parent::inputData();

        $tracks = [];
        $playlists = null;

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
                                'join' => [
                                    'artist' => [
                                        'fields' => ['name as artist_name'],
                                        'on' => ['parent_id' => 'id'],
                                        //'single' => true
                                    ]
                                ],
                                //'join_structure' =>true
                            ]);
                        }

                    }
                }

            }

        }


        if (!$tracks && (empty($this->userData) || (!empty($this->userData) && empty($_GET['pl'])))){

            $tracks = $this->model->get('track', [
                'where' => ['visible' => 1],
                'limit' => 20,
                'join' => [
                    'artist' => [
                        'fields' => ['name as artist_name'],
                        'on' => ['parent_id' => 'id'],
                        //'single' => true
                    ]
                ],
                //'join_structure' =>true
            ]);
        }

        return compact('tracks', 'playlists');
    }

}
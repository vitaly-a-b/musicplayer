<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 08.09.2019
 * Time: 15:38
 */

namespace core\user\controller;


class AjaxController extends BaseUser
{

    public function ajax(){

        parent::inputData();

        if($this->ajaxData){

            if(!empty($this->ajaxData['data']) && !is_array($this->ajaxData['data'])){

                $data = json_decode($this->ajaxData['data'], true);

                if($data && is_array($data)){

                    foreach ($data as $key => $item) $this->ajaxData[$key] = $item;

                }

            }

            if(!$this->columns && !empty($this->ajaxData['table'])){
                $this->columns = $this->model->showColumns($this->ajaxData['table']);
            }

        }

        switch($this->ajaxData['ajax']){

            case 'site_search':
                return $this->userSearch();


            case 'add_to_playlist':
                return $this->_addToPlaylist();

            case 'delete_playlist':
                return $this->_deletePlaylist();


            case 'deleteTrack':
                return $this->_deleteTrack();

            case 'addTrack':
                return $this->_addTrack();

        }
    }


    protected function _addToPlaylist(){
        return $this->addToPlaylist();
    }

    protected function _deletePlaylist(){
        return $this->deletePlaylist();
    }

    protected function _deleteTrack(){
        return $this->addDeleteTrackToPlaylist(null, null, 'delete');
    }


    protected function _addTrack(){
        return $this->addDeleteTrackToPlaylist();
    }


    protected function userSearch(){


        return [];

    }



}
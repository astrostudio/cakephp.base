<?php
App::uses('IBaseAccess','Base.Model/Access');

class BaseAccess implements IBaseAccess {

    public $Model=null;

    public function __construct(Model $Model){
        $this->Model=$Model;
    }

    public function accessFind(array $query=[]){
        return($query);
    }

    public function accessSave(array $data=[]){
        return(true);
    }

    public function accessDelete($id){
        return(true);
    }
}
<?php
class BaseBehavior extends ModelBehavior {

    public $settings=array();
    
    public function setup(Model $Model,$settings=array()) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=array(
                'contain'=>[]
            );
        }
        
        $this->settings[$Model->alias]=Base::extend([],$this->settings[$Model->alias]);
    }

    public function transact(Model $Model,$method){
        $ds=$Model->getDataSource();
        $ds->begin();

        $result=call_user_func_array([$Model,$method],array_splice(func_get_args(),2));

        if($result===false){
            $ds->rollback();

            return(false);
        }

        $ds->commit();

        return($result);
    }

    public function load(Model $Model,$id,$query=[]){
        return($Model->find('first',Base::extend($query,[
            'conditions'=>[
                $Model->alias.'.'.$Model->primaryKey=>$id
            ]
        ])));
    }

}

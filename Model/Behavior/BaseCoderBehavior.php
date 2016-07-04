<?php
App::uses('Base','Vendor/Base');
App::uses('BaseCoder','Base.Model/Behavior/BaseCoder');

class BaseCoderBehavior extends ModelBehavior {

    var $settings=[];
    
    public function setup(Model $Model,$settings=[]) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=[
                'fields'=>[],
                'prefix'=>'user_'
            ];
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);
    }

    public function beforeValidate(Model $Model,$options=[]){
        foreach($this->settings[$Model->alias]['fields'] as $field=>$coder){
            if(isset($Model->data[$Model->alias][$this->settings[$Model->alias]['prefix'].$field])){
                $Model->data[$Model->alias][$field]=BaseCoder::encode($Model->data[$Model->alias][$this->settings[$Model->alias]['prefix'].$field],$Model->data);
            }
        }

        return(true);
    }

    public function afterFind(Model $Model,$results=[],$primary = false){
        foreach($results as &$result){
            foreach($this->settings[$Model->alias]['fields'] as $field=>$coder){
                if(isset($result[$Model->alias][$field])) {
                    $result[$Model->alias][$this->settings[$Model->alias]['prefix'].$field]=BaseCoder::decode($result[$Model->alias][$field],$result);
                }
            }
        }

        return($results);
    }
}

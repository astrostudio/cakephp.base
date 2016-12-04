<?php
class BaseLogBehavior extends ModelBehavior {

    var $settings=array();
    
    public function setup(Model $Model,$settings=array()) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=array(
                'name'=>'model'
            );
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);
    }
    

    public function afterSave(Model $Model,$created,$options=array()){
        if($created){
            CakeLog::write($this->settings[$Model->alias]['name'],'INSERT: '.json_encode($Model->data));
        }
        else {
            CakeLog::write($this->settings[$Model->alias]['name'],'UPDATE: '.json_encode($Model->data));
        }
    }

    public function afterDelete(Model $Model){
        CakeLog::write($this->settings[$Model->alias]['name'],'DELETE: '.json_encode($Model->id));
    }


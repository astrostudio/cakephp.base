<?php
class BaseUpdateBehavior extends ModelBehavior {

    var $settings=array();
    
    public function setup(Model $Model,$settings=array()) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=array(
                'method'=>'update_'
            );
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);
    }
    
    public function update(Model $Model,$data){
        $method=$this->settings[$Model->alias]['method'];

        if(!method_exists($Model,$method)){
            return(false);
        }
        
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        $result=$Model->$method($data);
        
        if($result===false){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return($result);
    }
    
}

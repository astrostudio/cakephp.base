<?php
class BaseListBehavior extends ModelBehavior {

    var $settings=array();
    
    var $deleting=array();
    
    public function setup(Model $Model,$settings=array()) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=array(
                'position'=>'position'
            );
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);
        
        $Model->__listPosition=$this->settings[$Model->alias['position']];
        $Model->__listScope=array();
    }
    
    public function scopeList(Model $Model,$score=array()){
        $Model->__listScope=$scope;
    }
    
    public function queryList(Model $Model,$offset=0,$length=null){
        $query=array(
            'conditions'=>array(
                $this->__listScope,
                $Model->alias.'.'.$Model->__listPosition.'>'.$offset
            ),
            'order'=>$Model->alias.'.'.$Model->__listPosition
        );
        
        if(isset($length)){
            array_push($query['conditions'],$Model->alias.'.'.$Model->__listPosition.'<'.($offset+$length));
        }
        
        return($query);
    }
    
    public function orderList(Model $Model,$order=null){
        return(false);
    }

    public function insertList($Model,$data,$offset=null){
        return(false);
    }

}

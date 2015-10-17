<?php
App::uses('IBaseFilter','Base.Model');

class BaseJQGridFilter implements IBaseFilter {
    
    private $__alias=null;
    
    public function __construct($alias){
        $this->__alias=$alias;
    }
    
    public function filter($data,$options=array()){
        if(empty($this->__alias)){
            return(array());
        }
        
        if(!is_array($data)){
            return(array());
        }
        
        return(Hash::get($data,$this->__alias));
    }
}
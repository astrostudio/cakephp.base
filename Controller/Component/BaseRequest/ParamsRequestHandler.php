<?php
App::uses('IBaseRequestHandler','Base.Controller/Component/BaseRequest');

class ParamsRequestHandler implements IBaseRequestHandler {

    public function has(CakeRequest $request,$name){
        if(!empty($request->params)){
            return(Hash::check($request->params,$name));
        }
        
        return(false);
    }
    
    public function get(CakeRequest $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->params,$name));
        }
        
        return($value);
    }
    
    public function set(CakeRequest $request,$name,$value){
        if(!isset($request->params)){
            $request->params=array();
        }
        
        $request->params=Hash::insert($request->params,$name,$value);
    }
    
    public function clear(CakeRequest $request,$name){
        if(isset($request->params)){
            $request->params=Hash::remove($request->params,$name);
        }
    }
}

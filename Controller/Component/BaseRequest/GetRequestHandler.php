<?php
App::uses('IBaseRequestHandler','Base.Controller/Component/BaseRequest');

class GetRequestHandler implements IBaseRequestHandler {

    public function has(CakeRequest $request,$name){
        if(!empty($request->query)){
            return(Hash::check($request->query,$name));
        }
        
        return(false);
    }
    
    public function get(CakeRequest $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->query,$name));
        }
        
        return($value);
    }
    
    public function set(CakeRequest $request,$name,$value){
        if(!isset($request->query)){
            $request->query=array();
        }
        
        $request->query=Hash::insert($request->query,$name,$value);
    }
    
    public function clear(CakeRequest $request,$name){
        if(isset($request->query)){
            $request->query=Hash::remove($request->query,$name);
        }
    }
}


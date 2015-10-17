<?php
App::uses('IBaseRequestHandler','Base.Controller/Component/BaseRequest');

class PassRequestHandler implements IBaseRequestHandler {

    public function has(CakeRequest $request,$name){
        if(!empty($request->pass[$name])){
            return(Hash::check($request->pass,$name));
        }
        
        return(false);
    }
    
    public function get(CakeRequest $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->pass,$name));
        }
        
        return($value);
    }
    
    public function set(CakeRequest $request,$name,$value){
        if(!isset($request->pass)){
            $request->pass=array();
        }
        
        $request->pass=Hash::insert($request->pass,$name,$value);
    }
    
    public function clear(CakeRequest $request,$name){
        if(isset($request->pass)){
            $request->pass=Hash::remove($request->pass,$name);
        }
    }
}

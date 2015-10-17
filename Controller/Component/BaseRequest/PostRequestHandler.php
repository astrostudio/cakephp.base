<?php
App::uses('IBaseRequestHandler','Base.Controller/Component/BaseRequest');

class PostRequestHandler implements IBaseRequestHandler {

    public function has(CakeRequest $request,$name){
        if(!empty($request->data)){
            return(Hash::check($request->data,$name));
        }
        
        return(false);
    }
    
    public function get(CakeRequest $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->data,$name));
        }
        
        return($value);
    }
    
    public function set(CakeRequest $request,$name,$value){
        if(!isset($request->data)){
            $request->data=array();
        }
        
        $request->data=Hash::insert($request->data,$name,$value);
    }
    
    public function clear(CakeRequest $request,$name){
        if(isset($request->data)){
            $request->data=Hash::remove($request->data,$name);
        }
    }
}

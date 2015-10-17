<?php
App::uses('IBaseRequestHandler','Base.Controller/Component/BaseRequest');

class ParamRequestHandler implements IBaseRequestHandler {

    public function has(CakeRequest $request,$name){
        if(!empty($request->params['named'])){
            return(Hash::check($request->params['named'],$name));
        }
        
        return(false);
    }
    
    public function get(CakeRequest $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->params['named'],$name));
        }
        
        return($value);
    }
    
    public function set(CakeRequest $request,$name,$value){
        if(!isset($request->params['named'])){
            $request->params['named']=array();
        }
        
        $request->params['named']=Hash::insert($request->params['named'],$name,$value);
    }
    
    public function clear(CakeRequest $request,$name){
        if(isset($request->params['named'])){
            $request->params['named']=Hash::remove($request->params['named'],$name);
        }
    }
}

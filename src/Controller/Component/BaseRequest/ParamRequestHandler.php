<?php
namespace Base\Controller\Component\BaseRequest;

use Cake\Network\Request;
use Cake\Utility\Hash;
use Base\Controller\Component\BaseRequest\IBaseRequestHandler;

class ParamRequestHandler implements IBaseRequestHandler {

    public function has(Request $request,$name){
        if(!empty($request->params[$name])){
            return(Hash::check($request->params,$name));
        }
        
        return(false);
    }
    
    public function get(Request $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->params,$name));
        }
        
        return($value);
    }
    
    public function set(Request $request,$name,$value){
        $request->params[$name]=$value;
    }
    
    public function clear(Request $request,$name){
        if(isset($request->params[$name])){
            unset($request->params[$name]);
        }
    }
}

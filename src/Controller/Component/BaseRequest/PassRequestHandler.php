<?php
namespace Base\Controller\Component\BaseRequest;

use Cake\Network\Request;
use Cake\Utility\Hash;
use Base\Controller\Component\BaseRequest\IBaseRequestHandler;

class PassRequestHandler implements IBaseRequestHandler {

    public function has(Request $request,$name){
        if(!empty($request->pass[$name])){
            return(Hash::check($request->pass,$name));
        }
        
        return(false);
    }
    
    public function get(Request $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->pass,$name));
        }
        
        return($value);
    }
    
    public function set(Request $request,$name,$value){
        if(!isset($request->pass)){
            $request->pass=array();
        }
        
        $request->pass=Hash::insert($request->pass,$name,$value);
    }
    
    public function clear(Request $request,$name){
        if(isset($request->pass)){
            $request->pass=Hash::remove($request->pass,$name);
        }
    }
}

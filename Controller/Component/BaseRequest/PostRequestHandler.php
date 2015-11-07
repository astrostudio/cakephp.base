<?php
namespace Base\Controller\Component\BaseRequest;

use Cake\Network\Request;
use Cake\Utility\Hash;
use Base\Controller\Component\BaseRequest\IBaseRequestHandler;

class PostRequestHandler implements IBaseRequestHandler {

    public function has(Request $request,$name){
        if(!empty($request->data)){
            return(Hash::check($request->data,$name));
        }
        
        return(false);
    }
    
    public function get(Request $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->data,$name));
        }
        
        return($value);
    }
    
    public function set(Request $request,$name,$value){
        if(!isset($request->data)){
            $request->data=array();
        }
        
        $request->data=Hash::insert($request->data,$name,$value);
    }
    
    public function clear(Request $request,$name){
        if(isset($request->data)){
            $request->data=Hash::remove($request->data,$name);
        }
    }
}

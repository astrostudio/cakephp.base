<?php
namespace Base\Controller\Component\RequestHandler;

use Cake\Network\Request;
use Cake\Utility\Hash;

class GetRequestHandler implements IRequestHandler {

    public function has(Request $request,$name){
        if(!empty($request->query)){
            return(Hash::check($request->query,$name));
        }
        
        return(false);
    }
    
    public function get(Request $request,$name,$value=null){
        if($this->has($request,$name)){
            return(Hash::get($request->query,$name));
        }
        
        return($value);
    }
    
    public function set(Request $request,$name,$value){
        if(!isset($request->query)){
            $request->query=array();
        }
        
        $request->query=Hash::insert($request->query,$name,$value);
    }
    
    public function clear(Request $request,$name){
        if(isset($request->query)){
            $request->query=Hash::remove($request->query,$name);
        }
    }
}


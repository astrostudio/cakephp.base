<?php
class BaseQuery {

    static public function search($text,$fields=array()){
        $conditions=array();
        
        foreach($fields as $field){
            array_push($conditions,array(
                $field.' like "%'.$text.'%"'
            ));
        }
        
        return(array('conditions'=>array(array('OR'=>$conditions))));
    }
    
    static public function tag($field,$tag=null){
        if(is_string($tag)){
            $tags=explode(' ',$tag);
        }
        else if(is_array($tag)){
            $tags=$tag;
        }        
        else {
            $tags=array();
        }
    
        $conditions=array();
        
        foreach($tags as $tag){
            if(is_string($tag) or is_numeric($tag)){
                array_push($conditions,array(
                    $field.' like "%'.$tag.'%"'
                ));
            }
        }
        
        return(array('conditions'=>array(array('OR'=>$conditions))));
    }
    
}
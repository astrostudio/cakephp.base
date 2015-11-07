<?php
namespace Base\Model;

use Cake\ORM\Query;

class BaseQuery {

    static public function search(Query $query,$text,$fields=[]){
        $conditions=array();

        foreach($fields as $field){
            array_push($conditions,array(
                $field.' like "%'.$text.'%"'
            ));
        }

        return($query->where(['OR'=>$conditions]));
    }

    static public function tag(Query $query,$field,$tag=null){
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

        return($query->where(['OR'=>$conditions]));
    }
    
}
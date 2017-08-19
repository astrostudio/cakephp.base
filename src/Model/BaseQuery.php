<?php
namespace Base\Model;

use Cake\ORM\Query;

class BaseQuery {

    static public function search(Query $query,$text,$fields=[]){
        $conditions=[];

        foreach($fields as $field){
            $conditions[]=$field.' like "%'.$text.'%"';
        }

        if(empty($conditions)){
            return($query);
        }

        return($query->where(['OR'=>$conditions]));
    }

    static public function tag(Query $query,$field,$tag=null){
        if(is_string($tag)){
            if(!empty($tag)) {
                $tags = explode(' ', $tag);
            }
            else {
                $tags=[];
            }
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

        debug($conditions);

        return($query->where(['OR'=>$conditions]));
    }

}
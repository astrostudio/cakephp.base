<?php
namespace Base\Model;

use Cake\ORM\Query;

class Queries {

    static public function order(Query $query,$order,array $orders=[]){
        if(is_array($order)){
            foreach($order as $o){
                $query=self::order($query,$o,$orders);
            }

            return($query);
        }

        if(empty($order)){
            return($query);
        }

        if(mb_strpos($order,',')!==false){
            $order=explode(',',$order);

            foreach($order as $o){
                $query=self::order($query,$o,$orders);
            }

            return($query);
        }

        if(!empty($orders[$order])){
            return(self::order($query,$orders[$order],$orders));
        }

        $direction=mb_substr($order,0,1);

        if(($direction=='+') or ($direction=='-')){
            $direction=$direction=='+'?'ASC':'DESC';
            $order=mb_substr($order,1);
        }
        else {
            $direction='ASC';
        }

        if(!empty($orders[$order])){
            $field=$orders[$order];
        }
        else {
            $field=$order;
        }

        return($query->order([$field=>$direction]));
    }

    static public function filter(Query $query,$filter,array $filters=[]){
        if(is_array($filter)){
            foreach($filter as $f){
                $query=self::filter($query,$f,$filters);
            }

            return($query);
        }

        if(empty($filter)){
            return($query);
        }

        if(mb_strpos($filter,',')!==false){
            $filter=explode(',',$filter);

            foreach($filter as $f){
                $query=self::filter($query,$f,$filters);
            }

            return($query);
        }

        if(empty($filters[$filter])){
            return($query);
        }

        return($query->applyOptions($filters[$filter]));
    }

    static public function search(Query $query,$search,$fields=[]){
        if(is_array($search)){
            foreach($search as $field=>$text){
                $query=self::search($query,$text,[$field]);
            }

            return($query);
        }

        $conditions=[];

        foreach($fields as $field){
            $conditions[]=$field.' like "%'.$search.'%"';
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
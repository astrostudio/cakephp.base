<?php
namespace Base\Model;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;

class Queries {

    static public function order(Query $query,$order,$direction=null,array $orders=[],array $subOrder=[]):Query{
        if(is_array($order)){
            foreach($order as $o){
                $query=self::order($query,$o,null,[],$subOrder);
            }

            return($query);
        }

        if(empty($order)){
            return($query);
        }

        $order=addslashes($order);

        if(mb_strpos($order,',')!==false){
            $order=explode(',',$order);

            foreach($order as $o){
                $query=self::order($query,$o,null,[],$subOrder);
            }

            return($query);
        }

        if(!empty($orders[$order])){
            return(self::order($query,$orders[$order],$direction,[],$subOrder));
        }

        $prefix=mb_substr($order,0,1);

        if(($prefix=='+') or ($prefix=='-')){
            $asc=$prefix=='-'?'DESC':'ASC';
            $order=mb_substr($order,1);
        }
        else {
            $asc='ASC';

            if(!empty($direction)){
                if(in_array($direction,['-','d','D','desc','DESC'])){
                    $asc='DESC';
                }
            }
        }

        if(!empty($orders[$order])){
            $field=$orders[$order];
        }
        else {
            $field=$order;
        }

        return($query->order(array_merge([$field=>$asc],$subOrder)));
    }

    static public function filter(Query $query,$filter,array $filters=[]):Query{
        if(is_array($filter)){
            foreach($filter as $f){
                $query=self::filter($query,$f,$filters);
            }

            return($query);
        }

        if(empty($filter)){
            return($query);
        }

        $filter=addslashes($filter);

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

    static public function search(Query $query,$search,$fields=[]):Query{
        if(is_array($search)){
            foreach($search as $field=>$text){
                $query=self::search($query,$text,[$field]);
            }

            return($query);
        }

        $search=addslashes($search);

        if(empty($search)){
            return($query);
        }

        $conditions=[];

        foreach($fields as $field){
            $conditions['LOWER('.$query->getConnection()->quoteIdentifier($field).') LIKE']='%'.mb_strtolower($search).'%';
        }

        if(empty($conditions)){
            return($query);
        }

        return($query->where(['OR'=>$conditions]));
    }

    static public function tag(Query $query,$field,$tag=null):Query{
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
            $tags=[];
        }

        $conditions= [];

        foreach($tags as $tag){
            if(is_string($tag) or is_numeric($tag)){
                array_push($conditions,
                    [
                    $field.' like "%'.$tag.'%"'
                    ]
                );
            }
        }

        return($query->where(['OR'=>$conditions]));
    }

    static public function getPrimaryKey($primaryKey,array $data=[]){
        if(is_array($primaryKey)){
            $value=[];

            foreach($primaryKey as $primaryKeyField){
                $value[]=$data[$primaryKeyField]??null;
            }
        }
        else {
            $value=$data[$primaryKey]??null;
        }

        return($value);
    }

    static public function emptyPrimaryKey($primaryKeyValue){
        if(is_array($primaryKeyValue)){
            foreach($primaryKeyValue as $value){
                if(!isset($value)){
                    return(true);
                }
            }

            return(false);
        }

        return(!isset($primaryKeyValue));
    }

    static public function getPrimaryKeyConditions(string $alias,$primaryKey,$primaryKeyValue){
        $conditions=[];

        if(is_array($primaryKey)){
            if(!is_array($primaryKeyValue)){
                return(false);
            }

            $i=0;

            foreach($primaryKey as $primaryKeyField){
                if(!isset($primaryKeyValue[$i])){
                    return(false);
                }

                $conditions[$alias.'.'.$primaryKeyField]=$primaryKeyValue[$i];

                ++$i;
            }

            return($conditions);
        }

        $conditions[$alias.'.'.$primaryKey]=$primaryKeyValue;

        return($conditions);
    }

    static public function getPrimaryKeyFromEntity(EntityInterface $entity,$primaryKey){
        if(is_array($primaryKey)){
            $value=[];

            foreach($primaryKey as $primaryKeyField){
                $value[]=$entity->get($primaryKeyField);
            }

            return($value);
        }

        return($entity->get($primaryKey));
    }

    static public function apply(Query $query,$options=null){
        if(isset($options)){
            if(is_array($options)){
                $query=$query->applyOptions($options);
            }
            else if(is_callable($options)){
                $query=call_user_func($options,$query);
            }
        }

        return($query);
    }
}

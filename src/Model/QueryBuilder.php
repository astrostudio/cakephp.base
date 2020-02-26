<?php
namespace Base\Model;

use Cake\ORM\Query;

class QueryBuilder
{
    /** @var Query */
    private $query;

    public function __construct(Query $query){
        $this->query=$query;
    }

    public function query():Query
    {
        return($this->query);
    }

    public function sorter($sorter,$direction=null,array $sorters=[],array $subSorter=[]):QueryBuilder
    {
        if(is_array($sorter)){
            foreach($sorter as $s){
                $this->sorter($s,null,[],$subSorter);
            }

            return($this);
        }

        if(empty($sorter)){
            return($this);
        }

        $sorter=addslashes($sorter);

        if(mb_strpos($sorter,',')!==false){
            $sorter=explode(',',$sorter);

            foreach($sorter as $s){
                $this->sorter($s,null,[],$subSorter);
            }

            return($this);
        }

        if(!empty($sorters[$sorter])){
            return($this->sorter($sorters[$sorter],$direction,[],$subSorter));
        }

        $prefix=mb_substr($sorter,0,1);

        if(($prefix=='+') or ($prefix=='-')){
            $asc=$prefix=='-'?'DESC':'ASC';
            $sorter=mb_substr($sorter,1);
        }
        else {
            $asc='ASC';

            if(!empty($direction)){
                if(in_array($direction,['-','d','D','desc','DESC'])){
                    $asc='DESC';
                }
            }
        }

        if(!empty($sorters[$sorter])){
            $field=$sorters[$sorter];
        }
        else {
            $field=$sorter;
        }

        $this->query=$this->query->order(array_merge([$field=>$asc],$subSorter));

        return($this);
    }

    public function filter($filter,array $filters=[]):QueryBuilder{
        if(is_array($filter)){
            foreach($filter as $f){
                $this->filter($f,$filters);
            }

            return($this);
        }

        if(empty($filter)){
            return($this);
        }

        $filter=addslashes($filter);

        if(mb_strpos($filter,',')!==false){
            $filter=explode(',',$filter);

            foreach($filter as $f){
                $this->filter($f,$filters);
            }

            return($this);
        }

        if(empty($filters[$filter])){
            return($this);
        }

        $this->query=$this->query->applyOptions($filters[$filter]);

        return($this);
    }

    public function search($search,array $fields=[]):QueryBuilder{
        if(is_array($search)){
            foreach($search as $field=>$text){
                $this->search($text,[$field]);
            }

            return($this);
        }

        $search=addslashes($search);

        $conditions=[];

        foreach($fields as $field){
            $conditions['LOWER('.$this->query->getConnection()->quoteIdentifier($field).') LIKE']='%'.mb_strtolower($search).'%';
        }

        if(empty($conditions)){
            return($this);
        }

        $this->query=$this->query->where(['OR'=>$conditions]);

        return($this);
    }

    public function tag($field,$tag=null):QueryBuilder{
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

        $this->query=$this->query->where(['OR'=>$conditions]);

        return($this);
    }


}
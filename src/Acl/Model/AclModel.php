<?php
namespace Base\Acl\Model;

use Base\Model\Entity\AclEntity;
use Base\Model\Table\AclTable;
use Cake\ORM\Query;
use Exception;

class AclModel
{
    const ARO='aro';
    const ACO='aco';
    const ALO='alo';

    /**
     * @var AclModelInterface[][]
     */
    static private $models=[
        self::ARO=>[],
        self::ACO=>[],
        self::ALO=>[]
    ];

    static public function check(string $type){
        if(!isset(self::$models[$type])){
            throw new Exception('\\Base\\Acl\\Model\\AclModel::connect(): $type not valid');
        }
    }

    static public function connect($type,$name=null,AclModelInterface $model=null){
        if(is_array($type)){
            foreach($type as $t=>$n){
                if(is_array($n)) {
                    self::connect($t, $n);
                }
            }

            return;
        }

        self::check($type);

        if(is_array($name)){
            foreach($name as $n=>$m){
                self::connect($type,$n,$m);
            }

            return;
        }

        if(!is_string($name)){
            throw new Exception('\\Base\\Acl\\Model\\AclModel::connect(): $name must be string');
        }

        if($model){
            self::$models[$type][$name]=$model;
        }
        else {
            unset(self::$models[$type][$name]);
        }
    }

    static public function aro($name,AclModelInterface $model=null){
        self::connect(self::ARO,$name,$model);
    }

    static public function aco($name,AclModelInterface $model=null){
        self::connect(self::ACO,$name,$model);
    }

    static public function alo($name,AclModelInterface $model=null){
        self::connect(self::ALO,$name,$model);
    }

    static public function initialize(string $type,AclTable $table){
        self::check($type);

        foreach(self::$models[$type] as $model){
            $model->initialize($table);
        }
    }

    static public function find(string $type,Query $query):Query
    {
        self::check($type);

        foreach(self::$models[$type] as $model){
            $query=$model->find($query);
        }

        return($query);
    }

    static public function model(string $type,AclEntity $entity){
        self::check($type);

        $aclModel='';
        $d='';

        foreach(self::$models[$type] as $model){
            if($model->check($entity)) {
                $modelModel = $model->model($entity);

                if (!empty($modelModel)) {
                    $aclModel = $d . $modelModel;
                    $d = ' ';
                }
            }
        }

        if(empty($aclModel)){
            $aclModel=mb_strtoupper($type);
        }

        return($aclModel);
    }

    static public function label(string $type,AclEntity $entity){
        self::check($type);

        $aclLabel='';
        $d='';

        foreach(self::$models[$type] as $model){
            if($model->check($entity)) {
                $modelLabel = $model->label($entity);

                if (!empty($modelLabel)) {
                    $aclLabel = $d . $modelLabel;
                    $d = ' ';
                }
            }
        }

        if(empty($aclLabel)){
            $aclLabel='#'.$entity->get('id');
        }

        return($aclLabel);
    }

    static public function contain(string $type):array
    {
        self::check($type);

        $contain=[];

        foreach(self::$models[$type] as $model){
            $contain=array_merge($contain,$model->contain());
        }

        return($contain);
    }

    static public function filter(string $type):array
    {
        self::check($type);

        $filter=[];

        foreach(self::$models[$type] as $model){
            $filter=array_merge($filter,$model->filter());
        }

        return($filter);
    }

    static public function search(string $type):array
    {
        self::check($type);

        $search=[];

        foreach(self::$models[$type] as $model){
            $search=array_merge($search,$model->search());
        }

        return($search);
    }

    static public function sorter(string $type):array
    {
        self::check($type);

        $sorter=[];

        foreach(self::$models[$type] as $model){
            $sorter=array_merge($sorter,$model->sorter());
        }

        return($sorter);
    }

    /**
     * @param string $type
     *
     * @return AclModelInterface[]
     */
    static public function get(string $type):array
    {
        self::check($type);

        return(self::$models[$type]);
    }

    static private $masks=[];

    static public function mask($alo,int $mask=0){
        $label='';
        $flag=1;

        for($i=0;$i<16;++$i){
            if(($mask & $flag)!=0){
                $label=(isset(self::$masks[$alo][$i])?self::$masks[$alo][$i]:'1').$label;
            }
            else {
                $label='0'.$label;
            }

            $flag=$flag<<1;
        }

        return($label);
    }

    static public function hasMask($alo){
        return(isset(self::$masks[$alo]));
    }

    static public function setMask($alo,$mask=null,string $char=null){
        if(is_array($alo)){
            foreach($alo as $l=>$m){
                self::setMask($l,$m);
            }

            return;
        }

        if(is_array($mask)){
            foreach($mask as $f=>$c){
                self::setMask($alo,$f,$c);
            }

            return;
        }

        if(isset($char)){
            if(!isset(self::$masks[$alo])){
                self::$masks[$alo]=[];
            }

            self::$masks[$alo][$mask]=$char;

            return;
        }

        unset(self::$masks[$alo][$mask]);
    }


}

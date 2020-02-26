<?php
namespace Base\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * @property \Base\Model\Table\AclAroTable $AclAro
 * @property \Base\Model\Table\AclAroTable $AclAcoAro
 * @property \Base\Model\Table\AclAloTable $AclAlo
 */
class AclAroAccessTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('acl_aro_access');
        $this->setPrimaryKey(false);
        $this->belongsTo('Base.AclAro');
        $this->belongsTo('AclAcoAro',[
            'className'=>'Base.AclAro',
            'foreignKey'=>'acl_aco_aro_id'
        ]);
        $this->belongsTo('Base.AclAlo');
    }

    public function mask($aroId,$acoAroId,$aloId){
        /** @var \Cake\ORM\Query $query */
        $query=$this->find();

        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        $query=$query->where([$this->getAlias().'.acl_aro_id'=>$aroId]);

        if(!is_numeric($acoAroId)){
            $acoAroId=$this->AclAro->getIdByName($acoAroId);
        }

        $query=$query->where([$this->getAlias().'.acl_aco_aco_id'=>$acoAroId]);

        if(!is_numeric($aloId)){
            $aloId=$this->AclAlo->getIdByName($aloId);
        }

        $query=$query->where([$this->getAlias().'.acl_alo_id'=>$aloId]);
        $access=$query->first();

        if(!$access){
            return(0);
        }

        return($access->get('mask'));
    }

    public function check($aroId,$acoAroId,$aloId,int $mask=null,bool $full=false){
        /** @var \Cake\ORM\Query $query */
        $query=$this->find();

        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        $query=$query->where([$this->getAlias().'.acl_aro_id'=>$aroId]);

        if(!is_numeric($acoAroId)){
            $acoAroId=$this->AclAro->getIdByName($acoAroId);
        }

        $query=$query->where([$this->getAlias().'.acl_aco_aro_id'=>$acoAroId]);

        if(!is_numeric($aloId)){
            $aloId=$this->AclAlo->getIdByName($aloId);
        }

        $query=$query->where([$this->getAlias().'.acl_alo_id'=>$aloId]);
        $access=$query->first();

        if(!$access){
            return(0);
        }

        if(!isset($mask)){
            return(1);
        }

        if($full){
            return(($access->get('mask') & $mask)==$mask?1:0);
        }

        return($access->get('mask') & $mask);
    }

    public function join(Query $query,$aroId,$field,$aloId,int $mask=null,array $options=[]):Query{
        $alias=Hash::get($options,'alias',$this->getAlias());

        /** @var \Cake\ORM\Query $query */
        $query=$query->join([
            'table'=>$this->getTable(),
            'alias'=>$alias,
            'conditions'=>[$alias.'.acl_aco_aro_id='.$field]
        ]);

        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        $query=$query->where([$alias.'.acl_aro_id'=>$aroId]);

        if(!is_numeric($aloId)){
            $aloId=$this->AclAlo->getIdByName($aloId);
        }

        $query=$query->where([$alias.'.acl_alo_id'=>$aloId]);

        if(isset($mask)){
            if(!empty($options['full'])){
                $query=$query->where(['(('.$alias.'.mask & '.$mask.')='.$mask.')']);
            }
            else {
                $query=$query->where(['(('.$alias.'.mask & '.$mask.')<>0)']);
            }
        }

        return($query);
    }

    public function joinLeft(Query $query,$aroId,$field,$aloId,int $mask=null,array $options=[]):Query{
        $alias=Hash::get($options,'alias',$this->getAlias());
        $aloField=Hash::get($options,'aloField',Inflector::underscore($alias));

        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        if(!is_numeric($aloId)){
            $aloId=$this->AclAlo->getIdByName($aloId);
        }

        $conditions=[
            $alias.'.acl_aco_aro_id='.$field,
            $alias.'.acl_aro_id='.$aroId,
            $alias.'.acl_alo_id='.$aloId,
        ];

        if(isset($mask)){
            if(!empty($options['full'])){
                $conditions[]='(('.$alias.'.mask & '.$mask.')='.$mask.')';
            }
            else {
                $conditions[]='(('.$alias.'.mask & '.$mask.')<>0)';
            }
        }

        $query=$query->join([
            'table'=>$this->getTable(),
            'alias'=>$alias,
            'type'=>'LEFT',
            'conditions'=>$conditions
        ])->select([$aloField=>'IF(ISNULL('.$alias.'.acl_alo_id),0,'.$alias.'.mask)']);

        return($query);
    }

    public function joinAlo(Query $query,$aroId,$field,$alo):Query{
        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        $alo=is_array($alo)?$alo:[$alo];

        foreach($alo as $aloField=>$aloId){
            if(!is_numeric($aloId)){
                $aloId=$this->AclAlo->getIdByName($aloId);
            }

            if(is_int($aloField)){
                $aloField='acl_alo_'.$aloId;
            }

            $aloAlias='AclAlo'.$aloId;

            $query=$query->join([
                'table'=>$this->getTable(),
                'alias'=>$aloAlias,
                'type'=>'LEFT',
                'conditions'=>[
                    $aloAlias.'.acl_aco_aro_id='.$field,
                    $aloAlias.'.acl_aro_id='.$aroId,
                    $aloAlias.'.acl_alo_id='.$aloId
                ]
            ])->select([$aloField=>'IF(ISNULL('.$aloAlias.'.acl_alo_id),0,1)']);
        }

        return($query);
    }



}

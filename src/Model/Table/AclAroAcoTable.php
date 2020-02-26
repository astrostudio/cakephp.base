<?php
namespace Base\Model\Table;

use Cake\ORM\Table;

/**
 * @property \Base\Model\Table\AclAroTable $AclAro
 * @property \Base\Model\Table\AclAcoTable $AclAco
 */
class AclAroAcoTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('acl_aro_aco');
        $this->setPrimaryKey(['acl_aro_id','acl_aco_id']);
        $this->belongsTo('Base.AclAro');
        $this->belongsTo('Base.AclAco');
        $this->addBehavior('Timestamp');
    }

    public function append($aroId,$acoId):bool{
        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        if(!is_numeric($acoId)){
            $acoId=$this->AclAco->getIdByName($acoId);
        }

        $aroAco=$this->find()->where([
            'acl_aro_id'=>$aroId,
            'acl_aco_id'=>$acoId
        ])->first();

        if(!$aroAco){
            $aroAco=$this->newEntity([
                'acl_aro_id'=>$aroId,
                'acl_aco_id'=>$acoId
            ]);

            if(!$this->save($aroAco)){
                return(false);
            }
        }

        return(true);
    }

    public function remove($aroId,$acoId){
        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        if(!is_numeric($acoId)){
            $acoId=$this->AclAco->getIdByName($acoId);
        }

        return($this->deleteAll([
            'acl_aro_id'=>$aroId,
            'acl_aco_id'=>$acoId
        ]));
    }
}

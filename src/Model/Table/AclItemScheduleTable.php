<?php
namespace Base\Model\Table;

use Cake\ORM\Table;
use DateTimeInterface;

/**
 * @property \Base\Model\Table\AclAroTable $AclAro
 * @property \Base\Model\Table\AclAcoTable $AclAco
 * @property \Base\Model\Table\AclAloTable $AclAlo
 */
class AclItemScheduleTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('acl_item_schedule');
        $this->setPrimaryKey('id');
        $this->belongsTo('Base.AclAro');
        $this->belongsTo('Base.AclAco');
        $this->belongsTo('Base.AclAlo');
        $this->addBehavior('Timestamp');
    }

    public function append($aroId,$acoId,$aloId,int $mask=0,DateTimeInterface $start=null,DateTimeInterface $stop=null){
        if(!is_numeric($aroId)){
            $aroId=$this->AclAro->getIdByName($aroId);
        }

        if(!is_numeric($acoId)){
            $acoId=$this->AclAco->getIdByName($acoId);
        }

        if(!is_numeric($aloId)){
            $aloId=$this->AclAlo->getIdByName($aloId);
        }

        $item = $this->newEntity(
            [
                'acl_aro_id' => $aroId,
                'acl_aco_id' => $acoId,
                'acl_alo_id' => $aloId,
                'mask'=>$mask,
                'start'=>$start,
                'stop'=>$stop
            ]
        );

        return($this->save($item));
    }

    public function copy($srcAroId,$srcAcoId,$trgAroId,$trgAcoId){
        $conditions=[];

        if(!empty($srcAroId)) {
            if (!is_numeric($srcAroId)) {
                $srcAroId = $this->AclAro->getIdByName($srcAroId);
            }

            $conditions['AclItem.acl_aro_id']=$srcAroId;
        }

        if(!empty($srcAcoId)) {
            if (!is_numeric($srcAcoId)) {
                $srcAcoId = $this->AclAco->getIdByName($srcAcoId);
            }

            $conditions['AclItem.acl_aco_id']=$srcAcoId;
        }

        if(empty($conditions)){
            return(true);
        }

        if(!empty($trgAroId)) {
            if (!is_numeric($trgAroId)) {
                $trgAroId = $this->AclAro->getIdByName($trgAroId);
            }
        }

        if(!empty($trgAcoId)) {
            if (!is_numeric($trgAcoId)) {
                $trgAcoId = $this->AclAco->getIdByName($trgAcoId);
            }
        }

        $connection=$this->getConnection();

        return($connection->transactional(function() use ($conditions,$srcAcoId,$trgAroId,$trgAcoId){
            $items=$this->find()->where($conditions)->toArray();

            /** @var \Cake\ORM\Entity $item */
            foreach($items as $item){
                $this->append(
                    !empty($trgAroId)?$trgAroId:$item->get('acl_aro_id'),
                    !empty($trgAcoId)?$trgAcoId:$item->get('acl_aco_id'),
                    $item->get('acl_alo_id'),
                    $item->get('mask'),
                    $item->get('start'),
                    $item->get('stop')
                );
            }
        }));
    }

}

<?php
namespace Base\Model\Table;

use Base\Acl\Model\AclModel;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use ArrayObject;

/**
 * @property \Base\Model\Table\AclAcoLinkTable AclAcoLink
 */
class AclAcoTable extends AclTable {

    public function initialize(array $config):void{
        parent::initialize($config);

        $this->setTable('acl_aco');
        $this->setPrimaryKey('id');
        $this->hasMany('Base.AclAcoLink');
        $this->addBehavior('Timestamp');

        $this->_initializeAcl(AclModel::ACO);
    }

    public function afterSave(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->isNew()){
            $link=$this->AclAcoLink->newEntity(['acl_aco_id'=>$entity->id,'acl_sub_aco_id'=>$entity->id,'item'=>0]);

            if(!$this->AclAcoLink->save($link)){
                return(false);
            }
        }

        return(true);
    }

}

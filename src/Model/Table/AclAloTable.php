<?php
namespace Base\Model\Table;

use Base\Acl\Model\AclModel;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use ArrayObject;

/**
 * @property \Base\Model\Table\AclAloLinkTable $AclAloLink
 */
class AclAloTable extends AclTable {

    public function initialize(array $config):void{
        parent::initialize($config);

        $this->setTable('acl_alo');
        $this->setPrimaryKey('id');
        $this->hasMany('Base.AclAloLink');
        $this->addBehavior('Timestamp');

        $this->_initializeAcl(AclModel::ALO);
    }

    public function afterSave(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->isNew()){
            $link=$this->AclAloLink->newEntity(['acl_alo_id'=>$entity->id,'acl_sub_alo_id'=>$entity->id,'item'=>0]);

            if(!$this->AclAloLink->save($link)){
                return(false);
            }
        }

        return(true);
    }

}

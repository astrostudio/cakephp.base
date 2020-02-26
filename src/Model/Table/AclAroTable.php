<?php
namespace Base\Model\Table;

use Base\Acl\Model\AclModel;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use ArrayObject;

/**
 * @property \Base\Model\Table\AclAroLinkTable $AclAroLink
 */
class AclAroTable extends AclTable {

    public function initialize(array $config):void{
        parent::initialize($config);

        $this->setTable('acl_aro');
        $this->setPrimaryKey('id');
        $this->hasMany('Base.AclAroLink');
        $this->addBehavior('Timestamp');

        $this->_initializeAcl(AclModel::ARO);
    }

    public function afterSave(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->isNew()){
            $link=$this->AclAroLink->newEntity(['acl_aro_id'=>$entity->id,'acl_sub_aro_id'=>$entity->id,'item'=>0]);

            if(!$this->AclAroLink->save($link)){
                return(false);
            }
        }

        return(true);
    }

}

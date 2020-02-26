<?php
namespace Base\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Table;
use ArrayObject;

/**
 * @method appendLink($pred,$succ,$options)
 * @method removeLink($pred,$succ,$options)
 * @method extractLinkRoot($options)
 * @method queryLink($options,$params=[]))
 * @method extractLinkPred($id,$options)
 * @method extractLinkSucc($id,$options)
 * @method extendLinkUp($pred,$succ)
 * @method extendLinkDown($pred,$succ)
 * @method shrinkLinkUp($pred,$succ)
 * @method shrinkLinkDown($pred,$succ)
 */
class AclAcoLinkTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('acl_aco_link');
        $this->setPrimaryKey(['acl_aco_id','acl_sub_aco_id']);
        $this->belongsTo('Base.AclAco');
        $this->belongsTo('AclSubAco',['className'=>'Base.AclAco','foreignKey'=>'acl_sub_aco_id']);
        $this->addBehavior('Base.Link',[
            'pred'=>'acl_aco_id',
            'succ'=>'acl_sub_aco_id',
            'item'=>'item',
            'node'=>'Base.AclAco'
        ]);
        $this->addBehavior('Timestamp');
    }

    public function afterSave(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->get('extend_up')){
            $this->extendLinkUp($entity->get('acl_aco_id'),$entity->get('acl_sub_aco_id'));
        }

        if($entity->get('extend_down')){
            $this->extendLinkDown($entity->get('acl_aco_id'),$entity->get('acl_sub_aco_id'));
        }

        return(true);
    }

    public function beforeDelete(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->get('shrink_up')){
            $this->shrinkLinkUp($entity->get('acl_aco_id'),$entity->get('acl_sub_aco_id'));
        }

        if($entity->get('shrink_down')){
            $this->shrinkLinkDown($entity->get('acl_aco_id'),$entity->get('acl_sub_aco_id'));
        }

        return(true);
    }

}

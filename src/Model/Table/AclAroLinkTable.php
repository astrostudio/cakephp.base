<?php
namespace Base\Model\Table;

use Cake\ORM\Table;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use ArrayObject;


/**
 * @method appendLink($pred,$succ,$options)
 * @method removeLink($pred,$succ)
 * @method extractLinkRoot($options)
 * @method queryLink($options,$params=[]))
 * @method extractLinkPred($id,$options)
 * @method extractLinkSucc($id,$options)
 * @method extendLinkAll()
 * @method extendLinkUp($pred,$succ)
 * @method extendLinkDown($pred,$succ)
 * @method shrinkLinkUp($pred,$succ)
 * @method shrinkLinkDown($pred,$succ)
 */
class AclAroLinkTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('acl_aro_link');
        $this->setPrimaryKey(['acl_aro_id','acl_sub_aro_id']);
        $this->belongsTo('Base.AclAro');
        $this->belongsTo('AclSubAro',['className'=>'Base.AclAro','foreignKey'=>'acl_sub_aro_id']);
        $this->addBehavior('Base.Link',[
            'pred'=>'acl_aro_id',
            'succ'=>'acl_sub_aro_id',
            'item'=>'item',
            'node'=>'Base.AclAro'
        ]);
        $this->addBehavior('Timestamp');
    }

    public function afterSave(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->get('extend_up')){
            $this->extendLinkUp($entity->get('acl_aro_id'),$entity->get('acl_sub_aro_id'));
        }

        if($entity->get('extend_down')){
            $this->extendLinkDown($entity->get('acl_aro_id'),$entity->get('acl_sub_aro_id'));
        }

        return(true);
    }

    public function beforeDelete(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->get('shrink_up')){
            $this->shrinkLinkUp($entity->get('acl_aro_id'),$entity->get('acl_sub_aro_id'));
        }

        if($entity->get('shrink_down')){
            $this->shrinkLinkDown($entity->get('acl_aro_id'),$entity->get('acl_sub_aro_id'));
        }

        return(true);
    }

}

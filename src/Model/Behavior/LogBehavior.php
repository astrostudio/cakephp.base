<?php
namespace Base\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Utility\Hash;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;
use ArrayObject;
use Cake\Log\Log;

class LogBehavior extends Behavior {

    private $__scope=null;

    public function initialize(array $config){
        $this->__scope=Hash::get($config,'scope','model');
    }

    public function afterSaveCommit(Event $event, EntityInterface $entity, ArrayObject $options){
        if($entity->isNew()){
            Log::info('INSERT'."\t".$this->_table->alias()."\t".json_encode($entity),['scope'=>$this->__scope]);
        }
        else {
            Log::info('UPDATE'."\t".$this->_table->alias()."\t".json_encode($entity),['scope'=>$this->__scope]);
        }
    }


    public function afterDeleteCommit(Event $event, EntityInterface $entity, ArrayObject $options){
        Log::info('DELETE'."\t".$this->_table->alias()."\t".json_encode($entity),['scope'=>$this->__scope]);
    }

}

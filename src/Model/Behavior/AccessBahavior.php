<?php
namespace Base\Model\Behavior;

use Base\Model\Access\IAccess;
use Cake\ORM\Behavior;
use Cake\Utility\Hash;
use Cake\Core\App;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Inflector;
use Exception;
use ArrayObject;

class AccessBehavior extends Behavior {

    protected $_accessObject=null;

    public function initialize(array $config){
        $class=Hash::get($config,'class',$this->_table->alias);

        if(!App::className($class,'Model/Access','Access')){
            throw new Exception('Base.AccessBehavior::initialize(): No class '.$class);
        }

        list($plugin,$name)=pluginSplit($class);

        $accessObject=new $name($this->_table);

        if(!($accessObject instanceof IAccess)){
            throw new Exception('Base.AccessBehavior::initialize(): No IAccess object');
        }

        $this->_accessObject=new $name($this->_table);
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary){
        if($this->_accessObject){
            $query=$this->_accessObject->accessFind($query);
        }

        return($query);
    }

    public function beforeSave(Event $event,EntityInterface $entity, ArrayObject $options){
        if($this->_accessObject){
            if(!$this->_accessObject->accessFind($entity,$options)){
                $entity->errors($this->_table->primaryKey(),__d(Inflector::underscore($this->_table->alias()),'_no_access'));

                return(false);
            }
        }

        return(true);
    }

    function beforeDelete(Event $event,EntityInterface $entity, ArrayObject $options){
        if($this->_accessObject){
            if(!$this->_accessObject->accessDelete($entity,$options)){
                $entity->errors($this->_table->primaryKey(),__d(Inflector::underscore($this->_table->alias()),'_no_access'));

                return(false);
            }
        }

        return(true);
    }

}
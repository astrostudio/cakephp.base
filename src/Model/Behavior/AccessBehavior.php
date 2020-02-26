<?php
namespace Base\Model\Behavior;

use Base\Model\Access\AccessInterface;
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

    /** @var \Base\Model\Access\AccessInterface */
    protected $_accessObject=null;

    public function initialize(array $config):void{
        $class=Hash::get($config,'class',$this->_table->getAlias());

        if(!App::className($class,'Model/Access','Access')){
            throw new Exception('Base.AccessBehavior::initialize(): No class '.$class);
        }

        list(,$name)=pluginSplit($class);

        $accessObject=new $name($this->_table);

        if(!($accessObject instanceof AccessInterface)){
            throw new Exception('Base.AccessBehavior::initialize(): No AccessInterface object');
        }

        $this->_accessObject=new $name($this->_table);
    }

    public function beforeFind(/** @noinspection PhpUnusedParameterInspection */ Event $event, Query $query, ArrayObject $options, $primary){
        if($this->_accessObject){
            $query=$this->_accessObject->accessFind($query);
        }

        return($query);
    }

    public function beforeSave(/** @noinspection PhpUnusedParameterInspection */ Event $event,EntityInterface $entity, ArrayObject $options){
        if($this->_accessObject){
            if(!$this->_accessObject->beforeSave($entity,$options)){
                $entity->setErrors($this->_table->getPrimaryKey(),__d(Inflector::underscore($this->_table->getAlias()),'_no_access'));

                return(false);
            }
        }

        return(true);
    }

    function beforeDelete(/** @noinspection PhpUnusedParameterInspection */ Event $event,EntityInterface $entity, ArrayObject $options){
        if($this->_accessObject){
            if(!$this->_accessObject->beforeDelete($entity,$options)){
                $entity->setErrors($this->_table->getPrimaryKey(),__d(Inflector::underscore($this->_table->getAlias()),'_no_access'));

                return(false);
            }
        }

        return(true);
    }

}

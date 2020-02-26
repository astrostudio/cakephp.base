<?php
namespace Base\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Base\Base;

class ListBehavior extends Behavior {

    private $__listField='position';
    private $__listScope=[];

    public function initialize(array $config):void{
        $this->__listField=Hash::get($config,'field','position');
        $this->__listScope=Hash::get($config,'scope',[]);
    }

    private function __scope($values=[]){
        $scope=[];

        foreach($this->__listScope as $field){
            $scope[]=[
                $this->_table->getAlias().'.'.$field=>$values[$field]
            ];
        }

        return($scope);
    }

    private function __max($scope=[]){
        $max=$this->_table
            ->find()
            ->select(['max'=>'MAX('.$this->_table->getAlias().'.'.$this->__listField.')'])
            ->where($scope)
            ->first()
            ->toArray();

        if(isset($max)){
            return($max['max']);
        }

        return(0);
    }

    public function beforeSave(/** @noinspection PhpUnusedParameterInspection */ Event $event,EntityInterface $entity, ArrayObject $options){
        $scope=$this->__scope($entity->extract($this->__listScope));

        if($entity->isNew()) {
            $position = $entity->get($this->__listField);
            $max = $this->__max($scope);

            if (!isset($position)) {
                $position = $max + 1;
            } else {
                if ($position < 1) {
                    $position = 1;
                } else if ($position > $max) {
                    $position = $max + 1;
                }
            }

            $entity->set($this->__listField, $position);
        }
        else {
            $old=$entity->extractOriginalChanged($this->__listScope);

            if(!empty($old)){
                $position=$entity->get($this->__listField);
                $max = $this->__max($scope);

                if (!isset($position)) {
                    $position = $max + 1;
                } else {
                    if ($position < 1) {
                        $position = 1;
                    } else if ($position > $max) {
                        $position = $max + 1;
                    }
                }

                $entity->set($this->__listField, $position);
            }
            else {
                $old=$entity->extractOriginalChanged([$this->__listField]);

                if(!empty($old)){
                    $position=$entity->get($this->__listField);
                    $max = $this->__max($scope);

                    if ($position < 1) {
                        $position = 1;
                    } else if ($position > $max) {
                        $position = $max;
                    }

                    $entity->set($this->__listField, $position);
                }
            }
        }

        return(true);
    }

    public function afterSave(/** @noinspection PhpUnusedParameterInspection */ Event $event,EntityInterface $entity, ArrayObject $options){
        $scope=$this->__scope($entity->extract($this->__listScope));
        $primaryKeyFields=$this->_table->getPrimaryKey();
        $primaryKeyConditions=[];

        foreach($primaryKeyFields as $primaryKeyField){
            $primaryKeyConditions[]=$primaryKeyField.' <> '.$entity->get($primaryKeyField);
        }

        if($entity->isNew()) {
            if(!$this->_table->query()->update()->set(
                new QueryExpression($this->__listField.'='.$this->__listField.' + 1')
            )->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                $this->__listField . ' >=' => $entity->get($this->__listField)
            ])))->execute()) {
                return (false);
            }
        }
        else {
            $old=$entity->extractOriginalChanged($this->__listScope);

            if(!empty($old)){
                $oldScope=$this->__scope($entity->extractOriginal($this->__listScope));
                $old=$entity->extractOriginal([$this->__listField]);
                $oldPosition=$old[$this->__listField];

                if(!$this->_table->query()->update()->set(
                    new QueryExpression($this->__listField.'='.$this->__listField.' - 1')
                )->where(Base::extend($oldScope,[
                    $this->_table->getAlias().'.'.$this->__listField.'>'.$oldPosition
                ]))->execute()){
                    return(false);
                }

                if(!$this->_table->query()->update()->set(
                    new QueryExpression($this->__listField.'='.$this->__listField.' + 1')
                )->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                    $this->_table->getAlias() . '.' . $this->__listField . '>=' . $entity->get($this->__listField)
                ])))->execute()) {
                    return (false);
                }
            }
            else {
                $old=$entity->extractOriginalChanged([$this->__listField]);

                if(!empty($old)) {
                    $oldPosition=$old[$this->__listField];
                    $position=$entity->get($this->__listField);

                    if ($oldPosition > $position) {
                        if (!$this->_table->query()->update()->set(
                            new QueryExpression($this->__listField . ' = ' . $this->__listField . ' + 1')
                        )->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                            $this->_table->getAlias() . '.' . $this->__listField . '>=' . $position,
                            $this->_table->getAlias() . '.' . $this->__listField . '<' . $oldPosition
                        ])))->execute()
                        ) {
                            return (false);
                        }
                    } else {
                        if (!$this->_table->query()->update()->set(new QueryExpression($this->__listField . ' = ' . $this->__listField . ' - 1'))->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                            $this->_table->getAlias() . '.' . $this->__listField . '>' . $oldPosition,
                            $this->_table->getAlias() . '.' . $this->__listField . '<=' . $position
                        ])))->execute()
                        ) {
                            return (false);
                        }
                    }
                }
            }
        }

        return(true);
    }

    public function afterDelete(/** @noinspection PhpUnusedParameterInspection */ Event $event, EntityInterface $entity, ArrayObject $options){
        $position=$entity->get($this->__listField);
        $scope=$this->__scope($entity->extract($this->__listScope));

        if(!$this->_table->query()->update()->set(new QueryExpression($this->__listField.'='.$this->__listField.' - 1'))->where(Base::extend($scope,[
            $this->_table->getAlias().'.'.$this->__listField.'>'.$position
        ]))->execute()){
            return(false);
        }

        return(true);
    }

    public function moveAt($id,$position){
        $entity=$this->_table->get($id);
        $entity->set($this->__listField,$position);

        if(!$this->_table->save($entity)){
            return(false);
        }

        return(true);
    }

}

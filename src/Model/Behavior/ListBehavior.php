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

    private $listField='position';
    private $listScope=[];

    public function initialize(array $config):void{
        $this->listField=Hash::get($config,'field','position');
        $this->listScope=Hash::get($config,'scope',[]);
    }

    private function scope($values=[]){
        $scope=[];

        foreach($this->listScope as $field){
            $scope[]=[
                $this->_table->getAlias().'.'.$field=>$values[$field]
            ];
        }

        return($scope);
    }

    private function max($scope=[]){
        $max=$this->_table
            ->find()
            ->select(['max'=>'MAX('.$this->_table->getAlias().'.'.$this->listField.')'])
            ->where($scope)
            ->first()
            ->toArray();

        if(isset($max)){
            return($max['max']);
        }

        return(0);
    }

    public function beforeSave(/** @noinspection PhpUnusedParameterInspection */ Event $event,EntityInterface $entity, ArrayObject $options){
        $scope=$this->scope($entity->extract($this->listScope));

        if($entity->isNew()) {
            $position = $entity->get($this->listField);
            $max = $this->max($scope);

            if (!isset($position)) {
                $position = $max + 1;
            } else {
                if ($position < 1) {
                    $position = 1;
                } else if ($position > $max) {
                    $position = $max + 1;
                }
            }

            $entity->set($this->listField, $position);
        }
        else {
            $old=$entity->extractOriginalChanged($this->listScope);

            if(!empty($old)){
                $position=$entity->get($this->listField);
                $max = $this->max($scope);

                if (!isset($position)) {
                    $position = $max + 1;
                } else {
                    if ($position < 1) {
                        $position = 1;
                    } else if ($position > $max) {
                        $position = $max + 1;
                    }
                }

                $entity->set($this->listField, $position);
            }
            else {
                $old=$entity->extractOriginalChanged([$this->listField]);

                if(!empty($old)){
                    $position=$entity->get($this->listField);
                    $max = $this->max($scope);

                    if ($position < 1) {
                        $position = 1;
                    } else if ($position > $max) {
                        $position = $max;
                    }

                    $entity->set($this->listField, $position);
                }
            }
        }

        return(true);
    }

    public function afterSave(/** @noinspection PhpUnusedParameterInspection */ Event $event,EntityInterface $entity, ArrayObject $options){
        $scope=$this->scope($entity->extract($this->listScope));
        $primaryKeyFields=$this->_table->getPrimaryKey();
        $primaryKeyConditions=[];

        foreach($primaryKeyFields as $primaryKeyField){
            $primaryKeyConditions[]=$primaryKeyField.' <> '.$entity->get($primaryKeyField);
        }

        if($entity->isNew()) {
            if(!$this->_table->query()->update()->set(
                new QueryExpression($this->listField.'='.$this->listField.' + 1')
            )->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                $this->listField . ' >=' => $entity->get($this->listField)
            ])))->execute()) {
                return (false);
            }
        }
        else {
            $old=$entity->extractOriginalChanged($this->listScope);

            if(!empty($old)){
                $oldScope=$this->scope($entity->extractOriginal($this->listScope));
                $old=$entity->extractOriginal([$this->listField]);
                $oldPosition=$old[$this->listField];

                if(!$this->_table->query()->update()->set(
                    new QueryExpression($this->listField.'='.$this->listField.' - 1')
                )->where(Base::extend($oldScope,[
                    $this->_table->getAlias().'.'.$this->listField.'>'.$oldPosition
                ]))->execute()){
                    return(false);
                }

                if(!$this->_table->query()->update()->set(
                    new QueryExpression($this->listField.'='.$this->listField.' + 1')
                )->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                    $this->_table->getAlias() . '.' . $this->listField . '>=' . $entity->get($this->listField)
                ])))->execute()) {
                    return (false);
                }
            }
            else {
                $old=$entity->extractOriginalChanged([$this->listField]);

                if(!empty($old)) {
                    $oldPosition=$old[$this->listField];
                    $position=$entity->get($this->listField);

                    if ($oldPosition > $position) {
                        if (!$this->_table->query()->update()->set(
                            new QueryExpression($this->listField . ' = ' . $this->listField . ' + 1')
                        )->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                            $this->_table->getAlias() . '.' . $this->listField . '>=' . $position,
                            $this->_table->getAlias() . '.' . $this->listField . '<' . $oldPosition
                        ])))->execute()
                        ) {
                            return (false);
                        }
                    } else {
                        if (!$this->_table->query()->update()->set(new QueryExpression($this->listField . ' = ' . $this->listField . ' - 1'))->where(Base::extend($scope, array_merge($primaryKeyConditions,[
                            $this->_table->getAlias() . '.' . $this->listField . '>' . $oldPosition,
                            $this->_table->getAlias() . '.' . $this->listField . '<=' . $position
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
        $position=$entity->get($this->listField);
        $scope=$this->scope($entity->extract($this->listScope));

        if(!$this->_table->query()->update()->set(new QueryExpression($this->listField.'='.$this->listField.' - 1'))->where(Base::extend($scope,[
            $this->_table->getAlias().'.'.$this->listField.'>'.$position
        ]))->execute()){
            return(false);
        }

        return(true);
    }

    public function moveAt($id,$position){
        $entity=$this->_table->get($id);
        $entity->set($this->listField,$position);

        if(!$this->_table->save($entity)){
            return(false);
        }

        return(true);
    }

}

<?php
class BaseListBehavior extends ModelBehavior {

    var $settings= [];
    
    public function setup(Model $Model,$settings= []) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=[
                'score'=>[],
                'field'=>'position'
            ];
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);
        
        $Model->__listField=$this->settings[$Model->alias]['field'];
        $Model->__listScope=$this->settings[$Model->alias]['scope'];
    }

    private function __scopeValue(Model $Model,$data){
        $data=[];

        foreach($Model->__listScope as $field){
            $data[$field]=Hash::get($data,$Model->alias.'.'.$field);
        }

        return($data);
    }

    private function __scopeSame(Model $Model,$newScope,$oldScope){
        foreach($Model->__listScope as $field){
            if(isset($newScope[$field])){
                if($newScope[$field]!=$oldScope[$field]){
                    return(false);
                }
            }
        }

        return(true);
    }

    private function __scope(Model $Model,$values=[]){
        $scope=[];

        foreach($Model->__listScope as $field){
            $scope[]=[
                $Model->alias.'.'.$field=>$values[$field]
            ];
        }

        return($scope);
    }

    private function __max(Model $Model,$scope=[]){
        $max=$Model->find('first',[
            'recursive'=>-1,
            'conditions'=>$scope,
            'fields'=>['MAX('.$Model->alias.'.'.$Model->__listField.') as max']
        ]);

        if(isset($max)){
            return($max['max']);
        }

        return(0);
    }

    public function beforeSave(Model $Model,$options=[]){
        $scope=$this->__scope($Model,$Model->__listScope);

        if(empty($Model->data[$Model->alias][$Model->primaryKey])){
            $position=Hash::get($Model->data,$Model->alias.$Model->__ListField);
            $max = $this->__max($Model,$scope);

            if (!isset($position)) {
                $position = $max + 1;
            } else {
                if ($position < 1) {
                    $position = 1;
                } else if ($position > $max) {
                    $position = $max + 1;
                }
            }

            $Model->data[$Model->alias][$Model->__listField]=$position;
        }
        else {
            $Model->__listData=$Model->find('first',[
                'recursive'=>-1,
                'conditions'=>[
                    $Model->alias.'.'.$Model->primaryKey=>$Model->data[$Model->alias][$Model->primaryKey]
                ]
            ]);

            $oldScope=$this->__scopeValue($Model,$Model->__listData);
            $newScope=$this->__scopeValue($Model,$Model->data);

            if(!$this->__scopeSame($Model,$newScope,$oldScope)){
                $position=Hash::get($Model->data,$Model->alias.'.'.$Model->__listField);
                $max = $this->__max($Model,$newScope);

                if (!isset($position)) {
                    $position = $max + 1;
                } else {
                    if ($position < 1) {
                        $position = 1;
                    } else if ($position > $max) {
                        $position = $max + 1;
                    }
                }

                $Model->data[$Model->alias][$Model->__listField]=$position;
            }
            else {
                $oldPosition=$Model->__listData[$Model->alias][$Model->__listField];
                $position=Hash::get($Model->data,$Model->alias.'.'.$Model->__listField,$oldPosition);

                if($position!=$oldPosition){
                    $max = $this->__max($Model,$scope);

                    if ($position < 1) {
                        $position = 1;
                    } else if ($position > $max) {
                        $position = $max;
                    }

                    $Model->data[$Model->alias][$Model->__listField]=$position;
                }
            }
        }

        return(true);
    }

    public function afterSave(Model $Model,$created,$options=[]){
        $scope=$this->__scope($Model,$Model->data);

        if($created) {
            if(!$Model->updateAll([
                $Model->alias.'.'.$Model->__listField=>$Model->alias.'.'.$Model->__ListField.'+1'
            ],Base::extend($scope,[
                $Model->alias.'.'.$Model->primaryKey.'<>'.$Model->id,
                $Model->alias.'.'.$Model->__listField.'>='.$Model->data[$Model->alias][$Model->__listField]
            ]))){
                return(false);
            }
        }
        else {
            $oldScope=$this->__scopeValue($Model,$Model->__listData);
            $newScope=$this->__scopeValue($Model,$Model->data);

            if(!$this->__scopeSame($Model,$newScope,$oldScope)){
                $oldScopeCond=$this->__scope($Model,$Model->__listData);
                $oldPosition=$Model->__listData[$Model->alias][$Model->__listFIeld];

                if(!$Model->updateAll([
                    $Model->alias.'.'.$Model->__listField=>$Model->alias.'.'.$Model->__listField.'-1'
                ],Base::extend($oldScope,[
                    $Model->alias.'.'.$Model->__listField.'>'.$oldPosition
                ]))){
                    return(false);
                }

                if($Model->updateAll([
                    $Model->alias.'.'.$Model->__listField=>$Model->alias.'.'.$Model->__listField.'+1'
                ],Base::extend($scope,[
                    $Model->alias.'.'.$Model->primaryKey.'<>'.$Model->id,
                    $Model->alias.'.'.$Model->__listField.'>='.$Model->data[$Model->alias][$Model->__listField]
                ]))){
                    return(false);
                }
            }
            else {
                $oldPosition=$Model->__listData[$Model->alias][$Model->__listField];
                $position=Hash::get($Model->data,$Model->alias.'.'.$Model->__listField,$oldPosition);

                if($position!=$oldPosition) {
                    if ($oldPosition > $position) {
                        if(!$Model->updateAll([
                            $Model->alias.'.'.$Model->__listField=>$Model->alias.'.'.$Model->__listField.'+1'
                        ],Base::extend($scope,[
                            $Model->alias.'.'.$Model->primaryKey.'<>'.$Model->id,
                            $Model->alias.'.'.$Model->__listField.'>='.$position,
                            $Model->alias.'.'.$Model->__listField.'<'.$oldPosition
                        ]))){
                            return(false);
                        }
                    } else {
                        if(!$Model->updateAll([
                            $Model->alias.'.'.$Model->__listField=>$Model->alias.'.'.$Model->__listField.'-1'
                        ],Base::extend($scope,[
                            $Model->alias.'.'.$Model->primaryKey.'<>'.$Model->id,
                            $Model->alias.'.'.$Model->__listField.'>'.$oldPosition,
                            $Model->alias.'.'.$Model->__listField.'<='.$position
                        ]))){
                            return(false);
                        }
                    }
                }
            }
        }

        return(true);
    }

    public function beforeDelete(Model $Model,$options=[]){
        $Model->__listData=$Model->find('first',[
            'recursive'=>-1,
            'conditions'=>[
                $Model->alias.'.'.$Model->primaryKey=>$Model->id
            ]
        ]);

        return(true);
    }

    public function afterDelete(Model $Model){
        $position=$Model->__listData[$Model->alias][$Model->__listField];
        $scope=$this->__scope($Model,$Model->__listData);

        if(!$Model->updateAll([
            $Model->alias.'.'.$Model->__listField=>$Model->alias.'.'.$Model->__listField.'-1'
        ],Base::extend($scope,[
            $Model->alias.'.'.$Model->__listField.'>'.$position
        ]))){
            return(false);
        }

        return(true);
    }

    public function moveAt(Model $Model,$id,$position){
        $data=$Model->read(null,$id);
        $data[$Model->alias][$Model->__listField]=$position;

        if(!$Model->save($data)){
            return(false);
        }

        return(true);
    }
}

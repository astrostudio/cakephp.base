<?php
App::uses('Base','Vendor/Base');

class BaseAccessBehavior extends ModelBehavior {

    var $settings=[];
    
    public function setup(Model $Model,$settings=[]) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=[
                'accessClassName'=>$Model->alias.'Access'
            ];
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);

        $className=Hash::get($this->settings[$Model->alias],'accessClassName');

        if(!empty($className)){
            list($plugin,$name)=pluginSplit($className);

            App::uses($name,(!empty($plugin)?($plugin.'.'):'').'Model/Access');

            if(class_exists($name)){
                $Model->__baseAccess=new $name($Model);
            }
        }
    }

    public function beforeFind(Model $Model,$query){
        if(isset($Model->__baseAccess)){
            $query=$Model->__baseAccess->accessFind($query);
        }

        return($query);
    }

    public function beforeSave(Model $Model,$options=[]){
        if(isset($Model->__baseAccess)){
            if(!$Model->__baseAccess->accessSave($Model->data)){
                $Model->invalidate($Model->primaryKey,__d(Inflector::underscore($Model->alias),'_no_access'));

                return(false);
            }
        }

        return(true);
    }

    public function beforeDelete(Model $Model,$cascade=true){
        if(isset($Model->__baseAccess)){
            if(!$Model->__baseAccess->accessDelete($Model->data)){
                $Model->invalidate($Model->primaryKey,__d(Inflector::underscore($Model->alias),'_no_access'));

                return(false);
            }
        }

        return(true);
    }

}

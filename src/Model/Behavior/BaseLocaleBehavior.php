<?php
namespace Base\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\I18n\I18n;
use Cake\ORM\TableRegistry;

class BaseLocaleBehavior extends Behavior {

    protected $_localeTable=null;
    protected $_locale=null;

    public function initialize(array $config){
        $this->_config=array_merge($this->_config,[
            'alias'=>$this->_table->alias().'Locale',
            'fields'=>[],
            'locale'=>'locale',
            'suffix'=>'_locale'
        ],$config);

        $this->_localeTable=TableRegistry::get($this->_config['alias']);
    }

    public function beforeFind(Event $event, Query $query, $options){
        $locale=!empty($options['locale'])?$options['locale']:$this->locale();
        $where=[$this->_localeTable->aliasField($this->_config['locale'])=>$locale];
        $fields=[];

        foreach($this->_config['fields'] as $field){
            $localeField=$this->_localeTable->aliasField($field);

            $fields[$field.$this->_config['suffix']]='IF(LENGTH('.$localeField.')>0,'.$localeField.','.$this->_table->aliasField($field).')';
        }

        $query=$query->leftJoinWith($this->_config['alias'],function(Query $q) use ($where,$fields){
            return($q->where($where)->select($fields));
        });

        return($query);
    }

    public function localeField($field){
        if(in_array($field,$this->_config['fields'])){
            return($field.$this->_config['suffix']);
        }

        return(null);
    }

    public function locale($locale=null){
        if($locale===null){
            return(!empty($this->_locale)?$this->_locale:I18n::locale());
        }

        $this->_locale=$locale;
    }

    /*
    public function findLocale(Query $query,array $options){
        $locale=!empty($options['locale'])?$options['locale']:$this->locale();
        $where=[$this->_localeTable->aliasField($this->_config['locale'])=>$locale];
        $fields=[];

        foreach($this->_config['fields'] as $field){
            $localeField=$this->_localeTable->aliasField($field);

            $fields[$field.$this->_config['suffix']]='IF(LENGTH('.$localeField.')>0,'.$localeField.','.$this->_table->aliasField($field).')';
        }

        $query=$query->leftJoinWith($this->_config['alias'],function(Query $q) use ($where,$fields){
            return($q->where($where)->select($fields));
        });

        return($query);
    }
    */
}

<?php
namespace Base\Model\Behavior;

use Cake\Collection\CollectionInterface;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\I18n\I18n;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class LocaleBehavior extends Behavior {

    /** @var \Cake\ORM\Table */
    protected $_localeTable=null;
    protected $_locale=null;

    public function initialize(array $config):void{
        $this->_config=array_merge($this->_config,[
            'alias'=>$this->_table->getAlias().'Locale',
            'fields'=>[],
            'locale'=>'locale',
            'suffix'=>'_locale'
        ],$config);

        $this->_localeTable=TableRegistry::getTableLocator()->get($this->_config['alias']);
    }

    public function beforeFind(/** @noinspection PhpUnusedParameterInspection */ Event $event, Query $query, $options){
        $locale=!empty($options['locale'])?$options['locale']:$this->locale();
        $where=[$this->_localeTable->aliasField($this->_config['locale'])=>$locale];
        $fields=[];

        foreach($this->_config['fields'] as $field){
           $fields[$this->_table->getAlias().'__'.$field.$this->_config['suffix']]=$this->getLocaleColumn($field);
        }

        $query=$query->leftJoinWith($this->_config['alias'],function(Query $q) use ($where,$fields){
            return($q->where($where)->select($fields));
        });

        return($query);
    }

    public function findLocale(Query $query,array $options=[]):Query
    {
        if(empty($options['locales'])){
            return($query);
        }

        return($query->contain([$this->getLocaleAlias()=>function(Query $q) use ($options){
            return($q->where([
                $this->getLocaleAlias().'.'.$this->getLocaleName().' IN'=>$options['locales']
            ]));
        }])->formatResults(function (CollectionInterface $results) {
            $field = Inflector::underscore($this->getLocaleAlias());

            return ($results->map(
                function ($row) use ($field) {
                    $locales=$row[$field]??[];
                    $list=[];

                    foreach($locales as $locale){
                        $list[$locale->get($this->getLocaleName())]=$locale;
                    }

                    $row[$field]=$list;

                    return $row;
                }
            ));
        }));
    }

    public function getLocaleColumn(string $field){
        $localeField=$this->_localeTable->aliasField($field);

        return('IF(LENGTH('.$localeField.')>0,'.$localeField.','.$this->_table->aliasField($field).')');
    }

    public function getLocaleAlias():string
    {
        return($this->_config['alias']);
    }

    public function getLocaleFields():array
    {
        return($this->_config['fields']);
    }

    public function getLocaleSuffix():string
    {
        return($this->_config['suffix']);
    }

    public function getLocaleName():string
    {
        return($this->_config['locale']);
    }

    public function localeField($field){
        if(in_array($field,$this->_config['fields'])){
            return($field.$this->_config['suffix']);
        }

        return(null);
    }

    public function locale($locale=null){
        if($locale===null){
            return(!empty($this->_locale)?$this->_locale:I18n::getLocale());
        }

        $this->_locale=$locale;

        return($this->_locale);
    }
}

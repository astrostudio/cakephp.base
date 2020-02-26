<?php
namespace Base\Acl\Model;

use Base\Model\Entity\AclEntity;
use Base\Model\Table\AclTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class BasicAclModel extends BaseAclModel
{
    static private function template(string $template){
        preg_match_all('/\{\{[^{}]*\}\}/',$template,$matches,PREG_OFFSET_CAPTURE);

        $templates=[];

        if(!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $exists=false;

                foreach($templates as $t){
                    if($t['template']==$match[0]){
                        $exists=true;

                        break;
                    }
                }

                if(!$exists) {
                    $templates[] = [
                        'template' => $match[0],
                        'name'     => mb_substr($match[0], 2, mb_strlen($match[0]) - 4),
                        'offset'   => $match[1]
                    ];
                }
            }
        }

        return($templates);
    }

    static private function getProperty(string $path,AclEntity $entity,string $delimiter='.'){
        $items=explode($delimiter,$path);
        $e=$entity;

        while(!empty($items)){
            if($e instanceof Entity){
                $e=$e->get($items[0]);

                if(!isset($e)){
                    return(null);
                }
            }

            array_shift($items);
        }

        return($e);
    }

    static private function render(string $template,AclEntity $entity){
        $templates=self::template($template);

        foreach($templates as $t){
            $v=self::getProperty($t['name'],$entity);

            if(!isset($v)) {
                return (null);
            }

            $template = str_replace($t['template'], $v, $template);
        }

        return($template);
    }

    const DEFAULT_OPTIONS=[
        'foreignKey'=>'id',
        'dependent'=>true,
        'cascadeCallbacks'=>true
    ];

    private $alias;
    private $options;

    public function __construct(string $alias,array $options=[]){
        $this->alias=$alias;
        $this->options=$options;
    }

    public function initialize(AclTable $table){
        parent::initialize($table);

        if(isset($this->options['initialize']) and is_callable($this->options['initialize'])){
            call_user_func($this->options['initialize'],$table);

            return;
        }

        $options=array_merge(self::DEFAULT_OPTIONS,$this->options['options']??[]);

        $table->hasOne($this->alias, $options);
    }

    public function find(Query $query):Query
    {
        $query=parent::find($query);

        if(isset($this->options['find']) and is_callable($this->options['find'])){
            return(call_user_func($this->options['find'],$query));
        }

        if(!empty($this->options['contain'])){
            $query=$query->contain($this->options['contain']);
        }

        return($query);
    }

    public function check(AclEntity $entity):bool
    {
        if(isset($this->options['check']) and is_callable($this->options['check'])){
            return(call_user_func($this->options['check'],$entity));
        }

        $property=!empty($this->options['property'])?$this->options['property']:Inflector::underscore($this->alias);

        return(!empty($entity->get($property)));
    }

    public function model(AclEntity $entity):?string
    {
        if(isset($this->options['model'])){
            if(is_callable($this->options['model'])) {
                return (call_user_func($this->options['model'], $entity));
            }

            return($this->options['model']);
        }

        return($this->alias);
    }

    public function label(AclEntity $entity):?string
    {
        if(isset($this->options['label'])){
            if(is_callable($this->options['label'])) {
                return (call_user_func($this->options['label'], $entity));
            }

            return(self::render($this->options['label'],$entity));
        }

        $properties=$entity->visibleProperties();

        foreach($properties as $property){
            return($entity->get($property));
        }

        return (null);
    }

    public function contain():array
    {
        return($this->options['contain']??[]);
    }

    public function filter():array{
        return($this->options['filter']??[]);
    }

    public function search():array{
        return($this->options['search']??[]);
    }

    public function sorter():array{
        return($this->options['sorter']??[]);
    }

}
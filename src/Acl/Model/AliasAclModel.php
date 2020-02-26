<?php
namespace Base\Acl\Model;

use Cake\Utility\Inflector;

class AliasAclModel extends BasicAclModel
{
    public function __construct(string $alias,string $field,array $fields=[],array $search=[],array $options=[]){
        $property=Inflector::underscore($alias);

        foreach($search as &$s){
            $s=$alias.'.'.$s;
        }

        $options=array_merge([
            'label'=>'{{'.$property.'.'.$field.'}}',
            'contain'=>[
                $alias=>[
                    'fields'=>$fields
                ]
            ]
        ],$options,[
            'search'=>array_merge($options['serach']??[],$search)
        ]);

        parent::__construct($alias,$options);
    }
}
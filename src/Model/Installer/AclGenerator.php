<?php
namespace Base\Model\Installer;

use Cake\Utility\Inflector;

class AclGenerator extends BasicGenerator
{
    public function __construct(string $type,string $name=null){
        $alias=Inflector::humanize($type);
        $items=[];
        $key=uniqid($type);
        $items[$key]=[
            self::ALIAS=>'Base.Acl'.$alias,
            self::DATA=>[
                'name'=>$name
            ]
        ];

        parent::__construct($items);
    }
}
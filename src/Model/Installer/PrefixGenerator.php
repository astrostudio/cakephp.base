<?php
namespace Base\Model\Installer;

class PrefixGenerator extends ProxyGenerator
{
    private $prefix;

    public function __construct(string $prefix='',GeneratorInterface $generator=null){
        parent::__construct($generator);

        $this->prefix=$prefix;
    }

    public function generate(InstallerInterface $installer,array $options=[]):array
    {
        $subItems=parent::generate($installer,$options);
        $items=[];

        foreach($subItems as $key=>$item){
            if(is_int($key)){
                $items[]=$item;
            }
            else {
                $items[$this->prefix.$key]=$item;
            }
        }

        return($items);
    }
}
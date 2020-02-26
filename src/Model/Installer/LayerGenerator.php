<?php
namespace Base\Model\Installer;

use Base\Base;

class LayerGenerator implements GeneratorInterface
{
    private $generators=[];

    public function __construct(array $generators=[]){
        $this->setGenerator($generators);
    }

    public function generate(InstallerInterface $installer,array $options=[]):array
    {
        $items=[];

        foreach($this->generators as $key=>$generator){
            $subItems=$generator->generate($installer,$options);

            $items=array_merge($items,$subItems);
        }

        return($items);
    }

    public function setGenerator($name,GeneratorInterface $generator=null,int $offset=null){
        if(is_array($name)){
            foreach($name as $n=>$g){
                $this->setGenerator($n,$g);
            }

            return;
        }

        if($generator){
            $this->generators=Base::insert($this->generators,$name,$generator,$offset);
        }
        else {
            unset($this->generators[$name]);
        }
    }
}
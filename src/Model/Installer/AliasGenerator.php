<?php
namespace Base\Model\Installer;

class AliasGenerator extends BasicGenerator
{
    public function __construct(string $alias,array $dataList=[]){
        $items=[];

        foreach($dataList as $key=>$data){
            if(is_int($key)){
                $items[]=['alias'=>$alias,'data'=>$data];
            }
            else {
                $items[$key]=['alias'=>$alias,'data'=>$data];
            }
        }

        parent::__construct($items);
    }
}
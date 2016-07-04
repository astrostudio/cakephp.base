<?php
App::uses('IBaseFactory','Vendor/Base');

class BaseFactory implements IBaseFactory {
    public function __construct($class,$location){
        $this->class=$class;
        $this->location=$location;
    }

    public function get($options=[]){
        App::uses($this->class,$this->location);

        $reflection=new ReflectionClass($this->class);
        $instance=$reflection->newInstanceArgs(array_splice(func_get_args(),1));

        return($instance);
    }
}
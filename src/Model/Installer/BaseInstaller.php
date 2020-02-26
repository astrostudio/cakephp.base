<?php
namespace Base\Model\Installer;

abstract class BaseInstaller implements InstallerInterface
{
    private $values=[];

    public function keys():array
    {
        return(array_keys($this->values));
    }

    public function has(string $key):bool
    {
        return(array_key_exists($key,$this->values));
    }

    public function get(string $key){
        return($this->values[$key]??null);
    }

    public function set(string $key,$value){
        $this->values[$key]=$value;
    }

    public function remove(string $key){
        unset($this->values[$key]);
    }

}
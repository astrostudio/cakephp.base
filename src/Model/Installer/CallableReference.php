<?php
namespace Base\Model\Installer;

class CallableReference implements ReferenceInterface
{
    private $callable;

    public function __construct(callable $callable){
        $this->callable=$callable;
    }

    public function getValue(InstallerInterface $installer){
        return(call_user_func($this->callable,$installer,$this));
    }

}
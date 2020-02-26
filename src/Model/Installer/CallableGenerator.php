<?php
namespace Base\Model\Installer;

class CallableGenerator implements GeneratorInterface
{
    private $callable;

    public function __construct(callable $callable){
        $this->callable=$callable;
    }

    public function generate(InstallerInterface $installer,array $options=[]):array{
        return(call_user_func($this->callable,$installer,$options));
    }
}
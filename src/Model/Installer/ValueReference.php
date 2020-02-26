<?php
namespace Base\Model\Installer;

class ValueReference implements ReferenceInterface
{
    private $name;

    public function __construct(string $name){
        $this->name=$name;
    }

    public function getValue(InstallerInterface $installer){
        return($installer->get($this->name));
    }
}
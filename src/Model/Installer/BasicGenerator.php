<?php
namespace Base\Model\Installer;

class BasicGenerator implements GeneratorInterface
{
    private $items;

    public function __construct(array $items=[]){
        $this->items=$items;
    }

    public function generate(InstallerInterface $installer,array $options=[]):array{
        return($this->items);
    }
}
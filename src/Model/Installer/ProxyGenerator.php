<?php
namespace Base\Model\Installer;

class ProxyGenerator implements GeneratorInterface
{
    private $generator;

    public function __construct(GeneratorInterface $generator=null){
        $this->generator=$generator;
    }

    public function generate(InstallerInterface $installer,array $options=[]):array
    {
        return($this->generator?$this->generator->generate($installer,$options):[]);
    }
}
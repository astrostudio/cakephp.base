<?php
namespace Base\Model\Installer;

interface ComponentInterface
{
    function install(InstallerInterface $installer):bool;
}


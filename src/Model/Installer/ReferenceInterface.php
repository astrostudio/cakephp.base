<?php
namespace Base\Model\Installer;

interface ReferenceInterface
{
    function getValue(InstallerInterface $installer);
}
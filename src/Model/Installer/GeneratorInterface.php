<?php
namespace Base\Model\Installer;

interface GeneratorInterface
{
    const ALIAS='alias';
    const DATA='data';
    const OPTIONS='options';

    function generate(InstallerInterface $installer,array $options=[]):array;
}

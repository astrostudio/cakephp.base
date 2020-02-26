<?php
namespace Base\Model\Installer;

interface InstallerInterface
{
    function keys():array;
    function has(string $key):bool;
    function get(string $key);
    function set(string $key,$value);
    function remove(string $key);
    function install(GeneratorInterface $generator,array $options=[]):bool;

}

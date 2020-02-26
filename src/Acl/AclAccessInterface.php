<?php
namespace Base\Acl;

interface AclAccessInterface
{
    function check($aclAro,$aclAco,$aclAlo):bool;
}

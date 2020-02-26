<?php
namespace Base\Acl;

interface AclEditorInterface
{
    function append($aclAro,$aclAco,$aclAlo):bool;
    function remove($aclAro,$aclAco,$aclAlo):bool;
}

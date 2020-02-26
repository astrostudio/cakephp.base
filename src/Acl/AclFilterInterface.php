<?php
namespace Base\Acl;

use Cake\ORM\Query;

interface AclFilterInterface
{
    function filter(Query $query,$aclAro,$aclAlo):Query;
}

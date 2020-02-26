<?php
namespace Base\Acl\Model;

use Base\Model\Entity\AclEntity;
use Base\Model\Table\AclTable;
use Cake\ORM\Query;

interface AclModelInterface
{
    function initialize(AclTable $aclTable);
    function find(Query $query):Query;
    function check(AclEntity $entity):bool;
    function model(AclEntity $entity);
    function label(AclEntity $entity);
    function contain():array;
    function search():array;
    function filter():array;
    function sorter():array;
}


<?php
namespace Base\Acl\Model;

use Base\Model\Entity\AclEntity;
use Base\Model\Table\AclTable;
use Cake\ORM\Query;

abstract class BaseAclModel implements AclModelInterface
{
    public function initialize(AclTable $table){
    }

    public function find(Query $query):Query
    {
        return($query);
    }

    public function check(AclEntity $entity):bool{
        return(false);
    }

    public function model(AclEntity $entity){
        return(null);
    }

    public function label(AclEntity $entity){
        return(null);
    }

    public function contain():array
    {
        return([]);
    }

    public function filter():array
    {
        return([]);
    }

    public function search():array
    {
        return([]);
    }

    public function sorter():array
    {
        return([]);
    }
}
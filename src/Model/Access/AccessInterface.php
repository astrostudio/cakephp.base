<?php
namespace Base\Model\Access;

use Cake\ORM\Query;
use Cake\Datasource\EntityInterface;
use ArrayObject;

interface AccessInterface {
    function accessFind(Query $query);
    function beforeSave(EntityInterface $entity, ArrayObject $options);
    function beforeDelete(EntityInterface $entity, ArrayObject $options);
}
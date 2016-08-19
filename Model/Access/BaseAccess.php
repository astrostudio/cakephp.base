<?php
namespace Base\Model\Access;

use Base\Model\Access\IBaseAccess;
use Cake\ORM\Query;
use Cake\Datasource\EntityInterface;
use ArrayObject;
use Cake\ORM\Table;

class BaseAccess implements IBaseAccess {

    protected $_table=null;

    public function __construct(Table $table){
        $this->_table=$table;
    }

    public function accessFind(Query $query){
        return($query);
    }

    function beforeSave(EntityInterface $entity, ArrayObject $options){
        return(true);
    }

    function beforeDelete(EntityInterface $entity, ArrayObject $options){
        return(true);
    }

}
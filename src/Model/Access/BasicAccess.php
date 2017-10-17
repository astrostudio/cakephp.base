<?php
namespace Base\Model\Access;

use Cake\ORM\Query;
use Cake\Datasource\EntityInterface;
use ArrayObject;
use Cake\ORM\Table;

class BasicAccess implements IAccess {

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
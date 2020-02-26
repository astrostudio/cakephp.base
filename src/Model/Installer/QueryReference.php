<?php
namespace Base\Model\Installer;

use Cake\ORM\Query;
use Exception;

class QueryReference implements ReferenceInterface
{
    private $query;
    private $field;

    public function __construct(Query $query,string $field){
        $this->query=$query;
        $this->field=$field;
    }

    public function getValue(InstallerInterface $installer){
        $entity=$this->query->first();

        if(!$entity){
            throw new Exception('QueryReference::getValue(): No entity');
        }

        return($entity->get($this->field));
    }
}
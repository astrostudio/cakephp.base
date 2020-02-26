<?php
namespace Base\Model\Installer;

use Cake\Datasource\EntityInterface;
use Exception;

class EntityReference implements ReferenceInterface
{
    private $name;
    private $field;

    public function __construct(string $name,string $field){
        $this->name=$name;
        $this->field=$field;
    }

    public function getValue(InstallerInterface $installer){
        $entity=$installer->get($this->name);

        if(!($entity instanceof EntityInterface)){
            throw new Exception('EntityReference::getValue(): No entity "'.$this->name.'"');
        }

        return($entity->get($this->field));
    }
}
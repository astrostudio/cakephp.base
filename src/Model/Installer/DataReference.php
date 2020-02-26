<?php
namespace Base\Model\Installer;

use Cake\ORM\TableRegistry;
use Exception;

class DataReference implements ReferenceInterface
{
    private $alias;
    private $key;
    private $field;

    public function __construct(string $alias,$key,string $field=null){
        $this->alias=$alias;
        $this->key=$key;
        $this->field=$field;
    }

    public function getValue(InstallerInterface $installer)
    {
        $table=TableRegistry::getTableLocator()->get($this->alias);

        if(!$table){
            throw new Exception('Base\\Model\\Installer\\DataReference: No table for alias: '.$this->alias);
        }

        $entity=$table->get($this->key);

        if(!$entity){
            throw new Exception('Base\\Model\\Installer\\DataReference: No entity for key: '.json_encode($this->alias));
        }

        return($entity->get($this->field));
    }

}
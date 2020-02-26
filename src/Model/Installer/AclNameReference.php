<?php
namespace Base\Model\Installer;

use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class AclNameReference implements ReferenceInterface
{
    private $type;
    private $alias;
    private $name;

    public function __construct(string $type,string $name){
        $this->type=$type;
        $this->alias=Inflector::humanize($type);
        $this->name=$name;
    }

    public function getValue(InstallerInterface $installer)
    {
        /** @var \Base\Model\Table\AclTable $table */
        $table=TableRegistry::getTableLocator()->get('Base.Acl'.$this->alias);

        $id=$table->getIdByName($this->name);

        return($id);
    }
}
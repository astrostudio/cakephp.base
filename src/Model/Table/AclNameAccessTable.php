<?php
namespace Base\Model\Table;

use Cake\ORM\Table;

/**
 * @property \Base\Model\Table\AclAroTable $AclAro
 * @property \Base\Model\Table\AclAcoTable $AclAco
 * @property \Base\Model\Table\AclAloTable $AclAlo
 */
class AclNameAccessTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('acl_name_access');
        $this->setPrimaryKey(false);
        $this->belongsTo('Base.AclAro');
        $this->belongsTo('Base.AclAco');
        $this->belongsTo('Base.AclAlo');
    }

}

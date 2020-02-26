<?php
namespace Base\Model\Entity;

use Base\Acl\Model\AclModel;

class AclAro extends AclEntity {

    public function __construct(array $properties=[],array $options=[]){
        parent::__construct(AclModel::ARO,$properties,$options);
    }

}

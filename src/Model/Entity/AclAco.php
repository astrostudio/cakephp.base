<?php
namespace Base\Model\Entity;

use Base\Acl\Model\AclModel;

class AclAco extends AclEntity {

    public function __construct(array $properties=[],array $options=[]){
        parent::__construct(AclModel::ACO,$properties,$options);
    }

}
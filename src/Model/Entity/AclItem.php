<?php
namespace Base\Model\Entity;

use Base\Acl\Model\AclModel;
use Cake\ORM\Entity;

class AclItem extends Entity {

    protected $_virtual=['acl_mask_info'];

    protected function _getAclMaskInfo(){
        return(AclModel::mask($this->acl_alo_id,$this->mask));
    }
}
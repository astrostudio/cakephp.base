<?php
namespace Base\Acl\Access;

use Base\Acl\AclAccessInterface;

class ProxyAclAccess extends BaseAclAccess
{
    protected $_access;
    protected $_value;

    public function __construct(AclAccessInterface $access=null,bool $value=false){
        $this->_access=$access;
    }

    public function check($aclAro,$aclAco,$aclAlo):bool
    {
        return($this->_access?$this->_access->check($aclAro,$aclAco,$aclAlo):$this->_value);
    }
}

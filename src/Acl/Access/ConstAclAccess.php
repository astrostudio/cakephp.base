<?php
namespace Base\Acl\Access;

class ConstAclAccess extends BaseAclAccess
{
    protected $_value;

    public function __construct(bool $value=false){
        $this->_value=$value;
    }

    public function check($aclAro,$aclAco,$aclAlo):bool
    {
        return($this->_value);
    }
}

<?php
namespace Base\Acl\Access;

use Base\Acl\AclAccessInterface;
use Base\Base;

class LayerAclAccess extends BaseAclAccess
{
    protected $_accesses=[];

    public function __construct(array $accesses=[]){
        $this->setAccess($accesses);
    }

    public function check($aclAro,$aclAco,$aclAlo):bool
    {
        foreach($this->_accesses as $access){
            if($access->check($aclAro,$aclAco,$aclAlo)){
                return(true);
            }
        }

        return(false);
    }

    public function getAccess(string $name):?AclAccessInterface
    {
        return($this->_accesses[$name]??null);
    }

    public function setAccess($name,AclAccessInterface $access=null,int $offset=null){
        if(is_array($name)){
            foreach($name as $n=>$a){
                $this->setAccess($n,$a);
            }

            return;
        }

        unset($this->_accesses[$name]);

        if($access){
            $this->_accesses=Base::insert($this->_accesses,$name,$access,$offset);
        }
    }
}

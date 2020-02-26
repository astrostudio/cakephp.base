<?php
namespace Base\Model\Entity;

use Base\Acl\Model\AclModel;
use Cake\ORM\Entity;

class AclEntity extends Entity {

    protected $_aclType;
    protected $_virtual=['acl_model','acl_label','acl_info'];

    public function __construct(string $type,array $properties=[],array $options=[]){
        parent::__construct($properties,$options);

        $this->_aclType=$type;
    }

    protected function _getAclModel(){
        return(AclModel::model($this->_aclType,$this));
    }

    protected function _getAclLabel(){
        return(AclModel::label($this->_aclType,$this));
    }

    protected function _getAclInfo(){
        $info=$this->_getAclLabel();
        $name=$this->get('name');

        if(!empty($name)){
            $info.=' ['.$name.']';
        }

        $info.=' ('.$this->_getAclModel().')';

        return($info);
    }
}
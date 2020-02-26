<?php
namespace Base\Acl\Access;

class BasicAclAccess extends BaseAclAccess
{
    protected $_accesses=[];
    protected $_value;

    public function __construct(array $accesses=[],bool $value=false){
        $this->set($accesses);

        $this->_value=$value;
    }

    public function check($aclAro,$aclAco,$aclAlo):bool
    {
        return($this->_accesses[$aclAro][$aclAco][$aclAlo]??$this->_value);
    }

    public function set($aclAro,$aclAco=null,$aclAlo=null,bool $value=true){
        if(is_array($aclAro)){
            foreach($aclAro as $r=>$c){
                $this->set($r,$c);
            }

            return;
        }

        if(is_array($aclAco)){
            foreach($aclAco as $c=>$l){
                $this->set($aclAro,$c,$l);
            }

            return;
        }

        if(is_array($aclAlo)){
            foreach($aclAlo as $l=>$v){
                $this->set($aclAro,$aclAco,$l,$v);
            }

            return;
        }

        if($value){
            if(!isset($this->_accesses[$aclAro])){
                $this->_accesses[$aclAro]=[];
            }

            if(!isset($this->_accesses[$aclAro][$aclAco])){
                $this->_accesses[$aclAro][$aclAco]=[];
            }

            $this->_accesses[$aclAro][$aclAco][$aclAlo]=$value;
        }
        else {
            unset($this->_accesses[$aclAro][$aclAco][$aclAlo]);
        }
    }
}

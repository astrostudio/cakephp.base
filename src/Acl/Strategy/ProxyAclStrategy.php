<?php
namespace Base\Acl\Strategy;

use Base\Acl\AclStrategyInterface;

class ProxyAclStrategy extends BaseAclStrategy
{
    protected $_strategy;

    public function __construct(AclStrategyInterface $strategy=null){
        $this->_strategy=$strategy;
    }

    public function createAro($aclAro,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->createAro($aclAro,$options);
        }
    }

    public function createAco($aclAco,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->createAco($aclAco,$options);
        }
    }

    public function createAlo($aclAlo,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->createAlo($aclAlo,$options);
        }
    }

    public function updateAro($aclAro,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->updateAro($aclAro,$options);
        }
    }

    public function updateAco($aclAco,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->updateAco($aclAco,$options);
        }
    }

    public function updateAlo($aclAlo,array $options=[]){
        if($this->_strategy){
            $this->_strategy->updateAlo($aclAlo,$options);
        }
    }

    public function deleteAro($aclAro,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->deleteAro($aclAro,$options);
        }
    }

    public function deleteAco($aclAco,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->deleteAco($aclAco,$options);
        }
    }

    public function deleteAlo($aclAlo,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->deleteAlo($aclAlo,$options);
        }
    }

    public function append($aclAro,$aclAco,$aclAlo,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->append($aclAro,$aclAco,$aclAlo,$options);
        }
    }

    public function remove($aclAro,$aclAco,$aclAlo,array $options=[])
    {
        if($this->_strategy){
            $this->_strategy->remove($aclAro,$aclAco,$aclAlo,$options);
        }
    }

}

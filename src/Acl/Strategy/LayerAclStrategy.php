<?php
namespace Base\Acl\Strategy;

class LayerAclStrategy extends BaseAclStrategy
{
    protected $_strategies=[];

    public function __construct(array $strategies=[]){
        $this->setStrategy($strategies);
    }

    public function createAro($aclAro,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->createAro($aclAro,$options);
        }
    }

    public function createAco($aclAco,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->createAco($aclAco,$options);
        }
    }

    public function createAlo($aclAlo,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->createAlo($aclAlo,$options);
        }
    }

    public function deleteAro($aclAro,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->deleteAro($aclAro,$options);
        }
    }

    public function deleteAco($aclAco,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->deleteAco($aclAco,$options);
        }
    }

    public function deleteAlo($aclAlo,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->deleteAlo($aclAlo,$options);
        }
    }

    public function updateAro($aclAro,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->updateAro($aclAro,$options);
        }
    }

    public function updateAco($aclAco,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->updateAco($aclAco,$options);
        }
    }

    public function updateAlo($aclAlo,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->updateAlo($aclAlo,$options);
        }
    }

    public function append($aclAro,$aclAco,$aclAlo,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->append($aclAro,$aclAco,$aclAlo,$options);
        }
    }

    public function remove($aclAro,$aclAco,$aclAlo,array $options=[]){
        foreach($this->_strategies as $strategy){
            $strategy->remove($aclAro,$aclAco,$aclAlo,$options);
        }
    }


}

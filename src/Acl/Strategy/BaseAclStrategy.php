<?php
namespace Base\Acl\Strategy;

use Base\Acl\AclStrategyInterface;

class BaseAclStrategy implements AclStrategyInterface
{
    public function createAro($aclAro,array $options=[])
    {
    }

    public function createAco($aclAco,array $options=[])
    {
    }

    public function createAlo($aclAlo,array $options=[])
    {
    }

    public function updateAro($aclAro,array $options=[])
    {
    }

    public function updateAco($aclAco,array $options=[])
    {
    }

    public function updateAlo($aclAlo,array $options=[]){
    }

    public function deleteAro($aclAro,array $options=[])
    {
    }

    public function deleteAco($aclAco,array $options=[])
    {
    }

    public function deleteAlo($aclAlo,array $options=[])
    {
    }

    public function append($aclAro,$aclAco,$aclAlo,array $options=[])
    {
    }

    public function remove($aclAro,$aclAco,$aclAlo,array $options=[])
    {
    }

}

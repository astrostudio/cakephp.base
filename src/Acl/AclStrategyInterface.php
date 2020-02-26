<?php
namespace Base\Acl;

interface AclStrategyInterface
{
    function createAro($aclAro,array $options=[]);
    function createAco($aclAco,array $options=[]);
    function createAlo($aclAlo,array $options=[]);
    function updateAro($aclAro,array $options=[]);
    function updateAco($aclAco,array $options=[]);
    function updateAlo($aclAlo,array $options=[]);
    function deleteAro($aclAro,array $options=[]);
    function deleteAco($aclAco,array $options=[]);
    function deleteAlo($aclAlo,array $options=[]);
    function append($aclAro,$aclAco,$aclAlo,array $options=[]);
    function remove($aclAro,$aclAco,$aclAlo,array $options=[]);
}

<?php
namespace Base\Acl\Filter;

use Base\Acl\AclFilterInterface;
use Cake\ORM\Query;

class ProxyAclFilter extends BaseAclFilter
{
    protected $_filter;

    public function __construct(AclFilterInterface $filter=null){
        $this->_filter=$filter;
    }

    public function filter(Query $query,$aclAro,$aclAlo):Query
    {
        return($this->_filter?$this->_filter->filter($query,$aclAro,$aclAlo):$query);
    }
}

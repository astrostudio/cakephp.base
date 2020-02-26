<?php
namespace Base\Model\Table;

use Cake\Cache\Cache;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Base\Acl\Model\AclModel;
use Cake\Core\Configure;

class AclTable extends Table {

    private $nameCache;

    private function __loadNameCache(){
        if(!Cache::read($this->getAlias().'exists',$this->nameCache)){
            $entities=$this->find()->all();

            /** @var \Cake\ORM\Entity $entity */
            foreach($entities as $entity){
                Cache::write($this->getAlias().'.'.$entity->get('name'),$entity->get('id'));
            }

            Cache::write($this->getAlias().'exists',true,$this->nameCache);
        }
    }

    protected $_aclType;

    protected function _initializeAcl(string $type){
        $this->_aclType=$type;

        AclModel::initialize($type,$this);
    }

    public function initialize(array $config):void{
        $this->nameCache=Configure::read('Base.Acl.Cache','default');
    }

    public function findAcl(Query $query,array $options=[]):Query
    {
        return(AclModel::find($this->_aclType,$query));
    }

    public function aclContain(){
        return(AclModel::contain($this->_aclType));
    }

    public function aclFilter(){
        return(AclModel::filter($this->_aclType));
    }

    public function aclSorter(){
        return(AclModel::sorter($this->_aclType));
    }

    public function aclSearch(){
        return(AclModel::search($this->_aclType));
    }

    public function getIdByName($name)
    {
        $entity=$this->find()->where(['name'=>$name])->first();

        if(!$entity){
            return(0);
        }

        Cache::write($this->getAlias().'.'.$name,$entity->get('id'),$this->nameCache);

        return($entity->get('id'));
    }

    public function extractId($id){
        if(is_array($id)){
            $result=[];
            $names=[];

            foreach($id as $subId){
                if(is_numeric($subId)) {
                    $result[] = $subId;
                }
                else{
                    $names[]=$subId;
                }
            }

            if(!empty($names)) {
                $entities = $this->find()->where([
                    $this->getAlias() . '.name IN'=>$names
                ])->toArray();

                /** @var \Cake\ORM\Entity $entity */
                foreach($entities as $entity) {
                    $result[]=$entity->get('id');
                }
            }

            return($result);
        }

        if(is_numeric($id)){
            return($id);
        }

        return($this->getIdByName($id));
    }

}


<?php
namespace Base\Model\Installer;

use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class AclTreeGenerator extends LayerGenerator
{
    private $type;

    private function generators(array $nodes=[],string $supNode=null){
        $generators=[];

        foreach($nodes as $node=>$subNodes){
            if(is_int($node)){
                if(is_array($subNodes)) {
                    $generators=array_merge($generators,$this->generators($subNodes,$supNode));
                }
                else if(is_string($subNodes)){
                    $generators[]=new AclGenerator($this->type,$subNodes);

                    if(!empty($supNode)){
                        $generators[]=new AclLinkGenerator(
                            $this->type,
                            new AclNameReference($this->type,$supNode),
                            new AclNameReference($this->type,$subNodes),
                            1,
                            true,
                            true
                        );
                    }
                }
            }
            else {
                $generators[]=new AclGenerator($this->type,$node);

                if(!empty($supNode)){
                    $generators[]=new AclLinkGenerator(
                        $this->type,
                        new AclNameReference($this->type,$supNode),
                        new AclNameReference($this->type,$node),
                        1,
                        true,
                        true
                    );
                }

                $generators=array_merge($generators,$this->generators($subNodes,$node));
            }
        }

        $generators[]=new CallableGenerator(function(){
            /** @var \Base\Model\Table\AclAroLinkTable $aclLinkTable */
            $aclLinkTable=TableRegistry::getTableLocator()->get('Base.Acl'.Inflector::humanize($this->type).'Link');
            $aclLinkTable->extendLinkAll();

            return([]);
        });

        return($generators);
    }

    public function __construct(string $type,array $nodes=[]){
        $this->type=$type;

        parent::__construct($this->generators($nodes));
    }

}
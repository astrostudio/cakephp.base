<?php
namespace Base\Model\Installer;

use Cake\Utility\Inflector;

class AclLinkGenerator implements GeneratorInterface
{
    private $type;
    private $id;
    private $subId;
    private $item;
    private $extendUp;
    private $extendDown;

    public function __construct(string $type,$id,$subId,int $item=1,bool $extendUp=false,bool $extendDown=false){
        $this->type=$type;
        $this->id=$id;
        $this->subId=$subId;
        $this->item=$item;
        $this->extendUp=$extendUp;
        $this->extendDown=$extendDown;
    }

    public function generate(InstallerInterface $installer,array $options=[]):array
    {
        $alias=Inflector::humanize($this->type);

        $data=[
            'item'=>$this->item
        ];

        if(is_numeric($this->id) or ($this->id instanceof ReferenceInterface)){
            $data['acl_'.$this->type.'_id']=$this->id;
        }
        else {
            $data['acl_'.$this->type.'_id']=new AclNameReference($this->type,$this->id);
        }

        if(is_numeric($this->subId) or ($this->subId instanceof ReferenceInterface)){
            $data['acl_sub_'.$this->type.'_id']=$this->subId;
        }
        else {
            $data['acl_sub_'.$this->type.'_id']=new AclNameReference($this->type,$this->subId);
        }

        if($this->extendUp){
            $data['extend_up']=$this->extendUp;
        }

        if($this->extendDown){
            $data['extend_down']=$this->extendDown;
        }

        $item=[
            self::ALIAS=>'Base.Acl'.$alias.'Link',
            self::DATA=>$data
        ];

        return([$item]);
    }
}

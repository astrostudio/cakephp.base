<?php
namespace Base\Model\Installer;

class AclItemGenerator implements GeneratorInterface
{
    private $aro;
    private $aco;
    private $alo;
    private $mask;

    public function __construct($aro,$aco,$alo,int $mask=0){
        $this->aro=$aro;
        $this->aco=$aco;
        $this->alo=$alo;
        $this->mask=$mask;
    }

    public function generate(InstallerInterface $installer,array $options=[]):array
    {
        $data=[];

        if(is_numeric($this->aro) or ($this->aro instanceof ReferenceInterface)){
            $data['acl_aro_id']=$this->aro;
        }
        else {
            $data['acl_aro_id']=new AclNameReference('aro',$this->aro);
        }

        if(is_numeric($this->aco) or ($this->aco instanceof ReferenceInterface)){
            $data['acl_aco_id']=$this->aco;
        }
        else {
            $data['acl_aco_id']=new AclNameReference('aco',$this->aco);
        }

        if(is_numeric($this->alo) or ($this->alo instanceof ReferenceInterface)){
            $data['acl_alo_id']=$this->alo;
        }
        else {
            $data['acl_alo_id']=new AclNameReference('alo',$this->alo);
        }

        $data['mask']=$this->mask;

        return([[self::ALIAS=>'Base.AclItem',self::DATA=>$data]]);
    }
}

<?php
namespace Base\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Cake\Utility\Hash;
use Cake\Routing\Router;
use Base\Base;
use DateTime;
use Exception;

class BaseHelper extends Helper {

    public $helpers=array('Session','Html');
    
    public $baseSlotClass='base-slot';

    public function __construct(View $View,array $config=[]){
        parent::__construct($View,$config);
        
        $this->baseSlotClass=Hash::get($config,'baseSlotClass','base-slot');
    }
    
    public function value(&$variable,$default=null){
        return(!empty($variable)?$variable:$default);
    }
    
    function content($path,$default=null){
        if(is_file($path)) {
            return(file_get_contents($path));
        }
        
        return($default);
    }    
    
    public function back($url=array()){
        if($this->request->session()->check('Base.back')){
            return($this->request->session()->read('Base.back'));
        }
        
        return($url);
    }
    
    public function backUrl(){
        return($this->url($this->back()));
    }
    
    public function get($array,$path,$default=null){
        if(Hash::check($array,$path)){
            return(Hash::get($array,$path));
        }
        
        return($default);
    }
    
    public function formatTime($value,$format){
        $time=DateTime::createFromFormat('Y-m-d H:i:s',$value);
        
        if(!$time){
            $time=DateTime::createFromFormat('Y-m-d',$value);            
        }
        
        return(!empty($time)?$time->format($format):'-');
    }

    public function url($url=null){
        $surl=Router::url('/',true);
        
        if(!isset($url)){
            if(!empty($this->request->params['plugin'])){
                $surl.=$this->request->params['plugin'];
            }
            
            $surl.=$this->request->params['controller'].'/'.$this->request->params['action'];
        }
        else if(is_array($url)){
            if(!empty($url['plugin'])){
                $surl.=$url['plugin'].'/';
            }

            $surl.=(!empty($url['controller'])?$url['controller']:$this->request->params['controller']).'/';
            $surl.=!empty($url['action'])?$url['action']:'index';
        }
        
        return($surl);        
    }
    
    
    private $buffers=array();
    private $bufferName=null;
    
    public function start($name='default',$clear=true){
        if(!empty($this->bufferName)){
            throw new Exception('BaseHelper::start: Buffer not ended.');
        }
        
        $this->bufferName=$name;
        
        if($clear){
            unset($this->buffers[$this->bufferName]);
        }
        
        if(!ob_start()){
            throw new Exception('BaseHelper::start: Start error.');
        }
        
        return(true);
    }
    
    public function fetch($name='default'){
        return(Hash::get($this->buffers,$name));
    }
    
    public function end(){
        if(empty($this->bufferName)){
            throw new Exception('BaseHelper::start: Buffer not started.');
        }
    
        $this->buffers[$this->bufferName]=Hash::get($this->buffers,$this->bufferName,'').ob_get_clean();        
        $this->bufferName=null;
    }
    
    public function clear($name='default'){
        unset($this->buffers[$name]);
    }
  
    public function slot($url=null,$options=array()){
        $class=Hash::get($options,'class','');
        
        if(strpos($class,$this->baseSlotClass)===false){
            $class.=!empty($class)?' ':'';
            $class.=$this->baseSlotClass;
        }
        
        $options['class']=$class;
    
        $output='';
        $output.='<div';
        
        foreach($options as $name=>$value){
            $output.=' '.$name.'="'.$value.'"';
        }
        
        $output.='>';
        
        $output.='<div class="'.$this->baseSlotClass.'-body">';
        
        if(!empty($url)){
            $output.=$this->Html->link('',$url,array('class'=>$this->baseSlotClass.'-init'));
        }
        
        $output.='</div>';
        $output.='</div>';
        
        return($this->output($output));
    }
}
?>
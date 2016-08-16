<?php
App::uses('Base','Vendor/Base');
App::uses('BaseCell','Base.Model');

class BaseHelper extends AppHelper {

    public $helpers=array('Session','Html');
    
    public $baseSlotClass='base-slot';

    public function __construct(View $View,$settings =array()){
        parent::__construct($View,$settings);
        
        $this->baseSlotClass=Hash::get($settings,'baseSlotClass','base-slot');
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
        if($this->Session->check('Base.back')){
            return($this->Session->read('Base.back'));
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


    private $bufferStack=[];
    private $bufferLevel=false;
    private $bufferAlias=false;

    public function start($alias='default',$clear=true){
        if(empty($this->bufferLevel)){
            $this->bufferLevel=[];
        }

        if(!empty($this->bufferAlias)){
            array_push($this->bufferStack,['alias'=>$this->bufferAlias,'level'=>$this->bufferLevel]);

            $this->bufferLevel=[];
            $this->bufferAlias=$alias;
            $this->bufferLevel[$this->bufferAlias]='';
        }
        else {
            $this->bufferAlias=$alias;

            if($clear or empty($this->bufferLevel[$this->bufferAlias])){
                $this->bufferLevel[$this->bufferAlias]='';
            }
        }

        if(!ob_start()){
            throw new Exception('BaseHelper::start: Start error.');
        }

        return(true);
    }

    public function end(){
        if(empty($this->bufferLevel)){
            throw new Exception('BaseHelper::start:  Buffer not started.');
        }

        if(empty($this->bufferAlias)) {
            if(empty($this->bufferStack)) {
                throw new Exception('BaseHelper::start:  Buffer not started.');
            }

            $item=array_pop($this->bufferStack);
            $this->bufferLevel=$item['level'];
            $this->bufferAlias=$item['alias'];
        }

        $this->bufferLevel[$this->bufferAlias].=ob_get_clean();
        $this->bufferAlias=false;
    }

    public function fetch($alias='default'){
        if(empty($this->bufferLevel)){
            return('');
        }

        if(empty($this->bufferLevel[$alias])){
            return('');
        }

        return($this->bufferLevel[$alias]);
    }
    
    public function clear($name='default'){
        unset($this->buffers[$name]);
    }
  
    public function cell($name,$options=array()){
        $cell=BaseCell::cell($name);

        if(!$cell){
            throw new Exception('Base: Cell not found.');
        }
        
        return($this->output($cell->display($this->_View,$options)));
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

    private $__elementBuffer=[];

    public function _class($class=null){
        if(is_string($class)){
            return($class);
        }

        if(!is_array($class)){
            return('');
        }

        $s='';
        $d='';

        if(is_array($class)){
            foreach($class as $c){
                $s.=$d;
                $s.=$this->_class($c);
                $d=' ';
            }
        }
        else if(is_string($class)){
            $s=$class;
        }

        return($s);
    }

    public function _style($style=null){
        if(is_string($style)){
            return($style);
        }

        if(!is_array($style)){
            return('');
        }

        $s='';

        if(is_array($style)){
            foreach($style as $name=>$value){
                $s.=$name.':'.$value;
            }
        }
        else if(is_string($style)){
            $s=$style;
        }

        return($s);
    }

    public function _attrs(array $attrs=[]){
        $s='';
        $d='';

        foreach($attrs as $name=>$value){
            $s.=$d;

            switch($name){
                case 'class':
                    $value=$this->_class($value);

                    break;
                case 'style':
                    $value=$this->_style($value);

                    break;
            }

            $s.=$name.'="'.htmlspecialchars($value).'"';
            $d=' ';
        }

        return($s);
    }

    public function _open($name='div',$attrs=[]){
        $s='<'.$name;

        if(!empty($attrs)){
            $s.=' '.$this->_attrs($attrs);
        }

        return($s);
    }

    public function open($name='div',$attrs=[]){
        array_push($this->__elementBuffer,$name);

        return($this->_open($name,$attrs).'>');
    }

    public function close(){
        if(empty($this->__elementBuffer)){
            throw new Exception('BaseHelper::close: Stack empty!');
        }

        $name=array_pop($this->__elementBuffer);

        return('</'.$name.'>');
    }

    public function simple($name='div',$attrs=[]){
        return($this->_open($name,$attrs).'/>');
    }

    public function element($name='div',$attrs=[],$body=''){
        return($this->open($name,$attrs).$body.$this->close());
    }
}
?>
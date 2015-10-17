<?php
App::uses('ParamRequestHandler','Base.Controller/Component/BaseRequest');
App::uses('GetRequestHandler','Base.Controller/Component/BaseRequest');
App::uses('PostRequestHandler','Base.Controller/Component/BaseRequest');
App::uses('PassRequestHandler','Base.Controller/Component/BaseRequest');

class BaseRequestComponent extends Component {

    public $components=array('Session');
    
    public $controller=null;
    
    public $settings=array();
    
    public $handlers=array();
    
    public $handler=null;
    
    public function __construct(ComponentCollection $collection,$settings=array()) {
        parent::__construct($collection,$settings);

        $this->settings=$settings;
        $this->handlers=array();
        
        $this->setHandler('param',new ParamRequestHandler());
        $this->setHandler('get',new GetRequestHandler());
        $this->setHandler('post',new PostRequestHandler());
        $this->setHandler('pass',new PassRequestHandler());
        
        foreach($this->handlers as $name=>$handler){
            $this->handler=$name;
            
            break;
        }
    }
    
    public function getHandler($name){
        if(isset($this->handlers[$name])){
            return($this->handlers[$name]);
        }
        
        return(null);
    }
    
    public function setHandler($name,IBaseRequestHandler $handler=null){
        if(isset($handler)){
            $this->handlers[$name]=$handler;
        }
    }
    
    public function handlers($handler='param'){
        if($handler instanceof IBaseRequestHandler){
            return(array($handler));
        }
        
        if(is_string($handler)){
            if(isset($this->handlers[$handler])){
                return(array($this->handlers[$handler]));
            }
            
            return(array());
        }
        
        if(is_array($handler)){
            $handlers=array();
            
            foreach($handler as $h){
                $handlers=array_merge($handlers,$this->handlers($h));
            }
            
            return($handlers);
        }
        
        return(array());
    }
    
    public function has($name,$handler='param'){
        $handlers=$this->handlers($handler);
        
        foreach($handlers as $handler){
            if($handler->has($this->controller->request,$name)){
                return(true);
            }
        }
        
        return(false);
    }
    
    public function get($name,$value=null,$handler='param'){
        $handlers=$this->handlers($handler);
        
        foreach($handlers as $handler){
            if($handler->has($this->controller->request,$name)){
                return($handler->get($this->controller->request,$name,$value));
            }
        }
        
        return($value);
    }
    
    public function set($name,$value,$handler='param'){
        $handlers=$this->handlers($handler);
        
        foreach($handlers as $handler){
            $handler->set($this->controller->request,$name,$value);
        }
        
        return(true);
    }
    
    public function clear($name,$handler='param'){
        $handlers=$this->handlers($handler);
        
        foreach($handlers as $handler){
            $handler->clear($this->controller->request,$name);
        }
        
        return(true);
    }

    public function session($name,$space=null,$handler=null){    
        $path='Base.BaseRequest'; 
        $space=isset($space)?$space:$this->space();
        $path.='.'.$space;
        
        if(!empty($handler)){
            $path.='.'.$handler;
        }
        
        if(!empty($name)){
            $path.='.'.$name;
        }
        
        return($path);        
    }
        
    public function space(){
        $space='';

        if($this->controller){
            if(!empty($this->controller->params['plugin'])){
                $space.=$this->controller->params['plugin'].'.';
            }
            
            $space.=$this->controller->params['controller'].'.'.$this->controller->params['action'];
        }
        
        return($space);
    }
     
    public function check($name,$space=null,$handler=null){
        return($this->Session->check($this->session($name,$space,$handler)));
    }
    
    public function read($name,$space=null,$handler=null){
        return($this->Session->read($this->session($name,$space,$handler)));
    }
    
    public function write($name,$value,$space=null,$handler=null){
        $this->Session->write($this->session($name,$space,$handler),$value);
    }
    
    public function changed($name=null,$space=null,$handler=null){
        if(!empty($name)){
            $ovalue=$this->read($name,$space,$handler);
            $nvalue=$this->get($name,$ovalue,$handler);
            
            return($ovalue!==$nvalue);
        }
        
        $space=!empty($space)?$space:$this->space();
        
        if($this->controller){
            if(!empty($this->settings[$this->controller->params['action']])){
                foreach($this->settings[$this->controller->params['action']] as $handler=>$names){
                    if(!empty($names)){
                        foreach($names as $name){
                            if($this->changed($name,$space,$handler)){
                                return(true);
                            }
                        }
                    }
                }
            }
        }
        
        return(false);
    }
    
    public function delete($name,$space=null,$handler=null){
        $this->Session->delete($this->session($name,$space,$handler));
    }
    
    public function load($space=null){
        $space=!empty($space)?$space:$this->space();
        
        if($this->controller){
            if(!empty($this->settings[$this->controller->params['action']])){
                foreach($this->settings[$this->controller->params['action']] as $handler=>$names){
                    if(!empty($names)){
                        foreach($names as $name=>$spec){
                            if(!is_string($name)){
                                $name=$spec;
                                $space0=$space;
                            }
                            else {
                                $space0=$spec;
                            }
                        
                            $ovalue=$this->read($name,$space0,$handler);
                            
                            if(!$this->has($name,$handler)){
                                $this->set($name,$ovalue,$handler);
                            }
                            else {
                                $nvalue=$this->get($name,null,$handler);
                                
                                if($nvalue=='null'){
                                    $this->clear($name,$handler);
                                    $this->delete($name,$space0,$handler);
                                }                                
                            }
                        }
                    }
                }
            }
        }
    }
    
    public function save($space=null){
        $space=!empty($space)?$space:$this->space();        

        if($this->controller){
            if(!empty($this->settings[$this->controller->params['action']])){
                foreach($this->settings[$this->controller->params['action']] as $handler=>$names){
                    if(!empty($names)){
                        foreach($names as $name=>$spec){                            
                            if(!is_string($name)){
                                $name=$spec;
                                $space0=$space;
                            }
                            else {
                                $space0=$spec;
                            }
                        
                            if($this->has($name,$handler)){
                                $this->write($name,$this->get($name,null,$handler),$space0,$handler);
                            }
                            else {
                                $this->delete($name,$space0,$handler);
                            }
                        }
                    }
                }
            }
        }
    }
	    
    public function initialize($controller) {
        $this->controller=$controller;
    }
    
    public function startup($controller) {
		$this->load();
    }
    
    public function shutdown($controller) {
		$this->save();
    }
    
}
<?php
namespace Base\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Base\Base;
use Base\Controller\Component\RequestHandler\IRequestHandler;
use Base\Controller\Component\RequestHandler\ParamRequestHandler;
use Base\Controller\Component\RequestHandler\GetRequestHandler;
use Base\Controller\Component\RequestHandler\PostRequestHandler;
use Base\Controller\Component\RequestHandler\PassRequestHandler;

class RequestComponent extends Component {

    public $settings=array();
    
    public $handlers=array();
    
    public $handler=null;

    public function initialize(array $config){
        parent::initialize($config);

        $this->settings=Base::extend([],$config);
        $this->handlers=[];

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
    
    public function setHandler($name,IRequestHandler $handler=null){
        if(isset($handler)){
            $this->handlers[$name]=$handler;
        }
    }
    
    public function handlers($handler='param'){
        if($handler instanceof IRequestHandler){
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
            if($handler->has($this->_registry->getController()->request,$name)){
                return(true);
            }
        }
        
        return(false);
    }
    
    public function get($name,$value=null,$handler='param'){
        $handlers=$this->handlers($handler);
        
        foreach($handlers as $handler){
            if($handler->has($this->_registry->getController()->request,$name)){
                return($handler->get($this->_registry->getController()->request,$name,$value));
            }
        }
        
        return($value);
    }
    
    public function set($name,$value,$handler='param'){
        $handlers=$this->handlers($handler);
        
        foreach($handlers as $handler){
            $handler->set($this->_registry->getController()->request,$name,$value);
        }
        
        return(true);
    }
    
    public function clear($name,$handler='param'){
        $handlers=$this->handlers($handler);
        
        foreach($handlers as $handler){
            $handler->clear($this->_registry->getController()->request,$name);
        }
        
        return(true);
    }

    public function session($name,$space=null,$handler=null){    
        $path='Base.Request';
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
        $request=$this->_registry->getController()->request;

        if(!empty($request->params['plugin'])){
            $space.=$request->params['plugin'].'.';
        }

        $space.=$request->params['controller'].'.'.$request->params['action'];

        return($space);
    }
     
    public function check($name,$space=null,$handler=null){
        return($this->_registry->getController()->request->session()->check($this->session($name,$space,$handler)));
    }
    
    public function read($name,$space=null,$handler=null){
        return($this->_registry->getController()->request->session()->read($this->session($name,$space,$handler)));
    }
    
    public function write($name,$value,$space=null,$handler=null){
        $this->_registry->getController()->request->session()->write($this->session($name,$space,$handler),$value);
    }
    
    public function changed($name=null,$space=null,$handler=null){
        if(!empty($name)){
            $ovalue=$this->read($name,$space,$handler);
            $nvalue=$this->get($name,$ovalue,$handler);
            
            return($ovalue!==$nvalue);
        }
        
        $space=!empty($space)?$space:$this->space();
        $request=$this->_registry->getController()->request;


        if(!empty($this->settings[$request->params['action']])){
            foreach($this->settings[$request->params['action']] as $handler=>$names){
                if(!empty($names)){
                    foreach($names as $name){
                        if($this->changed($name,$space,$handler)){
                            return(true);
                        }
                    }
                }
            }
        }

        return(false);
    }
    
    public function delete($name,$space=null,$handler=null){
        $this->_registry->getController()->request->session()->delete($this->session($name,$space,$handler));
    }
    
    public function load($space=null){
        $space=!empty($space)?$space:$this->space();
        $request=$this->_registry->getController()->request;

        if(!empty($this->settings[$request->params['action']])){
            foreach($this->settings[$request->params['action']] as $handler=>$names){
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
    
    public function save($space=null){
        $space=!empty($space)?$space:$this->space();
        $request=$this->_registry->getController()->request;

        if(!empty($this->settings[$request->params['action']])){
            foreach($this->settings[$request->params['action']] as $handler=>$names){
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
	    
    public function startup(Event $event) {
		$this->load();
    }
    
    public function shutdown(Event $event) {
		$this->save();
    }
    
}
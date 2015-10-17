<?php
App::uses('Base','Vendor/Base');

class BaseComponent extends Component {

    var $components=array('Session');
    var $controller=null;
    var $settings=array();

    public function __construct(ComponentCollection $collection, $settings = array()) {
        parent::__construct($collection,$settings);
        
        if(isset($settings)) {
            $this->settings=$settings;
        }
    }
    
    public function check($condition,$debug,$url=null,$status=null,$exit=true) {
        if(!$condition) {
            if(isset($debug)) {
                $this->Session->setFlash($debug);
            }

            $this->controller->redirect($url,$status,$exit);
        }
    }
    
    public function link(){
        $url=array();
        $url['plugin']=Hash::get($this->controller->request->params,'plugin');
        $url['controller']=Hash::get($this->controller->request->params,'controller');
        $url['action']=Hash::get($this->controller->request->params,'action');
        
        if(!empty($this->controller->request->params['named'])){
            $url=Base::extend($url,$this->controller->request->params['named']);
        }
        
        if(!empty($this->controller->request->params['pass'])){
            $url=Base::extend($url,$this->controller->request->params['pass']);
        }
        
        return($url);
    }
    
    public function backHere(){
        if($this->controller){
            $url=$this->link();
        
            $this->backUrl($url);
        }
    }
    
    public function back($url=array()){
        $backUrl=$this->backUrl();
        
        if(empty($backUrl)){
            return($url);
        }
        
        return($backUrl);
    }
    
    public function backUrl($url=null){
        if($url===false){
            $this->Session->delete('Base.back');
            
            return(array());
        }
        
        if(!empty($url)){
            $this->Session->write('Base.back',$url);
        }
        
        if($this->Session->check('Base.back')){
            return($this->Session->read('Base.back'));
        }
        
        return(array());
    }
    
    public function url($url=null){
        $surl=Router::url('/',true);
        
        if(!isset($url)){
            if(!empty($this->request->params['plugin'])){
                $surl.=$this->request->params['plugin'];
            }
            
            $surl.=$this->controller->params['controller'].'/'.$this->controller->params['action'];
        }
        else if(is_array($url)){
            if(!empty($url['plugin'])){
                $surl.=$url['plugin'].'/';
            }

            $surl.=!empty($url['controller'])?$url['controller']:$this->controller->params['controller'].'/';
            $surl.=!empty($url['action'])?$url['action']:'index';
        }
        
        return($surl);
    }
    
    public function clear(){
        $this->Session->delete('Base');
    }
    
    function initialize($controller) {
        $this->controller=$controller;
    }
    
    function startup($controller) {
    }
    
    function shutdown($controller) {        
    }
}
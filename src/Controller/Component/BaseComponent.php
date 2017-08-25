<?php
namespace Base\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\Routing\Router;
use Base\Base;

class BaseComponent extends Component {

    public $components=array('Flash');
    public $settings=array();

    public function initialize(array $config){
        parent::initialize($config);

        $this->settings=Base::extend([],$config);
    }
    
    public function check($condition,$debug,$url=null,$status=null) {
        if(!$condition) {
            if(isset($debug)) {
                $this->Flash->set($debug);
            }

            $this->_registry->getController()->redirect($url,$status);
        }
    }
    
    public function link(){
        $request=$this->_registry->getController()->request;
        $url=array();
        $url['plugin']=$request->param('plugin');
        $url['controller']=$request->param('controller');
        $url['action']=$request->param('action');
        
        if(!empty($request->params['pass'])){
            $url=Base::extend($url,$request->params['pass']);
        }
        
        return($url);
    }
    
    public function backHere(){
        $url=$this->link();

        $this->backUrl($url);
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
            $this->_registry->getController()->request->session()->delete('Base.back');

            return(array());
        }
        
        if(!empty($url)){
            $this->_registry->getController()->request->session()->write('Base.back',$url);
        }
        
        if($this->_registry->getController()->request->session()->check('Base.back')){
            return($this->_registry->getController()->request->session()->read('Base.back'));
        }
        
        return(array());
    }
    
    public function url($url=null){
        $surl=Router::url('/',true);
        $request=$this->_registry->getController()->request;
        
        if(!isset($url)){
            if(!empty($request->params['plugin'])){
                $surl.=$request->params['plugin'];
            }
            
            $surl.=$request->params['controller'].'/'.$request->params['action'];
        }
        else if(is_array($url)){
            if(!empty($url['plugin'])){
                $surl.=$url['plugin'].'/';
            }

            $surl.=!empty($url['controller'])?$url['controller']:$request->params['controller'].'/';
            $surl.=!empty($url['action'])?$url['action']:'index';
        }
        
        return($surl);
    }
    
    public function clear(){
        $this->_registry->getController()->request->session()->delete('Base');
    }

    public function redirect($url,$status=302,$name='redirect'){
        if(!empty($name)) {
            $redirect = $this->_registry->getController()->request->query($name);

            if (!empty($redirect)) {
                return ($this->_registry->getController()->redirect($redirect));
            }
        }

        return($this->_registry->getController()->redirect($url,$status));
    }
    
}
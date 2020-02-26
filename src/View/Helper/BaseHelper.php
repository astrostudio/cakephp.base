<?php
namespace Base\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Hash;
use Cake\Routing\Router;
use Base\Base;
use Exception;

class BaseHelper extends Helper {

    function content($path,$default=null){
        if(is_file($path)) {
            return(file_get_contents($path));
        }
        
        return($default);
    }    
    
    public function back($url=[]){
        if($this->request->getSession()->check('Base.back')){
            return($this->request->getSession()->read('Base.back'));
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
    
    public function url($url=null){
        $surl=Router::url('/',true);
        
        if(!isset($url)){
            if(!empty($this->request->getParam('plugin'))){
                $surl.=$this->request->getParam('plugin');
            }

            if(!empty($this->request->getParam('prefix'))){
                $surl.=$this->request->getParam('prefix');
            }

            $surl.=$this->request->getParam('controller').'/'.$this->request->getParam('action');
        }
        else if(is_array($url)){
            if(!empty($url['plugin'])){
                $surl.=$url['plugin'].'/';
            }

            $surl.=(!empty($url['controller'])?$url['controller']:$this->request->getParam('controller')).'/';
            $surl.=!empty($url['action'])?$url['action']:'index';
        }
        
        return($surl);        
    }

    private $__bufferStack=[];
    private $__bufferLevel=false;
    private $__bufferAlias=false;

    public function start($alias='default',$clear=true){
        if(empty($this->__bufferLevel)){
            $this->__bufferLevel=[];
        }

        if(!empty($this->__bufferAlias)){
            array_push($this->__bufferStack,['alias'=>$this->__bufferAlias,'level'=>$this->__bufferLevel]);

            $this->__bufferLevel=[];
            $this->__bufferAlias=$alias;
            $this->__bufferLevel[$this->__bufferAlias]='';
        }
        else {
            $this->__bufferAlias=$alias;

            if($clear or empty($this->__bufferLevel[$this->__bufferAlias])){
                $this->__bufferLevel[$this->__bufferAlias]='';
            }
        }

        if(!ob_start()){
            throw new Exception('Base\\BaseHelper::start: Start error.');
        }

        return(true);
    }

    public function end(){
        if(empty($this->__bufferLevel)){
            throw new Exception('Base\\BaseHelper::start:  Buffer not started.');
        }

        if(empty($this->__bufferAlias)) {
            if(empty($this->__bufferStack)) {
                throw new Exception('Base\\BaseHelper::start:  Buffer not started.');
            }

            $item=array_pop($this->__bufferStack);
            $this->__bufferLevel=$item['level'];
            $this->__bufferAlias=$item['alias'];
        }

        $this->__bufferLevel[$this->__bufferAlias].=ob_get_clean();
        $this->__bufferAlias=false;
    }

    public function fetch($alias='default'){
        if(empty($this->__bufferLevel)){
            return('');
        }

        if(empty($this->__bufferLevel[$alias])){
            return('');
        }

        return($this->__bufferLevel[$alias]);
    }

    public function urlWithRedirect($url,$name='redirect'){
        $redirect=$this->request->getQuery($name);

        if(is_array($url)){
            $url=Base::extend($url,['?'=>['redirect'=>$redirect]]);
        }

        return($url);
    }
}
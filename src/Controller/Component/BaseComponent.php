<?php
namespace Base\Controller\Component;

use Cake\Controller\Component;
use Cake\Routing\Router;
use Base\Base;

/**
 * Class BaseComponent
 * @package Base\Controller\Component
 * @property Component\FlashComponent $Flash
 */
class BaseComponent extends Component {

    public $components= ['Flash'];
    public $settings= [];

    public function initialize(array $config):void{
        parent::initialize($config);

        $this->settings=Base::extend([],$config);
    }

    public function check($condition,$debug,$url=null,$status=null) {
        if(!$condition) {
            if(isset($debug)) {
                $this->Flash->set($debug);
            }

            $this->getController()->redirect($url,$status);
        }
    }

    public function link(){
        $request=$this->getController()->getRequest();
        $url= [];
        $url['plugin']=$request->getParam('plugin');
        $url['prefix']=$request->getParam('prefix');
        $url['controller']=$request->getParam('controller');
        $url['action']=$request->getParam('action');

        return($url);
    }

    public function backHere(){
        $url=$this->link();

        $this->backUrl($url);
    }

    public function back($url=[]){
        $backUrl=$this->backUrl();

        if(empty($backUrl)){
            return($url);
        }

        return($backUrl);
    }

    public function backUrl($url=null){
        if($url===false){
            $this->getController()->getRequest()->getSession()->delete('Base.back');

            return([]);
        }

        if(!empty($url)){
            $this->getController()->getRequest()->getSession()->write('Base.back',$url);
        }

        if($this->getController()->getRequest()->getSession()->check('Base.back')){
            return($this->getController()->getRequest()->getSession()->read('Base.back'));
        }

        return([]);
    }

    public function url($url=null){
        $surl=Router::url('/',true);
        $request=$this->getController()->getRequest();

        if(!isset($url)){
            if(!empty($request->getParam('plugin'))){
                $surl.=$request->getParam('plugin');
            }

            $surl.=$request->getParam('controller').'/'.$request->getParam('action');
        }
        else if(is_array($url)){
            if(!empty($url['plugin'])){
                $surl.=$url['plugin'].'/';
            }

            if(!empty($url['prefix'])){
                $surl.=$url['prefix'].'/';
            }

            $surl.=!empty($url['controller'])?$url['controller']:$request->getParam('controller').'/';
            $surl.=!empty($url['action'])?$url['action']:'index';
        }

        return($surl);
    }

    public function clear(){
        $this->getController()->getRequest()->getSession()->delete('Base');
    }

    public function redirect($url,$status=302,$name='redirect'){
        if(!empty($name)) {
            $redirect = $this->getController()->getRequest()->getQuery($name);

            if (!empty($redirect)) {
                return ($this->getController()->redirect($redirect));
            }
        }

        return($this->getController()->redirect($url,$status));
    }

}

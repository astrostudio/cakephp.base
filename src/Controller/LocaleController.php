<?php
namespace Base\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;

class LocaleController extends Controller {

    public function change($locale=null){
        if(!empty($locale)){
            if(in_array($locale,array_keys(Configure::read('Base.locale')))){
                $this->request->getSession()->write('Config.locale',$locale);
            }
        }

        return($this->redirect($this->referer()));
    }

}
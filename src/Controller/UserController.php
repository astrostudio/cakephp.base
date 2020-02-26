<?php
namespace Base\Controller;

use App\Controller\AppController;
use Base\Model\Table\UserSessionTable;
use Base\Model\Table\UserTable;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Query;

/**
 * @property UserTable        $User
 * @property UserSessionTable $UserSession
 */
class UserController extends AppController {

    public function initialize():void{
        parent::initialize();

        $this->loadModel('Base/User.User');
        $this->loadModel('Base/User.UserSession');
        $this->loadComponent('Auth');

        $this->viewBuilder()->setLayout(Configure::read('User.layout'));
    }

    public function beforeFilter(EventInterface $event){
        parent::beforeFilter($event);

        $this->Auth->allow(['register','activate','login','logout','remind','change']);
    }

    public function index(){
    }

    /**
     * @return Response|null
     */
    public function register(){
        if(!Configure::read('User.registration')){
            $this->Flash->error(__d('user','_registration_not_allowed'));

            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
        }

        if(!empty($this->request->is('post'))) {
            $email = $this->request->getData('email');
            $password = $this->request->getData('password');
            $passwordConfirmation = $this->request->getData('password_confirmation');

            /** @var Query $query */
            $query=$this->User->findByEmail($email);

            if ($query->isEmpty()) {
                if (!empty($password)) {
                    if ($password == $passwordConfirmation) {
                        if($this->User->register($email,$password)){
                            $this->Flash->success(__d('user','_registered'));

                            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
                        }
                        else {
                            $this->Flash->error(__d('user','_not_registered'));
                        }
                    } else {
                        $this->Flash->error(__d('user', '_not_confirmed_password'));
                    }
                } else {
                    $this->Flash->error(__d('user', '_not_valid_password'));
                }
            } else {
                $this->Flash->error(__d('user', '_user_exists'));
            }
        }

        return(null);
    }

    public function activate(){
        $token=$this->request->getQuery('token');

        if(!$this->User->activate($token)){
            $this->Flash->success(__d('user','_activated'));

            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
        }

        $this->Flash->error(__d('user','_not_activated'));

        return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
    }

    public function login(){
        if($this->Auth->isAuthorized()){
            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'index']));
        }

        if(!empty($this->request->is('post'))){
            $user=$this->Auth->identify();

            if(!empty($user)){
                $this->Auth->setUser($user);
                $this->UserSession->login($user['id'],$this->request->getSession()->id(),$this->request->clientIp());

                $this->request->getSession()->write('Config.locale',$user['locale']);

                return($this->redirect($this->Auth->redirectUrl()));
            }

            $this->Flash->error(__d('user','_user_or_password_incorrect'));
        }

        return(null);
    }

    public function logout(){
        $userId=$this->Auth->user('id');
        $this->Auth->logout();

        if(!empty($userId)){
            $this->UserSession->logout($userId,$this->request->getSession()->id());
        }

        return($this->redirect('/'));
    }

    public function remind(){
        if($this->request->is('post')){
            $email=$this->request->getData('email');

            if(!empty($email)){
                $this->User->remind($email);
                $this->Flash->success(__d('user','_reminded'));

                return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
            }
        }

        return(null);
    }

    public function change(){
        $token=$this->request->getQuery('token');

        $this->set('token',$token);

        if($this->request->is('post')){
            $password=$this->request->getData('password');
            $passwordConfirmation=$this->request->getData('password_confirmation');

            if (!empty($password)) {
                if ($password == $passwordConfirmation) {
                    if($this->User->change($token,$password)){
                        $this->Flash->success(__d('user','_changed'));

                        return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
                    }
                    else {
                        $this->Flash->error(__d('user','_not_changed'));
                    }
                } else {
                    $this->Flash->error(__d('user', '_not_confirmed_password'));
                }
            } else {
                $this->Flash->error(__d('user', '_not_valid_password'));
            }
        }

        if(!$this->User->token($token)){
            $this->Flash->error(__d('user','_not_token'));

            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
        }

        return(null);
    }

    public function locale($locale=null){
        if(!empty($locale)){
            if(in_array($locale,array_keys(Configure::read('Setting.locale')))){
                $this->request->getSession()->write('Config.locale',$locale);
            }
        }

        return($this->redirect($this->referer()));
    }


}

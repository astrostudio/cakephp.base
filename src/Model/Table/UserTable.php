<?php
namespace Base\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Mailer\Email;
use Cake\Utility\Hash;

/**
 * @property \Cake\ORM\Table UserSetting
 * @method findByEmail($email)
 */
class UserTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('user');
        $this->setPrimaryKey('id');
        $this->hasMany('Base.UserSession');
        $this->hasMany('Base.UserSetting');
    }

    public function findAuth(Query $query){
        $query->select(['id', 'username', 'email','password'])->where(['User.active' => 1,'User.password is not null']);

        return($query);
    }

    public function authenticateByIp($ip,$name='base-ip-authenticate'){
        $user=$this->find()->join([
            'table'=>$this->UserSetting->getTable(),
            'alias'=>$this->UserSetting->getAlias(),
            'conditions'=>[$this->getAlias().'.id='.$this->UserSetting->getAlias().'.user_id']
        ])->where([
            'active'=>1,
            $this->UserSetting->getAlias().'.name'=>$name,
            'OR'=>[
                $this->UserSetting->getAlias().'.body'=>$ip,
                '"'.$ip.'"'=>'REGEXP '.$this->UserSetting->getAlias().'.body'
            ]
        ])->first();

        return($user?$user->toArray():false);
    }

    public function authenticateByKey($key,$name='base-key-authenticate'){
        $user=$this->find()->join([
            'table'=>$this->UserSetting->getTable(),
            'alias'=>$this->UserSetting->getAlias(),
            'conditions'=>[$this->getAlias().'.id='.$this->UserSetting->getAlias().'.user_id']
        ])->where([
            'active'=>1,
            $this->UserSetting->getAlias().'.name'=>$name,
            $this->UserSetting->getAlias().'.body'=>$key
        ])->first();

        return($user?$user->toArray():false);
    }

    public function register($email,$password,array $options=[]){
        $user=$this->newEntity([
            'email'=>$email,
            'password'=>$password,
            'token'=>md5(uniqid(rand(),true))
        ]);

        if(!$this->save($user)){
            return(false);
        }

        if(!empty($options['email'])) {
            $config = Hash::get($options, 'emailConfig', 'default');
            $subject=Hash::get($options,'emailSubject',__d('user','_email_subject_registration'));

            $email = new Email($config);
            $email->setTo($user->email)
                ->setSubject($subject)
                ->send(\Cake\Routing\Router::url([
                    'plugin'=>'Base/User',
                    'controller' => 'User',
                    'action' => 'activate',
                    '?' => ['token' => $user->token]
                ], true));
        }

        return($user);
    }

    public function verify($id,array $options=[]){
        $user=$this->get($id);
        $user->token=md5(uniqid(rand(),true));
        $user->active=0;

        if(!$this->save($user)){
            return(false);
        }

        if(!empty($options['email'])) {
            $config = Hash::get($options, 'emailConfig', 'default');
            $subject=Hash::get($options,'emailSubject',__d('user','_email_subject_verification'));

            $email = new Email($config);
            $email->setTo($user->email)
                ->setSubject($subject)
                ->send(\Cake\Routing\Router::url([
                    'plugin'=>'Base/User',
                    'controller' => 'User',
                    'action' => 'activate',
                    '?' => ['token' => $user->token]
                ], true));
        }

        return($user);
    }

    public function activate($token){
        $user=$this->find()->where([
            'token'=>$token
        ])->first();

        if(!$user){
            return(false);
        }

        $user->set('active',1);

        return($this->save($user));
    }

    public function remind($email,array $options=[]){
        $user=$this->find()->where([
            'email'=>$email,
            'active'=>1
        ])->first();

        if(!$user){
            return(false);
        }

        $user->set('token',md5(uniqid(rand(),true)));

        if(!empty($options['email'])) {
            $config = Hash::get($options, 'emailConfig', 'default');
            $subject=Hash::get($options,'emailSubject',__d('user','_email_subject_remind'));

            $email = new Email($config);
            $email->setTo($user->get('email'))
                ->setSubject($subject)
                ->send(\Cake\Routing\Router::url([
                    'plugin'=>'Base/User',
                    'controller' => 'User',
                    'action' => 'change',
                    '?' => ['token' => $user->get('token')]
                ], true));
        }

        if(!$this->save($user)){
            return(false);
        }

        return(true);
    }

    public function token($token){
        if(empty($token)){
            return(false);
        }

        return(!$this->find()->where([
            'token'=>$token,
            'active'=>1
        ])->isEmpty());
    }

    public function change($token,$password){
        $user=$this->find()->where([
            'token'=>$token,
            'active'=>1
        ])->first();

        if(!$user){
            return(false);
        }

        $user->set('password',$password);

        if(!$this->save($user)){
            return(false);
        }

        return(true);
    }

}

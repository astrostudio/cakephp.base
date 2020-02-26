<?php
namespace Base\Model\Table;

use Cake\ORM\Table;
use DateTimeInterface;
use DateTime;
use DateInterval;

class UserSessionTable extends Table {

    public function initialize(array $config):void{
        $this->setTable('user_session');
        $this->setPrimaryKey(['user_id','session']);
        $this->belongsTo('Base/User');
    }

    public function login($userId,$key,$ip=null,DateTimeInterface $time=null){
        $time=$time?$time:new DateTime();
        $session=$this->find()->where([
            'user_id'=>$userId,
            'session'=>$key
        ])->first();

        if(!$session){
            $session=$this->newEntity([
                'user_id'=>$userId,
                'session'=>$key,
                'ip'=>$ip
            ]);
        }

        $session->login=$time;

        return($this->save($session));
    }

    public function logout($userId,$key,DateTimeInterface $time=null){
        $time=$time?$time:new DateTime();
        $session=$this->find()->where([
            'user_id'=>$userId,
            'session'=>$key
        ])->first();

        if(!$session){
            $session=$this->newEntity([
                'user_id'=>$userId,
                'session'=>$key
            ]);
        }

        $session->logout=$time;

        return($this->save($session));
    }

    public function access($userId,$key,DateTimeInterface $time=null){
        $time=$time?$time:new DateTime();
        $session=$this->find()->where([
            'user_id'=>$userId,
            'session'=>$key
        ])->first();

        if(!$session){
            $session=$this->newEntity([
                'user_id'=>$userId,
                'session'=>$key
            ]);
        }

        $session->access=$time;

        return($this->save($session));
    }

    public function expire(DateTimeInterface $time=null){
        if(!$time){
            $time=new DateTime();
            $time->sub(new DateInterval('P1D'));
        }

        return($this->updateAll([
            'logout'=>'access'
        ],[
            'logout is NULL',
            'access <='=>$time->format('Y-m-d H:i:s')
        ])!==false);
    }


}

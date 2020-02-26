<?php
namespace Base\Model\Table;

use Cake\ORM\Table;
use Cake\Core\Configure;
use DateTime;
use Base\Task;
use DateTimeInterface;
use DateInterval;

class TaskTable extends Table {

    static public function seconds(DateTime $base=null,DateTime $time=null){
        $base=$base?$base:new DateTime();
        $time=$time?$time:new DateTime();
        $step=$base->diff($time);

        return(($step->invert?-1:1)*($step->days*3600*24+$step->h*3600+$step->i*60+$step->s));
    }

    static public function exceeds(DateTime $base=null,DateTime $time=null,$timeout=0){
        return(self::seconds($base,$time)>$timeout);
    }

    public function initialize(array $config):void{
        $this->setTable('task');
        $this->setPrimaryKey('id');
    }

    public function append($shell,$action,array $params=[],array $data=[]){
        if(!empty($options['alias'])){
            if($this->locked($options['alias'])){
                return(false);
            }
        }

        $data=array_merge([
            'timeout'=>null,
            'step'=>null,
            'alias'=>null,
            'progress'=>0,
            'message'=>null
        ],$data,[
            'shell'=>$shell,
            'action'=>$action,
            'params'=>json_encode($params),
        ]);

        $task=$this->newEntity($data);

        if(!$this->save($task)){
            return(false);
        }

        return($task->get('id'));
    }

    public function load($id){
        return($this->find()->where(['id'=>$id])->first());
    }

    public function update($id,$pid){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        if($task->started){
            return(false);
        }

        if($task->stopped){
            return(false);
        }

        $task->started=new DateTime();
        $task->pid=$pid;

        if(!$this->save($task)){
            return(false);
        }

        return(true);
    }

    public function start($id){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        if($task->started){
            return(false);
        }

        if($task->stopped){
            return(false);
        }

        $params=!empty($task->params)?json_decode($task->params,true): [];
        $params[]='--task-id='.$id;

        $result=Task::execute($task->shell,$task->action,$params);

        return($result);
    }

    public function started($id){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        return($task->started);
    }

    public function stop($id,$result=null,$code=0,$error=null){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        if(!$task->started){
            return(false);
        }

        if($task->stopped){
            return(false);
        }

        $task->result=json_encode($result);
        $task->code=0;
        $task->error=$error;
        $task->stopped=new DateTime();

        if(!$this->save($task)){
            return(false);
        }

        return(true);
    }

    public function done($id,$result=null,$code=0,$error=null){
        return($this->stop($id,$result,$code,$error));
    }

    public function fail($id,$code=0,$error=null){
        return($this->stop($id,null,$code,$error));
    }

    public function kill($id,$code=-999,$error=null){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        if(!$task->started){
            return(false);
        }

        if($task->stopped){
            return(false);
        }

        $error=isset($error)?$error:__d('task','_killed');

        if(!empty($task->pid)){
            Task::kill($task->pid);
        }

        if(!$this->fail($id,$code,$error)){
            return(false);
        }

        return(true);

    }

    public function stopped($id){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        return($task->stopped);
    }

    public function result($id){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        if(!empty($task->result)){
            return(json_decode($task->result,true));
        }

        return(null);
    }

    public function invoke($id,$timeout=null,$step=null){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        if(isset($task->timeout) and $task->timeout>0){
            $timeout=$task->timeout;
        }
        else if(!isset($timeout) or ($timeout<=0)){
            $timeout=Configure::read('Task.timeout');
            $timeout=isset($timeout)?$timeout:30;
        }

        if(isset($task->step) and $task->step>0){
            $step=$task->step;
        }
        else if(!isset($step) or ($step<=0)){
            $step=Configure::read('Task.step');
            $step=isset($step)?$step:5;
        }

        if(!$this->start($id)){
            return(false);
        }

        $base=new DateTime();

        do {
            sleep($step);

            if(!$this->started($id)){
                return(false);
            }

            if($this->stopped($id)){
                return(true);
            }

            $time=new DateTime();

            if(self::exceeds($base,$time,$timeout)){
                $this->kill($id,-999,__d('task','_timeout'));

                return(false);
            }
        }
        while(true);
    }

    public function launch($id,$timeout=null,$step=null){
        $params=[$id];

        if(isset($timeout)){
            $params[]='--timeout='.$timeout;
        }

        if(isset($step)){
            $params[]='--step='.$step;
        }

        return(Task::execute('Base.Executor','invoke',$params));
    }

    public function locked($alias){
        $task=$this->find()->where([
            'alias'=>$alias,
            'started IS NOT NULL',
            'stopped IS NULL'
        ])->first();

        return($task);
    }

    public function lock($id,$alias){
        if($this->locked($alias)){
            return(false);
        }

        return($this->updateAll([
            'alias'=>$alias,
        ],['id'=>$id])!==false);
    }

    public function unlock($alias=null,$force=false){
        if(!isset($alias)){
            return($this->updateAll([
                'alias'=>null
            ], [
                'true'
            ]));
        }

        $tasks=$this->find()->where([
            'alias'=>$alias,
            'started IS NOT NULL',
            'stopped IS NULL'
        ])->toArray();

        foreach($tasks as $task){
            $this->kill($task->id);
        }

        return($this->updateAll([
            'alias'=>null
        ], [
            'alias'=>$alias
        ])!==false);
    }

    public function updatePID($id,$pid){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        $task->pid=$pid;

        if(!$this->save($task)){
            return(false);
        }

        return(true);
    }

    public function step($id,$progress=null,$message=null){
        $task=$this->load($id);

        if(!$task){
            return(false);
        }

        if($progress!==false) {
            $task->progress = $progress;
        }

        if($message!==false) {
            $task->message = $message;
        }

        if(!$this->save($task)){
            return(false);
        }

        return(true);
    }

    public function execute($shell,$action,array $params=[],array $data=[]){
        $this->remove();
        $id=$this->append($shell,$action,$params,$data);

        if(empty($id)){
            return(false);
        }

        return($this->launch($id));
    }

    public function remove(DateTimeInterface $time=null){
        if(!$time){
            $time=new DateTime();
            $time->sub(new DateInterval('P1D'));
        }

        $this->deleteAll([
            'stopped <="'.$time->format('Y-m-d H:i:s').'"'
        ]);
    }
}

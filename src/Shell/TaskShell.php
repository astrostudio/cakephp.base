<?php
namespace Base\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;

/**
 * @property \Base\Model\Table\TaskTable $Task
 */
class TaskShell extends Shell{

    protected $_id=null;

    protected function _update(){
        $this->_id=!empty($this->params['task-id'])?$this->params['task-id']:0;
        $pid=getmypid();

        if(empty($this->_id)){
            $this->out('PID: '.$pid);
        }

        return($this->Task->update($this->_id,$pid));
    }

    protected function _load(){
        return($this->Task->load($this->_id));
    }

    protected function _done($result=null){
        if(empty($this->_id)){
            $this->out('DONE: '.json_encode($result));
        }

        return($this->Task->done($this->_id,$result));
    }

    protected function _fail($code=null,$error=null){
        if(empty($this->_id)){
            $this->out('FAIL: '.$code.' '.json_encode($error));
        }

        return($this->Task->fail($this->_id,$code,$error));
    }

    protected function _kill(){
        if(empty($this->_id)){
            $this->out('KILL');
        }

        return($this->Task->kill($this->_id));
    }

    protected function _step($progress=null,$message=null){
        if(empty($this->_id)){
            $this->out('STEP: '.$progress.' '.$message);
        }

        return($this->Task->step($this->_id,$progress,$message));
    }

    protected function _setTaskCommand(ConsoleOptionParser $parser, array $commands = [])
    {
        $subcommands = $parser->subcommands();

        if (empty($commands)) {
            foreach ($subcommands as $subcommand) {
                $subCommandParser = $subcommand->parser();
                $subCommandParser->addOption('task-id', ['help' => 'TaskID']);
            }
        } else {
            foreach ($subcommands as $subcommand) {
                if (in_array($subcommand->name(), $commands)) {
                    $subParser = $subcommand->parser();
                    $subParser->addOption('task-id', ['help' => 'TaskID']);
                }
            }
        }

        return $parser;
    }

    public function initialize(){
        parent::initialize();

        $this->loadModel('Base.Task');
    }

    public function getOptionParser(){
        $parser=parent::getOptionParser();
        $parser->addOption('task-id',['help' =>'TaskID']);

        return($parser);
    }

    public function startup(){
        parent::startup();

        $this->_update();
    }

}

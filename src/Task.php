<?php
namespace Base;

class Task {

    static private $__php='php';

    /** @var TaskExecutorInterface   */
    static private $__executor=null;

    static public function php($php=null){
        if(isset($php)){
            self::$__php=$php;
        }

        return(self::$__php);
    }

    static public function getExecutor(){
        return(self::$__executor);
    }

    static public function setExecutor(TaskExecutorInterface $executor=null){
        self::$__executor=$executor;
    }

    static public function execute($shell,$action,array $params=[],$log=null){
        $cmd=self::php();
        $cmd.=' '.ROOT.DS.'bin'.DS.'cake.php '.$shell.' '.$action;

        foreach($params as $param){
            $cmd.=' '.$param;
        }


        if(!empty($log)) {
            $cmd .= ' > ' . LOGS . $log . '';
        }

        $result=self::getExecutor()->execute($cmd);

        return($result);
    }

    static public function kill($pid){
        return(self::getExecutor()->kill($pid));
    }

}
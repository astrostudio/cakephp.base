<?php
namespace Base;

class WindowsExecutor implements TaskExecutorInterface {

    public function execute($cmd){
        $output= [];
        $ps=LOGS.uniqid('ps');
        $exec="PsExec.exe -d $cmd 2>$ps";

        exec($exec,$output);
        $output=file($ps);

        if(!empty($output[5])){
            preg_match('/ID (\d+)/',$output[5],$matches);
            $pid=$matches[1];
        }
        else {
            $pid=0;
        }

        return($pid);
    }

    public function kill($pid){
        exec('pskill '.$pid);
    }

}
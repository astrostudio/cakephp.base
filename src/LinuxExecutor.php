<?php
namespace Base;

use Cake\Log\Log;

class LinuxExecutor implements TaskExecutorInterface {

    public function execute($cmd){
        $cmd.=' > /dev/null 2>&1 & echo $!';

        exec($cmd,$output);

        if(!empty($output[0])){
            return($output[0]);
        }

        return(0);
    }

    public function kill($pid){
        return(exec('kill -9 '.$pid));
    }

}

<?php
namespace Base\Shell;

use Base\Model\Installer\GeneratorInterface;
use Cake\Console\Shell;
use Base\Model\Installer\Installer;
use Exception;

/**
 * @property \Base\Model\Table\TaskTable Task
 */
class ModelInstallerShell extends Shell
{
    public function initialize(){
        parent::initialize();
    }

    public function main()
    {
        $path=$this->args[0]??null;

        if(!isset($path)){
            $this->out('Configuration file not specified');

            return(-1);
        }

        $generator=include($path);

        if((!$generator) or !($generator instanceof GeneratorInterface)){
            $this->out('Generator not defined in "'.$path.'"');

            return(-1);
        }

        try {
            Installer::getInstance()->install($generator);
        }
        catch(Exception $exc) {
            $this->out($exc->getMessage());

            return(-1);
        }

        return(0);
    }

    public function sql(){
        $path=$this->args[0]??null;

        if(!isset($path)){
            $this->out('Template not specified');

            return(-1);
        }

        $c=count($this->args);
        $names=[];

        for($i=1;$i<$c;++$i){
            $items=explode('=',$this->args[$i]);

            if(!empty($items[0])){
                if(!empty($items[1])){
                    $names[$items[0]]=$items[1];
                }
            }
        }

        $body=file_get_contents($path);

        foreach($names as $name=>$value){
            $body=str_replace('{{'.$name.'}}',$value,$body);
        }

        $this->out($body);

        return(0);
    }
}
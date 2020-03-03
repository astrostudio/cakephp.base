<?php
namespace Base\Command;

use Base\Model\Installer\GeneratorInterface;
use Base\Model\Installer\Installer;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;

class ModelInstallerCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addArgument('path',[
            'required'=>true,
            'help'=>'Generators file'
        ]);

        return parent::buildOptionParser($parser);
    }

    public function execute(Arguments $args,ConsoleIo $io){
        $path=$args->getArgument('path');

        if(!isset($path)){
            $io->out('Configuration file not specified');

            $this->abort();
        }

        $generator=include($path);

        if((!$generator) or !($generator instanceof GeneratorInterface)){
            $io->out('Generator not defined in "'.$path.'"');

            $this->abort();
        }

        try {
            Installer::getInstance()->install($generator);
        }
        catch(Exception $exc) {
            $io->out($exc->getMessage());

            $this->abort();
        }
    }
}

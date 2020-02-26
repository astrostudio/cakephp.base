<?php
namespace Base\Model\Installer;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Exception;

class Installer extends BaseInstaller
{
    static private $instance=null;

    static public function getInstance():Installer
    {
        if(!self::$instance){
            self::$instance=new Installer();
        }

        return(self::$instance);
    }

    private $config;

    public function __construct(string $config='default',array $values=[]){
        $this->config=$config;
    }

    private function prepare(array &$data=[]){
        foreach($data as $field=>$value){
            if(is_array($value)){
                $this->prepare($value);
            }
            else if($value instanceof ReferenceInterface){
                $data[$field]=$value->getValue($this);
            }
        }
    }

    private function item($key,$item){
        $info='';

        if(!is_int($key)){
            $info.=$key.'=>';
        }

        $info.=json_encode($item);

        return($info);
    }

    public function install(GeneratorInterface $generator,array $options=[]):bool{

        $connection=ConnectionManager::get('default');

        return($connection->transactional(function()use ($generator,$options) {
            $items = $generator->generate($this, $options);

            foreach ($items as $key => $item) {
                if (!is_array($item)) {
                    throw new Exception('Bad definition for: ' . $this->item($key, $item));
                }

                $alias = $item[GeneratorInterface::ALIAS] ?? null;

                if (empty($alias)) {
                    throw new Exception('No alias for: ' . $this->item($key, $item));
                }

                $data = $item[GeneratorInterface::DATA] ?? [];

                $this->prepare($data);

                $table = TableRegistry::getTableLocator()->get($alias);

                if (!$table) {
                    throw new Exception('No table for: ' . $this->item($key, $item));
                }

                $options=$item[GeneratorInterface::OPTIONS]??[];

                $entity = $table->newEntity($data,$options);

                if (!$table->save($entity,$options)) {
                    throw new Exception('Save error for: ' . $this->item($key, $item));
                }

                if (!is_int($key)) {
                    $this->set($key, $entity);
                }
            }

            return(true);
        }));
    }
}

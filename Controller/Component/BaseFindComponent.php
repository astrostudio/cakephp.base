<?php
namespace Base\Controller\Component;

use Cake\Controller\Component;
use Base\Base;

class BaseFindComponent extends Component {

    public $components=[];
    
    public $settings=array();

    public function initialize(array $config){
        parent::initialize($config);

        $this->settings=Base::extend([],$config);
    }

    public function options($name,$value){
        if(!empty($this->settings[$name][$value]['options'])){
            return($this->settings[$name][$value]['options']);
        }
        
        return(array());
    }
    
    public function set($name,$value,$settings=null){
        if(!isset($this->settings[$name])){
            $this->settings[$name]=array();
        }        
        
        if(is_array($value)){
            foreach($value as $v=>$settings){
                $this->set($name,$v,$settings);
            }
            
            return;
        }
        
        $this->settings[$name][$value]=$settings;
    }
    
    public function select($name,$settings=null){
        if(!isset($settings)){
            
        }
    
        $options=array();
        
        if(!empty($this->settings[$name])){
            foreach($this->settings[$name] as $value=>$settings){
                $options[$value]=!empty($settings['title'])?$settings['title']:$value;
            }
        }
        
        return($options);
    }
    
    public function search($value,$field){
        if(!empty($value)){
            if(is_string($field)){
                return(array('conditions'=>array($field.' like "%'.$value.'"')));
            }
            
            if(is_array($field)){
                $items=array();
                
                foreach($field as $f){
                    array_push($items,$f.' like "%'.$value.'%"');
                }
                
                return(array('conditions'=>array('OR'=>$items)));
            }
        }
        
        return(array());
    }
    
}
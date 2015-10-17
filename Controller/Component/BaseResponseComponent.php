<?php
App::uses('IBaseFilter','Base.Model');

class BaseResponseComponent extends Component {

    public $controller=null;
    
    public $settings=array();
    
    public function __construct(ComponentCollection $collection,$settings=array()) {
        parent::__construct($collection,$settings);

        $this->settings=$settings;
    }
    
    public function json($response){
        return(new CakeResponse(array(
            'body'=>json_encode($response)
        )));
    }
    
    public function browse(Model $Model,$query=array()){        
        return($this->json($Model->find('all',$query)));
    }
    
    public function jsonData($data=null,$options=null,$errors=null,$code=0,$message=null){
        return($this->json(array(
            'code'=>$code,
            'message'=>$message,
            'data'=>$data,
            'options'=>$options,
            'errors'=>$errors
        )));
    }
    
    public function jsonCode($code=null,$message=null){
        return($this->jsonData(null,null,null,$code,$message));
    }
    
    public function read(Model $Model,$id){
        try {
            $data=$Model->read(null,$id);
        }
        catch(Exception $exc){
            return($this->jsonCode(-1,$exc->getMessage()));
        }
        
        return($this->jsonData($data));
    }
    
    public function insert(Model $Model,$data=array(),$options=array()){
        return($this->jsonData($data,$options));
    }
    
    public function modify(Model $Model,$id=null,$options=array()){
        $response=array();       

        try {
            if(empty($id)){
                return($this->jsonData());
            }
             
            $data=$Model->read(null,$id);
        }
        catch(Exception $exc){
            return($this->jsonCode(-1,$exc->getMessage()));
        }
        
        return($this->jsonData($data,$options));
    }
    
    protected function update(Model $Model,$options=array()){
        try {
            if(empty($this->controller->request->data)){
                return($this->jsonData());
            }
            
            if(empty($this->request->data[$Model->alias]['id'])){
                $Model->create();
            }
            
            if(!$Model->save($this->request->data)){
                return($this->jsonData($this->request->data,$options,$Model->invalidFields(),1));
            }
        }
        catch(Exception $exc){
            return($this->jsonCode(-1,$exc->getMessage()));
        }
        
        return($this->jsonData($Model->id,null,null,0));
    }
    
    protected function delete(Model $Model,$id){
        try {
            if(!$Model->delete($id)){
                return($this->jsonCode(1));
            }
        }
        catch(Exception $exc){
            return($this->jsonCode(-1,$exc->getMessage()));
        }
        
        return($this->jsonCode(0));
    }
    
    public function initialize($controller) {
        $this->controller=$controller;
    }
    
    public function startup($controller) {
    }
    
    public function shutdown($controller) {
    }
    
}
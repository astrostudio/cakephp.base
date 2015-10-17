<?php
App::uses('Base','Vendor/Base')
App::uses('IBaseFilter','Vendor/Base');
App::uses('BaseJQGridFilter','Base.Model');

class BaseJQGridComponent extends Component {

    public $controller=null;
    
    public $settings=array();
    
    public function __construct(ComponentCollection $collection,$settings=array()) {
        parent::__construct($collection,$settings);

        $this->settings=$settings;
    }
    
    public function browse(Model $Model,$query=array(),$sorters=array(),$searches=array(),$filter=null){ 
        $page=Hash::get($this->controller->request->query,'page',1);
        $page=$page?$page:1;
        $limit=Hash::get($this->controller->request->query,'rows',20);
        $limit=$limit?$limit:20;
        $sidx=Hash::get($this->controller->request->query,'sidx',null);
        $sord=Hash::get($this->controller->request->query,'sord','asc');
        $search=Hash::get($this->controller->request->query,'_filter',null);
        
        if(!empty($search) and ($search!=='false')){
            $query=Base::extend($query,BaseQuery::search($search,$searches));
        }
        
        $count=$Model->find('count',$query);
        
        $pages=$count>0?ceil($count/$limit):0;
        
        if($page>$pages){
            $page=$pages;
        }
        
        $offset=($page-1)*$limit;
        
        $query=Base::extend($query,array(
            'limit'=>$limit,
            'offset'=>$offset
        ));
        
        if(!empty($sidx) and !empty($sorters[$sidx])){
            $query=Base::extend($query,array('order'=>$sorters[$sidx].($sord=='desc'?' DESC':'')));
        }
        
        $rows=$Model->find('all',$query);
        
        $response=array(
            'rows'=>array(),
            'page'=>$page,
            'total'=>$pages,
            'records'=>$count
        );
        
        foreach($rows as $row){            
            $response['rows'][]=$filter?(($filter instanceof IBaseFilter)?$filter->filter($row):(is_callable($filter)?call_user_func($filter,$row):$row)):$row;
        }
        
        return(new CakeResponse(array(
            'body'=>json_encode($response)
        )));
    }
        
    public function initialize($controller) {
        $this->controller=$controller;
    }
    
    public function startup($controller) {
    }
    
    public function shutdown($controller) {
    }
    
}
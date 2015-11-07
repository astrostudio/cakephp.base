<?php
namespace Base\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\ORM\Query;
use Cake\Network\Response;
use Base\Base;
use Base\Model\BaseQuery;
use Base\IBaseFilter;
use Base\Model\BaseJQGridFilter;

class BaseJQGridComponent extends Component {

    public $settings=array();

    public function initialize(array $config){
        parent::initialize($config);

        $this->settings=Base::extend([],$config);
    }

    public function browse(Query $query,$sorters=array(),$searches=array(),$filter=null){
        $request=$this->_registry->getController()->request;
        $page=Hash::get($request->query,'page',1);
        $page=$page?$page:1;
        $limit=Hash::get($request->query,'rows',20);
        $limit=$limit?$limit:20;
        $sidx=Hash::get($request->query,'sidx',null);
        $sord=Hash::get($request->query,'sord','asc');
        $search=Hash::get($request->query,'_filter',null);
        
        if(!empty($search) and ($search!=='false')){
            $query=BaseQuery::search($query,$search,$searches);
        }

        $count=$query->count();

        $pages=$count>0?ceil($count/$limit):0;
        
        if($page>$pages){
            $page=$pages;
        }
        
        $offset=($page-1)*$limit;
        
        $query=$query->limit($limit)->page($page);

        if(!empty($sidx) and !empty($sorters[$sidx])){
            $query=$query->order([$sorters[$sidx]=>$sord=='desc'?'DESC':'ASC']);
        }
        
        $rows=$query->toArray();
        
        $response=array(
            'rows'=>array(),
            'page'=>$page,
            'total'=>$pages,
            'records'=>$count
        );
        
        foreach($rows as $row){            
            $response['rows'][]=$filter?(($filter instanceof IBaseFilter)?$filter->filter($row):(is_callable($filter)?call_user_func($filter,$row):$row)):$row;
        }
        
        return(new Response(array(
            'body'=>json_encode($response)
        )));
    }
        
}
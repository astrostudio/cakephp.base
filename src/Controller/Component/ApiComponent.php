<?php
namespace Base\Controller\Component;

use Cake\Controller\Component;
use Cake\Network\Response;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Base\Base;
use Base\Model\Queries;
use Exception;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;

class ApiComponent extends Component {

    const SUCCESS=200;
    const CREATED=201;
    const ACCEPTED=202;
    const UNAUTHORIZED=401;
    const FORBIDDEN=403;
    const NOT_FOUND=404;
    const NOT_ALLOWED=405;
    const PRECONDITION=412;
    const EXCEPTION=500;

    public $components=['Base.Request'];
    public $settings=[];

    public function initialize(array $config){
        parent::initialize($config);
    }

    public function json($data=null,$status=200){
        return(new Response([
            'type'=>'application/json',
            'body'=>json_encode($data),
            'status'=>$status
        ]));
    }

    public function response($data=null,$code=200){
        return($this->json($data,$code));
    }

    public function responseCode($code=500,$message=null,$data=[]){
        $data=['code'=>$code];

        if(isset($message)){
            $data['message']=$message;
        }

        return($this->json($data,$code));
    }

    public function call($callable){
        try{
            $args=func_get_args();
            $result=Base::evaluate($callable,array_splice($args,1));

            return($this->response($result));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function find(Table $table,$queryOptions=[],$sorters=[],$searches=[],$filters=[],$callable=null){
        return($this->query($table->query()->applyOptions($queryOptions),$sorters,$searches,$filters,$callable));
    }

    public function page(Query $query,array $options=[]){
        try{
            $data=[];
            $page=$this->request->query('page');
            $limit=$this->request->query('limit');
            $offset=$this->request->query('offset');

            $page=!empty($page)?(int)$page:1;
            $limit=!empty($limit)?(int)$limit:20;
            $offset=isset($offset)?(int)$offset:0;
            $offset=($page-1)*$limit+$offset;
            $count=$query->count();
            $pages=$count > 0 ? ceil($count / $limit) : 0;

            $data['page']=$page;
            $data['pages']=$pages;
            $data['count']=$count;
            $data['offset']=$offset;
            $data['limit']=$limit;

            $query->offset($offset)->limit($limit);

            $filter=$this->request->query('filter');

            if(!empty($filter)){
                $data['filter']=$filter;
                $filters=Hash::get($options,'filters',[]);

                $query=Queries::filter($query,$filter,$filters);
            }

            $sorter=$this->request->query('sorter');

            if(!empty($sorter)){
                $data['sorter']=$sorter;
                $sorters=Hash::get($options,'sorters',[]);

                $query=Queries::order($query,$sorter,$sorters);
            }


            $searches=Hash::get($options,'searches',[]);

            if(!empty($searches)) {
                $data['search'] = [];

                foreach ($searches as $p => $s) {
                    $v = $this->request->query($p);

                    if (!empty($v)) {
                        $data['search'][$p] = $s;

                        $query = Queries::search($query, $v, $s);
                    }
                }
            }

            $data['rows']=$query->toArray();

            return($this->response($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function query(Query $query,$sorters=[],$searches=[],$filters=[],$callable=null){
        try {
            $page = addslashes($this->Request->get('page', 1,'get'));
            $page = $page ? $page : 1;
            $limit = addslashes($this->Request->get('limit', 20,'get'));
            $offset=addslashes($this->Request->get('offset',null,'get'));
            $sorter = addslashes($this->Request->get('sorter', null,'get'));
            $search = addslashes($this->Request->get('search', null,'get'));
            $filter= addslashes($this->Request->get('filter',null,'get'));
            $fields = addslashes($this->Request->get('fields', '','get'));

            if(!empty($fields)){
                $fields = explode(',', $fields);

                $query=$query->select($fields);
            }

            if(!empty($sorter)){
                if(!empty($sorters[$sorter])){
                    $query=$query->order($sorters[$sorter]);
                }
            }

            if (!empty($search) and ($search !== 'false')) {
                $query=Queries::search($query,$search,$searches);
            }

            if(!empty($filter) and !empty($filters[$filter])){
                $query=$query->applyOptions($filters[$filter]);
            }

            $count = $query->count();
            $limit=(int)$limit;

            if ($limit > 0) {
                $limit = $limit <= 1000 ? $limit : 1000;
                $pages = $count > 0 ? ceil($count / $limit) : 0;
            } else {
                $pages = 1;
            }

            if ($page > $pages) {
                $page = $pages;
            }

            if($page<1){
                $page=1;
            }


            $offset = ($page - 1) * $limit;

            if ($limit > 0) {
                $query=$query->offset($offset)->limit($limit);
            }

            $rows = $query->toArray();

            $data = [
                'page' => $page,
                'pages' => $pages,
                'count' => $count,
                'offset'=>$offset,
                'limit'=>$limit,
                'sorter'=>$sorter,
                'search'=>$search,
                'filter'=>$filter,
                'rows'=>[]
            ];

            foreach ($rows as $row) {
                $data['rows'][] = Base::evaluate($callable, [$row], $row);
            }

            return ($this->response($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }


    public function load(Table $table,$queryOptions=[]){
        try {
            $query=$table->find()->applyOptions($queryOptions);

            if($this->Request->has('id','param')) {
                $query=$query->where([
                    $table->primaryKey()=>$this->Request->get('id',0,'param')
                ]);
            }

            $data=$query->first();

            if(empty($data)){
                return($this->responseCode(self::NOT_FOUND,__d(Inflector::underscore($table->alias()),'_not_loaded')));
            }

            return($this->response($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function loadAll(Table $table,$queryOptions=[]){
        try {
            $query=$table->find()->applyOptions($queryOptions);
            $data=$query->all();

            return($this->response($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function save(Table $table,$data=[],$options=[],$queryOptions=[]){
        try {
            $code=self::SUCCESS;

            if(isset($this->_registry->getController()->request->data)){
                $data=Base::extend($this->_registry->getController()->request->data,$data);
            }

            $data=Base::extend($this->_registry->getController()->request->query,$data);

            $id=$this->Request->get($table->primaryKey(),0,'post');

            if(!empty($id)){
                $entity=$table->get($id);
            }
            else {
                $entity=$table->newEntity();

                $code=self::CREATED;
            }

            $table->patchEntity($entity,$data);

            if(!$table->save($entity)){
                return($this->responseCode(self::PRECONDITION,__d(Inflector::underscore($table->alias()),'_not_saved'),['errors'=>$entity->errors()]));
            }

            $query=$table->find()->applyOptions($queryOptions);
            $query=$query->where([
                $table->primaryKey()=>$entity->id
            ]);
            $data=$query->first();

            return($this->response($data,$code));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function delete(Table $table,$cascade=true){
        try {
            $entity=$table->get($this->Request->get('id',0,'param'));

            if(!$entity){
                return ($this->responseCode(self::NOT_FOUND, __d(Inflector::underscore($table->alias()), '_not_deleted')));
            }

            if(!$table->delete($entity)){
                return ($this->responseCode(self::NOT_FOUND, __d(Inflector::underscore($table->alias()), '_not_deleted'), $entity->errors()));
            }

            return ($this->response(true));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function deleteAll(Table $table,$conditions=[],$cascade=true){
        try {
            if(!$table->deleteAll($conditions)){
                return ($this->responseCode(self::NOT_FOUND, __d(Inflector::underscore($table->alias()), '_not_deleted')));
            }

            return ($this->response(true));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }
}
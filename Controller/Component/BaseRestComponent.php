<?php
App::uses('BaseQuery','Base.Model');
App::uses('Base','Vendor/Base');

class BaseRestComponent extends Component {

    const SUCCESS=200;
    const CREATED=201;
    const ACCEPTED=202;
    const UNAUTHORIZED=401;
    const FORBIDDEN=403;
    const NOT_FOUND=404;
    const NOT_ALLOWED=405;
    const PRECONDITION=412;
    const EXCEPTION=500;

    public $controller=null;
    public $settings=[];
    public $components=['Base.BaseRequest'];

    public function __construct(ComponentCollection $collection, $settings = []) {
        parent::__construct($collection,$settings);

    }

    public function initialize($controller) {
        $this->BaseRequest->initialize($controller);
        $this->controller=$controller;
    }

    public function json($data=null,$status=200){
        return(new CakeResponse([
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
            $result=Base::evaluate($callable,array_splice(func_get_args(),1));

            return($this->response($result));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function find(Model $model,$query=[],$sorters=[],$searches=[],$callable=null){
        try {
            $page = addslashes($this->BaseRequest->get('page', 1,'get'));
            $page = $page ? $page : 1;
            $limit = addslashes($this->BaseRequest->get('limit', 20,'get'));
            $offset=addslashes($this->BaseRequest->get('offset',null,'get'));
            $sorter = addslashes($this->BaseRequest->get('sorter', null,'get'));
            $search = addslashes($this->BaseRequest->get('search', null,'get'));
            $fields = addslashes($this->BaseRequest->get('fields', '','get'));

            if(!empty($fields)){
                $fields = explode(',', $fields);

                $query['fields'] = $fields;
            }

            if(!empty($sorter)){
                if(substr($sorter,0,1)=='-'){
                    $sorter=substr($sorter,1);
                    $suffix=' DESC';
                }
                else {
                    if(substr($sorter,0,1)=='+'){
                        $sorter=substr($sorter,1);
                    }

                    $suffix='';
                }

                if(!empty($sorters[$sorter])){
                    $query=Base::extend($query,['order'=>$sorters[$sorter].$suffix]);
                }
            }

            if (!empty($search) and ($search !== 'false')) {
                $query = Base::extend($query, BaseQuery::search($search, $searches));
            }

            $count = $model->find('count', $query);
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
                $query = Base::extend($query, [
                    'limit' => $limit,
                    'offset' => $offset
                ]);
            }

            $rows = $model->find('all', $query);

            $data = [
                'page' => $page,
                'pages' => $pages,
                'count' => $count,
                'offset'=>$offset,
                'limit'=>$limit,
                'sorter'=>$sorter,
                'search'=>$search,
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

    public function load(Model $model,$query=[]){
        try {
            if($this->BaseRequest->has('id','params')) {
                $query = Base::extend($query, [
                    'conditions' => [
                        $model->alias . '.' . $model->primaryKey => $this->BaseRequest->get('id',0,'params')
                    ]
                ]);
            }

            $data=$model->find('first', $query);

            if(empty($data)){
                return($this->responseCode(self::NOT_FOUND,__d(Inflector::underscore($model->alias),'_not_loaded')));
            }

            return($this->response($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function loadAll(Model $model,$query=[]){
        try {
            $data=$model->find('all', $query);

            return($this->response($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    private function __save($save,Model $model,$data=[],$options=[],$query=[]){
        try {
            $code=self::SUCCESS;

            $id = Hash::get($this->controller->request->data, $model->alias . '.' . $model->primaryKey);

            if (empty($id)) {
                $model->create();

                $code=self::CREATED;
            }

            $data=Base::extend($this->controller->request->data,$data);

            if(!empty($this->controller->request->query)){
                $data=Base::extend($data,[$model->alias=>$this->controller->request->query]) ;
            }

            if(!call_user_func([$model,$save],$data,$options)){
                return($this->responseCode(self::PRECONDITION,__d(Inflector::underscore($model->alias),'_not_saved'),['errors'=>$model->validationErrors]));
            }

            $data=$model->find('first', Base::extend([
                'conditions' => [
                    $model->alias . '.' . $model->primaryKey => $model->id
                ]
            ], $query));

            return($this->response($data,$code));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function save(Model $model,$data=[],$options=[],$query=[]){
        return($this->__save('save',$model,$data,$options,$query));
    }

    public function saveAll(Model $model,$data=[],$options=[],$query=[]){
        return($this->__save('saveAll',$model,$data,$options,$query));
    }

    public function delete(Model $model,$cascade=true){
        try {
            if (!$model->delete($this->BaseRequest->get('id',0,'params'), $cascade)) {
                return ($this->responseCode(self::NOT_FOUND, __d(Inflector::underscore($model->alias), '_not_deleted'), $model->validationErrors));
            }

            return ($this->response(true));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function deleteAll(Model $model,$conditions=[],$cascade=true){
        try {
            if(!$model->deleteAll($conditions,$cascade,true)){
                return ($this->responseCode(self::NOT_FOUND, __d(Inflector::underscore($model->alias), '_not_deleted'), $model->validationErrors));
            }

            return ($this->response(true));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }


}
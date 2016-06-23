<?php
App::uses('BaseQuery','Base.Model');
App::uses('Base','Vendor/Base');

class BaseApiComponent extends Component {

    const SUCCESS=0;
    const FAILURE=1;
    const EXCEPTION=-1;

    public $controller=null;
    public $settings=[];

    public function __construct(ComponentCollection $collection, $settings = []) {
        parent::__construct($collection,$settings);

    }

    public function initialize($controller) {
        $this->controller=$controller;
    }

    public function json($data){
        return(new CakeResponse([
            'body'=>json_encode($data)
        ]));
    }

    public function response($data=null,$code=self::SUCCESS,$message=null,$errors=[],$options=[]){
        return($this->json(Base::extend($options,[
            'data'=>$data,
            'code'=>$code,
            'message'=>$message,
            'errors'=>$errors
        ])));
    }

    public function responseData($data=null,$options=[]){
        return($this->response($data,self::SUCCESS,null,[],$options));
    }

    public function responseCode($code=self::SUCCESS,$message=null,$errors=[],$options=[]){
        return($this->response(null,$code,$message,$errors,$options));
    }

    public function get($name,$value=null){
        if(empty($this->controller->request->query)){
            return($value);
        }

        return(Hash::get($this->controller->request->query,$name,$value));
    }

    public function has($name){
        if(is_array($name)){
            foreach($name as $name0){
                if(!$this->has($name0)){
                    return(false);
                }
            }

            return(true);
        }

        if(!is_string($name)){
            return(false);
        }

        if(empty($this->controller->request->query)){
            return(false);
        }

        return(isset($this->controller->request->query[$name]));
    }

    public function find(Model $model,$query=[],$sorters=[],$searches=[],$callable=null){
        $page=addslashes($this->get('page',1));
        $page=$page?$page:1;
        $limit=addslashes($this->get('limit',20));
        $sorter=addslashes($this->get('sorter',null));
        $direction=addslashes($this->get('direction','asc'));
        $search=addslashes($this->get('search',null));

        if(!empty($sorter) and !empty($sorters[$sorter])){
            $query=Base::extend($query, ['order'=>$sorters[$sorter].($direction=='desc'?' DESC':'')]);
        }

        if(!empty($search) and ($search!=='false')){
            $query=Base::extend($query,BaseQuery::search($search,$searches));
        }

        $count=$model->find('count',$query);

        if($limit>0) {
            $limit=$limit<=1000?$limit:1000;
            $pages = $count > 0 ? ceil($count / $limit) : 0;
        }
        else {
            $pages=1;
        }

        if($page>$pages){
            $page=$pages;
        }

        $offset=($page-1)*$limit;

        if($limit>0) {
            $query = Base::extend($query, [
                'limit' => $limit,
                'offset'=> $offset
            ]);
        }

        $rows=$model->find('all',$query);

        $options=[
            'page'=>$page,
            'pages'=>$pages,
            'count'=>$count
        ];

        $data=[];

        foreach($rows as $row){
            $data[]=Base::evaluate($callable,[$row],$row);
        }

        return($this->responseData($data,$options));
    }

    public function load(Model $model,$query=[]){
        try {
            if($this->has('id')) {
                $query = Base::extend($query, [
                    'conditions' => [
                        $model->alias . '.' . $model->primaryKey => $this->get('id')
                    ]
                ]);
            }

            $data=$model->find('first', $query);

            if(empty($data)){
                return($this->responseCode(self::FAILURE,__d(Inflector::underscore($model->alias),'_not_loaded')));
            }

            return($this->responseData($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function loadAll(Model $model,$query=[]){
        try {
            if($this->has('id')) {
                $query = Base::extend($query, [
                    'conditions' => [
                        $model->alias . '.' . $model->primaryKey => $this->get('id')
                    ]
                ]);
            }

            $data=$model->find('all', $query);

            return($this->responseData($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function save(Model $model,$data=[],$options=[],$query=[]){
        try {
            $id = Hash::get($this->controller->request->data, $model->alias . '.' . $model->primaryKey);

            if (empty($id)) {
                $model->create();
            }

            $data=Base::extend($this->controller->request->data,$data);

            if(!empty($this->controller->request->query)){
                $data=Base::extend($data,[$model->alias=>$this->controller->request->query]) ;
            }

            if (!$model->saveAll($data,$options)){
                return($this->response($data,self::FAILURE,__d(Inflector::underscore($model->alias),'_not_saved'),$model->validationErrors));
            }

            $data=$model->find('first', Base::extend([
                'conditions' => [
                    $model->alias . '.' . $model->primaryKey => $model->id
                ]
            ], $query));

            return($this->responseData($data));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function delete(Model $model,$conditions=[],$cascade=true){
        try {
            if($this->has('id')) {
                if (!$model->delete($this->get('id'), $cascade)) {
                    return ($this->responseCode(self::FAILURE, __d(Inflector::underscore($model->alias), '_not_deleted'), $model->validationErrors));
                }

                return ($this->responseCode());
            }

            if(!empty($conditions)){
                if(!$model->deleteAll($conditions,$cascade,true)){
                    return ($this->responseCode(self::FAILURE, __d(Inflector::underscore($model->alias), '_not_deleted'), $model->validationErrors));
                }
            }

            return ($this->responseCode());
        }
        catch(Exception $exc){
            return($this->responseCode(self::FAILURE,$exc->getMessage()));
        }
    }


}
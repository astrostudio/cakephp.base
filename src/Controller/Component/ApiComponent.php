<?php
namespace Base\Controller\Component;

use Base\Error\ApiException;
use Cake\Controller\Component;
use Cake\Http\Response;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Base\Base;
use Base\Model\Queries;
use Exception;
use Cake\Utility\Inflector;
use Cake\Http\Exception\HttpException;

/**
 */
class ApiComponent extends Component {

    const SUCCESS=200;
    const CREATED=201;
    const ACCEPTED=202;
    const NOT_MODIFIED=304;
    const BAD_REQUEST=400;
    const UNAUTHORIZED=401;
    const FORBIDDEN=403;
    const NOT_FOUND=404;
    const NOT_ALLOWED=405;
    const NOT_ACCEPTABLE=406;
    const GONE=410;
    const PRECONDITION=412;
    const UNPROCESSABLE=422;
    const EXCEPTION=500;

    const DEFAULT_PARAMS=[
        'id'=>'id',
        'sorter'=>'sorter',
        'direction'=>'direction',
        'filter'=>'filter',
        'search'=>'search',
        'offset'=>'offset',
        'page'=>'page',
        'limit'=>'limit',
        'pages'=>'pages',
        'count'=>'count',
        'items'=>'items'
    ];

    private $callback=null;
    private $etag=false;

    public $params=[];

    public function initialize(array $config):void
    {
        parent::initialize($config);

        if(isset($config['callback']) and is_callable($config['callback'])){
            $this->callback=$config['callback'];
        }

        $this->params=self::DEFAULT_PARAMS;

        if(isset($config['params']) and is_array($config['params'])){
            $this->params=array_merge($this->params,$config['params']);
        }

        $this->etag=$config['etag']??false;
    }

    public function getParam(string $name,$value=null){
        return($this->params[$name]??$value);
    }

    public function setParam($name,$value=null){
        if(is_array($name)){
            foreach($name as $n=>$v){
                $this->setParam($n,$v);
            }

            return;
        }

        unset($this->params[$name]);

        if(isset($value)){
            $this->params[$name]=$value;
        }
    }

    public function check($condition=true,int $status=ApiComponent::BAD_REQUEST,string $message=null){
        if(!$condition){
            throw new ApiException($message,$status);
        }
    }

    public function access($condition=true){
        if(!$condition){
            if(empty($this->user)){
                throw new ApiException('',ApiComponent::UNAUTHORIZED);
            }

            throw new ApiException('',ApiComponent::FORBIDDEN);
        }
    }

    public function json($data=null,int $status=200,array $options=[]){
        $body=json_encode($data);

        $response=new Response(array_merge([
            'type'=>'application/json',
            'body'=>$body,
            'status'=>$status
        ],$options));

        if($this->etag){
            $response=$response->withEtag(md5($body));
        }

        if($this->callback){
            $response=call_user_func($this->callback,$response);
        }

        return($response);
    }

    public function response($data=null,int $status=200,array $options=[]){
        return($this->json($data,$status,$options));
    }

    public function responseCode(int $status=500,$message=null,array $data=[],array $options=[]){
        if(isset($message)){
            $data['message']=$message;
        }

        return($this->json($data,$status,$options));
    }

    public function responseError(int $status=500,string $code=null,string $name=null,string $message=null,array $data=[],array $options=[]){
        if(isset($code)){
            $data['code']=$code;
        }

        if(isset($name)){
            $data['name']=$name;
        }

        return($this->responseCode($status,$message,$data,$options));
    }

    public function call(callable $callable){
        try{
            $args=func_get_args();
            $result=Base::evaluate($callable,array_splice($args,1));

            return($this->response($result));
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function sort(Query $query,array $sorters=[],array $subOrder=[]):Query
    {
        if(empty($this->params['sorter'])){
            return($query);
        }

        if(!empty($this->params['direction'])){
            $direction=$this->getController()->getRequest()->getQuery($this->params['direction']);
        }
        else {
            $direction=null;
        }

        return(Queries::order($query,$this->getController()->getRequest()->getQuery($this->params['sorter']),$direction,$sorters,$subOrder));
    }

    public function search(Query $query,array $searches=[]):Query
    {
        if(empty($this->params['search'])){
            return($query);
        }

        return(Queries::search($query,$this->getController()->getRequest()->getQuery($this->params['search']),$searches));
    }

    public function filter(Query $query,array $filters=[]):Query
    {
        if(empty($this->params['filter'])){
            return($query);
        }

        return(Queries::filter($query,$this->getController()->getRequest()->getQuery($this->params['filter']),$filters));
    }

    public function page(Query $query,callable $callback=null,array $data=[])
    {
        $params=array_merge(self::DEFAULT_PARAMS,$this->params);

        try{
            $request = $this->getController()->getRequest();

            $offset = $request->getQuery($params['offset']);
            $limit = $request->getQuery($params['limit']);
            $page = $request->getQuery($params['page']);

            $count = $query->count();
            $data[$params['count']] = $count;

            if (!isset($limit)) {
                $limit = 20;
            } else {
                $limit = (int)$limit;
            }

            if ($limit < 0) {
                $limit = -$limit;
            }

            if ($limit > 1000) {
                $limit = 1000;
            }

            if ($limit > 0) {
                $data[$params['limit']] = $limit;

                if (isset($offset)) {
                    $offset = (int)$offset;
                    $data[$params['offset']] = $offset;

                    $query = $query->offset($offset)->limit($limit);
                } else {
                    $pages = $count > 0 ? ceil($count / $limit) : 0;
                    $data[$params['pages']] = $pages;

                    if (isset($page)) {
                        $page = (int)$page;
                        $page = $page >= 1 ? $page : 1;

                        if($pages>0 && $page>$pages) {
                            $page = $pages;
                        }
                    } else {
                        $page = 1;
                    }

                    $data[$params['page']] = $page;

                    $query = $query->offset(($page - 1) * $limit)->limit($limit);
                }
            }

            $data[$params['items']] = $query->all();

            if ($callback) {
                $data = call_user_func($callback, $data);
            }

            return($this->response($data));
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function query(Query $query,array $sorters=[],array $searches=[],array $filters=[],callable $callback=null,array $data=[]){
        $query=$this->filter($query,$filters);
        $query=$this->search($query,$searches);
        $query=$this->sort($query,$sorters);
        $request=$this->getController()->getRequest();

        if(!empty($this->params['filter'])){
            $data[$this->params['filter']]=$request->getQuery($this->params['filter']);
        }

        if(!empty($this->params['sorter'])){
            $data[$this->params['sorter']]=$request->getQuery($this->params['sorter']);
        }

        if(!empty($this->params['search'])){
            $data[$this->params['search']]=$request->getQuery($this->params['search']);
        }

        return($this->page($query,$callback,$data));
    }

    public function find(Table $table,array $query=[],array $sorters=[],array $searches=[],array $filters=[],callable $callback=null,array $data=[])
    {
        return($this->query($table->find()->applyOptions($query),$sorters,$searches,$filters,$callback,$data));
    }

    public function load(Table $table,$queryOptions=[],array $primaryKeyValue=null,array $options=[]){
        try {
            $query=$table->find()->applyOptions($queryOptions);

            if(isset($primaryKeyValue)){
                $id=Queries::getPrimaryKey($table->getPrimaryKey(),$primaryKeyValue);
            }
            else {
                if(empty($this->params['id'])){
                    return($this->responseCode(self::NOT_FOUND));
                }

                $id = $this->getController()->getRequest()->getParam($this->params['id']);
            }

            if(!Queries::emptyPrimaryKey($id)){
                $query=$query->where(Queries::getPrimaryKeyConditions($table->getAlias(),$table->getPrimaryKey(),$id));
            }

            $data=$query->first();

            if(empty($data)){
                return($this->responseCode(self::NOT_FOUND,__d(Inflector::underscore($table->getAlias()),'_not_loaded')));
            }

            $response=$this->response($data);

            if(!empty($options['modified'])){
                $response=$response->withModified($data->get($options['modified']));
            }

            return($response);
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    private function errors($errors){
        if(!isset($errors)){
            return('');
        }

        $message='';

        if(is_array($errors)){
            foreach($errors as $error){
                if(is_array($error)){
                    foreach($error as $e){
                        $message.=!empty($message)?' ':'';
                        $message.=$e;
                    }
                }
            }
        }

        return($message);
    }

    public function post(Table $table,array $data=[],array $options=[],array $queryOptions=[],callable $callback=null){
        try {
            $request=$this->getController()->getRequest();

            $requestData=[];

            if($request->is(['put','post'])){
                $requestData=array_merge($request->getData(),$requestData);
            }

            if(!empty($options['fields'])){
                $requestData=Base::leave($requestData,$options['fields']);
            }

            $data=array_merge($data,$requestData);

            $entity=$table->newEntity($data,$options);

            $code=self::CREATED;

            if(!$table->save($entity,$options)){
                $errors=$entity->getErrors();

                if(!empty($errors)) {
                    return ($this->responseCode(self::UNPROCESSABLE, $this->errors($errors)));
                }

                return($this->responseCode(self::UNPROCESSABLE,__d(Inflector::underscore($table->getAlias()),'_not_saved')));
            }

            if($callback){
                call_user_func($callback,$entity);
            }

            $primaryKeyValue=Queries::getPrimaryKeyFromEntity($entity,$table->getPrimaryKey());
            $query=$table->find()->applyOptions($queryOptions);
            $query=$query->where(Queries::getPrimaryKeyConditions($table->getAlias(),$table->getPrimaryKey(),$primaryKeyValue));
            $data=$query->first();

            return($this->response($data,$code));
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function put(Table $table,array $data=[],array $options=[],array $queryOptions=[],callable $callback=null){
        try {
            $code=self::SUCCESS;
            $request=$this->getController()->getRequest();

            $requestData=[];

            if($request->is(['put','post'])){
                $requestData=$request->getData();
            }

            if(!empty($options['fields'])){
                $requestData=Base::leave($requestData,$options['fields']);
            }

            $data=array_merge($requestData,$data);

            $primaryKeyValue=Queries::getPrimaryKey($table->getPrimaryKey(),$data);

            $entity=$table->get($primaryKeyValue);

            $table->patchEntity($entity,$data);

            if(!$table->save($entity,$options)){
                $errors=$entity->getErrors();

                if(!empty($errors)) {
                    return ($this->responseCode(self::UNPROCESSABLE, $this->errors($errors)));
                }

                return($this->responseCode(self::UNPROCESSABLE,__d(Inflector::underscore($table->getAlias()),'_not_saved')));
            }

            if($callback){
                call_user_func($callback,$entity);
            }

            $primaryKeyValue=Queries::getPrimaryKeyFromEntity($entity,$table->getPrimaryKey());
            $query=$table->find()->applyOptions($queryOptions);
            $query=$query->where(Queries::getPrimaryKeyConditions($table->getAlias(),$table->getPrimaryKey(),$primaryKeyValue));
            $data=$query->first();

            return($this->response($data,$code));
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function save(Table $table,array $data=[],array $options=[],array $queryOptions=[],callable $callback=null){
        try {
            $code=self::SUCCESS;
            $request=$this->getController()->getRequest();

            $requestData=[];

            if($request->is(['put','post'])){
                $requestData=$request->getData();
            }

            if(!empty($options['fields'])){
                $requestData=Base::leave($requestData,$options['fields']);
            }

            $data=array_merge($requestData,$data);

            $primaryKeyValue=Queries::getPrimaryKey($table->getPrimaryKey(),$data);

            if(!Queries::emptyPrimaryKey($primaryKeyValue)){
                $entity=$table->find()->where(
                    Queries::getPrimaryKeyConditions(
                        $table->getAlias(),
                        $table->getPrimaryKey(),
                        $primaryKeyValue
                    )
                )->first();

                if(!$entity){
                    $entity=$table->newEntity([]);

                    $code=self::CREATED;
                }
            }
            else {
                $entity=$table->newEntity([]);

                $code=self::CREATED;
            }

            $table->patchEntity($entity,$data);

            if(!$table->save($entity,$options)){
                $errors=$entity->getErrors();

                if(!empty($errors)) {
                    return ($this->responseCode(self::UNPROCESSABLE, $this->errors($errors)));
                }

                return($this->responseCode(self::UNPROCESSABLE,__d(Inflector::underscore($table->getAlias()),'_not_saved')));
            }

            if($callback){
                call_user_func($callback,$entity,$code==self::CREATED);
            }

            $primaryKeyValue=Queries::getPrimaryKeyFromEntity($entity,$table->getPrimaryKey());
            $query=$table->find()->applyOptions($queryOptions);
            $query=$query->where(Queries::getPrimaryKeyConditions($table->getAlias(),$table->getPrimaryKey(),$primaryKeyValue));
            $data=$query->first();

            return($this->response($data,$code));
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function delete(Table $table,array $primaryKeyValue=null,bool $cascade=true,bool $callbacks=true){
        try {
            if(isset($primaryKeyValue)){
                $id=Queries::getPrimaryKey($table->getPrimaryKey(),$primaryKeyValue);
            }
            else {
                if(empty($this->params['id'])){
                    return($this->responseCode(self::NOT_FOUND));
                }

                $id=$this->getController()->getRequest()->getParam($this->params['id'],0);
            }

            $entity=$table->get($id);

            if(!$entity){
                return ($this->responseCode(self::NOT_FOUND, __d(Inflector::underscore($table->getAlias()), '_not_deleted')));
            }

            if(!$table->delete($entity,['cascade'=>$cascade,'cascadeCallbacks'=>$callbacks])){
                return ($this->responseCode(self::UNPROCESSABLE, __d(Inflector::underscore($table->getAlias()), '_not_deleted'), $entity->getErrors()));
            }

            return ($this->response(true));
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function deleteAll(Table $table,array $conditions=[]){
        try {
            if(!$table->deleteAll($conditions)){
                return ($this->responseCode(self::NOT_FOUND, __d(Inflector::underscore($table->getAlias()), '_not_deleted')));
            }

            return ($this->response(true));
        }
        catch(HttpException $exc){
            return($this->responseCode($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->responseCode(self::EXCEPTION,$exc->getMessage()));
        }
    }

}

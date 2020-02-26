<?php
namespace Base\Controller\Component;

use Base\Base;
use Base\Model\Queries;
use Cake\Http\Exception\HttpException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Exception;

class ApiResponseBuilder
{
    const SUCCESS=200;
    const CREATED=201;
    const ACCEPTED=202;
    const UNAUTHORIZED=401;
    const FORBIDDEN=403;
    const NOT_FOUND=404;
    const NOT_ALLOWED=405;
    const PRECONDITION=412;
    const UNPROCESSABLE=422;
    const EXCEPTION=500;

    const OPTION_DATA='data';
    const OPTION_PATH='path';
    const OPTION_QUERY='query';
    const OPTION_FIELDS='fields';
    const OPTION_ID='id';
    const OPTION_SORTER='sorter';
    const OPTION_DIRECTION='direction';
    const OPTION_FILTER='filter';
    const OPTION_SEARCH='search';
    const OPTION_OFFSET='offset';
    const OPTION_PAGE='page';
    const OPTION_LIMIT='limit';
    const OPTION_PAGES='pages';
    const OPTION_COUNT='count';
    const OPTION_ITEMS='items';
    const OPTION_CALLBACK='callback';

    const OPTIONS=[
        self::OPTION_ID=>'id',
        self::OPTION_SORTER=>'sorter',
        self::OPTION_DIRECTION=>'direction',
        self::OPTION_FILTER=>'filter',
        self::OPTION_SEARCH=>'search',
        self::OPTION_OFFSET=>'offset',
        self::OPTION_PAGE=>'page',
        self::OPTION_LIMIT=>'limit',
        self::OPTION_PAGES=>'pages',
        self::OPTION_COUNT=>'count',
        self::OPTION_ITEMS=>'items',
        self::OPTION_CALLBACK=>'callback',
        self::OPTION_DATA=>'data'
    ];

    /** @var ServerRequest */
    private $request;
    /** @var Response  */
    private $response;
    private $data;
    private $options=[];

    public function __construct(ServerRequest $request,Response $response){
        $this->request=$request;
        $this->response=$response;
        $this->data=[];
        $this->options=self::OPTIONS;
    }

    public function getRequest():ServerRequest
    {
        return($this->request);
    }

    public function getResponse():Response
    {
        return($this->response->withStringBody(json_encode($this->data)));
    }

    public function getData(){
        return($this->data);
    }

    public function getMessage(){
        return($this->message());
    }

    public function getCode()
    {
        return($this->response->getStatusCode());
    }

    public function options($name,$value=null):ApiResponseBuilder
    {
        if(is_array($name)){
            foreach($name as $n=>$v){
                $this->options($n,$v);
            }

            return($this);
        }

        $this->options[$name]=$value;

        return($this);
    }

    public function with(callable $callback):ApiResponseBuilder{
        $response=call_user_func($callback,$this->response);

        if(!($response instanceof Response)){
            return($this);
        }

        $this->response=$response;

        return($this);
    }

    public function data($data):ApiResponseBuilder{
        if(is_callable($data)){
            $this->data=call_user_func($data,$this->data);

            return($this);
        }

        $this->data=$data;

        return($this);
    }

    public function set($path,$value=null):ApiResponseBuilder{
        if(is_array($path)){
            foreach($path as $p=>$v){
                $this->set($p,$v);
            }

            return($this);
        }

        if(!is_array($this->data)){
            $this->data=[];
        }

        $this->data=Hash::insert($this->data,$path,$value);

        return($this);
    }

    public function remove($path):ApiResponseBuilder{
        if(is_array($path)){
            foreach($path as $p){
                $this->remove($p);
            }

            return($this);
        }

        if(!is_array($this->data)){
            return($this);
        }

        $this->data=Hash::remove($this->data,$path);

        return($this);
    }

    public function clear():ApiResponseBuilder
    {
        $this->data=null;
        $this->response=$this->response->withStatus(self::SUCCESS);

        return($this);
    }

    public function code(int $code=self::SUCCESS,string $message=null):ApiResponseBuilder{
        $this->response=$this->response->withStatus($code);

        return($this->message($message));
    }

    public function message(string $message=null):ApiResponseBuilder{
        if(isset($message)){
            return($this->set('message',$message));
        }

        return($this->remove('message'));
    }

    public function call(callable $callable):ApiResponseBuilder{
        try{
            $args=func_get_args();
            $result=Base::evaluate($callable,array_splice($args,1));

            return($this->data($result));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function getTable($table):Table
    {
        if($table instanceof Table){
            return($table);
        }

        if(is_string($table)){
            return(TableRegistry::getTableLocator()->get($table));
        }

        return(null);
    }

    public function getQuery($query):Query
    {
        if($query instanceof Query){
            return($query);
        }

        if($query instanceof Table){
            return($query->find());
        }

        if(is_string($query)){
            return(TableRegistry::getTableLocator()->get($query)->find());
        }

        return(null);
    }

    public function page($query,array $options=[]):ApiResponseBuilder
    {
        $query=$this->getQuery($query);
        $options=array_merge($this->options,$options);
        $params=$this->getRequest()->getQueryParams();

        try{
            $offset = $params[$options[self::OPTION_OFFSET]]??null;
            $limit = $params[$options[self::OPTION_LIMIT]]??null;
            $page = $params[$options[self::OPTION_PAGE]]??null;

            $count = $query->count();

            $this->set($options[self::OPTION_COUNT],$count);

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
                $this->set($options[self::OPTION_LIMIT],$limit);

                if (isset($offset)) {
                    $offset = (int)$offset;

                    $this->set($options[self::OPTION_OFFSET],$offset);

                    $query = $query->offset($offset)->limit($limit);
                } else {
                    $pages = $count > 0 ? ceil($count / $limit) : 0;

                    $this->set($options[self::OPTION_PAGES],$pages);

                    if (isset($page)) {
                        $page = (int)$page;
                        $page = $page >= 1 ? $page : 1;

                        if($pages>0 && $page>$pages) {
                            $page = $pages;
                        }
                    } else {
                        $page = 1;
                    }

                    $this->set($options[self::OPTION_PAGE],$page);

                    $query = $query->offset(($page - 1) * $limit)->limit($limit);
                }
            }

            $this->set($options[self::OPTION_ITEMS],$query->all());

            return($this);
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function find($query):ApiResponseBuilder
    {
        try {
            $query = $this->getQuery($query);
            $data = $query->all();

            return ($this->data($data));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function load($query):ApiResponseBuilder
    {
        try {
            $query = $this->getQuery($query);
            $entity = $query->first();

            if (!$entity) {
                return ($this->code(self::NOT_FOUND));
            }

            return ($this->data($entity));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function getKeyCondition(Table $table,$key=null,$value=':id'){
        $id=[];
        $conditions=[];

        $key=isset($key)?$key:$value;
        $key=is_array($key)?$key:[$key];

        foreach($key as $k=>&$v){
            if(!is_array($v)) {
                if (mb_substr($v, 0, 1) === ':') {
                    $v = $this->getRequest()->getParam(mb_substr($v, 1));
                }

                if(is_int($k)){
                    $id[]=$v;
                }
                else {
                    $conditions[$k]=$v;
                }
            }
            else {
                $conditions[]=$v;
            }
        }

        if(!empty($id)) {
            $primaryKey = $table->getPrimaryKey();
            $primaryKey = is_array($primaryKey) ? $primaryKey : [$primaryKey];

            for($i=0;$i<count($primaryKey);++$i){
                $conditions[$table->aliasField($primaryKey[$i])]=$id[$i]??null;
            }
        }

        return($conditions);
    }

    public function get($table,$key=null,array $options=[]):ApiResponseBuilder
    {
        try {
            $table = $this->getTable($table);
            $options = array_merge($this->options, $options);

            $query = $table->find();

            if (!empty($options[self::OPTION_QUERY])) {
                $query = Queries::apply($query, $options[self::OPTION_QUERY]);
            }

            if (is_callable($key)) {
                $query = call_user_func($key, $query);
            } else {
                $keyCondition = $this->getKeyCondition($table, $key);

                if (!empty($keyCondition)) {
                    $query = $query->where($keyCondition);
                }
            }

            return ($this->load($query));
        }
        catch(HttpException $exc){
                return($this->code($exc->getCode(),$exc->getMessage()));
            }
        catch(Exception $exc){
                return($this->code(self::EXCEPTION,$exc->getMessage()));
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

    public function post($table,array $data=[],array $options=[]):ApiResponseBuilder{
        $table=$this->getTable($table);
        $options=array_merge($this->options,$options);

        try {
            $request=$this->getRequest();
            $requestData=[];

            if($request->is(['put','post'])){
                $requestData=$request->getData();
            }

            if(!empty($options[self::OPTION_FIELDS])){
                $requestData=Base::leave($requestData,$options[self::OPTION_FIELDS]);
            }

            $data=array_merge($requestData,$data);

            $entity=$table->newEntity($data);

            if(!$table->save($entity,$options)){
                $errors=$entity->getErrors();

                if(!empty($errors)) {
                    return($this->code(self::UNPROCESSABLE,$this->errors($errors)));
                }

                return($this->code(self::UNPROCESSABLE,__d(Inflector::underscore($table->getAlias()),'_not_saved')));
            }

            return($this->data($entity)->code(self::CREATED));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function put($table,$key=null,array $data=[],array $options=[]):ApiResponseBuilder
    {
        $table=$this->getTable($table);
        $options=array_merge($this->options,$options);

        try {
            $request=$this->getRequest();
            $requestData=[];

            if($request->is(['put','post'])){
                $requestData=$request->getData();
            }

            if(!empty($options[self::OPTION_FIELDS])){
                $requestData=Base::leave($requestData,$options[self::OPTION_FIELDS]);
            }

            $data=array_merge($requestData,$data);

            $query=$table->find();

            if(is_callable($key)){
                $query=call_user_func($key,$query);
            }
            else {
                $keyCondition = $this->getKeyCondition($table, $key);

                if (!empty($keyCondition)) {
                    $query = $query->where($keyCondition);
                }
            }

            $entity=$query->first();

            $table->patchEntity($entity,$data);

            if(!$table->save($entity,$options)){
                $errors=$entity->getErrors();

                if(!empty($errors)) {
                    return($this->code(self::UNPROCESSABLE,$this->errors($errors)));
                }

                return($this->code(self::UNPROCESSABLE,__d(Inflector::underscore($table->getAlias()),'_not_saved')));
            }

            return($this->data($entity)->code(self::CREATED));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function save(Table $table,$key=null,array $data=[],array $options=[]){
        try {
            $code=self::SUCCESS;
            $request=$this->getRequest();

            $requestData=[];

            if($request->is(['put','post'])){
                $requestData=$request->getData();
            }

            if(!empty($options[self::OPTION_FIELDS])){
                $requestData=Base::leave($requestData,$options[self::OPTION_FIELDS]);
            }

            $data=array_merge($requestData,$data);

            if(isset($key)){
                $query=$table->find();

                if(is_callable($key)){
                    $query=call_user_func($key,$query);
                }
                else {
                    $keyCondition=$this->getKeyCondition($table,$key,[]);

                    if(!empty($keyCondition)) {
                        $query = $query->where($keyCondition);
                    }
                }

                $entity = $query->first();

                if (!$entity) {
                    $entity = $table->newEntity([]);

                    $code = self::CREATED;
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
                    return ($this->code(self::UNPROCESSABLE, $this->errors($errors)));
                }

                return($this->code(self::UNPROCESSABLE,__d(Inflector::underscore($table->getAlias()),'_not_saved')));
            }

            return($this->data($entity)->code($code));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function delete($table,$key=null,bool $cascade=true,bool $callbacks=true){
        $table=$this->getTable($table);

        try {
            $query=$table->find();

            if(is_callable($key)){
                $query=call_user_func($key,$query);
            }
            else {
                $keyCondition=$this->getKeyCondition($table,$key);

                if(!empty($keyCondition)){
                    $query=$query->where($keyCondition);
                }
            }

            $entity=$query->first();

            if(!$entity){
                return ($this->code(self::NOT_FOUND, __d(Inflector::underscore($table->getAlias()), '_not_deleted')));
            }

            if(!$table->delete($entity,['cascade'=>$cascade,'cascadeCallbacks'=>$callbacks])){
                return ($this->code(self::UNPROCESSABLE, json_encode($entity->getErrors())));
            }

            return ($this->data($entity));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

    public function deleteAll(Table $table,array $conditions=[]){
        try {
            if(!$table->deleteAll($conditions)){
                return ($this->code(self::NOT_FOUND, __d(Inflector::underscore($table->getAlias()), '_not_deleted')));
            }

            return ($this->data(true));
        }
        catch(HttpException $exc){
            return($this->code($exc->getCode(),$exc->getMessage()));
        }
        catch(Exception $exc){
            return($this->code(self::EXCEPTION,$exc->getMessage()));
        }
    }

}


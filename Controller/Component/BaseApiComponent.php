<?php
namespace Base\Controller\Component;

use Cake\Controller\Component;
use Cake\Network\Response;
use Cake\Utility\Hash;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Base\Base;
use Base\Model\BaseQuery;
use Exception;

class BaseApiComponent extends Component
{
    public $settings=[];

    public function initialize(array $config){
        $this->settings=Base::extend([
            'page'=>'page',
            'limit'=>'limit',
            'sorter'=>'sorter',
            'direction'=>'direction',
            'search'=>'search',
            'pages'=>'pages',
            'total'=>'total',
            'rows'=>'rows'
        ],$config);
    }

    public function json($response=null){
        return(new Response(['body'=>json_encode($response)]));
    }

    public function response($data=null,$code=0,$message=null){
        $response=[];

        if(isset($data)){
            $response['data']=$data;
        }

        if(isset($code)) {
            $response['code'] = $code;
        }

        if(isset($message)){
            $response['message']=$message;
        }

        return($response);
    }

    public function responseCode($code=0,$message=null){
        return($this->response(null,$code,$message));
    }

    public function respond($data=null,$code=0,$message=null){
        return($this->json($this->Response($data,$code,$message)));
    }

    public function respondCode($code=0,$message=null){
        return($this->respond(null,$code,$message));
    }

    public function success($message=null){
        return($this->response(null,0,$message));
    }

    public function failure($code,$message=null){
        return($this->response(null,$code,$message));
    }

    public function serialize($response){
        $this->_registry->getController()->set(array_merge($response,['_serialize'=>array_keys($response)]));
    }

    public function select(Query $query,$sorters=[],$searches=[]){
        try {
            $request = $this->_registry->getController()->request;
            $page = Hash::get($request->query, $this->settings['page'], 1);
            $page = $page ? $page : 1;
            $limit = Hash::get($request->query, $this->settings['limit'], 20);
            $limit = $limit > 0 ? $limit : 20;
            $sorter = Hash::get($request->query, $this->settings['sorter']);
            $direction = Hash::get($request->query, $this->settings['direction'], 'asc');
            $search = Hash::get($request->query, $this->settings['search']);

            $offset = ($page - 1) * $limit;
            $query = $query->limit($limit)->page($page);

            if (!empty($sorter) and !empty($sorters[$sorter])) {
                $query = $query->order([$sorters[$sorter] => ($direction == 'desc' ? 'DESC' : 'ASC')]);
            }

            if (!empty($search) and ($search !== 'false')) {
                $query = BaseQuery::search($query, $search, $searches);
            }

            $count = $query->count();

            $pages = $count > 0 ? ceil($count / $limit) : 0;

            if (($pages>0) && ($page > $pages)) {
                $page = $pages;
            }

            $rows = $query->toArray();

            $response = [
                $this->settings['rows'] => $rows,
                $this->settings['page'] => $page,
                $this->settings['pages'] => $pages,
                $this->settings['total'] => $count
            ];
        }
        catch(Exception $exc){
            $this->serialize($this->failure(2,$exc->getMessage()));

            return($this->responseCode(2,$exc->getMessage()));
        }

        return($response);
    }

    public function get(Table $table,$id,$options=[]){
        try {
            $entity=$table->get($id,$options);

            if(empty($entity)){
                return($this->failure(1,__d('common','_none')));
            }
        }
        catch(Exception $exc){
            return($this->failure(2,$exc->getMessage()));
        }

        return($this->response($entity));
    }

    public function post(Table $table,$options=[]){
        try {
            $request=$this->_registry->getController()->request;

            if(empty($request->data)){
                return($this->failure(1,__d('common','_none')));
            }

            $id=Hash::get($request->data,'id');

            if(!empty($id)){
                $entity=$table->get($id);
                $entity=$table->patchEntity($entity,$request->data);
            }
            else {
                $entity=$table->newEntity($request->data);
            }

            if(!$table->save($entity,$options)){
                return(['data'=>$request->data,'errors'=>$entity->errors(),'code'=>1,'message'=>__d('common','_not_saved')]);

            }
        }
        catch(Exception $exc){
            return($this->failure(2,$exc->getMessage()));
        }

        return(['data'=>$table->get($entity->id),'code'=>0,'message'=>__d('common','_saved')]);
    }

    public function delete(Table $table,$id){
        try {
            $entity=$table->get($id);

            if(!$entity){
                return($this->failure(1, __d('common', '_none')));
            }

            if (!$table->delete($entity)) {
                return($this->failure(1, __d('common', '_not_deleted')));;
            }
        }
        catch(Exception $exc){
            return($this->failure(2,$exc->getMessage()));
        }

        return($this->success(__d('common','_deleted')));
    }
}
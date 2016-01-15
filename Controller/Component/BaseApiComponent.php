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

    public function response($data,$code=0,$message=null){
        return(['data'=>$data,'code'=>$code,'message'=>$message]);
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

            $count = $query->count();

            $pages = $count > 0 ? ceil($count / $limit) : 0;

            if (($pages>0) && ($page > $pages)) {
                $page = $pages;
            }

            $offset = ($page - 1) * $limit;
            $query = $query->limit($limit)->page($page);

            if (!empty($sorter) and !empty($sorters[$sorter])) {
                $query = $query->order([$sorters[$sorter] => ($direction == 'desc' ? 'DESC' : 'ASC')]);
            }

            if (!empty($search) and ($search !== 'false')) {
                $query = BaseQuery::search($query, $search, $searches);
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

            return;
        }

        $this->serialize($response);
    }

    public function get(Table $table,$id,$options=[]){
        try {
            $entity=$table->get($id,$options);

            if(empty($entity)){
                $this->serialize($this->failure(1,__d('common','_none')));

                return;
            }
        }
        catch(Exception $exc){
            $this->serialize($this->failure(2,$exc->getMessage()));

            return;
        }

        $this->serialize($this->response($entity));
    }

    public function post(Table $table,$options=[]){
        $this->serialize(['x'=>'y']);

        try {
            $request=$this->_registry->getController()->request;

            if(empty($request->data)){
                $this->serialize($this->failure(1,__d('common','_none')));

                return;
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
                $this->serialize(['data'=>$request->data,'errors'=>$entity->errors(),'code'=>1,'message'=>__d('common','_not_saved')]);

                return;
            }
        }
        catch(Exception $exc){
            $this->serialize($this->failure(2,$exc->getMessage()));

            return;
        }

        $this->serialize(['data'=>$table->get($entity->id),'code'=>0,'message'=>__d('common','_saved')]);
    }

    public function delete(Table $table,$id){
        try {
            $entity=$table->get($id);

            if(!$entity){
                $this->serialize($this->failure(1, __d('common', '_none')));

                return;
            }

            if (!$table->delete($entity)) {
                $this->serialize($this->failure(1, __d('common', '_not_deleted')));

                return;
            }
        }
        catch(Exception $exc){
            $this->serialize($this->failure(2,$exc->getMessage()));

            return;
        }

        $this->serialize($this->success(0,__d('common','_deleted')));
    }
}
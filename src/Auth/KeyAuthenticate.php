<?php
namespace Base\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class KeyAuthenticate extends BaseAuthenticate
{
    public function authenticate(Request $request, Response $response){
        return($this->getUser($request));
    }

    public function getUser(Request $request){
        $key=$request->env('Authorization');

        if(empty($key)) {
            return (null);
        }

        $user=$this->_queryKey($key);

        if(!$user){
            return(false);
        }

        return($user->toArray());
    }

    protected function _queryKey($key){
        $config = $this->_config;
        $table = TableRegistry::get($config['userModel']);
        $field=Hash::get($config,'fields.key','key');


        $options = [
            'conditions'=>[
                'OR'=>[
                    $table->aliasField($field)=>$key,
                    '"'.$key.'"'=>'REGEXP '.$table->aliasField($field)
                ]
            ]
        ];

        if (!empty($config['scope'])) {
            $options['conditions'] = array_merge($options['conditions'], $config['scope']);
        }
        if (!empty($config['contain'])) {
            $options['contain'] = $config['contain'];
        }

        $finder = $config['finder'];
        if (is_array($finder)) {
            $options += current($finder);
            $finder = key($finder);
        }

        $query = $table->find($finder, $options);

        return $query;
    }

}

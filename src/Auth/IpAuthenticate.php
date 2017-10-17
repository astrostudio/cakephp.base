<?php
namespace Base\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class IpAuthenticate extends BaseAuthenticate
{
    public function authenticate(Request $request, Response $response){
        return($this->getUser($request));
    }

    public function getUser(Request $request){
        $ip=$request->clientIp();

        if(empty($ip)){
            return(false);
        }

        $user=$this->_queryIp($ip)->first();

        if(!$user){
            return(false);
        }

        return($user->toArray());
    }

    protected function _queryIp($ip){
        $config = $this->_config;
        $table = TableRegistry::get($config['userModel']);
        $field=Hash::get($config,'fields.ip','ip');


        $options = [
            'conditions'=>[
                'OR'=>[
                    $table->aliasField($field)=>$ip,
                    '"'.$ip.'"'=>'REGEXP '.$table->aliasField($field)
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

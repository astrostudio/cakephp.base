<?php
namespace Base\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Network\Request;
use Cake\Network\Response;
use Base\Controller\Component\BaseApiComponent;

class ApiUnAuthenticate extends BaseAuthenticate
{
    public function authenticate(Request $request, Response $response){
        return(false);
    }

    public function unauthenticated(Request $request, Response $response){
        $response->type('json');
        $response->statusCode(BaseApiComponent::UNAUTHORIZED);
        $response->body(json_encode(['error'=>'Access denied']));

        return($response);
    }
}
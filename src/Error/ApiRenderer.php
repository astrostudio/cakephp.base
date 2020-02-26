<?php
namespace Base\Error;

use Cake\Error\ExceptionRenderer;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;

class ApiRenderer extends ExceptionRenderer {

    public function render():ResponseInterface{
        if($this->error instanceof ApiException){
            return(new Response([
                'body'=>json_encode(['error'=>$this->error->getMessage()]),
                'type'=>'application/json',
                'status'=>$this->error->getCode()
            ]));
        }
        else if($this->error instanceof ForbiddenException){
            return($this->controller->redirect('/'));
        }
        else if($this->error instanceof UnauthorizedException){
            return($this->controller->redirect('/'));
        }
        else if($this->error instanceof ForbiddenException){
            return($this->controller->redirect('/'));
        }

        return(parent::render());
    }

}

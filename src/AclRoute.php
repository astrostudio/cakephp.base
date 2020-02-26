<?php
namespace Base;

use Cake\Routing\RouteBuilder;

class AclRoute
{
    static public function action(string $name,array $options=[]):array
    {
        return(array_merge(['plugin'=>'Base','controller'=>'Acl','action'=>$name],$options));
    }

    static public function routes(RouteBuilder $routes){
        $routes->scope('/acl',function(RouteBuilder $routes){
            $routes->scope('/aro',function(RouteBuilder $routes){
                $routes->get('/link',self::action('findAroLinkAll'));
                $routes->get('/aco',self::action('findAroAco'));
                $routes->get('/aco/:aro/:aco',self::action('loadAroAco'))->setPatterns(['aro'=>'\d+','aco'=>'\d+'])->setPass(['aro','aco']);
                $routes->post('/aco/:aro/:aco',self::action('appendAroAco'))->setPatterns(['aro'=>'\d+','aco'=>'\d+'])->setPass(['aro','aco']);
                $routes->delete('/aco/:aro/:aco',self::action('removeAroAco'))->setPatterns(['aro'=>'\d+','aco'=>'\d+'])->setPass(['aro','aco']);
                $routes->get('/access',self::action('findAroAccess'));
                $routes->get('/access/:aro/:acoAro/:alo',self::action( 'checkAroAccess'))->setPatterns(['aro'=>'\d+','acoAro'=>'\d+','alo'=>'\d+'])->setPass(['aro','acoAro','alo']);
                $routes->get('/:id/link',self::action('findAroLink'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/:id/link/:aro',self::action('loadAroLink'))->setPatterns(['id'=>'\d+','aro'=>'\d+'])->setPass(['id','aro']);
                $routes->post('/:id/link/:aro',self::action('appendAroLink'))->setPatterns(['id'=>'\d+','aro'=>'\d+'])->setPass(['id','aro']);
                $routes->delete('/:id/link/:aro',self::action('removeAroLink'))->setPatterns(['id'=>'\d+','aro'=>'\d+'])->setPass(['id','aro']);
                $routes->get('/:id/tree',self::action('loadAroTree'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/:id',self::action('loadAro'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->put('/:id',self::action('putAro'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->delete('/:id',self::action('deleteAro'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/',self::action('findAro'));
                $routes->post('/',self::action('postAro'));
            });

            $routes->scope('/aco',function(RouteBuilder $routes){
                $routes->get('/link',self::action('findAcoLinkAll'));
                $routes->get('/:id/link',self::action('findAcoLink'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/:id/link/:aco',self::action('loadAcoLink'))->setPatterns(['id'=>'\d+','aco'=>'\d+'])->setPass(['id','aco']);
                $routes->post('/:id/link/:aco',self::action('appendAcoLink'))->setPatterns(['id'=>'\d+','aco'=>'\d+'])->setPass(['id','aco']);
                $routes->delete('/:id/link/:aco',self::action('removeAcoLink'))->setPatterns(['id'=>'\d+','aco'=>'\d+'])->setPass(['id','aco']);
                $routes->get('/:id/tree',self::action('loadAcoTree'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/:id',self::action('loadAco'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->put('/:id',self::action('putAco'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->delete('/:id',self::action('deleteAco'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/',self::action('findAco'));
                $routes->post('/',self::action('postAco'));
            });

            $routes->scope('/alo',function(RouteBuilder $routes){
                $routes->get('/link',self::action('findAloLinkAll'));
                $routes->get('/:id/link',self::action('findAloLink'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/:id/link/:alo',self::action('loadAloLink'))->setPatterns(['id'=>'\d+','alo'=>'\d+'])->setPass(['id','alo']);
                $routes->post('/:id/link/:alo',self::action('appendAloLink'))->setPatterns(['id'=>'\d+','alo'=>'\d+'])->setPass(['id','alo']);
                $routes->delete('/:id/link/:alo',self::action('removeAloLink'))->setPatterns(['id'=>'\d+','alo'=>'\d+'])->setPass(['id','alo']);
                $routes->get('/:id/tree',self::action('loadAloTree'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/root',self::action('findAloRoot'));
                $routes->get('/:id',self::action('loadAlo'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->put('/:id',self::action('putAlo'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->delete('/:id',self::action('deleteAlo'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                $routes->get('/',self::action('findAlo'));
                $routes->post('/',self::action('postAlo'));
            });

            $routes->scope('/item',function(RouteBuilder $routes){
                $routes->scope('/schedule',function(RouteBuilder $routes){
                    $routes->get('/:id',self::action('loadItemSchedule'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                    $routes->put('/:id',self::action('putItemSchedule'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                    $routes->delete('/:id',self::action('deleteItemSchedule'))->setPatterns(['id'=>'\d+'])->setPass(['id']);
                    $routes->post('/',self::action('postItemSchedule'));
                    $routes->get('/',self::action('findItemSchedule'));
                });

                $routes->get('/:aro/:aco/:alo',self::action('loadItem'))->setPatterns(['aro'=>'\d+','aco'=>'\d+','alo'=>'\d+'])->setPass(['aro','aco','alo']);
                $routes->put('/:aro/:aco/:alo',self::action('putItem'))->setPatterns(['aro'=>'\d+','aco'=>'\d+','alo'=>'\d+'])->setPass(['aro','aco','alo']);
                $routes->delete('/:aro/:aco/:alo',self::action('deleteItem'))->setPatterns(['aro'=>'\d+','aco'=>'\d+','alo'=>'\d+'])->setPass(['aro','aco','alo']);
                $routes->post('/',self::action('postItem'));
                $routes->get('/',self::action('findItem'));
            });

            $routes->scope('/access',function(RouteBuilder $routes){
                $routes->get('/:aro/:aco/:alo',self::action( 'checkAccess'))->setPatterns(['aro'=>'\d+','aco'=>'\d+','alo'=>'\d+'])->setPass(['aro','aco','alo']);
                $routes->get('/', self::action('findAccess'));
            });
        });
    }

}

<?php
namespace Base\Controller\Component\RequestHandler;

use Cake\Network\Request;

interface IRequestHandler {

    function has(Request $request,$name);
    function get(Request $request,$name,$value=null);
    function set(Request $request,$name,$value);
    function clear(Request $request,$name);

}

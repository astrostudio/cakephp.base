<?php
App::uses('CakeRequest','Network');

interface IBaseRequestHandler {
    function has(CakeRequest $request,$name);
    function get(CakeRequest $request,$name,$value=null);
    function set(CakeRequest $request,$name,$value);
    function clear(CakeRequest $request,$name);
}

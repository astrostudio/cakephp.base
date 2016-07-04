<?php
App::uses('IBaseCoder','Base.Model/Behavior/BaseCoder');

class JsonCoder implements IBaseCoder {
    public function encode($value,$data=[]){
        return(json_encode($value));
    }

    public function decode($value,$data=[]){
        return(json_decode($value,true));
    }
}
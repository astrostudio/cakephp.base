<?php
App::uses('IBaseCoder','Base.Model/Behavior/BaseCoder');

class BaseCoder {
    static private $__coders=[];

    static public function get($name){
        if(!isset(self::$__coders[$name])){
            return(false);
        }

        return(self::$__coders[$name]);
    }

    static public function set($name,$coder=null){
        if(isset($coder)){
            self::$__coders[$name]=$coder;
        }
        else {
            unset(self::$__coders[$name]);
        }
    }

    static public function encode($coder,$value,$data=[]){
        if(is_string($coder)) {
            $coder = self::get($coder);
        }

        if(!isset($coder)){
            return($value);
        }

        if($coder instanceof IBaseCoder){
            return($coder->encode($value,$data));
        }

        return(Base::evaluate($coder,[$value,$data],$value));
    }

    static public function decode($coder,$value,$data=[]){
        if(is_string($coder)) {
            $coder = self::get($coder);
        }

        if(!isset($coder)){
            return($value);
        }

        if($coder instanceof IBaseCoder){
            return($coder->decode($value,$data));
        }

        return(Base::evaluate($coder,[$value,$data],$value));
    }
}
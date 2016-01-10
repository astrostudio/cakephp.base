<?php
class BaseCell {

    static private $__cells=array();

    static public function cell($name,$cell=null){
        if(is_array($name)){
            foreach($name as $name0=>$value){
                self::cell($name0,$value);
            }

            return(null);
        }

        if($cell===false){
            unset(self::$__cells[$name]);

            return(null);
        }

        if($cell instanceof IBaseCell){
            self::$__cells[$name]=$cell;

            return($cell);
        }

        return(!empty(self::$__cells[$name])?self::$__cells[$name]:null);
    }


}
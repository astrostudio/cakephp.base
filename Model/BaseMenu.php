<?php
class BaseMenu {
    
    static private $menus=array();       
    
    static public function get($path){
        return(Hash::get(self::$menus,$path));
    }
        
    /* 
    item=[name],[menu],[link] 
    
    [] - separator
    [name] - header
    [name,menu] - submenu
    [name,link] - link    
    */
    static public function set($path,$item){
        if($item!==false){
            self::$menus=Hash::insert(self::$menus,$path,$item);
        }
        else {
            self::$menus=Hash::remove(self::$menus,$path);
        }
    }
    
}
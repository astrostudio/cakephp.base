<?php
namespace Base;

use Cake\Utility\Hash;
use Cake\Core\Configure;
use Cake\I18n\I18n;

class Menu {

    static private $__menus=[];

    static public function get($path){
        return(Hash::get(self::$__menus,$path,false));
    }

    static public function set($path,array $items=[]){
        self::$__menus=Hash::insert(self::$__menus,$path,$items);
    }

    static public function remove($path){
        self::$__menus=Hash::remove(self::$__menus,$path);
    }

    static public function locale(){
        $item=[
            'name'=>'<span class="flag flag-'.I18n::locale().'"></span>',
            'menu'=>[]
        ];

        $locales=Configure::read('Base.locale');

        foreach($locales as $code=>$name) {
            $item['menu'][$code]=[
                'name'=>'<span class="flag flag-'.$code.'"></span> '.__d('language',$code),
                'link'=>['plugin'=>'Base', 'controller' => 'Locale', 'action' => 'change', $code]
            ];
        }

        return([$item]);
    }

}
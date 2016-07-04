<?php
App::uses('BaseTime','Vendor/Base');

class BaseQuery {

    static public function search($text,$fields=array()){
        $conditions=array();
        
        foreach($fields as $field){
            array_push($conditions,array(
                $field.' like "%'.$text.'%"'
            ));
        }
        
        return(array('conditions'=>array(array('OR'=>$conditions))));
    }
    
    static public function tag($field,$tag=null){
        if(is_string($tag)){
            $tags=explode(' ',$tag);
        }
        else if(is_array($tag)){
            $tags=$tag;
        }        
        else {
            $tags=array();
        }
    
        $conditions=array();
        
        foreach($tags as $tag){
            if(is_string($tag) or is_numeric($tag)){
                array_push($conditions,array(
                    $field.' like "%'.$tag.'%"'
                ));
            }
        }
        
        return(array('conditions'=>array(array('OR'=>$conditions))));
    }

    static public function options($query=[],$params=[],$options=[]){
        foreach($params as $name=>$value){
            if(isset($options[$name])){
                $query=Base::extend($query,Base::evaluate($options[$name],[$value],[]));
            }
        }

        return($query);
    }

    static public function conditions($query=[],$fields=[],$options=[]){
        foreach($fields as $name=>$field){
            if(isset($options[$name])){
                $query=Base::extend($query,[
                    'conditions'=>[
                        $field=>$options[$name]
                    ]
                ]);
            }
        }

        return($query);
    }

    static public function step($query,$field,$name,$step){
        $step=!empty($step)?$step:1;
        $expr='FLOOR('.$field.'/'.$step.')';

        return(Base::extend($query,[
            'fields'=>[$expr.' as '.$name],
            'group'=>[$expr],
            'order'=>[$expr]
        ]));
    }

    static public function range($field,$alias='range',$range=BaseTime::TR_DAY){
        $expr='DATE_FORMAT('.$field.',"'.BaseTime::range($range,'database').'")';

        return([
            'fields'=>[$expr.' as '.$alias],
            'group'=>[$expr],
            'order'=>[$expr]
        ]);
    }

    static public function period($field,$alias='range',$period=BaseTime::TP_LAST_WEEK,DateTime $time=null){
        $period=BaseTime::period($period,$time);

        $query=Base::extend([
            'conditions'=>[
                $field.'>="'.$period['start']->format(BaseTime::TF_DATABASE).'"',
                $field.'<="'.$period['stop']->format(BaseTime::TF_DATABASE).'"'
            ],
        ],self::range($field,$alias,BaseTime::periodRange($period)));

        return($query);
    }

    static public function value($expr,$default=0){
        return('IF(ISNULL('.$expr.'),'.$default.','.$expr.')');
    }

}
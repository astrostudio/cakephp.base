<?php
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

    static public function range($query,$field,$name,$step){
        $step=!empty($step)?$step:1;
        $expr='FLOOR('.$field.'/'.$step.')';

        return(Base::extend($query,[
            'fields'=>[$expr.' as '.$name],
            'group'=>[$expr]
        ]));
    }

    static public function rangeDateTime($query,$field,$name,DateInterval $step=null){
        $step=$step?$step:new DateInterval('P1D');
        $step = $step->d * 3600 * 24 + $step->h * 3600 + $step->i * 60 + $step->s;

        return(self::range($query,'UNIX_TIMESTAMP('.$field.')',$name,$step));
    }

    static private $__steps=[
        'y'=>['YEAR','year','%Y'],
        'm'=>['MONTH','month','%m','-'],
        'd'=>['DAY','day','%d','-'],
        'h'=>['HOUR','hour','%H',' '],
        'i'=>['MINUTE','minute','%i',':'],
        's'=>['SECOND','second','%s',':']
    ];

    static public function rangeDateInterval($query,$field,$name,DateInterval $step=null){
        $step=$step?$step:new DateInterval('P1D');

        $items=[];

        foreach(self::$__steps as $var=>$options){
            if($step->$var>0){
                $query=self::range($query,$options[0].'('.$field.')','`'.$name.'_'.$options[1].'`',$step->$var);
                $items[]='FLOOR('.$options[0].'('.$field.')/'.$step->$var.')*'.$step->$var.'+1';
            }
        }

        $expr='CONCAT(';
        $delimiter='';

        foreach($items as $item){
            $expr.=$delimiter;
            $expr.=$item;
            $delimiter=',"-",';
        }

        $expr.=')';

        $query=Base::extend($query,[
            'fields'=>[$expr.' as `'.$name.'`'],
            'group'=>[$expr]
        ]);

        return($query);
    }

    const TR_SECOND='second';
    const TR_MINUTE='minute';
    const TR_HOUR='hour';
    const TR_DAY='day';
    const TR_WEEK='week';
    const TR_MONTH='month';
    const TR_YEAR='year';

    static private $__timeRanges=[
        self::TR_SECOND=>['database'=>'%Y-%m-%d %H:%i:%s','interval'=>'PT1S','datetime'=>'Y-m-d H:i:s'],
        self::TR_MINUTE=>['database'=>'%Y-%m-%d %H:%i','interval'=>'PT1M','datetime'=>'Y-m-d H:i'],
        self::TR_HOUR=>['database'=>'%Y-%m-%d %H','interval'=>'PT1H','datetime'=>'Y-m-d H'],
        self::TR_DAY=>['database'=>'%Y-%m-%d','interval'=>'P1D','datetime'=>'Y-m-d'],
        self::TR_WEEK=>['database'=>'%Y-%v','interval'=>'P7D','datetime'=>'Y-W'],
        self::TR_MONTH=>['database'=>'%Y-%m','interval'=>'P1M','datetime'=>'Y-m'],
        self::TR_YEAR=>['database'=>'%Y','interval'=>'P1Y','datetime'=>'Y']
    ];

    static public function timeRangeProperty($range,$name){
        if(empty(self::$__timeRanges[$range])){
            return(null);
        }

        if(empty(self::$__timeRanges[$range][$name])){
            return(null);
        }

        return(self::$__timeRanges[$range][$name]);
    }

    static public function timeRangeQuery($query,$field,$name,$range){
        $format=self::timeRangeProperty($range,'database');
        $format=!empty($format)?$format:self::timeRangeProperty(self::TR_DAY,'database');

        $expr='DATE_FORMAT('.$field.',"'.$format.'")';

        return(Base::extend($query,[
            'fields'=>[$expr.' as `'.$name.'`'],
            'group'=>[$expr]
        ]));
    }

    static public function rangePeriod($query,$field,$name,$period=BaseTime::PN_THIS_WEEK,DateTime $time=null){
        switch($period){
            case BaseTime::PN_THIS_YEAR:
                $query=self::timeRangeQuery($query,$field,$name,self::TR_MONTH);
                break;
            case BaseTime::PN_THIS_MONTH:
                $query=self::timeRangeQuery($query,$field,$name,self::TR_WEEK);
                break;
            case BaseTime::PN_THIS_WEEK:
                $query=self::timeRangeQuery($query,$field,$name,self::TR_DAY);
                break;
            case BaseTime::PN_LAST_YEAR:
                $query=self::timeRangeQuery($query,$field,$name,self::TR_MONTH);
                break;
            case BaseTime::PN_LAST_MONTH:
                $query=self::timeRangeQuery($query,$field,$name,self::TR_WEEK);
                break;
            case BaseTime::PN_LAST_WEEK:
                $query=self::timeRangeQuery($query,$field,$name,self::TR_DAY);
                break;
        }

        return($query);
    }

}
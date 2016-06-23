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

    const RF_SECOND='second';
    const RF_MINUTE='minute';
    const RF_HOUR='hour';
    const RF_DAY='day';
    const RF_WEEK='week';
    const RF_MONTH='month';
    const RF_YEAR='year';

    static private $__rangeFormats=[
        self::RF_SECOND=>'%Y-%m-%d %H:%i:%s',
        self::RF_MINUTE=>'%Y-%m-%d %H:%i',
        self::RF_HOUR=>'%Y-%m-%d %H',
        self::RF_DAY=>'%Y-%m-%d',
        self::RF_WEEK=>'%Y-%v',
        self::RF_MONTH=>'%Y-%m',
        self::RF_YEAR=>'%Y',
    ];

    static public function rangeFormat($query,$field,$name,$format){
        if(!empty(self::$__rangeFormats[$format])){
            $format=self::$__rangeFormats[$format];
        }

        $expr='DATE_FORMAT('.$field.',"'.$format.'")';

        return(Base::extend($query,[
            'fields'=>[$expr.' as `'.$name.'`'],
            'group'=>[$expr]
        ]));
    }

    static public function rangePeriod($query,$field,$name,$period=BaseTime::PN_THIS_WEEK,DateTime $time=null){
        switch($period){
            case BaseTime::PN_THIS_YEAR:
                $query=self::rangeFormat($query,$field,$name,self::RF_MONTH);
                break;
            case BaseTime::PN_THIS_MONTH:
                $query=self::rangeFormat($query,$field,$name,self::RF_WEEK);
                break;
            case BaseTime::PN_THIS_WEEK:
                $query=self::rangeFormat($query,$field,$name,self::RF_DAY);
                break;
            case BaseTime::PN_LAST_YEAR:
                $query=self::rangeFormat($query,$field,$name,self::RF_MONTH);
                break;
            case BaseTime::PN_LAST_MONTH:
                $query=self::rangeFormat($query,$field,$name,self::RF_WEEK);
                break;
            case BaseTime::PN_LAST_WEEK:
                $query=self::rangeFormat($query,$field,$name,self::RF_DAY);
                break;
        }

        return($query);
    }

}
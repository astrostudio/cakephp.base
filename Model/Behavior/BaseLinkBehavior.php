<?php
App::uses('Base','Vendor/Base');

class BaseLinkBehavior extends ModelBehavior {

    var $settings=array();
    
    var $deleting=array();
    
    public function setup(Model $Model,$settings=array()) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=array(
                'pred'=>'pred_id',
                'succ'=>'succ_id',
                'item'=>'item',
                'node'=>null
            );
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);
        
        $Model->__linkPred=$this->settings[$Model->alias]['pred'];
        $Model->__linkSucc=$this->settings[$Model->alias]['succ'];
        $Model->__linkItem=$this->settings[$Model->alias]['item'];
        $Model->__linkNode=$this->settings[$Model->alias]['node'];
    }
    
    public function loadLink(Model $Model,$predId,$succId){
        $link=$Model->find('first',array(
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkPred=>$predId,
                $Model->alias.'.'.$Model->__linkSucc=>$succId
            )
        ));
        
        return($link);
    }
    
    public function checkLink(Model $Model,$predId,$succId,$transition=true){
        $count=$Model->find('count',array(
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkPred=>$predId,
                $Model->alias.'.'.$Model->__linkSucc=>$succId
            )
        ));
        
        if(!empty($count)){
            return(true);
        }
        
        if($transition){
            $count=$Model->find('count',array(
                'joins'=>array(
                    array('table'=>$Model->useTable,'alias'=>'Succ'.$Model->alias,'conditions'=>array('Succ'.$Model->alias.'.'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkSucc))
                ),
                'conditions'=>array(
                    $Model->alias.'.'.$Model->__linkPred=>$predId,
                    'Succ'.$Model->alias.'.'.$Model->__linkSucc=>$succId
                )
            ));
            
            if($count>0){
                return(true);
            }
        }
                    
        return(false);
    }
    
    public function appendLink(Model $Model,$predId,$succId,$cycles=false,$transition=true,$extendUp=false,$extendDown=false){
        if(!$cycles){
            if($this->checkLink($Model,$succId,$predId,$transition)){
                return(false);
            }
        }
        
        $link=$Model->find('first',array(
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkPred=>$predId,
                $Model->alias.'.'.$Model->__linkSucc=>$succId
            )
        ));
        
        if(empty($link)){
            $link=array(
                $Model->alias=>array(
                    $Model->__linkPred=>$predId,
                    $Model->__linkSucc=>$succId
                )
            );

            $Model->create();
        }
        
        $link[$Model->alias][$Model->__linkItem]=1;

        if(!$Model->save($link)){
            return(false);
        }
        
        if($extendUp){
            if($extendDown){
                if(!$this->extendLinkUp($Model,$predId,$succId)){
                    return(false);
                }

                if(!$this->extendLinkDown($Model,$predId,$succId)){
                    return(false);
                }
            }
            else {
                if(!$this->extendLinkUp($Model,$predId,$succId)){
                    return(false);
                }
            }
        }
        else if($extendDown){
            if(!$this->extendLinkDown($Model,$predId,$succId)){
                return(false);
            }
        }
        
        return($Model->id);        
    }

    public function deleteLink(Model $Model,$id,$cascade=false,$shrinkUp=false,$shrinkDown=false){
        $Model->recursive=-1;
        
        $link=$Model->read(null,$id);
                
        if(empty($link)){
            return(false);
        }
        
        if($shrinkUp){
            if($shrinkDown){
                if(!$this->shrinkLink($Model,$link[$Model->alias][$Model->__linkPred],$link[$Model->alias][$Model->__linkSucc])){
                    return(false);
                }
            }
            else if(!$this->shrinkLinkUp($Model,$link[$Model->alias][$Model->__linkPred],$link[$Model->alias][$Model->__linkSucc])){
                return(false);
            }            
        }
        else if($shrinkDown){
            if(!$this->shrinkLinkDown($Model,$link[$Model->alias][$Model->__linkPred],$link[$Model->alias][$Model->__linkSucc])){
                return(false);
            }            
        }
        
        return($Model->delete($id,$cascade));
    }
    
    public function removeLink(Model $Model,$predId,$succId,$cascade=false,$shrinkUp=false,$shrinkDown=false,$force=true){
        $link=$this->loadLink($Model,$predId,$succId);
        
        if(empty($link)){
            if($force){
                return(false);
            }
            
            return(true);
        }
        
        return($this->deleteLink($Model,$link[$Model->alias][$Model->primaryKey],$cascade,$shrinkUp,$shrinkDown));
    }
    
    public function extendLinkUp(Model $Model,$predId,$succId){
        $links=$Model->find('all',array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'Item'.$Model->alias,'type'=>'LEFT','conditions'=>array('Item'.$Model->alias.'.'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkPred,'Item'.$Model->alias.'.'.$Model->__linkSucc.'='.$succId))
            ),
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkSucc=>$predId,
                'Item'.$Model->alias.'.'.$Model->primaryKey.' is null'
            ),
            'fields'=>array($Model->alias.'.*')
        ));

        foreach($links as $link){
            $Model->create();
            
            if(!$Model->save(array(
                $Model->alias=>array(
                    $Model->__linkPred=>$link[$Model->alias][$Model->__linkPred],
                    $Model->__linkSucc=>$succId,
                    $Model->__linkItem=>0,
                )
            ))){
                return(false);
            }
        }
        
        return(true);
    }
    
    public function extendLinkDown(Model $Model,$predId,$succId){
        $links=$Model->find('all',array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'Item'.$Model->alias,'type'=>'LEFT','conditions'=>array('Item'.$Model->alias.'.'.$Model->__linkPred.'='.$predId,'Item'.$Model->alias.'.'.$Model->__linkSucc.'='.$Model->alias.'.'.$Model->__linkSucc))
            ),
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkPred=>$succId,
                'Item'.$Model->alias.'.'.$Model->primaryKey.' is null'
            ),
            'fields'=>array($Model->alias.'.*')
        ));

        foreach($links as $link){
            $Model->create();
            
            if(!$Model->save(array(
                $Model->alias=>array(
                    $Model->__linkPred=>$predId,
                    $Model->__linkSucc=>$link[$Model->alias][$Model->__linkSucc],
                    $Model->__linkItem=>0,
                )
            ))){
                return(false);
            }
        }
        
        return(true);
    }
    
    public function extendLinkAll(Model $Model){
        $links=$Model->find('all',array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'Succ'.$Model->alias,'conditions'=>array('Succ'.$Model->alias.'.'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkSucc)),
                array('table'=>$Model->useTable,'alias'=>'Item'.$Model->alias,'type'=>'LEFT','conditions'=>array('Item'.$Model->alias.'.'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkPred,'Item'.$Model->alias.'.'.$Model->__linkSucc.'=Succ'.$Model->alias.'.'.$Model->__linkSucc))
            ),
            'conditions'=>array(
                'Item'.$Model->alias.'.'.$Model->primaryKey.' is null'
            ),
            'fields'=>array($Model->alias.'.*','Succ'.$Model->alias.'.*')
        ));

        foreach($links as $link){
            $Model->create();
            
            if(!$Model->save(array(
                $Model->alias=>array(
                    $Model->__linkPred=>$link[$Model->alias][$Model->__linkPred],
                    $Model->__linkSucc=>$link['Succ'.$Model->alias][$Model->__linkSucc],
                    $Model->__linkItem=>0,
                )
            ))){
                return(false);
            }
        }
        
        return(true);
    }

    public function shrinkLink(Model $Model,$predId,$succId){
        $links=$Model->find('all',array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'P','conditions'=>array('P.'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkPred)),
                array('table'=>$Model->useTable,'alias'=>'S','conditions'=>array(
                    'S.'.$Model->__linkPred.'=P.'.$Model->__linkSucc,
                    'S.'.$Model->__linkSucc.'='.$Model->alias.'.'.$Model->__linkSucc
                ))
            ),
            'conditions'=>array(
                'P.'.$Model->__linkSucc=>$predId,
                'S.'.$Model->__linkSucc=>$succId,
                $Model->alias.'.'.$Model->__linkItem=>0
            ),
            'fields'=>array($Model->alias.'.*')
        ));
        
        foreach($links as $link){
            if(!$Model->delete($link[$Model->alias][$Model->primaryKey])){
                return(false);
            }
        }
        
        return(true);
    }
    
    public function shrinkLinkUp(Model $Model,$predId,$succId){
        $links=$Model->find('all',array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'P','conditions'=>array('P.'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkPred))
            ),
            'conditions'=>array(
                'P.'.$Model->__linkSucc=>$predId,
                $Model->alias.'.'.$Model->__LinkSucc=>$succId,
                $Model->alias.'.'.$Model->__linkItem=>0
            ),
            'fields'=>array($Model->alias.'.*')
        ));

        foreach($links as $link){
            if(!$Model->delete($link[$Model->alias][$Model->primaryKey])){
                return(false);
            }
        }
        
        return(true);
    }
    
    public function shrinkLinkDown(Model $Model,$predId,$succId){
        $links=$Model->find('all',array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'S','conditions'=>array('S.'.$Model->__linkSucc.'='.$Model->alias.'.'.$Model->__linkSucc))
            ),
            'conditions'=>array(
                'S.'.$Model->__linkPred=>$succId,
                $Model->alias.'.'.$Model->__linkPred=>$predId,
                $Model->alias.'.'.$Model->__linkItem=>0
            ),
            'fields'=>array($Model->alias.'.*')
        ));
        
        foreach($links as $link){
            if(!$Model->delete($link[$Model->alias][$Model->primaryKey])){
                return(false);
            }
        }
        
        return(true);
    }
    
    public function shrinkLinkAll(Model $Model){
        $links=$Model->find('all',array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'P','conditions'=>array('P,'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkPred)),
                array('table'=>$Model->useTable,'alias'=>'S','conditions'=>array(
                    'S.'.$Model->__linkPred.'=P.'.$Model->__linkSucc,
                    'S.'.$Model->__linkSucc.'='.$Model->alias.'.'.$Model->__linkSucc
                ))
            ),
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkItem=>0
            ),
            'fields'=>array($Model->alias.'.*')
        ));
              
        
        foreach($links as $link){
            if(!$Model->delete($link[$Model->alias][$Model->primaryKey])){
                return(false);
            }
        }
        
        return(true);
    }

    private function __extractLinkSuccLevel(Model $Model,&$nodes,$preds,$query=[]){
        $objects=ClassRegistry::init($Model->__linkNode);

        $links=$objects->find('all',Base::extend([
            'recursive'=>-1,
            'joins'=>[
               ['table'=>$Model->useTable,'alias'=>$Model->alias,'conditions'=>[
                   $Model->alias.'.'.$Model->__linkSucc.'='.$objects->alias.'.'.$objects->primaryKey
               ]]
            ],
            'conditions'=>[
                $Model->alias.'.'.$Model->__linkPred.'<>'.$Model->alias.'.'.$Model->__linkSucc,
                $Model->alias.'.'.$Model->__linkPred=>$preds,
                $Model->alias.'.'.$Model->__linkItem=>1
            ],
            'fields'=>[$Model->alias.'.*',$objects->alias.'.*']
        ],$query));

        if(empty($links)){
            return(false);
        }

        $preds=[];

        foreach($links as $link){
            if(empty($nodes[$link[$objects->alias][$objects->primaryKey]])){
                $link['succs']=[];
                $nodes[$link[$objects->alias][$objects->primaryKey]]=$link;
            }

            $preds[]=$link[$objects->alias][$objects->primaryKey];
            $nodes[$link[$Model->alias][$Model->__linkPred]]['succs'][]=$link;
        }

        $this->__extractLinkSuccLevel($Model,$nodes,$preds,$query);

        return(true);
    }

    private function __extractLinkSuccTree(Model $Model,$nodes,$nodeId){
        $objects=ClassRegistry::init($Model->__linkNode);
        $items=[];

        if(!empty($nodes[$nodeId])){
            foreach($nodes[$nodeId]['succs'] as &$succ){
                $succ['succs']=$this->__extractLinkSuccTree($Model,$nodes,$succ[$objects->alias][$objects->primaryKey]);
                $items[$succ[$objects->alias][$objects->primaryKey]]=$succ;
            }
        }

        return($items);
    }

    private function __extractLinkRoot(Model $Model,$query=[]){
        $objects=ClassRegistry::init($Model->__linkNode);

        $roots=$objects->find('all',Base::extend([
            'recursive'=>-1,
            'joins'=>[
                ['table'=>$Model->useTable,'alias'=>$Model->alias,'type'=>'LEFT','conditions'=>[
                    $Model->alias.'.'.$Model->__linkPred.'<>'.$Model->alias.'.'.$Model->__linkSucc,
                    $Model->alias.'.'.$Model->__linkSucc.'='.$objects->alias.'.'.$objects->primaryKey
                ]]
            ],
            'conditions'=>[
                $Model->alias.'.'.$Model->primaryKey.' is null'
            ],
            'fields'=>[$Model->alias.'.*',$objects->alias.'.*']
        ],$query));

        return($roots);
    }

    public function extractLinkSucc(Model $Model,$nodeId=null,$query=[]){
        $objects=ClassRegistry::init($Model->__linkNode);

        if($nodeId){
            $node=$objects->find('first',Base::extend([
                'recursive'=>-1,
                'conditions'=>[
                    $objects->alias.'.'.$objects->primaryKey=>$nodeId
                ]
            ],$query));

            if(empty($node)){
                return(false);
            }

            $node['succs']=[];
            $nodes=[$node];
            $this->__extractLinkSuccLevel($Model,$nodes,[$nodeId],$query);
            $node['succs']=$this->__extractLinkSuccTree($Model,$nodes,$nodeId);

            return($node);
        }

        $roots=$this->__extractLinkRoot($Model,$query);

        $nodes=[];
        $preds=[];

        foreach($roots as $root){
            $preds[]=$root[$objects->alias][$objects->primaryKey];
            $root['succs']=[];
            $nodes[$root[$objects->alias][$objects->primaryKey]]=$root;
        }

        $this->__extractLinkSuccLevel($Model,$nodes,$preds,$query);

        foreach($roots as &$root){
            $root['succs']=$this->__extractLinkSuccTree($Model,$nodes,$root[$objects->alias][$objects->primaryKey]);
        }

        return($roots);
    }

    private function __extractLinkLeaf(Model $Model,$query=[]){
        $objects=ClassRegistry::init($Model->__linkNode);

        $leafs=$objects->find('all',Base::extend([
            'recursive'=>-1,
            'joins'=>[
                ['table'=>$Model->useTable,'alias'=>$Model->alias,'type'=>'LEFT','conditions'=>[
                    $Model->alias.'.'.$Model->__linkPred.'<>'.$Model->alias.'.'.$Model->__linkSucc,
                    $Model->alias.'.'.$Model->__linkPred.'='.$objects->alias.'.'.$objects->primaryKey
                ]]
            ],
            'conditions'=>[
                $Model->alias.'.'.$Model->primaryKey.' is null'
            ],
            'fields'=>[$Model->alias.'.*',$objects->alias.'.*']
        ],$query));

        return($leafs);
    }

    private function __extractLinkPredLevel(Model $Model,&$nodes,$succs,$query=[]){
        $objects=ClassRegistry::init($Model->__linkNode);

        $links=$objects->find('all',Base::extend([
            'recursive'=>-1,
            'joins'=>[
                ['table'=>$Model->useTable,'alias'=>$Model->alias,'conditions'=>[
                    $Model->alias.'.'.$Model->__linkPred.'='.$objects->alias.'.'.$objects->primaryKey
                ]]
            ],
            'conditions'=>[
                $Model->alias.'.'.$Model->__linkPred.'<>'.$Model->alias.'.'.$Model->__linkSucc,
                $Model->alias.'.'.$Model->__linkSucc=>$succs,
                $Model->alias.'.'.$Model->__linkItem=>1
            ],
            'fields'=>[$Model->alias.'.*',$objects->alias.'.*']
        ],$query));

        if(empty($links)){
            return(false);
        }

        $succs=[];

        foreach($links as $link){
            if(empty($nodes[$link[$objects->alias][$objects->primaryKey]])){
                $link['preds']=[];
                $nodes[$link[$objects->alias][$objects->primaryKey]]=$link;
            }

            $succs[]=$link[$objects->alias][$objects->primaryKey];
            $nodes[$link[$Model->alias][$Model->__linkSucc]]['preds'][]=$link;
        }

        $this->__extractLinkPredLevel($Model,$nodes,$succs,$query);

        return(true);
    }

    private function __extractLinkPredTree(Model $Model,$nodes,$nodeId){
        $objects=ClassRegistry::init($Model->__linkNode);
        $items=[];

        if(!empty($nodes[$nodeId])){
            foreach($nodes[$nodeId]['preds'] as &$pred){
                $pred['preds']=$this->__extractLinkPredTree($Model,$nodes,$pred[$objects->alias][$objects->primaryKey]);
                $items[$pred[$objects->alias][$objects->primaryKey]]=$pred;
            }
        }

        return($items);
    }

    public function extractLinkPred(Model $Model,$nodeId=null,$query=[]){
        $objects=ClassRegistry::init($Model->__linkNode);

        if($nodeId){
            $node=$objects->find('first',Base::extend([
                'recursive'=>-1,
                'conditions'=>[
                    $objects->alias.'.'.$objects->primaryKey=>$nodeId
                ]
            ],$query));

            if(empty($node)){
                return(false);
            }

            $node['preds']=[];
            $nodes=[
                $nodeId=>$node
            ];
            $this->__extractLinkPredLevel($Model,$nodes,[$nodeId],$query);

            $node['preds']=$this->__extractLinkPredTree($Model,$nodes,$nodeId);

            return($node);
        }

        $leafs=$this->__extractLinkLeaf($Model,$query);

        $nodes=[];
        $succs=[];

        foreach($leafs as $leaf){
            $succs[]=$leaf[$objects->alias][$objects->primaryKey];
            $leaf['preds']=[];
            $nodes[$leaf[$objects->alias][$objects->primaryKey]]=$leaf;
        }

        $this->__extractLinkPredLevel($Model,$nodes,$succs,$query);

        foreach($leafs as &$leaf){
            $leaf['preds']=$this->__extractLinkPredTree($Model,$nodes,$leaf[$objects->alias][$objects->primaryKey]);
        }

        return($leafs);
    }

}

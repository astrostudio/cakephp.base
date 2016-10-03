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
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        $id=$this->appendLink_($Model,$predId,$succId,$cycles,$transition,$extendUp,$extendDown);
        
        if(!$id){
            $ds->rollback();
            
            return(false);
        }
                
        if(!$ds->commit()){
            $ds->rollback();
            
            return(false);
        }
        
        return($id);
    }

    public function appendLink_(Model $Model,$predId,$succId,$cycles=false,$transition=true,$extendUp=false,$extendDown=false){
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
                if(!$this->extendLink_($Model,$predId,$succId)){
                    return(false);
                }
            }
            else {
                if(!$this->extendLinkUp_($Model,$predId,$succId)){
                    return(false);
                }
            }
        }
        else if($extendDown){
            if(!$this->extendLinkDown_($Model,$predId,$succId)){
                return(false);
            }
        }
        
        return($Model->id);        
    }

    public function deleteLink(Model $Model,$id,$cascade=false,$shrinkUp=false,$shrinkDown=false){
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->deleteLink_($Model,$id,$cascade,$shrinkUp,$shrinkDown)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function deleteLink_(Model $Model,$id,$cascade=false,$shrinkUp=false,$shrinkDown=false){
        $Model->recursive=-1;
        
        $link=$Model->read(null,$id);
                
        if(empty($link)){
            return(false);
        }
        
        if($shrinkUp){
            if($shrinkDown){
                if(!$this->shrinkLink_($Model,$link[$Model->alias][$Model->__linkPred],$link[$Model->alias][$Model->__linkSucc])){
                    return(false);
                }
            }
            else if(!$this->shrinkLinkUp_($Model,$link[$Model->alias][$Model->__linkPred],$link[$Model->alias][$Model->__linkSucc])){
                return(false);
            }            
        }
        else if($shrinkDown){
            if(!$this->shrinkLinkDown_($Model,$link[$Model->alias][$Model->__linkPred],$link[$Model->alias][$Model->__linkSucc])){
                return(false);
            }            
        }
        
        return($this->delete($id,$cascade));
    }
    
    public function removeLink(Model $Model,$predId,$succId,$cascade=false,$shrinkUp=false,$shrinkDown=false,$force=true){
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->removeLink_($Model,$predId,$succId,$cascade,$shrinkUp,$shrinkDown,$force)){
            $ds->rolback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function removeLink_(Model $Model,$predId,$succId,$cascade=false,$shrinkUp=false,$shrinkDown=false,$force=true){
        $link=$this->loadLink($Model,$predId,$succId);
        
        if(empty($link)){
            if($force){
                return(false);
            }
            
            return(true);
        }
        
        return($this->deleteLink_($link[$Model->alias][$Model->primaryKey],$cascade,$shrinkUp,$shrinkDown));
    }
    
    public function extendLinkUp(Model $Model,$predId,$succId){
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->extendLinkUp_($Model,$predId,$succId)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function extendLinkUp_(Model $Model,$predId,$succId){
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
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->extendLinkDown_($Model,$predId,$succId)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function extendLinkDown_(Model $Model,$predId,$succId){
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
    
    public function extendLink(Model $Model,$predId,$succId){
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->extendLink_($Model,$predId,$succId)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function extendLinkAll(Model $Model){
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->extendLinkAll_($Model)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function extendLinkAll_(Model $Model){
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
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->shrinkLink_($Model,$predId,$succId)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function shrinkLink_(Model $Model,$predId,$succId){
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
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->shrinkLinkUp_($Model,$predId,$succId)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function shrinkLinkUp_(Model $Model,$predId,$succId){
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
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->shrinkLinkDown_($Model,$predId,$succId)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
    
    public function shrinkLinkDown_(Model $Model,$predId,$succId){
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
        $ds=$Model->getDataSource();
        
        if(!$ds->begin()){
            return(false);
        }
        
        if(!$this->shrinkLinkAll_($Model)){
            $ds->rollback();
            
            return(false);
        }
        
        if(!$ds->commit()){
            return(false);
        }
        
        return(true);
    }
 
    public function shrinkLinkAll_(Model $Model){
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
    
    private function __extractLinkLevel(Model $Model,&$nodes,$preds,$query=array()){
        $Node=ClassRegistry::init($this->__linkNode);
        
        $links=$Node->find('all',Base::extend($query,array(
            'recursive'=>-1,
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>$Model->alias,'conditions'=>array($Model->alias.'.'.$Model->__linkSucc.'='.$Node->alias.'.'.$Node->primaryKey))
            ),
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkPred.'<>'.$Model->alias.'.'.$Model->__linkSucc,
                $Model->alias.'.'.$Model->__linkPred=>$preds,
                $Model->alias.'.'.$Model->__linkItem=>1
            ),
            'fields'=>array($Node->alias.'.*',$Model->alias.'.*')
        )));
                
        if(empty($links)){
            return(false);
        }
        
        $preds=array();
        
        foreach($links as $link){
            $nodes[$link[$Node->alias][$Node->primaryKey]]=array();
            $preds[]=$link[$Node->alias][$Node->primaryKey];
            $nodes[$link[$Model->alias][$Model->__linkPred]][$link[$Node->alias][$Node->primaryKey]]=$link;
        }
        
        $this->__extractLinkLevel($Model,$nodes,$preds,$query);
    }
    
    private function __extractLinkNode(&$nodes,$nodeId){
        $items=array();
        
        if(!empty($nodes[$nodeId])){
            foreach($nodes[$nodeId] as $id=>$item){
                $items[$id]=$item;
            }
        }
        
        return($items);
    }
    
    private function __extractLinkRoot(Model $Model,$query=array()){
        $Node=ClassRegistry::init($this->__linkNode);
        
        $roots=$Node->find('all',Base::extend($query,array(
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>$Model->alias,'type'=>'LEFT','conditions'=>array($Model->alias.'.'.$Model->__linkPred.'<>'.$Model->alias.'.'.$Model->__linkSucc,$Model->alias.'.'.$Model->__linkSucc.'='.$Node->alias.'.'.$Node->primaryKey))
            ),
            'conditions'=>array(
                $Model->alias.'.'.$Model->primaryKey.' is null'
            ),
            'fields'=>array($Node->alias.'.*')
        )));
        
        return($roots);
    }
    
    public function extractLinkTree(Model $Model,$query=array(),$nodeId=null){
        $Node=ClassRegistry::init($this->__linkNode);

        if($nodeId){            
            $node=$Node->find('first',Base::extend($query,array(
                'conditions'=>array(
                    $Node->alias.'.'.$Node->primaryKey=>$nodeId
                ),
                'fields'=>array($Node->alias.'.*')
            )));
            
            if(empty($node)){
                return(false);
            }
            
            $nodes=array();
            $this->__extractLinkLevel($Model,$nodes,array($nodeId),$query);
            $node['nodes']=$this->__extractLinkNode($nodes,$nodeId);
            
            return($node);
        }
        
        $roots=$this->__extractLinkRoot($Model,$query);        
        $preds=array();
        
        foreach($roots as $root){
            $preds[]=$root[$Node->alias]['id'];
        }
        
        $nodes=array();
        
        $this->__extractLinkLevel($Model,$nodes,$preds,$query);
        
        foreach($roots as &$root){
            $root['nodes']=$this->__extractLinkNode($nodes,$root[$Node->alias][$Node->primaryKey]);
        }
        
        return($roots);
    }
    
    public function extractLinkSiblings(Model $Model,$nodeId,$otherNodeId){
        $count=$Model->find('count',array(
            'recursive'=>-1,
            'joins'=>array(
                array('table'=>$Model->useTable,'alias'=>'Other'.$Model->alias,'conditions'=>array('Other'.$Model->alias.'.'.$Model->__linkPred.'='.$Model->alias.'.'.$Model->__linkPred))
            ),
            'conditions'=>array(
                $Model->alias.'.'.$Model->__linkSucc=>$nodeId,
                'Other'.$Model->alias.'.'.$Model->__linkSucc=>$otherNodeId,
            )
        ));
        
        return($count>0);
    }
}

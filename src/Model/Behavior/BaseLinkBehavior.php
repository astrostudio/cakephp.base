<?php
namespace Base\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Utility\Hash;

class BaseLinkBehavior extends Behavior {

    private $__linkPred=null;
    private $__linkSucc=null;
    private $__linkItem=null;
    private $__linkNode=null;

    public function initialize(array $config)
    {
        $this->__linkPred=Hash::get($config,'pred','pred_id');
        $this->__linkSucc=Hash::get($config,'succ','succ_id');
        $this->__linkItem=Hash::get($config,'item','item');
        $this->__linkNode=Hash::get($config,'node',null);
    }

    public function loadLink($predId,$succId){
        $link=$this->_table->find()->where([
            $this->_table->alias().'.'.$this->__linkPred=>$predId,
            $this->_table->alias().'.'.$this->__linkSucc=>$succId,
        ])->first();

        return($link);
    }

    public function checkLink($predId,$succId,$transition=true){
        $count=$this->_table->find()->where([
            $this->_table->alias().'.'.$this->__linkPred=>$predId,
            $this->_table->alias().'.'.$this->__linkSucc=>$succId
        ])->count();

        if($count>0){
            return(true);
        }

        if($transition){
            $count=$this->_table->find()->join([
                'table'=>$this->_table->table(),
                'alias'=>'Succ'.$this->_table->alias(),
                'conditions'=>['Succ'.$this->_table->alias().'.'.$this->__linkPred.'='.$this->_table->alias().'.'.$this->__linkSucc]
            ])->where([
                $this->_table->alias().'.'.$this->__linkPred=>$predId,
                'Succ'.$this->_table->alias().'.'.$this->__linkSucc=>$succId
            ])->count();

            if($count>0){
                return(true);
            }
        }

        return(false);
    }

    public function appendLink($predId,$succId,array $options=[])
    {
        $options = array_merge(['cycles' => false, 'transition' => true, 'extendUp' => false, 'extendDown' => false],$options);

        if (!$options['cycles']) {
            if ($this->checkLink($succId, $predId, $options['transition'])) {
                return (false);
            }
        }

        $link = $this->loadLink($predId, $succId);

        if (!$link) {
            $link=$this->_table->newEntity([
                $this->__linkPred=>$predId,
                $this->__linkSucc=>$succId
            ]);
        }

        $link->set($this->__linkItem,1);

        if(!$this->_table->save($link)){
            return(false);
        }

        if($options['extendUp']){
            if($options['extendDown']){
                if(!$this->extendLink($predId,$succId)){
                    return(false);
                }
            }
            else {
                if(!$this->extendLinkUp($predId,$succId)){
                    return(false);
                }
            }
        }
        else if($options['extendDown']){
            if(!$this->extendLinkDown($predId,$succId)){
                return(false);
            }
        }

        return($link->id);
    }

    public function deleteLink($id,array $options=[]){
        $options=array_merge(['cascade'=>false,'shrinkUp'=>false,'shrinkDown'=>false],$options);
        $link=$this->_table->get($id);

        if(!$link){
            return(false);
        }

        if($options['shrinkUp']){
            if($options['shrinkDown']){
                if(!$this->shrinkLink($link->get($this->__linkPred),$link->get($this->__linkSucc))){
                    return(false);
                }
            }
            else if(!$this->shrinkLinkUp($link->get($this->__linkPred),$link->get($this->__linkSucc))){
                return(false);
            }
        }
        else if($options['shrinkDown']){
            if(!$this->shrinkLinkDown($link->get($this->__linkPred),$link->get($this->__linkSucc))){
                return(false);
            }
        }

        return($this->_table->delete($link,$options));
    }

    public function removeLink($predId,$succId,array $options=[]){
        $link=$this->loadLink($predId,$succId);

        if(!$link){
            if(!empty($options['force'])){
                return(false);
            }

            return(true);
        }

        return($this->deleteLink($link->get($this->_table->primaryKey()),$options));
    }

    public function extendLinkUp($predId,$succId){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>'Item'.$this->_table->alias(),
            'type'=>'LEFT',
            'conditions'=>[
                'Item'.$this->_table->alias().'.'.$this->__linkPred.'='.$this->_table->alias().'.'.$this->__linkPred,'Item'.$this->_table->alias().'.'.$this->__linkSucc.'='.$succId
            ]
        ])->where([
            $this->_table->alias().'.'.$this->__linkSucc=>$predId,
            'Item'.$this->_table->alias().'.'.$this->__linkPred.' is null'
        ])->select($this->_table)->all();

        foreach($links as $link){
            $newLink=$this->_table->newEntity([
                $this->__linkPred=>$link->get($this->__linkPred),
                $this->__linkSucc=>$succId,
                $this->__linkItem=>0
            ]);

            if(!$this->_table->save($newLink)){
                return(false);
            }
        }

        return(true);
    }

    public function extendLinkDown($predId,$succId){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>'Item'.$this->_table->alias(),
            'type'=>'LEFT',
            'conditions'=>[
                'Item'.$this->_table->alias().'.'.$this->__linkPred.'='.$predId,'Item'.$this->_table->alias().'.'.$this->__linkSucc.'='.$this->_table->alias().'.'.$this->__linkSucc
            ]
        ])->where([
            $this->_table->alias().'.'.$this->__linkPred=>$succId,
            'Item'.$this->_table->alias().'.'.$this->__linkPred.' is null'
        ])->select($this->_table)->all();

        foreach($links as $link){
            $newLink=$this->_table->newEntity([
                $this->__linkPred=>$predId,
                $this->__linkSucc=>$link->get($this->__linkSucc),
                $this->__linkItem=>0
            ]);

            if(!$this->_table->save($newLink)){
                return(false);
            }
        }

        return(true);
    }

    public function extendLink($predId,$succId){
        return($this->extendLinkUp($predId,$succId) and $this->extendLinkDown($predId,$succId));
    }

    public function extendLinkAll(){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>'Succ'.$this->_table->alias(),
            'conditions'=>[
                'Succ'.$this->_table->alias().'.'.$this->__linkPred.'='.$this->_table->alias().'.'.$this->__linkSucc
            ]
        ])->join([
            'table'=>$this->_table->table(),
            'alias'=>'Item'.$this->_table->table(),
            'type'=>'LEFT',
            'conditions'=>[
                'Item'.$this->_table->alias().'.'.$this->__linkPred.'='.$this->_table->alias().'.'.$this->__linkPred,
                'Item'.$this->_table->alias().'.'.$this->__linkSucc.'=Succ'.$this->_table->alias().'.'.$this->__linkSucc
            ]
        ])->where([
            'Item'.$this->_table->alias().'.'.$this->__linkPred.' is null'
        ])->select([$this->_table->alias().'.*','Succ'.$this->_table->alias().'.*'])->toArray();

        foreach($links as $link){
            $newLink=$this->_table->newEntity([
                $this->__linkPred=>$link[$this->_table->alias()][$this->__linkPred],
                $this->__linkSucc=>$link['Succ'.$this->_table->alias()][$this->__linkSucc],
                $this->__linkItem=>0
            ]);

            if(!$this->_table->save($newLink)){
                return(false);
            }
        }

        return(true);
    }

    public function shrinkLink($predId,$succId){
        return($this->shrinkLinkUp($predId,$succId) and $this->shrinkLinkDown($predId,$succId));
    }

    public function shrinkLinkUp($predId,$succId){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>'P',
            'conditions'=> ['P.'.$this->__linkPred.'='.$this->_table->alias().'.'.$this->__linkPred]
        ])->where([
            'P.'.$this->__linkSucc=>$predId,
            $this->_table->alias().'.'.$this->__linkSucc=>$succId,
            $this->_table->alias().'.'.$this->__linkItem=>0
        ])->select($this->_table)->all();

        foreach($links as $link){
            if(!$this->_table->delete($link)){
                return(false);
            }
        }

        return(true);
    }

    public function shrinkLinkDown($predId,$succId){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>'S',
            'conditions'=>['S.'.$this->__linkSucc.'='.$this->_table->alias().'.'.$this->__linkSucc]
        ])->where([
            'S.'.$this->__linkPred=>$succId,
            $this->_table->alias().'.'.$this->__linkPred=>$predId,
            $this->_table->alias().'.'.$this->__linkItem=>0
        ])->select($this->_table)->all();

        foreach($links as $link){
            if(!$this->_table->delete($link)){
                return(false);
            }
        }

        return(true);
    }

    public function shrinkLinkAll(){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>'P',
            'conditions'=>['P,'.$this->__linkPred.'='.$this->_table->alias().'.'.$this->__linkPred]
        ])->join([
            'table'=>$this->_table->table(),
            'alias'=>'S',
            'conditions'=>[
                'S.'.$this->__linkPred.'=P.'.$this->__linkSucc,
                'S.'.$this->__linkSucc.'='.$this->_table->alias().'.'.$this->__linkSucc
            ]
        ])->where([
            $this->_table->alias().'.'.$this->__linkItem=>0
        ])->select([$this->_table->alias().'.*'])->all();

        foreach($links as $link){
            if(!$this->_table->delete($link)){
                return(false);
            }
        }

        return(true);
    }

    private function __extractLinkSuccLevel(&$nodes,$preds){
        if(empty($preds)){
            return;
        }
        
        $objects=TableRegistry::get($this->__linkNode);

        $links=$objects->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>$this->_table->alias(),
            'conditions'=>[
                $this->_table->alias().'.'.$this->__linkSucc.'='.$objects->alias().'.'.$objects->primaryKey()
            ]
        ])->where([
            $this->_table->alias().'.'.$this->__linkPred.'<>'.$this->_table->alias().'.'.$this->__linkSucc,
            $this->_table->alias().'.'.$this->__linkPred.' IN'=>$preds,
            $this->_table->alias().'.'.$this->__linkItem=>1
        ])->select($objects)->select([$this->_table->alias().'.'.$this->__linkPred])->toArray();

        if(empty($links)){
            return;
        }

        $preds=[];

        foreach($links as $link){
            $linkId=$link->get($objects->primaryKey());
            $preds[]=$linkId;

            if(!isset($nodes[$linkId])){
                $nodes[$linkId]=[];
            }

            $nodes[$link->get($this->_table->alias())[$this->__linkPred]][$linkId]=$link;
        }

        $this->__extractLinkSuccLevel($nodes,$preds);
    }

    private function __extractLinkNode(&$nodes,$nodeId){
        $items=[];

        if(!empty($nodes[$nodeId])){
            foreach($nodes[$nodeId] as $id=>$item){
                $items[$id]=$item;
            }
        }

        return($items);
    }

    public function extractLinkRoot(){
        $objects=TableRegistry::get($this->__linkNode);

        $roots=$objects->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>$this->_table->alias(),
            'type'=>'LEFT',
            'conditions'=>[
                $this->_table->alias().'.'.$this->__linkPred.'<>'.$this->_table->alias().'.'.$this->__linkSucc,$this->_table->alias().'.'.$this->__linkSucc.'='.$objects->alias().'.'.$objects->primaryKey()
            ]
        ])->where([
            $this->_table->alias().'.'.$this->__linkPred.' is null'
        ])->select($objects)->toArray();

        return($roots);
    }

    public function extractLinkSucc($nodeId=null,$field='nodes'){
        $objects=TableRegistry::get($this->__linkNode);

        if($nodeId){
            $node=$objects->find()->where([
                $objects->alias().'.'.$objects->primaryKey()=>$nodeId
            ])->first();

            if(empty($node)){
                return(false);
            }

            $nodes=[];
            $nodes[$node->get($objects->primaryKey())]=[];
            $this->__extractLinkSuccLevel($nodes,[$node->get($objects->primaryKey())]);
            $node->set($field,$this->__extractLinkNode($nodes,$nodeId));

            return($node);
        }

        $roots=$this->extractLinkRoot();

        $nodes=[];
        $preds=[];

        foreach($roots as $root){
            $nodes[$root->get($objects->primaryKey())]=[];
            $preds[]=$root->get($objects->primaryKey());
        }

        $this->__extractLinkSuccLevel($nodes,$preds);

        foreach($roots as &$root){
            $root->set($field,$this->__extractLinkNode($nodes,$root->get($objects->primaryKey())));
        }

        return($roots);
    }

    public function extractLinkLeaf(){
        $objects=TableRegistry::get($this->__linkNode);

        $leafs=$objects->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>$this->_table->alias(),
            'type'=>'LEFT',
            'conditions'=>[
                $this->_table->alias().'.'.$this->__linkPred.'<>'.$this->_table->alias().'.'.$this->__linkSucc,$this->_table->alias().'.'.$this->__linkPred.'='.$objects->alias().'.'.$objects->primaryKey()
            ]
        ])->where([
            $this->_table->alias().'.'.$this->__linkPred.' is null'
        ])->select($objects)->toArray();

        return($leafs);
    }

    private function __extractLinkPredLevel(&$nodes,$succs){
        if(empty($succs)){
            return;
        }
        
        $objects=TableRegistry::get($this->__linkNode);

        $links=$objects->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>$this->_table->alias(),
            'conditions'=>[
                $this->_table->alias().'.'.$this->__linkPred.'='.$objects->alias().'.'.$objects->primaryKey()
            ]
        ])->where([
            $this->_table->alias().'.'.$this->__linkPred.'<>'.$this->_table->alias().'.'.$this->__linkSucc,
            $this->_table->alias().'.'.$this->__linkSucc.' IN'=>$succs,
            $this->_table->alias().'.'.$this->__linkItem=>1
        ])->select($objects)->select([$this->_table->alias().'.'.$this->__linkSucc])->toArray();

        if(empty($links)){
            return(false);
        }

        $succs=[];

        foreach($links as $link){
            $linkId=$link->get($objects->primaryKey());
            $succs[]=$linkId;

            if(!isset($nodes[$linkId])){
                $nodes[$linkId]=[];
            }

            $nodes[$link->get($this->_table->alias())[$this->__linkSucc]][]=$link;
        }

        $this->__extractLinkPredLevel($nodes,$succs);
    }


    public function extractLinkPred($nodeId=null,$field='preds'){
        $objects=TableRegistry::get($this->__linkNode);

        if($nodeId){
            $node=$objects->find()->where([
                $objects->alias().'.'.$objects->primaryKey()=>$nodeId
            ])->first();

            if(empty($node)){
                return(false);
            }

            $nodes=[];
            $nodes[$node->get($objects->primaryKey())]=[];
            $this->__extractLinkPredLevel($nodes,[$node->get($objects->primaryKey())]);
            $node->set($field,$this->__extractLinkNode($nodes,$nodeId));

            return($node);
        }

        $leafs=$this->extractLinkLeaf();

        $nodes=[];
        $succs=[];

        foreach($leafs as $leaf){
            $nodes[$leaf->get($objects->primaryKey())]=[];
            $succs[]=$leaf->get($objects->primaryKey());
        }

        $this->__extractLinkPredLevel($nodes,$succs);

        foreach($leafs as &$leaf){
            $leaf->set($field,$this->__extractLinkNode($nodes,$leaf->get($objects->primaryKey())));
        }

        return($leafs);
    }

    public function extractLinkSiblings($nodeId,$otherNodeId){
        $count=$this->_table->find()->join([
            'table'=>$this->_table->table(),
            'alias'=>'Other'.$this->_table->alias(),
            'conditions'=> ['Other'.$this->_table->alias().'.'.$this->__linkPred.'='.$this->_table->alias().'.'.$this->__linkPred]
        ])->where([
            $this->_table->alias().'.'.$this->__linkSucc=>$nodeId,
            'Other'.$this->_table->alias().'.'.$this->__linkSucc=>$otherNodeId
        ])->count();

        return($count>0);
    }

    public function queryLink(array $options=[],array $params=[]){
        $objects=TableRegistry::get($this->__linkNode);
        $alias=$objects->alias();
        $primaryKey=$objects->primaryKey();
        $params=array_merge(['link'=>'link','node'=>'node','alias'=>$alias.'Link'],$params);
        $query=[];

        if(!empty($options[$params['link']])){
            switch($options[$params['link']]){
                case 'root':
                    if(!empty($options[$params['node']])){
                        $query=[
                            'join'=>[[
                                'table'=>$this->_table->table(),
                                'alias'=>$params['alias'],
                                'conditions'=>[
                                    $alias.'.'.$this->__linkPred.'='.$alias.'.'.$primaryKey
                                ]
                            ]],
                            'conditions'=>[
                                $params['alias'].'.'.$this->__linkSucc=>$options[$params['node']],
                                $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                            ]
                        ];
                    }
                    else {
                        $query=[
                            'join'=>[[
                                'table'=>$this->_table->table(),
                                'alias'=>$params['alias'],
                                'type'=>'LEFT',
                                'conditions'=>[
                                    $params['alias'].'.'.$this->__linkSucc.'='.$alias.'.'.$primaryKey,
                                    $params['alias'].'.'.$this->__linkPred.'<>'.$params['alias'].'.'.$this->__linkSucc
                                ]
                            ]],
                            'conditions'=>[
                                $params['alias'].'.'.$this->__linkSucc.' is null'
                            ]];
                    }

                    break;
                case 'node':
                    if(!empty($options[$params['node']])){
                        $query=[
                            'join'=>[[
                                'table'=>$this->_table->table(),
                                'alias'=>$params['alias'],
                                'conditions'=>[
                                    $params['alias'].'.'.$this->__linkPred.'='.$alias.'.'.$primaryKey
                                ]
                            ]],
                            'conditions'=>[
                                $params['alias'].'.'.$this->__linkItem=>1,
                                $params['alias'].'.'.$this->__linkSucc=>$options[$params['node']],
                                $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                            ]];
                    }

                    break;
                case 'item':
                    if(!empty($options[$params['node']])){
                        $query=[
                            'join'=>[[
                                'table'=>$this->_table->alias(),
                                'alias'=>$params['alias'],
                                'conditions'=>[
                                    $params['alias'].'.'.$this->__linkSucc.'='.$alias.'.'.$primaryKey
                                ]
                            ]],
                            'conditions'=>[
                                $params['alias'].'.'.$this->__linkItem=>1,
                                $params['alias'].'.'.$this->__linkPred=>$options[$params['node']],
                                $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                            ]
                        ];
                    }

                    break;
                case 'leaf':
                    if(!empty($options['activity'])){
                        $query=[
                            'join'=>[[
                                'table'=>$this->_table->table(),
                                'alias'=>$params['alias'],
                                'conditions'=>[
                                    $alias.'.'.$this->__linkSucc.'='.$alias.'.'.$primaryKey
                                ]
                            ]],
                            'conditions'=>[
                                $params['alias'].'.'.$this->__linkPred=>$options[$params['node']],
                                $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                            ]];
                    }
                    else {
                        $query=[
                            'join'=>[[
                                'table'=>$this->_table->table(),
                                'alias'=>$params['alias'],
                                'type'=>'LEFT',
                                'conditions'=>[
                                    $params['alias'].'.'.$this->__linkPred.'='.$alias.'.'.$primaryKey,
                                    $params['alias'].'.'.$this->__linkPred.'<>'.$params['alias'].'.'.$this->__linkSucc
                                ]
                            ]],
                            'conditions'=>[
                                $params['alias'].'.'.$this->__linkPred.' is null'
                            ]];
                    }

                    break;
            }
        }

        if(!empty($options[$params['node'].'-not'])){
            $query=\Base\Base::extend($query,[
                'join'=>[[
                    'table'=>$this->_table->table(),
                    'alias'=>$params['alias'].'None',
                    'type'=>'LEFT',
                    'conditions'=>[
                        $params['alias'].'None.'.$this->__linkSucc.'='.$alias.'.'.$primaryKey,
                        $params['alias'].'None.'.$this->__linkPred.'='.$options[$params['node'].'-not']]
                    ]
                ],
                'conditions'=>[
                    $params['alias'].'None.'.$this->_table->__linkSucc.' is null'
                ]
            ]);
        }

        return($query);
    }

}

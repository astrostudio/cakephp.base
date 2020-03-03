<?php
namespace Base\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class LinkBehavior extends Behavior {

    private $linkPred=null;
    private $linkSucc=null;
    private $linkItem=null;
    private $linkNode=null;

    public function initialize(array $config):void
    {
        $this->linkPred=Hash::get($config,'pred','pred_id');
        $this->linkSucc=Hash::get($config,'succ','succ_id');
        $this->linkItem=Hash::get($config,'item','item');
        $this->linkNode=Hash::get($config,'node',null);
    }

    public function loadLink($predId,$succId){
        $link=$this->_table->find()->where([
            $this->_table->getAlias().'.'.$this->linkPred=>$predId,
            $this->_table->getAlias().'.'.$this->linkSucc=>$succId,
        ])->first();

        return($link);
    }

    public function checkLink($predId,$succId,$transition=true){
        $count=$this->_table->find()->where([
            $this->_table->getAlias().'.'.$this->linkPred=>$predId,
            $this->_table->getAlias().'.'.$this->linkSucc=>$succId
        ])->count();

        if($count>0){
            return(true);
        }

        if($transition){
            $count=$this->_table->find()->join([
                'table'=>$this->_table->getTable(),
                'alias'=>'Succ'.$this->_table->getAlias(),
                'conditions'=>['Succ'.$this->_table->getAlias().'.'.$this->linkPred.'='.$this->_table->getAlias().'.'.$this->linkSucc]
            ])->where([
                $this->_table->getAlias().'.'.$this->linkPred=>$predId,
                'Succ'.$this->_table->getAlias().'.'.$this->linkSucc=>$succId
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
                $this->linkPred=>$predId,
                $this->linkSucc=>$succId
            ]);
        }

        $link->set($this->linkItem,1);

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
                if(!$this->shrinkLink($link->get($this->linkPred),$link->get($this->linkSucc))){
                    return(false);
                }
            }
            else if(!$this->shrinkLinkUp($link->get($this->linkPred),$link->get($this->linkSucc))){
                return(false);
            }
        }
        else if($options['shrinkDown']){
            if(!$this->shrinkLinkDown($link->get($this->linkPred),$link->get($this->linkSucc))){
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

        if($options['shrinkUp']){
            if($options['shrinkDown']){
                if(!$this->shrinkLink($link->get($this->linkPred),$link->get($this->linkSucc))){
                    return(false);
                }
            }
            else if(!$this->shrinkLinkUp($link->get($this->linkPred),$link->get($this->linkSucc))){
                return(false);
            }
        }
        else if($options['shrinkDown']){
            if(!$this->shrinkLinkDown($link->get($this->linkPred),$link->get($this->linkSucc))){
                return(false);
            }
        }

        return($this->_table->delete($link,$options));
    }

    public function extendLinkUp($predId,$succId){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->getTable(),
            'alias'=>'Item'.$this->_table->getAlias(),
            'type'=>'LEFT',
            'conditions'=>[
                'Item'.$this->_table->getAlias().'.'.$this->linkPred.'='.$this->_table->getAlias().'.'.$this->linkPred,'Item'.$this->_table->getAlias().'.'.$this->linkSucc.'='.$succId
            ]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkSucc=>$predId,
            'Item'.$this->_table->getAlias().'.'.$this->linkPred.' is null'
        ])->select($this->_table)->all();

        /** @var \Cake\ORM\Entity $link */
        foreach($links as $link){
            $newLink=$this->_table->newEntity([
                $this->linkPred=>$link->get($this->linkPred),
                $this->linkSucc=>$succId,
                $this->linkItem=>0
            ]);

            if(!$this->_table->save($newLink)){
                return(false);
            }
        }

        return(true);
    }

    public function extendLinkDown($predId,$succId){
        $links=$this->_table->find()->join([
            'table'=>$this->_table->getTable(),
            'alias'=>'Item'.$this->_table->getAlias(),
            'type'=>'LEFT',
            'conditions'=>[
                'Item'.$this->_table->getAlias().'.'.$this->linkPred.'='.$predId,'Item'.$this->_table->getAlias().'.'.$this->linkSucc.'='.$this->_table->getAlias().'.'.$this->linkSucc
            ]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkPred=>$succId,
            'Item'.$this->_table->getAlias().'.'.$this->linkPred.' is null'
        ])->select($this->_table)->all();

        /** @var \Cake\ORM\Entity $link */
        foreach($links as $link){
            $newLink=$this->_table->newEntity([
                $this->linkPred=>$predId,
                $this->linkSucc=>$link->get($this->linkSucc),
                $this->linkItem=>0
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
        $query=$this->_table->find()->join([
            'table'=>$this->_table->getTable(),
            'alias'=>'Succ'.$this->_table->getAlias(),
            'conditions'=>[
                'Succ'.$this->_table->getAlias().'.'.$this->linkPred.'='.$this->_table->getAlias().'.'.$this->linkSucc
            ]
        ])->join([
            'table'=>$this->_table->getTable(),
            'alias'=>'Item'.$this->_table->getAlias(),
            'type'=>'LEFT',
            'conditions'=>[
                'Item'.$this->_table->getAlias().'.'.$this->linkPred.'='.$this->_table->getAlias().'.'.$this->linkPred,
                'Item'.$this->_table->getAlias().'.'.$this->linkSucc.'=Succ'.$this->_table->getAlias().'.'.$this->linkSucc
            ]
        ])->where([
            'Item'.$this->_table->getAlias().'.'.$this->linkPred.' is null'
        ]);//->select([$this->_table->getAlias().'.*','Succ'.$this->_table->getAlias().'.*']);

        $query=$query->select([
            'link_pred'=>$this->_table->getAlias().'.'.$this->linkPred,
            'link_succ'=>'Succ'.$this->_table->getAlias().'.'.$this->linkSucc
        ]);
        //$query=$query->select($this->_table->getAlias())->select('Succ'.$this->_table->getAlias());

        $links=$query->toArray();

        /** @var \Cake\ORM\Entity $link */
        foreach($links as $link){
            $newLink=$this->_table->newEntity([
                $this->linkPred=>$link->get('link_pred'),
                $this->linkSucc=>$link->get('link_succ'),
                $this->linkItem=>0
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
            'table'=>$this->_table->getTable(),
            'alias'=>'P',
            'conditions'=> ['P.'.$this->linkPred.'='.$this->_table->getAlias().'.'.$this->linkPred]
        ])->where([
            'P.'.$this->linkSucc=>$predId,
            $this->_table->getAlias().'.'.$this->linkSucc=>$succId,
            $this->_table->getAlias().'.'.$this->linkItem=>0
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
            'table'=>$this->_table->getTable(),
            'alias'=>'S',
            'conditions'=>['S.'.$this->linkSucc.'='.$this->_table->getAlias().'.'.$this->linkSucc]
        ])->where([
            'S.'.$this->linkPred=>$succId,
            $this->_table->getAlias().'.'.$this->linkPred=>$predId,
            $this->_table->getAlias().'.'.$this->linkItem=>0
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
            'table'=>$this->_table->getTable(),
            'alias'=>'P',
            'conditions'=>['P,'.$this->linkPred.'='.$this->_table->getAlias().'.'.$this->linkPred]
        ])->join([
            'table'=>$this->_table->getTable(),
            'alias'=>'S',
            'conditions'=>[
                'S.'.$this->linkPred.'=P.'.$this->linkSucc,
                'S.'.$this->linkSucc.'='.$this->_table->getAlias().'.'.$this->linkSucc
            ]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkItem=>0
        ])->select([$this->_table->getAlias().'.*'])->all();

        foreach($links as $link){
            if(!$this->_table->delete($link)){
                return(false);
            }
        }

        return(true);
    }

    private function extractLinkSuccLevel(&$nodes,$preds,array $options=[]){
        if(empty($preds)){
            return;
        }

        $objects=TableRegistry::getTableLocator()->get($this->linkNode);

        $links=$objects->find()->applyOptions($options)->join([
            'table'=>$this->_table->getTable(),
            'alias'=>$this->_table->getAlias(),
            'conditions'=>[
                $this->_table->getAlias().'.'.$this->linkSucc.'='.$objects->getAlias().'.'.$objects->getPrimaryKey()
            ]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkPred.'<>'.$this->_table->getAlias().'.'.$this->linkSucc,
            $this->_table->getAlias().'.'.$this->linkPred.' IN'=>$preds,
            $this->_table->getAlias().'.'.$this->linkItem=>1
        ])->select($objects)->select([$this->_table->getAlias().'.'.$this->linkPred])->toArray();

        if(empty($links)){
            return;
        }

        $preds=[];

        /** @var \Cake\ORM\Entity $link */
        foreach($links as $link){
            $linkId=$link->get($objects->getPrimaryKey());
            $preds[]=$linkId;

            if(!isset($nodes[$linkId])){
                $nodes[$linkId]=[];
            }

            $nodes[$link->get($this->_table->getAlias())[$this->linkPred]][$linkId]=$link;
        }

        $this->extractLinkSuccLevel($nodes,$preds);
    }

    private function extractLinkNode(&$nodes,$nodeId,$field){
        $items=[];

        if(!empty($nodes[$nodeId])){
            /**
             * @var \Cake\ORM\Entity $item
             */
            foreach($nodes[$nodeId] as $id=>$item){
                $items[$id]=$item;
                $item->set($field,$this->extractLinkNode($nodes,$item->id,$field));
            }
        }

        return($items);
    }

    public function extractLinkRoot(array $options=[]){
        $objects=TableRegistry::getTableLocator()->get($this->linkNode);

        $roots=$objects->find()->applyOptions($options)->join([
            'table'=>$this->_table->getTable(),
            'alias'=>$this->_table->getAlias(),
            'type'=>'LEFT',
            'conditions'=>[
                $this->_table->getAlias().'.'.$this->linkPred.'<>'.$this->_table->getAlias().'.'.$this->linkSucc,$this->_table->getAlias().'.'.$this->linkSucc.'='.$objects->getAlias().'.'.$objects->getPrimaryKey()
            ]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkPred.' is null'
        ])->select($objects)->toArray();

        return($roots);
    }

    public function extractLinkSucc($nodeId=null,array $options=[],$field='nodes'){
        $objects=TableRegistry::getTableLocator()->get($this->linkNode);

        if($nodeId){
            $node=$objects->find()->applyOptions($options)->where([
                $objects->getAlias().'.'.$objects->getPrimaryKey()=>$nodeId
            ])->first();

            if(empty($node)){
                return(false);
            }

            $nodes=[];
            $nodes[$node->get($objects->getPrimaryKey())]=[];
            $this->extractLinkSuccLevel($nodes,[$node->get($objects->getPrimaryKey())],$options);
            $node->set($field,$this->extractLinkNode($nodes,$nodeId,$field));

            return($node);
        }

        $roots=$this->extractLinkRoot($options);

        $nodes=[];
        $preds=[];

        /** @var \Cake\ORM\Entity $root */
        foreach($roots as $root){
            $nodes[$root->get($objects->getPrimaryKey())]=[];
            $preds[]=$root->get($objects->getPrimaryKey());
        }

        $this->extractLinkSuccLevel($nodes,$preds,$options);

        foreach($roots as &$root){
            $root->set($field,$this->extractLinkNode($nodes,$root->get($objects->getPrimaryKey()),$field));
        }

        return($roots);
    }

    public function extractLinkLeaf(array $options=[]){
        $objects=TableRegistry::getTableLocator()->get($this->linkNode);

        $leafs=$objects->find()->applyOptions($options)->join([
            'table'=>$this->_table->getTable(),
            'alias'=>$this->_table->getAlias(),
            'type'=>'LEFT',
            'conditions'=>[
                $this->_table->getAlias().'.'.$this->linkPred.'<>'.$this->_table->getAlias().'.'.$this->linkSucc,$this->_table->getAlias().'.'.$this->linkPred.'='.$objects->getAlias().'.'.$objects->getPrimaryKey()
            ]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkPred.' is null'
        ])->select($objects)->toArray();

        return($leafs);
    }

    private function extractLinkPredLevel(&$nodes,$succs,array $options=[]){
        if(empty($succs)){
            return(null);
        }

        $objects=TableRegistry::getTableLocator()->get($this->linkNode);

        $links=$objects->find()->applyOptions($options)->join([
            'table'=>$this->_table->getTable(),
            'alias'=>$this->_table->getAlias(),
            'conditions'=>[
                $this->_table->getAlias().'.'.$this->linkPred.'='.$objects->getAlias().'.'.$objects->getPrimaryKey()
            ]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkPred.'<>'.$this->_table->getAlias().'.'.$this->linkSucc,
            $this->_table->getAlias().'.'.$this->linkSucc.' IN'=>$succs,
            $this->_table->getAlias().'.'.$this->linkItem=>1
        ])->select($objects)->select([$this->_table->getAlias().'.'.$this->linkSucc])->toArray();

        if(empty($links)){
            return(false);
        }

        $succs=[];

        /** @var \Cake\ORM\Entity $link */
        foreach($links as $link){
            $linkId=$link->get($objects->getPrimaryKey());
            $succs[]=$linkId;

            if(!isset($nodes[$linkId])){
                $nodes[$linkId]=[];
            }

            $nodes[$link->get($this->_table->getAlias())[$this->linkSucc]][]=$link;
        }

        $this->extractLinkPredLevel($nodes,$succs,$options);

        return(null);
    }


    public function extractLinkPred($nodeId=null,array $options=[],$field='preds'){
        $objects=TableRegistry::getTableLocator()->get($this->linkNode);

        if($nodeId){
            $node=$objects->find()->applyOptions($options)->where([
                $objects->getAlias().'.'.$objects->getPrimaryKey()=>$nodeId
            ])->first();

            if(empty($node)){
                return(false);
            }

            $nodes=[];
            $nodes[$node->get($objects->getPrimaryKey())]=[];
            $this->extractLinkPredLevel($nodes,[$node->get($objects->getPrimaryKey())],$options);
            $node->set($field,$this->extractLinkNode($nodes,$nodeId,$field));

            return($node);
        }

        $leafs=$this->extractLinkLeaf($options);

        $nodes=[];
        $succs=[];

        /** @var \Cake\ORM\Entity $leaf */
        foreach($leafs as $leaf){
            $nodes[$leaf->get($objects->getPrimaryKey())]=[];
            $succs[]=$leaf->get($objects->getPrimaryKey());
        }

        $this->extractLinkPredLevel($nodes,$succs,$options);

        foreach($leafs as &$leaf){
            $leaf->set($field,$this->extractLinkNode($nodes,$leaf->get($objects->getPrimaryKey()),$field));
        }

        return($leafs);
    }

    public function extractLinkSiblings($nodeId,$otherNodeId,array $options=[]){
        $count=$this->_table->find()->applyOptions($options)->join([
            'table'=>$this->_table->getTable(),
            'alias'=>'Other'.$this->_table->getAlias(),
            'conditions'=> ['Other'.$this->_table->getAlias().'.'.$this->linkPred.'='.$this->_table->getAlias().'.'.$this->linkPred]
        ])->where([
            $this->_table->getAlias().'.'.$this->linkSucc=>$nodeId,
            'Other'.$this->_table->getAlias().'.'.$this->linkSucc=>$otherNodeId
        ])->count();

        return($count>0);
    }

    public function queryLink(Query $query,array $options=[],array $params=[]){
        $objects=TableRegistry::getTableLocator()->get($this->linkNode);
        $alias=$objects->getAlias();
        $primaryKey=$objects->getPrimaryKey();
        $params=array_merge(['link'=>'link','node'=>'node','alias'=>$alias.'Link'],$params);

        if(!empty($options[$params['link']])){
            switch($options[$params['link']]){
                case 'root':
                    if(!empty($options[$params['node']])){
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'conditions'=>[
                                $alias.'.'.$this->linkPred.'='.$alias.'.'.$primaryKey
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkSucc=>$options[$params['node']],
                            $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                        ]);
                    }
                    else {
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'type'=>'LEFT',
                            'conditions'=>[
                                $params['alias'].'.'.$this->linkSucc.'='.$alias.'.'.$primaryKey,
                                $params['alias'].'.'.$this->linkPred.'<>'.$params['alias'].'.'.$this->linkSucc
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkSucc.' is null'
                        ]);
                    }

                    break;
                case 'node':
                    if(!empty($options[$params['node']])){
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'conditions'=>[
                                $params['alias'].'.'.$this->linkPred.'='.$alias.'.'.$primaryKey
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkItem=>1,
                            $params['alias'].'.'.$this->linkSucc=>$options[$params['node']],
                            $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                        ]);
                    }

                    break;
                case 'pred':
                    if(!empty($options[$params['node']])){
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'conditions'=>[
                                $params['alias'].'.'.$this->linkPred.'='.$alias.'.'.$primaryKey
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkSucc=>$options[$params['node']],
                            $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                        ]);
                    }

                    break;
                case 'item':
                    if(!empty($options[$params['node']])){
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'conditions'=>[
                                $params['alias'].'.'.$this->linkSucc.'='.$alias.'.'.$primaryKey
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkItem=>1,
                            $params['alias'].'.'.$this->linkPred=>$options[$params['node']],
                            $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                        ]);
                    }

                    break;
                case 'succ':
                    if(!empty($options[$params['node']])){
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'conditions'=>[
                                $params['alias'].'.'.$this->linkSucc.'='.$alias.'.'.$primaryKey
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkPred=>$options[$params['node']],
                            $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                        ]);
                    }

                    break;
                case 'leaf':
                    if(!empty($options[$params['node']])){
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'conditions'=>[
                                $alias.'.'.$this->linkSucc.'='.$alias.'.'.$primaryKey
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkPred=>$options[$params['node']],
                            $alias.'.'.$primaryKey.'<>'.$options[$params['node']]
                        ]);
                    }
                    else {
                        $query=$query->join([[
                            'table'=>$this->_table->getTable(),
                            'alias'=>$params['alias'],
                            'type'=>'LEFT',
                            'conditions'=>[
                                $params['alias'].'.'.$this->linkPred.'='.$alias.'.'.$primaryKey,
                                $params['alias'].'.'.$this->linkPred.'<>'.$params['alias'].'.'.$this->linkSucc
                            ]
                        ]])->where([
                            $params['alias'].'.'.$this->linkPred.' is null'
                        ]);
                    }

                    break;
            }
        }

        if(!empty($options[$params['node'].'-not'])){
            $query=$query->join([[
                'table'=>$this->_table->getTable(),
                'alias'=>$params['alias'].'None',
                'type'=>'LEFT',
                'conditions'=>[
                    $params['alias'].'None.'.$this->linkSucc.'='.$alias.'.'.$primaryKey,
                    $params['alias'].'None.'.$this->linkPred.'='.$options[$params['node'].'-not']
                ]
            ]])->where([
                $params['alias'].'None.'.$this->linkSucc.' is null'
            ]);
        }

        return($query);
    }

}

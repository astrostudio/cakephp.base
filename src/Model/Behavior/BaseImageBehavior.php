<?php
class BaseImageBehavior extends ModelBehavior {

    var $settings=array();
    
    var $deleting=array();
    
    public function setup(Model $Model,$settings=array()) {
        if(!isset($this->settings[$Model->alias])){
            $this->settings[$Model->alias]=array(
            );
        }
        
        $this->settings[$Model->alias]=array_merge($this->settings[$Model->alias],(array)$settings);
    }
    
    public function beforeDelete(Model $Model,$cascade=true){
        $this->deleting[$Model->alias]=array();
        $Model->recursive=-1;
        $data=$Model->read(null,$Model->id);

        if(!empty($data)){
            foreach($this->settings[$Model->alias] as $name=>$settings){
                if(!empty($data[$Model->alias][$name])){
                    $dir=!empty($settings['dir'])?$settings['dir']:WWW_ROOT.'files';
                    $path=$dir.'/'.$data[$Model->alias][$name];
                    array_push($this->deleting[$Model->alias],$path);
                }
            }        
        }
    }
        
    public function afterDelete(Model $Model){
        foreach($this->deleting[$Model->alias] as $path){
            unlink($path);
        }
    }

    public function beforeSave(Model $Model){    
        foreach($this->settings[$Model->alias] as $name=>$settings){            
            $settings['extension']=array('jpeg','jpg','png','JPG','JPEG','PNG');
            $settings['mimeType']=array('image/jpeg','image/png');
        
            if(isset($Model->data[$Model->alias][$name]) and is_array($Model->data[$Model->alias][$name])){
                if(!empty($Model->data[$Model->alias][$name]['tmp_name'])){  
                    $tmp=$Model->data[$Model->alias][$name]['tmp_name'];
                    $dir=!empty($settings['dir'])?$settings['dir']:WWW_ROOT.'files';
                    $info=pathinfo($Model->data[$Model->alias][$name]['name']);

                    if(!empty($settings['extension'])){
                        if(is_string($settings['extension'])){
                            if($info['extension']!=$settings['extension']){
                                $Model->validationErrors[$name]=__('File extension error');
                                
                                return(false);
                            }
                        }
                        else if(is_array($settings['extension'])){
                            if(array_search($info['extension'],$settings['extension'])===false){
                                $Model->validationErrors[$name]=__('File extension error');

                                return(false);
                            }
                        }
                    }
                    
                    if(!empty($settings['mimeType'])){
                        $mimeType=$this->mimeType($tmp);
                        
                        if(is_string($settings['mimeType'])){
                            if($mimeType!=$settings['mimeType']){
                                $Model->validationErrors[$name]=__('File format error');
                                
                                return(false);
                            }
                        }
                        else if(is_array($settings['mimeType'])){
                            if(array_search($mimeType,$settings['mimeType'])===false){
                                $Model->validationErrors[$name]=__('File format error');

                                return(false);
                            }
                        }
                    }                    
                    
                    $file=$Model->alias.'-'.uniqid().'.'.$info['extension'];
                    $path=$dir.DS.$file;
                    
                    if(!move_uploaded_file($tmp,$path)){
                        $Model->validationErrors[$name]=__('File upload error');
                        
                        return(false);
                    }
                    
                    $Model->data[$Model->alias][$name]=$file;
                }
                else {
                    unset($Model->data[$Model->alias][$name]);
                }   
            }
        }
        
        return(true);
    }
    
    public function afterSave(Model $Model,$created){
        foreach($this->settings[$Model->alias] as $name=>$settings){
            if(!empty($Model->data[$Model->alias][$name])){
                $dir=!empty($settings['dir'])?$settings['dir']:WWW_ROOT.'files';
                $info=pathinfo($Model->data[$Model->alias][$name]);                
                $file=$Model->alias.'-'.$name.'-'.$Model->id.'.'.$info['extension'];
                $old=$dir.'/'.$Model->data[$Model->alias][$name];                
                $new=$dir.'/'.$file;

                if($old!==$new){
                    if(rename($old,$new)){
                        $width=Hash::get($settings,'width');
                        $height=Hash::get($settings,'height');
                        
                        if(!empty($width) and !empty($height)){
                            $this->resizeImage($new,$width,$height);
                        }
                    
                        if(!$Model->saveField($name,$file)){
                            rename($new,$old);
                        }
                    }       
                }
            }
        }
    }

    public function mimeType($path){
        $info=new finfo(FILEINFO_MIME);
        
        return($info->file($path,FILEINFO_MIME_TYPE));
    }
        
    public function resizeImage($path,$width,$height){
        $info=pathinfo($path);
        
        if($info['extension']=='png') {
            $src=imagecreatefrompng($path);
            imagealphablending($src,true); // setting alpha blending on
            imagesavealpha($src,true); // save alphablending setting (important)
        }
        else{
            $src=imagecreatefromjpeg($path);
        }
        
        list($iwidth,$iheight)=getimagesize($path);
        
        $isize=$iwidth>$iheight?$iwidth:$iheight;
        $size=$width>$height?$width:$height;
        $scale=$isize>$size?$size/$isize:1.0;
        $nwidth=floor($scale*$iwidth);
        $nheight=floor($scale*$iheight);
        $left=floor(($width-$nwidth)/2);
        $top=floor(($height-$nheight)/2);
                
        $image=imagecreate($width,$height);
        imagecopyresampled($image,$src,$left,$top,0,0,$nwidth,$nheight,$iwidth,$iheight);
        imagedestroy($src);
        
        if($info['extension']=='png'){
            imagepng($image,$path);
        }
        else {
            imagejpeg($image,$path);
        }
        
        imagedestroy($image);
    }
}

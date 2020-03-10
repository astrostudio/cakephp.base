<?php
namespace Base\View;

use Cake\View\Exception\MissingTemplateException;
use Cake\View\View;
use Cake\I18n\I18n;

class LocaleView extends View
{
    const SEPARATOR='/';

    private function extractElementNames(string $name){
        $names=[];
        $path=explode(self::SEPARATOR,$name);
        $newPath=[];
        $noPath=[];

        foreach($path as $item){
            if($item==='{locale}'){
                $item=I18n::getLocale();
            }
            else {
                $noPath[]=$item;
            }

            $newPath[]=$item;
        }

        $names[]=implode(self::SEPARATOR,$newPath);

        $newPath=$noPath;
        $element=array_pop($newPath);
        array_push($newPath,I18n::getLocale());
        array_push($newPath,$element);
        $names[]=implode(self::SEPARATOR,$newPath);
        $names[]=implode(self::SEPARATOR,$noPath);

        return($names);
    }

    public function elementExists($name):bool{
        $names=$this->extractElementNames($name);

        foreach($names as $name){
            if(parent::elementExists($name)){
                return(true);
            }
        }

        return(false);
    }

    public function element($name, array $data = [], array $options = []):string{
        if(is_string($name)) {
            $names = $this->extractElementNames($name);

            foreach ($names as $item) {
                if (parent::elementExists($item)) {
                    return (parent::element($item, $data, $options));
                }
            }
        }

        return(parent::element($item,$data,$options));
    }

    private function extractTemplateName(string $name):string
    {
        $items=explode('/',$name);

        $name1=implode('/',array_merge(
            array_slice($items,0,count($items)-1),
            [I18n::getLocale()],
            array_slice($items,count($items)-1,1)
        ));

        try {
            $this->_getTemplateFileName($name1);

            return($name1);
        }
        catch(MissingTemplateException $exc){
        }

        return($name);
    }

    public function render(?string $template = null, $layout = null): string{
        if($template===false){
            return(parent::render($template,$layout));
        }

        if($template===null){
            $template=$this->template;
        }

        $template=$this->extractTemplateName($template);

        return(parent::render($template,$layout));
    }

}

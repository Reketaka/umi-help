<?php


class Rh{

    public static function getAttributeElement($id, $attr){

        if(!$element = umiHierarchy::getInstance()->getElement($id)){
            return null;
        }

        if(!$field = $element->getValue($attr)){
            return null;
        }

        $value = $field;
        if($field instanceof umiImageFile){
            $value = $field->getFilePath(true);

            $system = system_buildin_load('system');

            if($image = $system->makeThumbnail(
                $field->getFilePath(),
                200
            )){
                return $image['src'];
            }
        }



        return $value;
    }

    private static function renderTagAttributes($attributes){
        if(empty($attributes)){
            return null;
        }

        $attributesText = null;
        foreach($attributes as $key=>$val){
            if(is_array($val)){
                $attributesText .= $key . "='" . implode(" ", $val) . "' ";
            }else{
                $attributesText .= $key . "='" . $val . "' ";
            }
        }

        $attributesText = substr($attributesText, 0, -1);
        $attributesText = trim($attributesText);
        return $attributesText;
    }

    public static function beginTag($tag, $attributes=[]){

        return "<" . $tag .((($attrs = self::renderTagAttributes($attributes)) && !empty($attrs))?" ".$attrs:"").">";
    }

    public static function endTag($tag){
        return "</".$tag.">";
    }

    public static function tag($tag, $content = null, $attributes = []){

        return "<".$tag.((($attrs = self::renderTagAttributes($attributes)) && !empty($attrs))?" ".$attrs:"").(is_null($content)?"/>":">") . $content . (is_null($content)?"":"</" . $tag . ">");
    }

    public static function dump($v){
        echo "<pre>";
        var_Dump($v);
        echo "</pre>";
    }

    public static function dd($v){
        echo "<pre>";
        var_Dump($v);
        echo "</pre>";
        exit();
    }
}


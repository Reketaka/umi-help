<?php


class Rh{

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
}


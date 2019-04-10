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

    /**
     * Возвращает umiHierarchyElement текущей корзины пользователя
     * @return array
     */
    public static function getBasketElements(){
        $emarket = cmsController::getInstance()->getModule("emarket");

        $order = $emarket->getBasketOrder();
        $elements = [];
        foreach($order->getItems() as $orderItem){
            $elements[] = $orderItem->getItemElement();
        }


        return $elements;
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

    public static function priceFormat($p){
        return number_format($p, 2, '.', ' ');
    }

    public static function insertSchema($data){
        $text = self::beginTag('script', ['type'=>'application/ld+json']).PHP_EOL;
        $text .= json_encode($data, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT).PHP_EOL;
        $text .= self::endTag('script');
        return $text;
    }

    public static function getSchemaImage($variables, $image, $name=false, $schemeName = "image", $add_last = true){

        if(!$image instanceof umiImageFile){
            return false;
        }

        $data = [
            $schemeName=>[
                '@type'=>'ImageObject',
                'contentUrl'=>$_SERVER['REQUEST_SCHEME'].'://'.$variables['domain'].$image->getFilePath(true),
                'url'=>$_SERVER['REQUEST_SCHEME'].'://'.$variables['domain'].$image->getFilePath(true),
                'height'=>$image->getHeight().'px',
                'width'=>$image->getWidth().'px'
            ]
        ];

        if($name){
            $data[$schemeName]['name'] = $name;
        }

        return $data;
    }

    /**
     * Возвращает первые 10 слов
     * @param $text
     * @return null|string
     */
    public static function getShortDescription($text, $count=15){
        $data = str_replace(["\"", "&nbsp;"], ["", " "], strip_tags(htmlspecialchars_decode($text)));

        $data = explode(" ", $data);
        $r = null;
        for($a=0;$a<=$count;$a++){
            if(!isset($data[$a])){
                continue;
            }
            $r.=$data[$a]." ";
        }
        return trim($r);
    }

    public static function generateSchema($variables){
        $httpType = $_SERVER['REQUEST_SCHEME'].'://';

        $r = [];
        $r[] = self::tag('link', null, ['href'=>'https://plus.google.com/109288727053155742352']);
        $r[] = self::tag('meta', null, ['property'=>'og:locale', 'content'=>'ru_RU']);
        $r[] = self::tag('meta', null, ['property'=>'og:type', 'content'=>'website']);
        $r[] = self::tag('meta', null, ['property'=>'og:description', 'content'=>$variables['meta']['description']]);
        $r[] = self::tag('meta', null, ['property'=>'og:url', 'content'=>$httpType.$variables['domain']]);
        $r[] = self::tag('meta', null, ['property'=>'og:site_name', 'content'=>regedit::getInstance()->get('//settings/site_name/1/1/')]);
        $r[] = self::tag('meta', null, ['property'=>'fb:admins', 'content'=>'100001032886653']);

        $r[] = self::tag('meta', null, ['name'=>'keywords', 'content'=>$variables['meta']['keywords']]);
        $r[] = self::tag('meta', null, ['name'=>'description', 'content'=>$variables['meta']['description']]);

        $r[] = self::insertSchema([
            '@context'=>'http://schema.org',
            '@type'=>'WebSite',
            'url'=>$httpType.$variables['domain'],
            'name'=>'Интернет-Магазин'
        ]);

        if($variables['module'] == 'catalog' && $variables['method'] == 'object'):
            $product = $variables['page'];
            $amount = $product->getValue("common_quantity");

            $ar = [
                '@context'=>'http://schema.org',
                '@type'=>'Product',
                'name'=>$product->getName(),
                'description'=>self::getShortDescription($product->getValue('description')),
                'offers'=>[
                    '@type'=>'Offer',
                    'price'=>self::priceFormat($product->getValue('price')),
                    'priceCurrency'=>'RUB'
                ]
            ];

            if($image = $product->getValue('photo')){
                $ar = array_merge($ar, self::getSchemaImage($variables, $image, $product->getName()));
            }

            if($amount > 0){
                $ar['offers']['availability'] = 'http://schema.org/InStock';
            }else{
                $ar['offers']['availability'] = 'http://schema.org/OutStock';
            }

            $r[] = self::insertSchema($ar);
        endif;

        if($variables['parents']):
            $ar = [
                '@context'=>'http://schema.org',
                '@type'=>'BreadcrumbList',
                'itemListElement'=>[]
            ];

            foreach($variables['parents'] as $key=>$page){
                $ar['itemListElement'][] = [
                    '@type'=>'ListItem',
                    'position'=>($key+1),
                    'item'=>[
                        '@id'=>$httpType.$variables['domain'].''.$page->link,
                        'name'=>$page->getName()
                    ]
                ];
            }

            $r[] = self::insertSchema($ar);
        endif;

        $r[] = self::insertSchema([
            '@context'=>'http://schema.org',
            '@type'=>'Person',
            'name'=>'Интернет-Магазин',
            'url'=>$httpType.$variables['domain'],
            'sameAs'=>[
                'https://vk.com/smytkin',
                'https://www.instagram.com/reketaka/'
            ]
        ]);

        if(($variables['module'] == "news") && ($variables['method'] == "item")):

            $page = $variables['page'];

            /**
             * @var $updateTime umiDate
             */
            $updateTime = new umiDate($page->getUpdateTime());

            $ar = [
                '@context'=>'http://schema.org',
                '@type'=>'NewsArticle',
                'mainEntityOfPage'=>[
                    '@type'=>'WebPage',
                    '@id'=>$page->link
                ],
                'headline'=>$page->getName(),
                'dateModified'=>str_replace("%", "T", $updateTime->getFormattedDate("Y-m-d%h:i:s")),
                'author'=>[
                    '@type'=>'Person',
                    'name'=>'Administrator'
                ],
                'publisher'=>[
                    '@type'=>'Organization',
                    'name'=>'Интернет-магазин Дары Алтая'
                ],
                'description'=>self::getShortDescription($page->getValue('content'))
            ];

            if($date = $page->getValue('publish_time')){
                $ar['datePublished'] = str_replace("%", "T", $date->getFormattedDate("Y-m-d%h:i:s"));
            }

            if($image = self::getSchemaImage($variables, $page->getValue("header_pic"), $page->getName())){
                $ar = array_merge($ar, $image);
            }

            $r[] = self::insertSchema($ar);
        endif;


        $r = implode(PHP_EOL, $r);
        return $r;
    }
}


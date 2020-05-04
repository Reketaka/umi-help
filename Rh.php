<?php


use UmiCms\Classes\System\Exception\Handler\Base;

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

    public static function beginTag($tag, $attributes=[]){

        return BaseHtml::beginTag($tag, $attributes);
    }

    public static function endTag($tag){
        return BaseHtml::endTag($tag);
    }

    public static function tag($tag, $content = null, $attributes = []){

        return BaseHtml::tag($tag, $content, $attributes);
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

    /**
     * Возвращает строку в которой остаются только цифры
     * @param $var
     * @return integer
     */
    public static function onlyNumbers($var){
        return preg_replace("/[^0-9]/", '', $var);
    }

    public static function priceFormat($p){
        return number_format($p, 2, '.', ' ');
    }

    public static function renderContentMenu($items, $renderSubMenu = false, $addDropdownButton = false){
        $attributes = [];
        if($renderSubMenu){

            if(in_array('active', BaseArrayHelper::getColumn($items['items'], 'status'))){
                BaseHtml::addCssClass($attributes, 'active');
            }
        }

        echo BaseHtml::beginTag('ul', $attributes);

        foreach($items['items'] as $itemData):

            echo BaseHtml::beginTag('li');

            $attributes = [];
            $hasSubMenu = false;
            if(array_key_exists('items', $itemData)){
                $attributes['class'] = ['dropdown'];
                $hasSubMenu = true;
            }
            $attributes['href'] = $itemData['link'];

            if(BaseArrayHelper::getValue($itemData, 'status') == 'active'){
                BaseHtml::addCssClass($attributes, 'active');
            }


            echo BaseHtml::beginTag('a', $attributes);
            echo $itemData['text'];

            if($hasSubMenu && $addDropdownButton){
                echo BaseHtml::tag('button', null, ['type'=>'button', 'class'=>'dropdown-button']);
            }

            echo BaseHtml::endTag('a');

            if($hasSubMenu){
                self::renderContentMenu(['items'=>$itemData['items']], $renderSubMenu, $addDropdownButton);
            }


            echo BaseHtml::endTag('li');

        endforeach;

        echo BaseHtml::endTag('ul');
    }

    public static function renderEmarketSteps($var){
        if(!$variables = BaseArrayHelper::getValue($var, 'data.steps', false)){
            $variables = BaseArrayHelper::getValue($var, 'purchasing.steps', false);
        }

        $text = null;
        $text .= Rh::beginTag('div', ['class'=>'steps-of-payment']);
        foreach($variables as $stepData):
            $attributes = [
                'class'=>['stepBox']
            ];
            $attributes['class'][] = $stepData['status'];
            $text .= Rh::beginTag('div', $attributes);
            $text .= Rh::beginTag('div', ['class'=>'box']);
            if($stepData['status'] == 'complete'){
                $text .= Rh::beginTag('a', ['href'=>$stepData['link']]);
            }

            $text .= Rh::beginTag('div', ['class'=>'valueStep']);
            $text .= $stepData['name'];
            $text .= Rh::endTag('div');
            $text .= Rh::beginTag('div', ['class'=>'clearfix']);
            $text .= Rh::endTag('div');

            if($stepData['status'] == 'complete'){
                $text .= Rh::endTag('a');
            }
            $text .= Rh::endTag('div');
            $text .= Rh::endTag('div');
        endforeach;
        $text .= Rh::endTag('div');

        return $text;
    }

    public static function renderOrderList($variables, $options = []){
        $orderList = array_key_exists('items', $variables)?$variables['items']:[];

        $emarket = cmsController::getInstance()->getModule('emarket');
        $umiOC = umiObjectsCollection::getInstance();

        $attributes = ['id'=>'con_tab_orders'];
        if(array_key_exists('tabClass', $options)){
            BaseHtml::addCssClass($attributes, $options['tabClass']);
        }

        $t = self::beginTag('div', $attributes);

            if(!$orderList):
                $t .= "Вы не сделали не одного заказа";
            endif;

            if($orderList):

                $t .= self::beginTag('div', ['class'=>'table-responsive']);
                    $t .= self::beginTag('table', ['class'=>'table table-bordered table-hover']);
                        $t .= self::beginTag('thead');
                            $t .= self::beginTag('tr');
                                foreach(['№ Заказа', 'Статус', 'Способ оплаты', 'Сумма'] as $title):
                                    $t .= self::tag('th', $title, ['class'=>'name']);
                                endforeach;
                            $t .= self::endTag('tr');
                        $t .= self::endTag('thead');

                        $t .= self::beginTag('tbody');
                            foreach ($orderList as $item):
                                $order = $umiOC->getObject($item['id']);
                                $orderInfo = $emarket->order($item['id']);


                                $t .= self::beginTag('tr');
                                    $t .= self::beginTag('td', ['class'=>'name']);
                                        $t .= self::tag('strong', '#'.($orderInfo['number']??null));

                                        $t .= self::tag('div', 'От '.(($order->getValue('order_date') instanceof umiDate) ? $order->getValue('order_date')->getFormattedDate('d.m.Y') : ''));

                                    $t.= self::endTag('td');

                                    $t .= self::beginTag('td', ['class'=>'name']);
                                        $t .= (isset($orderInfo['status']) && $orderInfo['status'] instanceof umiObject) ? $orderInfo['status']->getName() : '';
                                        $t .= "<br/>";
                                        $t .= "С ".(($order->getValue('status_change_date') instanceof umiDate) ? $order->getValue('status_change_date')->getFormattedDate('d.m.Y') : '');
                                    $t.= self::endTag('td');

                                    $t .= self::beginTag('td');
                                        if ($paymentObject = $umiOC->getObject($order->getValue('payment_id'))):
                                            $t.=$paymentObject->getName();
                                        endif;
                                    $t .= self::endTag('td');

                                    $t .= self::tag('td', self::priceFormat($orderInfo['summary']['price']['actual']));

                                $t .= self::endTag('tr');


                                foreach($orderInfo['items']['nodes:item'] as $orderItem):
                                    $t .= self::beginTag('tr');
                                        $t .= self::tag(
                                            'td',
                                            Rh::tag('a', $orderItem['attribute:name'], ['href'=>$orderItem['page']->link]),
                                            ['class'=>'name', 'colspan'=>3]);

                                        $t .= self::beginTag('td');
                                            $t .= self::priceFormat($orderItem['price']['actual']);
                                            $t .= " x ";
                                            $t .= $orderItem['amount'];
                                            $t .= " = ";
                                            $t .= self::priceFormat($orderItem['total-price']['actual']);
                                        $t .= self::endTag('td');


                                    $t .= self::endTag('tr');
                                endforeach;

                            endforeach;
                        $t .= self::endTag('tbody');


                    $t .= self::endTag('table');
                $t .= self::endTag('div');

            endif;

        $t .= Rh::endTag('div');

        return $t;
    }

    public static function renderPagination($variables, $pagination){
        $canShow = isset($variables['total']) &&
        isset($variables['per_page']) &&
        $variables['total'] > $variables['per_page'];

        if(!$canShow){
            return null;
        }

        echo BaseHtml::beginTag('nav');
            echo BaseHtml::beginTag('ul', ['class'=>'pagination']);

            $attributes = ['class'=>['page-item']];
            if(!isset($pagination['tobegin_link'])){
                BaseHtml::addCssClass($attributes, 'disabled');
            }


            echo BaseHtml::beginTag('li', $attributes);
            if(!isset($pagination['tobegin_link'])){
                echo BaseHtml::tag('span', "&laquo;", ['class'=>'page-link']);
            }else{
                echo BaseHtml::beginTag('a', ['class'=>['page-link'], 'href'=>$pagination['tobegin_link']['value']]);
                echo BaseHtml::tag('span', "&laquo;");
                echo BaseHtml::endTag('a');
            }
            echo BaseHtml::endTag('li');


            foreach($pagination['items'] as $item):
                $attributes = ['class'=>['page-item']];
                if(BaseArrayHelper::getValue($item, 'is-active', false)){
                    BaseHtml::addCssClass($attributes, 'active');
                }
                if(($item['page-num'] == 0) && (BaseArrayHelper::getValue($item, 'is-active', false))){
                    BaseHtml::removeCssClass($attributes, 'active');
                }

                echo BaseHtml::beginTag('li', $attributes);
                    echo BaseHtml::tag('a', $item['num'], ['class'=>'page-link', 'href'=>$item['link']]);
                echo BaseHtml::endTag('li');
            endforeach;


            $attributes = ['class'=>['page-item']];
            if(!isset($pagination['tonext_link'])){
                BaseHtml::addCssClass($attributes, 'disabled');
            }

            echo BaseHtml::beginTag('li', $attributes);
            if(!isset($pagination['tonext_link'])){
                echo BaseHtml::tag('span', "&raquo;", ['class'=>'page-link']);
            }else{
                echo BaseHtml::beginTag('a', ['class'=>['page-link'], 'href'=>$pagination['tonext_link']['value']]);
                echo BaseHtml::tag('span', "&raquo;");
                echo BaseHtml::endTag('a');
            }
            echo BaseHtml::endTag('li');


        echo BaseHtml::endTag('ul');
        echo BaseHtml::endTag('nav');
    }

    /**
     * @param $variables
     * @param DemomarketPhpExtension $extension
     * @return string|null
     */
    public static function renderBreadcrumbs($variables, $extension, $options = []){
        if(!$parentList = BaseArrayHelper::getValue($variables, 'parents')){
            return null;
        }

        $attributes = ['class'=>'breadcrumbs-block'];
        $t = self::beginTag('nav', $attributes);
            $t .= self::beginTag('ol', ['class'=>'breadcrumb']);

                if($homeHtml = BaseArrayHelper::getValue($options, 'mainPage.html')){
                    $t .= self::beginTag('li', ['class'=>['breadcrumb-item']]);
                    $t .= self::tag('a', $homeHtml, ['href'=>'/']);
                    $t .= self::endTag('li');
                }

                foreach($parentList as $parent):
                    $attributes = ['class'=>['breadcrumb-item']];
                    $t .= self::beginTag('li', $attributes);

                        $t .= self::tag('a', $parent->getName(), ['href'=>$extension->getPath($parent)]);

                    $t .= self::endTag('li');
                endforeach;

                $t .= self::tag('li', $variables['page']->getName(), ['class'=>['breadcrumb-item', 'active']]);

            $t .= self::endTag('ol');
        $t .= self::endTag('nav');

        return $t;
    }

    public static function getDiscount(umiHierarchyElement $product, $decimials = 0){
        $price = $product->getValue('price');
        $oldPrice = $product->getValue('old_price');

        if(!$oldPrice){
            return false;
        }

        return number_format((($price-$oldPrice)/$price)*100, $decimials, '.', ' ');


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

    /**
     * Подключает ксс
     * @param $includeCss
     */
    public static function includeCss($includeCss){
        $t = null;
        foreach($includeCss as $css):
            $attributes = ['rel'=>'stylesheet', 'href'=>$css];
            $t .= self::tag("link", null, $attributes).PHP_EOL;
        endforeach;

        return $t;
    }

    public static function getSortUrl($url, $fieldSort, $direction){
        $urlData = parse_url($url);
        $query = BaseArrayHelper::getValue($urlData, 'query', null);
        parse_str($query, $query);

        $query['sort_field'] = $fieldSort;
        $query['sort_direction'] = $direction;

//        self::dump($urlData);


//        self::dump($query);


        $newUrl = $urlData['path']."?".http_build_query($query);
        return $newUrl;
    }
}


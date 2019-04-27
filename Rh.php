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

    public static function priceFormat($p){
        return number_format($p, 2, '.', ' ');
    }

    public static function renderEmarketSteps($variables){
        if(array_key_exists('steps', $variables)){
            $variables = $variables['steps'];
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

    public static function renderOrderList($variables){
        $orderList = array_key_exists('items', $variables)?$variables['items']:[];

        $emarket = cmsController::getInstance()->getModule('emarket');
        $umiOC = umiObjectsCollection::getInstance();

        $t = self::beginTag('div', ['id'=>'con_tab_orders']);

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
}


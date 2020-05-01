css Emarket Cart
```
.steps-of-payment{display:flex;}
.steps-of-payment .stepBox{display: inline-block;position: relative;flex-grow: 1;height: 38px;padding: 8px 0;margin-right: -3px;background-color: transparent;border-bottom: 2px solid #f0f0f0;border-top: 2px solid #f0f0f0;color: #a4a4a4;text-decoration: none;text-indent: 1%;}
.steps-of-payment .stepBox.complete, .steps-of-payment .stepBox.active{background-color: #557da6;border-color: #557da6;color: #FFF;}
.steps-of-payment .stepBox.complete .valueStep{color:#FFF;}
.steps-of-payment .stepBox.active a{color:#FFF;}
.steps-of-payment .stepBox .valueStep{text-align:center;}
.steps-of-payment .stepBox.active:before, .steps-of-payment .stepBox.active:after{position: absolute;width: 0;height: 0;border-style: solid;z-index: 25;content: "";}
.steps-of-payment .stepBox.active:before{top: -2px;right: -12px;border-width: 19.5px 0 19.5px 15px;border-color: transparent transparent transparent #557da6;}
.steps-of-payment .stepBox.active:after{top: -1px;right: -12px;border-width: 18px 0 18px 14px;border-color: transparent transparent transparent #557da6;}
.steps-of-payment .stepBox.complete::after, .steps-of-payment .stepBox.complete::before, .steps-of-payment .stepBox.active::after,.steps-of-payment .stepBox.active::before{border-color: transparent transparent transparent #557da6;}
.steps-of-payment .stepBox.last{border-right:2px solid #f0f0f0;}
.steps-of-payment .stepBox.last:before, .steps-of-payment .stepBox.last:after{display:none;}
```


Пример использования Yandex Turbo страниц
```
permissions.php
$permissions['content'][] = "getYandexTurboPage";

class.php
public function getYandexTurboPage(){
        $ytp = (new YandexTurboPages());
        $ytp = $ytp->clearCache(true);


//        $ytp = $ytp
//            ->clearCache();
//            ->setCacheDir($cacheDir);
//            ->clearCache();

        $buffer = Service::Response()
            ->getCurrentBuffer();
        $buffer->charset('utf-8');
        $buffer->contentType('text/xml');


        if(file_exists(($cacheFilePath = $ytp->getCacheFilePath()))){
            $buffer->push(file_get_contents($cacheFilePath));
            $buffer->end();
            return;
        }

        $pages = new selector('pages');
        $pages->types('hierarchy-type')->name('content', '');
        $pages->types('hierarchy-type')->name('news', 'rubric');
        $pages->types('hierarchy-type')->name('news', 'item');
        $pages->where('use_yandex_turbo_page')->equals(true);
        $pages = $pages->result();

        $ytp = $ytp->loadDefaultValues()
            ->setMenu([
                ['text'=>'Новости', 'link'=>'#'],
                ['text'=>'Любовные обряды и заговоры', 'link'=>'#'],
                ['text'=>'Здоровье, целительство', 'link'=>'#']
            ])
            ->setItems($pages);

        $ytp = $ytp
            ->setContentGenerateCallback(function($item)use($ytp){
                $ids = [447, 448, 449, 450, 451, 452];

                /**
                 * @var $item umiHierarchyElement
                 */
                if($item->getModule() == 'news' && $item->getMethod() == 'rubric'){
                    $news = cmsController::getInstance()->getModule('news');
                    $items = $news->lastlist($item->getId(), [null, 10]);
                    $text = null;
                    if(isset($items['void:lines']) && ($items = $items['void:lines']) && $items){
                        $text .= Rh::beginTag('ul');
                        foreach($items as $item){
                            $text .= Rh::beginTag('li');

                            $text .= Rh::beginTag('a', ['href'=>$ytp->getDomainUrl().$item['attribute:link']]);
                            $text .= $item['node:name'];
                            $text .= Rh::endTag('a');

                            $text .= Rh::endTag('li');
                        }
                        $text .= Rh::endTag('ul');
                    }

                    $text .= Rh::beginTag('p')." ".Rh::endTag('p');

                    return $text;
                }

                if(in_array($item->getId(), $ids)){
                    $content = cmsController::getInstance()->getModule('content');
                    $items = $content->getList(null, $item->getId(), 1, 0, 1);
                    $text = null;
                    if(isset($items['items']) && isset($items['items']['nodes:item']) && ($items = $items['items']['nodes:item'])){
                        $text .= Rh::beginTag('ul');
                        foreach($items as $item){
                            $text .= Rh::beginTag('li');
                            $text .= Rh::beginTag('a', ['href'=>$ytp->getDomainUrl().$item['@link']]);
                            $text .= $item['name'];
                            $text .= Rh::endTag('a');
                            $text .= Rh::endTag('li');
                        }
                        $text .= Rh::endTag('ul');
                    }

                    $text .= Rh::beginTag('p')." ".Rh::endTag('p');

                    return $text;
                }

                if(!$text = $item->getValue('content')){
                    $text = $item->getValue('title');
                }


                return $text;
            });


        $buffer->push($ytp->getRss());
        $buffer->end();

        return;

    }
```
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
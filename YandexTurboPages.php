<?php

include_once "./Rh.php";

class YandexTurboPages{

    private $channelData = [
        'title'=>'',
        'link'=>'',
        'description'=>'',
        'language'=>'ru'
    ];

    private $items;
    private $cacheDir;
    private $cacheFileName = 'yandexTurboPages.xml';
    private $contentField = 'content';
    private $callbackToGenerateContent = null;
    /**
     * [ ['text'=>'', 'link'=>'']]
     * @var array
     */
    private $turboMenu = [];

    public function getRss(){

        $rssText = $this->generate();

        return $rssText;
    }

    public function generate(){

        if($cacheText = $this->getCache()){
            return $cacheText;
        }


        $resultText = '<?xml version="1.0" encoding="UTF-8"?>';
        $resultText .= Rh::beginTag('rss', [
            'xmlns:yandex'=>'http://news.yandex.ru',
            'xmlns:media'=>'http://search.yahoo.com/mrss/',
            'xmlns:turbo'=>'http://turbo.yandex.ru',
            'version'=>'2.0'
        ]);

        $resultText .= $this->generateChanel();

        $resultText .= Rh::endTag('rss');

        $this->saveCache($resultText);

        return $resultText;
    }

    /**
     * Удаляет всю папку с кешем
     */
    public function clearCache(){

    }

    /**
     * Сохраняет кеш
     * @param $text
     * @return bool
     */
    private function saveCache($text){
        $file = $this->cacheDir.'/'.$this->cacheFileName;

        file_put_contents($file, $text);
        return true;
    }

    /**
     * Возвращает кеш если он существует
     * @return bool|false|string
     */
    private function getCache(){
        $file = $this->cacheDir.'/'.$this->cacheFileName;

        if(!file_exists($file)){
            return false;
        }

        return file_get_contents($file);
    }

    /**
     * Устанавливает информации о канале
     * @param $d
     * @return $this
     */
    public function setChannelData($d){
        $keys = array_keys($this->channelData);

        foreach($d as $k=>$v){
            if(!in_array($k, $keys)){
                continue;
            }

            $this->channelData[$k] = $v;
        }

        return $this;
    }

    /**
     * Указывает из какого поля брать описание страницы для турбо страницы
     * @param $fieldName
     * @return $this
     */
    public function setFieldContent($fieldName){
        $this->contentField = $fieldName;
        return $this;
    }

    /**
     * Назначает директорию для кеша
     * @param $dir
     * @return $this
     */
    public function setCacheDir($dir){
        $this->cacheDir = $dir;

        return $this;
    }

    private function generateChanel(){
        $r = Rh::beginTag('channel');

        foreach($this->channelData as $k=>$v){
            if(!$v){
                continue;
            }
            $r .= Rh::tag($k, $v);
        }


        $r .= $this->generateItems();


        $r.= Rh::endTag('channel');

        return $r;
    }

    private function generateContentItem($item){

        $r = Rh::beginTag('header');
        $r .= Rh::tag('h1', $item->h1);

        $r .= Rh::beginTag('figure');
        $r .= Rh::tag('img', null, ['src'=>'http://example.com/img.jpg']);
        $r .= Rh::endTag('figure');

        $r .= Rh::endTag('header');

        $r .= $this->generateMenu();

        $r .= Rh::endTag('header');

        if($this->callbackToGenerateContent instanceof \Closure) {
            $func = $this->callbackToGenerateContent;
            $r .= $func($item);
        }else {
            $r .= $item->content;
        }

        return $r;
    }

    /**
     * Формирует меню если оно заданно
     * @return null
     */
    private function generateMenu(){
        if(!$this->turboMenu){
            return null;
        }

        $r = Rh::beginTag('menu');
        foreach($this->turboMenu as $d){
            $r .= Rh::tag('a', $d['text'], ['href'=>$d['link']]);
        }
        $r .= Rh::endTag('menu');

    }

    public function generateItems(){

        $r = null;

        foreach($this->items as $item) {

            $r .= Rh::beginTag('item', ['turbo' => 'true']);

            $r .= Rh::tag('link', $item->link);

            $r .= Rh::beginTag('turbo:content');
            $r .= $this->generateContentItem($item);
            $r .= Rh::endTag('turbo:content');

            $r .= Rh::endTag('item');
        }

        return $r;
    }

    /**
     * Устанавливает меню
     * @param $menuData
     * @return $this
     */
    public function setMenu($menuData){
        $this->turboMenu = $menuData;

        return $this;
    }

    /**
     * Устанавливает массив моделей для использования в тубространицах
     * @param $items
     * @return $this
     */
    public function setItems($items){
        $this->items = $items;
        return $this;
    }

    public function setContentGenerateCallback($callback){
        $this->callbackToGenerateContent = $callback;
        return $this;
    }
}




$ytp = (new YandexTurboPages())
    ->setChannelData([
        'title'=>'Title сайта',
        'link'=>'http://example.ru',
        'description'=>'Описание сайта'
    ])
    ->setCacheDir(__DIR__)
    ->setFieldContent('content')
    ->setMenu([
        ['text'=>'Пункт меню первый', 'link'=>'#'],
        ['text'=>'Пунтк меню второй', 'link'=>'#'],
    ])
    ->setItems([
        (object)['content'=>'Текст страницы', 'h1'=>'Заголовок страницы', 'link'=>'http://example.ru/page.html']
    ])
    ->setContentGenerateCallback(function($item){


        return "TEST CONTENT";
    });


echo $ytp->getRss();
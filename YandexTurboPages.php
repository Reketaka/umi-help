<?php

include_once "./ReketakaHelps.php";

class YandexTurboPages{

    private $channelData = [
        'title'=>'',
        'link'=>'',
        'description'=>'',
        'language'=>'ru'
    ];

    private $cacheDir;

    public function getRss(){

        $rssText = $this->generate();


    }

    public function generate(){


        $resultText = '<?xml version="1.0" encoding="UTF-8"?>';
        $resultText .= ReketakaHelps::beginTag('rss', [
            'xmlns:yandex'=>'http://news.yandex.ru',
            'xmlns:media'=>'http://search.yahoo.com/mrss/',
            'xmlns:turbo'=>'http://turbo.yandex.ru',
            'version'=>'2.0'
        ]);

        $resultText .= $this->generateChanel();

        $resultText .= ReketakaHelps::endTag('rss');

        echo $resultText.PHP_EOL;
    }

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

    public function setCacheDir($dir){
        $this->cacheDir = $dir;

        return $this;
    }

    private function generateChanel(){
        $r = ReketakaHelps::beginTag('channel');

        foreach($this->channelData as $k=>$v){
            if(!$v){
                continue;
            }
            $r .= ReketakaHelps::tag($k, $v);
        }


        $r .= $this->generateItems();


        $r.= ReketakaHelps::endTag('channel');

        return $r;
    }

    private function generateContentItem($item = false){

        $r = '...
    <header>
        <h1>Заголовок страницы</h1>
        <figure>
            <img src="http://example.com/img.jpg"/>
        </figure>
        <h2>Заголовок второго уровня</h2>
        <menu>
            <a href="http://labmagic.ru">Текст ссылки</a>
            <a href="http://labmagic.ru">Текст ссылки</a>
        </menu>
    </header>
    <!-- Контентная часть -->
...';
        $r .= 'Конент';

        return $r;


    }


    public function generateItems(){

        $r = null;



        $r .= ReketakaHelps::beginTag('item', ['turbo'=>'true']);

        $r .= ReketakaHelps::tag('link', 'http://labmagic.ru');

        $r .= ReketakaHelps::beginTag('turbo:content');
        $r .= $this->generateContentItem();
        $r .= ReketakaHelps::endTag('turbo:content');

        $r .= ReketakaHelps::endTag('item');

        return $r;
    }
}




$ytp = (new YandexTurboPages())
    ->setChannelData([
        'title'=>'Лабмаджик',
        'link'=>'http://labmagic.ru',
        'description'=>'Описание сайта'
    ])
    ->setCacheDir(__DIR__);


$ytp->getRss();
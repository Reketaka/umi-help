<?php

class YandexTurboPages{

    private $channelData = [
        'title'=>'',
        'link'=>'',
        'description'=>'',
        'language'=>'ru'
    ];

    private $domainUrl;
    private $items;
    public $cacheDir = '/sys-temp/yandexTurboPages/';
    public $cacheFileName = 'page.xml';
    public $cacheClearTime = 3600;
    private $contentField = 'content';
    private $callbackToGenerateContent = null;
    /**
     * [ ['text'=>'', 'link'=>'']]
     * @var array
     */
    private $turboMenu = [];

    /**
     * Устанавливает дефолтные значения канала домена и т.д.
     * @return $this
     */
    public function loadDefaultValues(){
        $domain = domainsCollection::getInstance()->getDefaultDomain();

        $this->domainUrl = ($domain->isUsingSsl()?'https://':'http://').$domain->getHost();

        $defaultPage = umiHierarchy::getInstance()->getDefaultElement();

        $this->channelData = [
            'title'=>$defaultPage->getValue('title'),
            'link'=>$this->domainUrl,
            'description'=>$defaultPage->getValue('meta_descriptions')
        ];

        //$this->cacheDir = '/sys-temp/yandexTurboPages/';

        return $this;
    }

    public function getRss(){

        $rssText = $this->generate();

        return $rssText;
    }

    public function generate(){

        if($this->cacheDir && ($cacheText = $this->getCache())){
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

        if($this->cacheDir) {
            $this->saveCache($resultText);
        }

        return $resultText;
    }

    /**
     * Удаляет всю папку с кешем
     * @param $clearByTime Проверяет сколько времени прошло с последнего изменения и удаляет кеш
     */
    public function clearCache($clearByTime=false){
        if(!$this->cacheDir){
            return $this;
        }

        $clearCache = false;

        if($clearByTime && ((time()-filectime($this->getCacheFilePath())) > $this->cacheClearTime)){
            $clearCache = true;
        }

        if(!$clearByTime){
            $clearCache = true;
        }

        if(!$clearCache){
            return $this;
        }

        $dir = new umiDirectory($this->getCacheDir());
        if(!$dir->isExists()){
            return $this;
        }

        $dir->deleteRecursively();
        return $this;
    }

    /**
     * Сохраняет кеш
     * @param $text
     * @return bool
     */
    private function saveCache($text){
        umiDirectory::requireFolder($this->getCacheDir());
        $file = $this->getCacheFilePath();

        file_put_contents($file, $text);
        return true;
    }

    /**
     * Возвращает кеш если он существует
     * @return bool|false|string
     */
    private function getCache(){
        $file = $this->getCacheFilePath();

        if(!file_exists($file)){
            return false;
        }

        return file_get_contents($file);
    }

    public function getCacheDir(){
        return CURRENT_WORKING_DIR.$this->cacheDir;
    }

    public function getCacheFilePath(){
        return $this->getCacheDir().$this->cacheFileName;
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
        /**
         * @var $item umiHierarchyElement
         */

        $r = Rh::beginTag('header');
        $r .= Rh::tag('h1', $item->h1);

        if($item->getModule() == 'content'){
            $headerPic = $item->getValue('header_pic');
        }

        if($item->getModule() == 'news' && $item->getMethod() == 'item'){
            $headerPic = $item->getValue('anons_pic');
        }

        if($headerPic instanceof umiImageFile) {
            $r .= Rh::beginTag('figure');
            $r .= Rh::tag('img', null, ['src' =>$this->getDomainUrl().$headerPic->getFilePath(true)]);
            $r .= Rh::endTag('figure');
        }

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
        return $r;
    }

    public function generateItems(){

        $r = null;

        foreach($this->items as $item) {

            $r .= Rh::beginTag('item', ['turbo' => 'true']);

            $r .= Rh::tag('link', $this->domainUrl.$item->link);

            $r .= Rh::beginTag('turbo:content');
            $r .= '<![CDATA[ '.$this->generateContentItem($item).' ]]>';
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

    public function setDomainUrl($url){
        $this->domainUrl = $url;
        return $this;
    }

    public function getDomainUrl(){
        return $this->domainUrl;
    }
}
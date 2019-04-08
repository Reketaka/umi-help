<?php

class CsvReader{

    public $file = false;

    public $first_header = true;

    public $saveBatch = false;

    public $batchAmount = 10;

    public $delimiter = ';';

    private $headerData = [];

    private $onReadLineFunction = false;

    private $onSaveBatchLineFunction = false;

    private $onLoadBatchDataFunction = false;

    public $currentRow = 0;

    public $encodeIn = false;
    public $encodeOut = 'UTF-8';

    public function init()
    {
        if(!$this->file){
            throw new \Exception('Файл не задан');
        }

        if(!file_exists($this->file)){
            throw new \Exception('Файл не найден');
        }

        if($this->first_header){
            $this->initHeader();

        }

    }

    public function initHeader(){
        $handle = fopen($this->file, 'r');
        $row = 0;
        while (($data = fgetcsv($handle, 0, $this->delimiter)) !== FALSE) {

            if($row == 0) {
                $this->headerData = $data;
                return true;
            }

        }
        fclose($handle);
    }

    public function run(){
        $handle = fopen($this->file, "r");

        $row = 0;
        $allDataBatch = [];
        $batchRow = 0;

        while (($data = fgetcsv($handle, 0, $this->delimiter)) !== FALSE) {

            if($row == 0){
                $row++;
                continue;
            }

            $resultLine = $data;
            if($this->first_header) {
                $resultLine = array_combine($this->headerData, $data);
            }

            if(($batchRow == $this->batchAmount) && ($this->onLoadBatchDataFunction instanceof \Closure)){
                /**
                 * @var $loadBatchDataFunction \Closure
                 */
                $loadBatchDataFunction = $this->onLoadBatchDataFunction;
                $loadBatchDataFunction($allDataBatch, $row);
                $allDataBatch = [];
                $batchRow = 0;
            }

            if($this->saveBatch && $batchRow != $this->batchAmount){
                $savedBatchFunction = $this->onSaveBatchLineFunction;
                /**
                 * @var $savedBatchFunction \Closure
                 */
                if($this->encodeOut){
                    $resultLine = array_map(function($v){
                        return iconv($this->encodeIn, $this->encodeOut, $v);
                    }, $resultLine);
                }

                $values = $savedBatchFunction($resultLine);
                $allDataBatch[] = $values;
            }

            if(!$this->saveBatch && $this->onReadLineFunction instanceof \Closure){
                $funcCallback = $this->onReadLineFunction;
                /**
                 * @var $funcCallback \Closure
                 */
                $funcCallback($resultLine);
            }

            $row++;
            $batchRow++;
            $this->currentRow = $row;
        }

        if(($batchRow !=0) && ($this->onLoadBatchDataFunction instanceof \Closure)){
            $loadBatchDataFunction = $this->onLoadBatchDataFunction;
            $loadBatchDataFunction($allDataBatch, $row);
            $allDataBatch = [];
        }

        fclose($handle);
    }

    public function onReadLine($callbackFunction){

        if(!$callbackFunction instanceof \Closure){
            return false;
        }

        $this->onReadLineFunction = $callbackFunction;

    }

    public function onSavedBatchLine($callbackFunction){
        if(!$callbackFunction instanceof \Closure){
            return false;
        }

        $this->onSaveBatchLineFunction = $callbackFunction;
    }

    public function onLoadBatchData($callbackFunction){
        if(!$callbackFunction instanceof \Closure){
            return false;
        }

        $this->onLoadBatchDataFunction = $callbackFunction;
    }

}
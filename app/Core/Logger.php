<?php

namespace app\Core;

class Logger
{

    private $path;                                                           //папка с лог-файлами
    private $fileName;
    private $name;                                                          //имя текущего логгера

    public function __construct($name)
    {
        $this->name=$name;
        $fileName=$this->name.'.log';
        $logDir = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'logger'.DIRECTORY_SEPARATOR;
        if (!file_exists($logDir)) {
            mkdir(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'logger');
        }      
        $this->path = $logDir.$fileName;
    }

    public function logMessage($message)
    {
        $log='------------------- ';
        $log.='['.date('d-m-Y H:i',time())."] -------------------\n";
        $log.=$message;
        $log.="\n";
        $this->write($log);
    }

    public function write($string)
    {
        file_put_contents($this->path, $string, FILE_APPEND);
    }
}

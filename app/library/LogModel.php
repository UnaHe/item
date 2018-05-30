<?php
use Phalcon\Logger\Formatter\Line;

class LogModel
{
    private $logFile;
    private $message = [];
    private $title = '';

    public function __construct($logFile='')
    {
        $this->setLogFile($logFile);
    }
    public function setLogFile($logFile='')
    {
        if (!empty($logFile)) {
            $this->logFile = str_replace('::', '_', $logFile);
            $this->checkDir($this->logFile);
        }
    }

    public function addMessage($message, $type = Phalcon\Logger::INFO)
    {
        $this->message[] = ['msg' => $message, 'type' => $type];
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function resetMessage()
    {
        $this->message = [];
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function appendTitle($text)
    {
        $this->title .= ' ' . $text;
    }

    public function log()
    {
        if (!empty($this->message)) {
            $formatter = new Line("[%date% %type%] " . $this->getTitle() . " %message%");
            $formatter->setDateFormat('Y-m-d H:i:s');
            $logger = new \Phalcon\Logger\Adapter\File($this->logFile);
            $logger->setFormatter($formatter);
            $logger->begin();
            foreach ($this->message as $v) {
                $logger->log($v['type'], $v['msg']);
            }
            $logger->commit();
        }
    }

    public function parsePhalconErrMsg($msg)
    {
        $errMsg = '';
        foreach ($msg as $v) {
            $errMsg .= $v->getMessage() . ' ';
        }
        return $errMsg;
    }

    private static function checkDir($fileName)
    {
        $replaceString = strrchr($fileName, '/');
        $dir = str_replace($replaceString, '', $fileName);
        if (!file_exists($dir)) {
            umask(0);
            mkdir($dir, 0777, true);
        }
    }
}
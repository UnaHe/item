<?php
namespace Roger\Resource;

class ResourceNotice extends \Roger\Resource\ResourceAbstract
{
    protected $options = ['file' => '', 'type' => ''];

    public function execute()
    {
        if (file_exists($this->options['file'])) {
            $md5 = md5_file($this->options['file']);
        }else{
            $this->errMsg = 'No File Found!';
            return;
        }
        $file = str_replace(APP_PATH . 'public/', '', $this->options['file']);
        return ['file' => $file, 'md5' => $md5];
    }
}
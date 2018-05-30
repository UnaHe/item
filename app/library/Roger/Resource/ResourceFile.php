<?php
namespace Roger\Resource;

class ResourceFile extends \Roger\Resource\ResourceAbstract
{
    protected $options = ['file' => '', 'type' => ''];
    public function execute()
    {
        if (file_exists($this->options['file'])) {
            $md5 = md5_file($this->options['file']);
        }else{
            $this->errMsg = 'No File Found!';
            return false;
        }
        $settingModel = new \SettingModel();
        $setting = $settingModel->getByKeys(['wapUrl']);
        $file = str_replace(APP_PATH . 'public/', '', $this->options['file']);
        return ['relativeFile' => $file, 'md5' => $md5 , 'file'=>$setting['wapUrl'].'/'.$file];
    }
}
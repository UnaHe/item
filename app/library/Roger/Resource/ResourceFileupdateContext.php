<?php
namespace Roger\Resource;

use Phalcon\Exception;
use Phalcon\Mvc\Model;

class ResourceFileupdateContext extends \Roger\Resource\ResourceContextAbstract
{
    protected $options = [
        'project_device_display_timeout' => '',
        'project_device_display_tpl' => '',
        'project_device_display_direction' => '',
        'project_device_round_times' => '',
        'map_point_id' => ''
    ];
    private $file = [];

    public function addResource(\Roger\Resource\ResourceAbstract $file)
    {
        if (!$file){
            throw new \Exception($file->getErrMsg());
            die();
        }
        $this->file[] = $file;
    }

    public function execute()
    {
        if (empty($this->file)){
            $this->errMsg = 'No file found';
            return false;
        }
        $result = ['cmd' => static::CMD_FILEUPDATE , 'project_device_display_timeout'=>$this->getOption('project_device_display_timeout'),'project_device_round_times'=>$this->getOption('project_device_round_times')];
        $settingModel = new \SettingModel();
        $setting = $settingModel->getByKeys(['wapUrl']);
        foreach ($this->file as $f){
            $type = $f->getOption('type');
            $_result = $f->execute();
            if (!empty($type)) {
                if (!array_key_exists($type, $result)) {
                    $result[$type] = [];
                }
                $result[$type][] = $_result;
            } else {
                $result += $_result;
            }
        }
        if ($this->getOption('project_device_display_timeout') > 0) {
            $pointModel = new \MapPointModel();
            $pointDetails = $pointModel->getDetailsByMapPointIdSimple($this->getOption('map_point_id'));
            $_s = \CryptModel::encrypt($pointDetails['id'],\CryptModel::POINT_KEY);
            $_f = \CryptModel::encrypt($pointDetails['map_id'],\CryptModel::POINT_KEY);
            $forwardString = $setting['wapUrl'] . '/?s=' . $_s .'&f=' . $_f . '&tpl=' . $this->getOption('project_device_display_tpl');
        } else {
            $forwardString = '';
        }
        $result['project_display_url'] = $forwardString;
        return $result;
    }
}
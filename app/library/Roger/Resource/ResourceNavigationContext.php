<?php
namespace Roger\Resource;

class ResourceNavigationContext extends \Roger\Resource\ResourceContextAbstract
{
    private $resource;

    public function addResource(\Roger\Resource\ResourceAbstract $resource)
    {
        $this->resource = $resource;
    }

    public function execute()
    {
        if (empty($this->resource)) {
            $this->errMsg = 'No resource found';
            return false;
        }
        $result = ['cmd' => static::CMD_NAVIGATION];
        $type = $this->resource->getOption('type');
        $_result = $this->resource->execute();
        $result[$type] = $_result;
        return $result;
    }
}
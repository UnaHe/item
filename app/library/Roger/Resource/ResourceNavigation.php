<?php
namespace Roger\Resource;

class ResourceNavigation extends \Roger\Resource\ResourceAbstract
{
    protected $options = [
        'distance' => '',
        'direction' => '',
        'name' => '',
        'lang' => '',
        'type' => '',
    ];

    public function execute()
    {
        $result = $this->options;
        unset($result['type']);
        return $result;
    }
}
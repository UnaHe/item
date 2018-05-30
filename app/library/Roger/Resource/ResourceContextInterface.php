<?php
namespace Roger\Resource;

interface ResourceContextInterface
{
    public function setOptions(array $options = []);

    public function getErrMsg();

    public function getOption($key);

    public function execute();

    public function addResource(\Roger\Resource\ResourceAbstract $resource);
}
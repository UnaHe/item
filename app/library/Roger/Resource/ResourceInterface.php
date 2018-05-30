<?php
namespace Roger\Resource;

interface ResourceInterface
{
    public function setOptions(array $options = []);

    public function getErrMsg();

    public function getOption($key);

    public function execute();
}
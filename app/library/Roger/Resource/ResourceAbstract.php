<?php
namespace Roger\Resource;

abstract class ResourceAbstract implements \Roger\Resource\ResourceInterface
{
    const TYPE_LIST = 'list';
    const TYPE_VIDEO = 'video';
    const TYPE_QRCODE = 'qrcode';
    const CMD_FILEUPDATE = 'fileupdate';
    const CMD_NAVIGATION = 'navigation';
	protected $errMsg = '';
	protected $options = [];

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

	public function setOptions(array $options = [])
	{
		$diffOptions = array_diff_key($this->options, $options);
		if (!empty($diffOptions)) {
			foreach ($diffOptions as $k => $v) {
				throw new \Exception('option:' . $k . ' can not be empty!');
			}
		}
		foreach ($this->options as $k => $v) {
			$this->options[$k] = $options[$k];
		}
	}

    public function getOption($key)
    {
        if (!array_key_exists($key , $this->options)){
            return null;
        }
        return $this->options[$key];
    }

	public function getErrMsg()
	{
		return $this->errMsg;
	}
}
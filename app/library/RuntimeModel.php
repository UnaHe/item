<?php
class RuntimeModel
{
	var $StartTime = 0;
	var $StopTime = 0;
	private $memoryUsageStart = 0;
	private $memoryUsageEnd = 0;

	private function get_microtime()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}

	public function start()
	{
		$this->StartTime = $this->get_microtime();
		$this->memoryUsageStart = memory_get_usage();
	}

	public function stop()
	{
		$this->StopTime = $this->get_microtime();
		$this->memoryUsageEnd = memory_get_usage();
	}

	public function getResult()
	{
		$spentTime = round(($this->StopTime - $this->StartTime) * 1000, 1);
		$memoryUsage = $this->memoryUsageEnd-$this->memoryUsageStart;
//		echo 'SpentTime:',$spentTime,'ms ';
//		echo 'MemoryUsage:',$memoryUsage;
		return 'SpentTime:'.$spentTime.'ms MemoryUsage:'.$memoryUsage;
	}
}
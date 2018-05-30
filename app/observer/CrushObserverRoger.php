<?php
/**
 * User: Roger
 * Date: 2016/2/1
 * Time: 10:54
 */

class CrushObserverRoger implements SplObserver{

	private $msg;

	public function __construct($msg)
	{
		$this->msg = $msg;
	}

	public function update(SplSubject $subject)
	{
		if (!empty($this->msg)) {
			$params = ['subject' => 'SmartSign3 (' . SYSTEM . ')', 'to' => '677182@qq.com', 'body' => $this->msg];
			return RequestModel::getHttpResponsePOST('http://121.199.49.224:8081/sendmail/sendmail.php', $params);
		}
	}

	public function getMsg(){
		return $this->msg;
	}
}
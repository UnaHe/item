<?php
class Context{
	protected $params = [];
	public function execute(){
		$this->process();
	}

	protected function process(){
		return;
	}

	public function setParam($key , $value){
		$this->params[$key] = $value;
	}

	public function getParam($key){
		return isset($this->params[$key])?$this->params[$key]:false;
	}
}
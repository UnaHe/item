<?php
/**
 * User: Roger
 * Date: 2016/2/1
 * Time: 10:46
 */

class ObserverSubject implements SplSubject{
	private $observers = [];

	public function attach(SplObserver $observer)
	{
		$this->observers[] = $observer;
	}

	public function detach(SplObserver $observer)
	{
	}

	public function notify()
	{
		if (!empty($this->observers)){
			foreach($this->observers as $observer){
				$observer->update($this);
			}
		}
	}

}
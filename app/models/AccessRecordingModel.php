<?php
class AccessRecordingModel extends ModelBase {
	const TAG_PREFIX = 'AccessRecordingModel_';

	public function reset(){
		unset($this->access_recording_id);
	}

	public function getSource() {
		return DB_PREFIX . 'access_recording';
	}
}
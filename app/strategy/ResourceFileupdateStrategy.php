<?php

class ResourceFileupdateStrategy extends Context
{
	protected function process()
	{
		$deviceModel = new ProjectDeviceModel();
		if (empty($this->getParam('device')) && !empty($this->getParam('project_id'))) {
			$deviceList = $deviceModel->getList(['project_id' => $this->getParam('project_id'), 'bind' => 1]);
		}
		if (empty($deviceList) && empty($this->getParam('device'))) {
			return;
		}
		$projectResourceModel = new ProjectResource();
		$config = require APP_PATH . 'app/config/config.php';
		$forwardModel = new ForwardModel();
		$resourceList = [];
		if (!empty($this->getParam('device'))) {
			$devices = $this->getParam('device');
		} elseif (empty($this->getParam('device'))) {
			$devices = $deviceList;
		}
		$logFileName = APP_PATH . 'app/log/' . date('Y-m-d') . '/ResourceFileupdateStrategy.txt';
		$multiRequest = new Roger\Request\MultiRequest();
		$r = new RuntimeModel();
		$r->start();
		foreach ($devices as $v) {
			if (!is_array($v)) {
				$deviceDetails = $deviceModel->getDetailsByProjectDeviceId($v);
				$deviceDetails = $deviceDetails ? $deviceDetails->toArray() : [];
			} else {
				$deviceDetails = $v;
			}
			if ($deviceDetails['project_device_status'] != 1) {
				LogModel::log($logFileName, 'device ID: ' . $deviceDetails['project_device_id'] . ' Offline');
				continue;
			}
			$files = [
				'cmd' => 'fileupdate',
				'project_id' => $deviceDetails['project_id'],
				'resource_category_id' => 1,
			];
			if (!isset($resourceList[$deviceDetails['project_resource_category_id']])) {
				$resourceList[$deviceDetails['project_resource_category_id']] = $projectResourceModel->getListSimple(['project_resource_category_id' => $deviceDetails['project_resource_category_id']]);
			}
			$list = $resourceList[$deviceDetails['project_resource_category_id']];
			foreach ($list as $l) {
				$files[$l['project_resource_type']][] = ['md5' => $l['project_resource_md5'], 'file' => $config->application->resourceUrl . '/' . $l['project_resource_file']];
			}
			$files['qrcode'][] = ['md5' => $deviceDetails['project_device_qrcode_md5'], 'file' => $config->application->resourceUrl . '/' . $deviceDetails['project_device_qrcode']];
			$files['project_display_outtime'] = $deviceDetails['project_device_display_outtime'];
			if ($deviceDetails['project_device_display_outtime'] > 0) {
				$forwardDetails = $forwardModel->getDetailsByMapDataId($deviceDetails['map_data_id']);
				if (!$forwardDetails) {
					$f = clone $forwardModel;
					$f->map_data_id = $deviceDetails['map_data_id'];
					$_data = MapData::createNavigationString($deviceDetails['map_data_id']);
					$f->forward_string = $config->application->wapUrl . '/?s=' . $_data . '&m=' . $deviceDetails['project_device_display_tpl'];
					if ($f->create() == false) {
						LogModel::log($logFileName, 'params: ' . $deviceDetails['map_data_id'] . ' ErrMsg:' . LogModel::parsePhalconErrMsg($f->getMessages()));
						continue;
					}
					$forwardString = $config->application->forwardUrl . '/' . $f->forward_id;
				} else {
					$forwardString = $config->application->forwardUrl . '/' . $forwardDetails->forward_id;
				}
			} else {
				$forwardString = '';
			}
			$files['project_display_url'] = $forwardString;
			$post_data = [
				'type' => 'publish',
				'content' => json_encode($files),
				'to' => $deviceDetails['project_id'] . '|' . $deviceDetails['project_device_id'],
			];
			$multiRequest->addRequest(new Roger\Request\Request(SOCKET_URL , Roger\Request\Request::POST , $post_data ));
		}
		$multiRequest->execute();
		$r->stop();
		LogModel::log($logFileName, 'Result: '.serialize($multiRequest->getResult()).' '.$r->getResult());
	}
}
<?php

class MeetingNoticeStrategy extends Context
{
	protected function process()
	{
		
		$logFileName = APP_PATH . 'app/log/' . date('Y-m-d') . '/MeetingNoticeStrategy.txt';
		$meetingNoticeModel = new MeetingNoticeModel();
		$noticeDetails = $meetingNoticeModel->getDetails($this->getParam('meeting_notice_id'));
		
		if (!$noticeDetails){
			LogModel::log($logFileName, 'meetingNoticeModel->getDetails false params: ' . $this->getParam('meeting_notice_id'));
			return;
		}

		$projectDeviceModel = new ProjectDeviceModel();
		$deviceList = $projectDeviceModel->getList(['project_id'=>$noticeDetails->project_id,'bind'=>1]);
// 		$mapDataModel = new MapData();
// 		$mapDataDetails = $mapDataModel->getDetails($noticeDetails->map_data_id);
// 		if (!$mapDataDetails){
// 			LogModel::log($logFileName, 'mapDataModel->getDetails false params: ' . $noticeDetails->map_data_id);
// 			return;
// 		}
		
		$files = ['cmd'=>'meetingnotice',
				  'title' 	=> $noticeDetails->meeting_notice_title."\n".$noticeDetails->meeting_notice_title_eg,
				  'content' 	=> $noticeDetails->meeting_notice_cont."\n".$noticeDetails->meeting_notice_cont_eg."\n\n地点/Add：".$noticeDetails->meeting_notice_address.'/'.$noticeDetails->meeting_notice_address_eg."\n时间/Time：".$noticeDetails->meeting_notice_time.'/'.$noticeDetails->meeting_notice_time_eg,
				  'time' 		=> $noticeDetails->meeting_notice_looptime,
		];
		
		if (!empty($deviceList)){
			$multiRequest = new Roger\Request\MultiRequest();
			$r = new RuntimeModel();
			$r->start();
			foreach($deviceList as $v){
				if ($v['project_device_status']!=1){
					LogModel::log($logFileName, 'device ID: '.$v['project_device_id'] .' Offline' );
					continue;
				}
				$post_data = [
					'type' => 'publish',
					'content' => json_encode($files),
					'to' => $v['project_id'].'|'.$v['project_device_id'],
				];
				$multiRequest->addRequest(new Roger\Request\Request(SOCKET_URL , Roger\Request\Request::POST , $post_data ));
			}
			$multiRequest->execute();
			$r->stop();
			LogModel::log($logFileName, 'Result: '.serialize($multiRequest->getResult()).' '.$r->getResult());
		}
	}
}
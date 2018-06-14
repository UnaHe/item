<?php

class AjaxController extends ControllerBase
{
    public function get_project_list_by_item_account_idAction()
    {
        if ($this->request->isAjax() && $this->request->isPost()) {
            $rules = [
                'id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ]
            ];

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $itemProjectModel= new ItemProjectModel();
            $itemProject = $itemProjectModel->getList(['item_account_id'=>$input['id']]);
            $this->resultModel->setResult('0', $itemProject['data']);
            return $this->resultModel->output();
        }
    }

    public function emergencyevacuationAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'status' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0,
                        'max_range' => 1
                    )
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $projectModel = new ProjectModel();
            $projectDetails = $projectModel->getDetailsByProjectId($this->user['project_id']);
            $projectDetails->project_emergency_evacuation = $input['status'];
            $this->db->begin();
            try {
                $projectDetails->update();
                $this->db->commit();
                $this->logger->addMessage('OK');
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage());
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [ClientModel::class . 'getList']);
            }
            $this->user['project_emergency_evacuation'] = $input['status'];
            $this->session->set('user', $this->user);
            $content = [
                'cmd' => 'evacuateUpdate',
                'status' => $input['status'],
            ];
            $post_data = [
                'type' => "all",
                'content' => json_encode($content),
                'project' => $this->user['project_id'],
            ];

            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest = new Roger\Request\MultiRequest();
            $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();
            $this->logger->addMessage('push:' . $multiRequest->getResult());
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function refreshAction()
    {
        $this->session->remove('AccessPlugin');
        $this->alert("OK", '/' . $this->user['project_id'] . '/index/index');
    }

    /**
     * 获取点位扫码次数(前十).
     */
    public function getScanTopAction()
    {
        if ($this->request->isAjax() && $this->request->isPost()) {
            $rules = [
                'scan_type' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 2
                    ],
                ]
            ];

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $params = $this->getTime($input['scan_type']);

            $params['project_id'] = $this->user['project_id'];

            $data = (new ScanRecordingModel())->getScanTop($params);

            $this->resultModel->setResult('0', $data);
            return $this->resultModel->output();
        }

        return false;
    }

    public function getNavigationTopAction()
    {
        if ($this->request->isAjax() && $this->request->isPost()) {
            $rules = [
                'navigation_type' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 2
                    ],
                ]
            ];

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $params = $this->getTime($input['navigation_type']);

            $params['project_id'] = $this->user['project_id'];

            $data = (new NaviposRecordingModel())->getNavigationTop($params);

            $this->resultModel->setResult('0', $data);
            return $this->resultModel->output();
        }

        return false;
    }

    public function getSearchTopAction()
    {
        if ($this->request->isAjax() && $this->request->isPost()) {
            $rules = [
                'search_type' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 2
                    ],
                ]
            ];

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $params = $this->getTime($input['search_type']);

            $params['project_id'] = $this->user['project_id'];

            $data = (new SearchRecordingModel())->getSearchTop($params);

            $this->resultModel->setResult('0', $data);
            return $this->resultModel->output();
        }

        return false;
    }

    public function getDestinationTopAction()
    {
        if ($this->request->isAjax() && $this->request->isPost()) {
            $rules = [
                'destination_type' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 2
                    ],
                ]
            ];

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $params = $this->getTime($input['destination_type']);

            $params['project_id'] = $this->user['project_id'];

            $data = (new NaviposRecordingModel())->getDestinationTop($params);

            $this->resultModel->setResult('0', $data);
            return $this->resultModel->output();
        }

        return false;
    }

    public function getTime($type)
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $week = date('w');

        // 时间范围.
        $params = [];
        if ($type === '2') {
            // 本月.
            $params['startTime'] = mktime(0, 0, 0, $month, 1, $year);
            $params['endTime'] = mktime(23, 59, 59, $month, date('t'), $year);
        } else if($type === '1') {
            // 本周.
            $params['startTime'] = mktime(0, 0, 0, $month, $day - $week + 1, $year);
            $params['endTime'] = mktime(23, 59, 59, $month, $day - $week + 7, $year);
        } else {
            //今天.
            $params['startTime'] = mktime(0, 0, 0, $month, $day, $year);
            $params['endTime'] = mktime(0, 0, 0, $month, $day + 1, $year) - 1;
        }

        return $params;
    }
}
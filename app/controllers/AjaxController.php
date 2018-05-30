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
}
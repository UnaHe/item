<?php

/**
 * MarkControllerName(紧急疏散)
 */
class EvacuateController extends ControllerBase
{
    /**
     * MarkActionName(紧急疏散操作)
     * @throws Exception
     */
    public function optAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'remark' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ],
                'evacutePass' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ],
                'timeout' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0,
                    ),
                    'required'
                ],
            );

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101', $filter->getErrMsg());
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            if ($input['evacutePass']!='888'){
                $this->resultModel->setResult('106');
                return $this->resultModel->output();
            }

            $projectModel = new ProjectModel();
            $projectDetails = $projectModel->getDetailsByProjectIdBase($this->user['project_id']);
            // 紧急疏散状态.
            $this->user['projectDetails']['project_emergency_evacuation'] = $this->user['projectDetails']['project_emergency_evacuation']==1?0:1;
            if ($projectDetails){
                $cloneDetails = $projectModel::cloneResult($projectModel, $projectDetails);
                $this->db->begin();
                try{
                    // 更新状态.
                    $cloneDetails->update(['project_emergency_evacuation'=>$this->user['projectDetails']['project_emergency_evacuation']]);
                    $this->db->commit();
                    $this->session->set('user',$this->user);
                }catch (Exception $e){
                    $this->db->rollback();
                }
            }

            // 推送类型, 内容.
            $postClients = [];
            $content = [
                'cmd' => $this->user['projectDetails']['project_emergency_evacuation']==1?'evacuate':'exevacuate',
                'text' => $input['remark'],
                'sTime' => time(),
                'timeout' => $input['timeout']*1,
            ];
            $postClients[] = [
                'type' => 'group',
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'to' => 'equipmentView',
                'project' => $this->user['project_id'],
            ];
            $postClients[] = [
                'type' => 'group',
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'to' => 'mobile',
                'project' => $this->user['project_id'],
            ];

            // 获取 socket地址.
            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest = new Roger\Request\MultiRequest();
            // 添加 socket请求.
            foreach ($postClients as $v) {
                $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST, $v));
            }
            // 执行 socket请求.
            $result = $multiRequest->execute();
            $this->logger->addMessage('evacuate:' . json_encode($multiRequest->getResult(), JSON_UNESCAPED_UNICODE) . '----' . json_encode($postClients, JSON_UNESCAPED_UNICODE));

            $this->resultModel->setResult('0', $result);
            return $this->resultModel->output();
        }

        $this->view->evacuation = $this->user['projectDetails']['project_emergency_evacuation'];
        $this->tag->setTitle($this->translate->_('evacuateOpt'));
    }
}
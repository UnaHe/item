<?php

/**
 * MarkControllerName(紧急疏散)
 */
class EvacuateController extends ControllerBase
{
    /**
     * MarkActionName(紧急疏散操作)
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
            $projectDetails = $projectModel->getDetailsByProjectId($this->user['project_id']);
            $this->user['projectDetails']['project_emergency_evacuation'] = $this->user['projectDetails']['project_emergency_evacuation']==1?0:1;
            if ($projectDetails){
                $cloneDetails = $projectModel::cloneResult($projectModel, $projectDetails);
                $this->db->begin();
                try{
                    $cloneDetails->update(['project_emergency_evacuation'=>$this->user['projectDetails']['project_emergency_evacuation']]);
                    $this->db->commit();
                    $this->session->set('user',$this->user);
                }catch (Exception $e){
                    $this->db->rollback();
                }
            }
            $postClients = [];
            $content = [
                'cmd' => $this->user['projectDetails']['project_emergency_evacuation']==1?'evacuate':'exevacuate',
                'text' => $input['remark']
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
            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest = new Roger\Request\MultiRequest();
            foreach ($postClients as $v) {
                $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'],
                    Roger\Request\Request::POST,
                    $v));
            }
            $result = $multiRequest->execute();
            $this->logger->addMessage('evacuate:' . json_encode($multiRequest->getResult()) . ' ' . json_encode($postClients));

            $this->resultModel->setResult('0', $result);
            return $this->resultModel->output();
        }

        $this->tag->setTitle($this->translate->_('evacuateOpt'));
    }
}
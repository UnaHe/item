<?php

class ProjectController extends ControllerBase
{
    public function panelAction(){

        if ($this->request->isAjax() && $this->request->isPost()) {
            $rules = [
                'project_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0
                    ],
                ]
            ];

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $itemProjectModel = new ItemProjectModel();
            $itemProject = $itemProjectModel->getDetailsByProjectIdAndAccountId($input['project_id'],$this->user['item_account_id']);

            $this->user['project_id'] = 59;
            $this->user['item_account_group_id'] = $itemProject['item_account_group_id'];
            $this->user['item_account_group_name'] = $itemProject['item_account_group_name'];
            $this->user['item_account_group_role'] = $itemProject['item_account_group_role'];


            $projectModel = new ProjectModel();
            $projectDetails = $projectModel->getDetailsByProjectIdBase($this->user['project_id']);

            if (!$projectDetails){
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }

            $this->user['projectDetails'] = $projectDetails;
            $this->session->set('user' , $this->user);
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }

        if ($this->user['project_id']==0){
            $this->forward('/');
        }
    }

}
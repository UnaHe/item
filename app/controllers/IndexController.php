<?php

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        $this->tag->appendTitle($this->translate->_('Dashboard'));
        if (array_key_exists('article', $this->view->modules)) {
            $this->view->articleCount = ArticleModel::count('project_id=' . $this->user['project_id']);
        }

        if (array_key_exists('message', $this->view->modules)) {
            $this->view->messageCount = ClientMessageModel::count('project_id=' . $this->user['project_id']);
        }
        if (array_key_exists('doctor', $this->view->modules)) {
            $this->view->doctorCount = DoctorModel::count('project_id=' . $this->user['project_id']);
            $equipmentModel = new EquipmentModel();
            $this->view->equipmentCount = $equipmentModel->getCountSimple(['project_id' => $this->user['project_id']]);
        }
    }

    public function panelAction(){
        if ($this->request->isAjax() && $this->request->isPost()) {
            $this->user['project_id'] = 0;
            unset($this->user['projectDetails']);
            $this->session->set('user' , $this->user);
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function refreshAction()
    {
        $this->session->remove('AccessPlugin');
        $this->alert("OK", '/project/panel');
    }
}

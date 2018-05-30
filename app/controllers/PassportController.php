<?php

class PassportController extends ControllerBase
{
    private $privateKey = "vUkzxmvgUY5DDSf9MAvrCT1QkXM2ubNIAKC0XvuEw5I=&f=tAiUyGSnPOCT9vW0sg553eMB/COUd3P5j3OVGb8klvgrs";
    private $authTimeout = 60;
    public function wxAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            if (!$this->security->checkToken()) {
                $this->resultModel->setResult('101','csrf');
                return $this->resultModel->output();
            }

            $rules = array(
                'openid' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'timestamp' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'sign' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                )
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            if ((time()-intval($input['timestamp']))>=$this->authTimeout){
                $this->resultModel->setResult('601');
                return $this->resultModel->output();
            }
            $_sign = md5($input['timestamp'].$this->privateKey);
            if (strcmp($input['sign'],$_sign)!=0){
                $this->resultModel->setResult('103');
                return $this->resultModel->output();
            }
            $clientModel = new ClientModel();
            $clientDetails  = $clientModel->getDetailsByOpenidAndProjectIdSimple($input ['openid'],73);
            if (!$clientDetails) {
                $this->resultModel->setResult('204');
                return $this->resultModel->output();
            }
            if ($clientDetails['client_status']!=1) {
                $this->resultModel->setResult('204');
                return $this->resultModel->output();
            }
            $clientDetails['user_role'] = $clientDetails['client_group_role'];
            $this->logger->addMessage(json_encode($clientDetails,JSON_UNESCAPED_UNICODE));
            
            $this->session->set('user', $clientDetails);
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }
    public function logoutAction()
    {
        $this->logger->addMessage('success' , Phalcon\Logger::INFO);
        $this->logger->log();
        
        $this->session->remove('AccessPlugin');
        $this->session->remove('user');
        $this->forward('/passport/login');
    }

    public function loginAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            if (!$this->security->checkToken()) {
                $this->resultModel->setResult('101','csrf');
                return $this->resultModel->output();
            }
            $rules = array(
                'username' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'passwd' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $itemAccountModel = new ItemAccountModel();
            $itemAccountDetails = $itemAccountModel->getDetailsByUsername($input['username']);

            if (!$itemAccountDetails || $itemAccountDetails['item_account_status']!=1){
                $this->resultModel->setResult('204');
                return $this->resultModel->output();
            }
            $security = new \Phalcon\Security();
            if (!$security->checkHash($input['passwd'], $itemAccountDetails['item_account_password'])) {
                $this->resultModel->setResult('204');
                return $this->resultModel->output();
            }
            unset ($itemAccountDetails ['item_account_password']);
            $itemAccountDetails['project_id'] = 0;
            $itemAccountDetails['item_account_group_id'] = "administrator";
            $this->logger->addMessage('success item_account_id:'.$itemAccountDetails['item_account_id'] , Phalcon\Logger::INFO);
            //设置session 保存用户登录信息
            $this->session->set('user', $itemAccountDetails);
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        if (!empty($this->user['item_account_id'])){
            $this->forward('/');
        }
        $this->tag->appendTitle('登录');
        $this->view->_csrfKey = $this->security->getTokenKey();
        $this->view->_csrf = $this->security->getToken();
        require APP_PATH . 'app/library/phpqrcode.php';
        $context = new \Roger\Weixin\Message\MessageContext();

        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $context->getAppId() . '&redirect_uri=' . urlencode('https://oauth.signp.cn/wx/client/login') . '&response_type=code&scope=snsapi_userinfo&state='.$this->view->_csrfKey.'|73#wechat_redirect';
        ob_start();
        ob_implicit_flush(1);
        QRcode::png($url);
        $image = ob_get_contents();
        ob_end_clean();
        $image = base64_encode($image);
        $this->response->setContentType('text/html; charset=UTF-8');
        $this->view->qrImg = $image;
    }

    function changepassAction(){
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'old_pass' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'new_pass' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $itemAccountModel = new ItemAccountModel();
            $itemAccountDetails = $itemAccountModel::findfirst($this->user['item_account_id']);
            $security = new \Phalcon\Security();
            if (!$security->checkHash($input['old_pass'], $itemAccountDetails->item_account_password)) {
                $this->resultModel->setResult('207');
                return $this->resultModel->output();
            }
            $itemAccountDetails->update(['item_account_password'=>$this->security->hash($input['new_pass'])]);
            $this->logger->addMessage('success item_account_id:'.$itemAccountDetails->item_account_id.' changepass '.$input['new_pass'] , Phalcon\Logger::INFO);
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }
}
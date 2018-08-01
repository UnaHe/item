<?php
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;

class ControllerBase extends Controller
{
    protected static $AccessKeyId = 'LTAIoh5uIkAzSN25';
    protected static $AccessKeySecret = '3XZO16LsLVx7V1JEBT6PnozzvhH4Xx';
    protected static $EndPoint = 'http://oss-cn-shenzhen.aliyuncs.com';
//    protected static $EndPoint = 'http://oss-cn-shenzhen-internal.aliyuncs.com';
    protected static $DefaultBucket = "signposs1";
    protected $user;
    protected $resultModel;
    protected $key = '43ad4680da98dec7c5b179ff63d11488';
    protected $displayTpl = ['21.5', '10.1', 'mobile'];
    protected $displayDirection = ['horizontal', 'vertical'];
    const SourceOss = 'oss';
    const SourceLocale = 'locale';

    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $this->user = $this->view->user = $this->session->get('user') ? $this->session->get('user') : [
            'item_account_locale' => 'zh_CN',
        ];

        $controllerName = $dispatcher->getControllerName();

        if ($controllerName === 'api') {
            $this->user['item_account_id'] = 0;
        }

        $this->logger->setLogFile(LOG_FILE_DIR . $controllerName . '.txt');
        $this->logger->setTitle('item_account_id:' . intval($this->user['item_account_id']) . ' [' . $this->request->getMethod() . ' '.$controllerName.'/' . $dispatcher->getActionName().']');
    }

    public function initialize()
    {
        $this->view->controller = $this->dispatcher->getControllerName();
        $this->view->action = $this->dispatcher->getActionName();
        $cpAclNew = ['resource' => []];
        if (isset($this->user['project_id'])) {
            $modules = [];
            if ($this->user['project_id']>0) {
                $modules = explode(',', $this->user['projectDetails']['project_modules_client_modules']);
            }
            $accessModel = new AccessModel();
            if($this->user['item_account_group_role'] == 'administrator'){
//                var_dump($this->user['item_account_group_role']);exit;
                $cpAclArr = $accessModel->getList('administrator', 0);
            }else{
                $cpAclArr = $accessModel->getList($this->user['item_account_group_role'], $this->user['project_id']);
            }
            if (!empty($cpAclArr) && !empty($modules[0])) {
                $modules = array_combine($modules, array_fill(0, count($modules), ''));
                foreach ($cpAclArr as $v) {
                    if ($v['rule'] == 'allow') {
                        if (is_null($v['resource']) && is_null($v['operation'])) {
                            foreach ($modules as $k => $_) {
                                $cpAclNew['resource'][$k][] = 'all';
                            }
                        } elseif (!is_null($v['resource']) && is_null($v['operation'])) {
                            if (array_key_exists($v['resource'], $modules)) {
                                $cpAclNew['resource'][$v['resource']][] = 'all';
                            }
                        } elseif (!is_null($v['resource']) && !is_null($v['operation'])) {
                            if (array_key_exists($v['resource'], $modules)) {
                                $cpAclNew['resource'][$v['resource']][] = $v['operation'];
                            }
                        }
                    }
                }
            }
            foreach (AccessPlugin::$commonResources as $v) {
                $cpAclNew['resource'][$v][] = 'all';
            }
            $this->view->modules = $modules;
        }
        if (isset($cpAclNew['resource']['article'])) {
            $articleCategoryModel = new ArticleCategoryModel();
            $articleCategoryList = $articleCategoryModel->getList(null, $this->user['project_id'],1);
            $this->view->articleCategoryList = $articleCategoryList;
        }

        $this->view->cpAclNew = $cpAclNew;
        $this->resultModel = new ResultModel ();
        $settingModel = new SettingModel();
        $this->view->setting = $settingModel->getByKeys(['ioUrl','socketUrl']);
        $this->tag->setTitle('项目管理平台');
        $itemProjectModel= new ItemProjectModel();
        $itemProject = $itemProjectModel->getList(['item_account_id'=>$this->user['item_account_id'],'item_project_status'=>1]);
        $this->view->itemProjectList = $itemProject['data'];
    }

    protected function forward($uri)
    {
        $html = '<script>location.href="' . $uri . '";</script>';
        header('Content-Type: text/html; charset=utf-8');
        die ($html);
    }

    protected function alert($message = '', $return_url = null)
    {
        if (is_null($return_url)) {
            $return_url = '/';
            if (isset ($_SERVER ['HTTP_REFERER'])) {
                $return_url = $_SERVER ['HTTP_REFERER'];
            }
        }
        $html = '<script>' . (empty($message) ? '' : 'alert("' . $message . '");') . 'location.href="' . $return_url . '";</script>';
        header('Content-Type: text/html; charset=utf-8');
        die ($html);
    }
}

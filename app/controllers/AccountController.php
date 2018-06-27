<?php

/**
 * MarkControllerName(账号管理)
 *
 */
class AccountController extends ControllerBase
{
    /**
     * MarkActionName(账号列表|ajaxhandle)
     */
    public function listAction()
    {
        $rules = array(
            'page' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => 1
            ),
            'psize' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => 30
            ),
            'usePage' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 0,
                    'max_range' => 1
                ),
                'default' => 1
            )
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $clientModel = new ClientModel();
        $client = $clientModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->accountList = $client['data'];
        $clientGroupModel = new ClientGroupModel();
        $clientGroup = $clientGroupModel->getList(['project_id' => $this->user['project_id']]);

        $baseGroup = $clientGroupModel->getList(['project_id' => 0]);
        $this->view->groupList = array_merge($clientGroup['data'], $baseGroup['data']);
        $this->view->pageCount = $client['pageCount'];
        $this->tag->appendTitle($this->translate->_('AccountList'));
        $this->view->filter = $input;
    }

    public function ajaxhandleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'openid' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'group_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                ),
                'name' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'status' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0,
                        'max_range' => 1,
                    ),
                ),
            );

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $clientModel = new ClientModel();
            $clientDetails = $clientModel->getDetailsByOpenidAndProjectIdSimple($input['openid'],
                $this->user['project_id']);
            if (!$clientDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $cloneDetails = $clientModel::cloneResult($clientModel, $clientDetails);
            $cloneDetails->client_status = $input['status'];
            $cloneDetails->client_group_id = $input['group_id'];
            $cloneDetails->client_realname = $input['name'];
            $this->db->begin();
            try {
                $cloneDetails->update();
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(),
                        JSON_UNESCAPED_UNICODE) . ' ' . json_encode($clientDetails,
                        JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input), Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }

            $this->logger->log();
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(ClientModel::class . 'getDetailsByOpenidAndProjectIdSimple',
                    [$input['openid'], $this->user['project_id']]));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [ClientModel::class . 'getList' . $this->user['project_id']]);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    /**
     * MarkActionName(分组管理)
     * @return mixed
     */
    public function groupAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                    'default' => null
                ),
                'name' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'role' => array(
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
            $clientGroupModel = new ClientGroupModel();
            $input['role'] = strtolower($input['role']);
            if ($input['role'] == 'administrator') {
                $this->resultModel->setResult('208');
                return $this->resultModel->output();
            }
            if (!is_null($input['id'])) {
                $clientGroupDetails = $clientGroupModel->getDetailsByClientGroupId($input['id']);
                if (!$clientGroupDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $clientGroupDetails = $oldDetails = $clientGroupDetails->toArray();
                $this->logger->appendTitle('update');
            } else {
                $attributes = $clientGroupModel->getModelsMetaData()->getAttributes($clientGroupModel);
                $clientGroupDetails = array_combine($attributes, array_fill(0, count($attributes), ''));
                unset($clientGroupDetails['client_group_id']);
                $clientGroupDetails['client_group_create_at'] = time();
                $this->logger->appendTitle('create');
            }
            $clientGroupDetails['client_group_name'] = $input['name'];
            $clientGroupDetails['client_group_role'] = $input['role'];
            $clientGroupDetails['project_id'] = $this->user['project_id'];

            $cloneDetails = $clientGroupModel::cloneResult($clientGroupModel, $clientGroupDetails);
            $this->db->begin();
            try {
                $cloneDetails->save();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage(json_encode($input) . ' ' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                if ($e->getCode() == '23505') {
                    $this->resultModel->setResult('206');
                } else {
                    $this->resultModel->setResult('102');
                }
                $this->logger->log();
                return $this->resultModel->output();
            }
            $this->db->commit();
            $this->logger->addMessage(json_encode($cloneDetails->toArray(),
                    JSON_UNESCAPED_UNICODE) . (isset($oldDetails) ? json_encode($oldDetails,
                    JSON_UNESCAPED_UNICODE) : ''));
            $this->logger->log();
            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [ClientGroupModel::class . 'getList' . $this->user['project_id']]);
                $this->cache->delete(CacheBase::makeTag(ClientGroupModel::class . 'getDetailsByClientGroupId',
                    $input['id']));
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $this->tag->appendTitle($this->translate->_('AccountGroup'));
        $clientGroupModel = new ClientGroupModel();
        $clientGroup = $clientGroupModel->getList(['project_id' => $this->user['project_id']]);
        $this->view->clientGroupList = $clientGroup['data'];
        $cateJson = [];
        if (!empty ($clientGroup['data'])) {
            foreach ($clientGroup['data'] as $v) {
                $cateJson [$v ['client_group_id']] = $v;
            }
        }
        $this->view->cates = json_encode($cateJson, JSON_UNESCAPED_UNICODE);
    }

    /**
     * MarkActionName(账号分组权限)
     * @return mixed
     */
    public function groupaccessAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'groupId' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                ),
                'data' => array(
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $data = json_decode($input['data'], JSON_OBJECT_AS_ARRAY);
            if ($data === false) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $clientGroupModel = new ClientGroupModel();
            $clientGroupDetails = $clientGroupModel->getDetailsByClientGroupId($input['groupId']);
            if (empty($data)) {
                $this->db->begin();
                try {
                    $this->db->query("delete from " . DB_PREFIX . "item_access where project_id=".$this->user['project_id']." AND role='" . $clientGroupDetails->client_group_role . "'");
                    $this->db->commit();
                    $this->logger->addMessage("access cleared:" . json_encode($clientGroupDetails->toArray(), JSON_UNESCAPED_UNICODE));
                } catch (Exception $e) {
                    $this->db->rollback();
                    $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input, JSON_UNESCAPED_UNICODE));
                    $this->logger->log();
                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }
            } else {
                $accessModel = new AccessModel();
                $grantedAccess = [];
                $this->db->begin();
                $this->db->query("delete from " . DB_PREFIX . "access where project_id=".$this->user['project_id']." AND system='" . SYSTEM . "' AND role='" . $clientGroupDetails->client_group_role . "'");
                foreach ($data as $v) {
                    $accessTag = sprintf('%s_%s', $v['controller'], $v['action']);
                    if (in_array($accessTag, $grantedAccess)) {
                        continue;
                    }
                    $_params = [
                        'system' => SYSTEM,
                        'role' => $clientGroupDetails->client_group_role,
                        'resource' => $v['controller'],
                        'operation' => $v['action'],
                        'rule' => 'allow',
                        'project_id' => $this->user['project_id'],
                    ];
                    $grantedAccess[] = $accessTag;
                    if (strpos($v['action'], ',') !== false) {
                        $_actions = explode(',', $v['action']);
                        if (in_array(sprintf('%s_%s', $_actions[0], $_actions[1]), $grantedAccess)) {
                            continue;
                        }

                        $_params['resource'] = $_actions[0];
                        $_params['operation'] = $_actions[1];
                    }

                    $cloneDetails = $accessModel::cloneResult($accessModel, $_params);
                    try {
                        $cloneDetails->create($_params);
                    } catch (Exception $e) {
                        $this->db->rollback();
                        $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input, JSON_UNESCAPED_UNICODE));
                        $this->logger->log();
                        $this->resultModel->setResult('102');
                        return $this->resultModel->output();
                    }
                }
                $this->db->commit();
            }

            $this->logger->addMessage(json_encode($input, JSON_UNESCAPED_UNICODE));
            $this->logger->log();
            $this->session->remove('AccessPlugin');
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(AccessModel::class . 'getList',
                    [$this->user['project_id'], SYSTEM, 0]));
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $this->tag->appendTitle($this->translate->_('AccountGroupAccess'));

        $rules = array(
            'group_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
            ),
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->resultModel->setResult('101');
            return $this->resultModel->output();
        }

        $input = $filter->getResult();
        $clientGroupModel = new ClientGroupModel();

        if (!is_null($input['group_id'])) {
            $groupDetails = $clientGroupModel->getDetailsByClientGroupId($input['group_id']);
            $projectModulesModel = new ProjectModulesModel();
            $projectModules = $projectModulesModel->getDetailsByProjectId($this->user['project_id']);

            $modules = explode(',', $projectModules['project_modules_client_modules']);
            $accessPlugin = new AccessPlugin();
            $acl = $accessPlugin->getAcl($groupDetails->client_group_role, $this->user['project_id']);
            $tag = CacheBase::makeTag('clientAccessFetch', $input['group_id']);
            $reource = $this->cache->get($tag);
            if (!$reource || 1) {
                $controllerDir = APP_PATH . 'app/controllers/';
                $files = scandir($controllerDir);
                $reource = [];
                foreach ($files as $v) {
                    if ($v == '.' || $v == '..' || $v == 'ControllerBase.php') {
                        continue;
                    }
                    $_controllerName = strtolower(str_replace('Controller.php', '', $v));
                    if (array_key_exists($_controllerName, AccessPlugin::$publicResources) || in_array($_controllerName,
                            AccessPlugin::$commonResources) || !in_array($_controllerName, $modules)
                    ) {
                        continue;
                    }

                    $_controller = file_get_contents($controllerDir . '/' . $v);
                    preg_match('/MarkControllerName\((.+)\)/', $_controller, $controller);
                    if (empty($controller)) {
                        continue;
                    }
                    $reource[$_controllerName]['name'] = isset($controller[1]) ? $controller[1] : '';
                    preg_match_all('/MarkActionName\((.+)\)/', $_controller, $action);
                    preg_match_all('/function (?!ajax)(.+)Action\(\)/', $_controller, $controllerActions);
                    if (!empty($controllerActions) && !empty($action)) {
                        if (!isset($reource[$_controllerName])) {
                            $reource[$_controllerName] = [];
                        }

                        foreach ($controllerActions[1] as $k => $v) {
                            if (isset($action[1][$k])) {
                                $bind = explode('|', $action[1][$k]);
                                $allowed = intval($acl->isAllowed($groupDetails->client_group_role, $_controllerName,
                                    $controllerActions[1][$k]));
                                $controllerActions[1][$k] = [
                                    'action' => $v,
                                    'name' => $bind[0],
                                    'allowed' => $allowed,
                                ];
                                if (count($bind) > 1) {
                                    unset($bind[0]);
                                    $controllerActions[1][$k]['bind'] = array_values($bind);
                                }
                            } else {
                                unset($controllerActions[1][$k]);
                            }
                        }
                        $reource[$_controllerName]['actions'] = $controllerActions[1];
                    }
                }
                $this->cache->save($tag, $reource);
            }
            $this->view->resource = $reource;
        }


        $this->tag->appendTitle($this->translate->_('AccountGroupAccess'));

        $clientGroup = $clientGroupModel->getList(['project_id' => $this->user['project_id']]);
        $this->view->groupList = $clientGroup['data'];
        $this->view->filter = $input;

    }
}

<?php

/**
 * MarkControllerName(消息管理)
 */
class NoticeController extends ControllerBase
{
    /**
     * MarkActionName(消息推送|pointresource,ajaxgetequipmentbymapid)
     */
    public function pushAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'equipment_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                    'default' => null
                ),
                'map_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    ),
                    'default' => 0
                ),
                'toMobile' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0,
                        'max_range' => 1
                    ),
                ),
                'point_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    ),
                    'default' => 0
                ),
                'level' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1,
                        'max_range' => 3,
                    ),
                ),
//                'title_cn' => [
//                    'filter' => FILTER_UNSAFE_RAW,
//                    'required'
//                ],
                'content_cn' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ],
//                'title_en' => [
//                    'filter' => FILTER_UNSAFE_RAW,
//                    'default' => ''
//                ],
                'content_en' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'default' => ''
                ],
                'timeout' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1,
                    ),
                ),
            );

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $postClients = [];
            $title = $input['level'] == 1 ? '紧急信息' : '临时信息';
            if (!empty($input['content_en'])) {
                $title .= "\n" . ($input['level'] == 1 ? 'urgent message' : 'simple message');
            }
            $content = [
                'cmd' => 'notice',
                'type' => 'text',
                'content' => $input['content_cn'] . (!empty($input['content_en']) ? "\n" . $input['content_en'] : ''),
                'title' => $title,
                'level' => $input['level'],
                'timeout' => $input['timeout'],
            ];
            $equipmentModel = new EquipmentModel();
            if ($input['map_id'] == 0) {
                $equipment = $equipmentModel->getListSimple(['project_id' => $this->user['project_id']]);
                if (!empty($equipment['data'])) {
                    $postClients[] = [
                        'type' => 'group',
                        'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                        'to' => 'equipmentView',
                        'project' => $this->user['project_id'],
                    ];
                }
            } else {
                if ($input['map_id'] > 0 && $input['point_id'] == 0) {
                    $equipment = $equipmentModel->getListSimple([
                        'project_id' => $this->user['project_id'],
                        'map_id' => $input['map_id']
                    ]);
                    if (!empty($equipment['data'])) {
                        foreach ($equipment['data'] as $v) {
                            $postClients[] = [
                                'type' => 'tag',
                                'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                                'to' => $this->user['project_id'] . '|equipment|' . $v['equipment_code'],
                                'project' => $this->user['project_id'],
                            ];
                        }
                    }
                } else {
                    if ($input['map_id'] > 0 && $input['point_id'] > 0 && !empty($input['equipment_id'])) {
                        $equipmentDetails = $equipmentModel->getDetailsByIdSimple($input['equipment_id']);
                        if (!$equipmentDetails) {
                            $this->resultModel->setResult('-1');
                            return $this->resultModel->output();
                        }
                        $postClients[] = [
                            'type' => 'tag',
                            'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                            'to' => $this->user['project_id'] . '|equipment|' . $equipmentDetails['equipment_code'],
                            'project' => $this->user['project_id'],
                        ];
                    }
                }
            }
            if ($input['toMobile']==1){
                $postClients[] = [
                    'type' => 'group',
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'to' => 'mobile',
                    'project' => $this->user['project_id'],
                ];
            }
            $noticeModel = new NoticeModel();
            $params = [
                'notice_level' => $input['level'],
                'notice_content_zh_CN' => $input['content_cn'],
                'notice_created_at' => time(),
                'notice_content_en_US' => $input['content_en'],
                'project_id' => $this->user['project_id'],
                'client_id' => $this->user['item_account_id'],
            ];
            $this->db->begin();
            try {
                $noticeModel->create($params);
                $this->db->commit();
                $this->logger->addMessage('new notice:'.json_encode($noticeModel->toArray() , JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input), Phalcon\Logger::CRITICAL);
            }
            if (!empty($postClients)) {
                $settingModel = new SettingModel();
                $setting = $settingModel->getByKeys(['socketUrl']);
                $multiRequest = new Roger\Request\MultiRequest();
                foreach ($postClients as $v) {
                    $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'],
                        Roger\Request\Request::POST,
                        $v));
                }
                $multiRequest->execute();
                $this->logger->addMessage('result:' . json_encode($multiRequest->getResult()).' '.json_encode($postClients));
            }

            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [NoticeModel::class . 'getList'.$this->user['project_id']]);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = array(
            'notice_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
            ),
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->setResult('101'));
        }
        $input = $filter->getResult();
        $noticeModel = new NoticeModel();
        if (!is_null($input['notice_id'])) {
            $noticeDetails = $noticeModel->getDetailsByNoticeIdSimple($input['notice_id']);
            if (!$noticeDetails) {
                $this->alert($this->resultModel->setResult('-1'));
            }
        } else {
            $noticeDetails = $noticeModel::cloneResult($noticeModel, [])->toArray();
        }
        $this->view->noticeDetails = $noticeDetails;
        $mapModel = new MapModel();
        $map = $mapModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->mapList = $map['data'];

        $projectModel = new ProjectModel();
        $project = $projectModel->getList();
        $this->view->projectList = $project['data'];

        $this->tag->appendTitle($this->translate->_('MessagePush'));
    }


    /**
     * MarkActionName(已推送列表)
     */
    public function logAction()
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
                'default' => 20
            ),
            'usePage' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 0,
                    'max_range' => 1
                ),
                'default' => 1
            ),
            'notice_level' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1,
                    'max_range' => 3
                ),
                'default' => null
            )
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $input['project_id'] = $this->user['project_id'];
        $noticeModel = new NoticeModel();
        $notice = $noticeModel->getListSimple($input);
        $this->view->noticeList = $notice['data'];
        $this->view->pageCount = $notice['pageCount'];
        $this->view->filter = $input;
        $this->tag->appendTitle($this->translate->_('NoticeLog'));
    }


    /**
     * 发布通知
     */
    public function ajaxsendnoticeAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'equipment_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                ),
                'level' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1,
                        'max_range' => 3,
                    ),
                ),
//                'title_cn' => [
//                    'filter' => FILTER_UNSAFE_RAW,
//                    'required'
//                ],
                'content_cn' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ],
//                'title_en' => [
//                    'filter' => FILTER_UNSAFE_RAW,
//                    'default' => ''
//                ],
                'content_en' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'default' => ''
                ],
                'timeout' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1,
                    ),
                ),
            );

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $postClients = [];
            $title = $input['level'] == 1 ? '紧急信息' : '临时信息';
            if (!empty($input['content_en'])) {
                $title .= "\n" . ($input['level'] == 1 ? 'urgent message' : 'simple message');
            }
            $content = [
                'cmd' => 'notice',
                'type' => 'text',
                'content' => $input['content_cn'] . (!empty($input['content_en']) ? "\n" . $input['content_en'] : ''),
                'title' => $title,
                'level' => $input['level'],
                'timeout' => $input['timeout'],
            ];
            $equipmentModel = new EquipmentModel();
            $equipmentDetails = $equipmentModel->getDetailsByIdSimple($input['equipment_id']);
            if (!$equipmentDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $noticeModel = new NoticeModel();
            $params = [
                'notice_level' => $input['level'],
                'notice_content_zh_CN' => $input['content_cn'],
                'notice_create_at' => time(),
                'notice_content_en_US' => $input['content_en'],
                'project_id' => $this->user['project_id'],
                'client_id' => $this->user['item_account_id'],
            ];
            $this->db->begin();
            try {
                $noticeModel->create($params);
                $this->db->commit();
                $this->logger->addMessage('new notice:'.json_encode($noticeModel->toArray() , JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input), Phalcon\Logger::CRITICAL);
            }

            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [NoticeModel::class . 'getList'.$this->user['project_id']]);
            }

            $post_data = [
                'type' => 'tag',
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'to' => $this->user['project_id'] . '|equipment|' . $equipmentDetails['equipment_code'],
                'project' => $this->user['project_id'],
            ];

            $multiRequest = new Roger\Request\MultiRequest();
            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();
            $this->logger->addMessage('result:' . $multiRequest->getResult() . ' ' . json_encode($post_data,
                    JSON_UNESCAPED_UNICODE));

            if ($multiRequest->getResult() == 'ok') {
                $this->resultModel->setResult('0');
            } else {
                $this->resultModel->setResult('801');
            }
            return $this->resultModel->output();
        }
        $rules = array(
            'notice_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
            ),
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $noticeModel = new NoticeModel();
        $details = $noticeModel->getDetails($input['notice_id']);
        if (!$details) {
            $this->alert($this->resultModel->getMsg('-1'));
        }
        $this->view->details = $details->toArray();
        $this->view->pageTitle = $this->translate->_('NoticePush');
        $this->tag->appendTitle($this->view->pageTitle);
    }

}
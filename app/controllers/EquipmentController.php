<?php

/**
 * MarkControllerName(设备管理)
 */
class EquipmentController extends ControllerBase
{
    /**
     * MarkActionName(红外设备)
     * 
     * @return mixed
     */
    public function infraredAction()
    {
        $rules = [
            'map_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => null
            ],
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
            )
        ];
        $filter = new FilterModel($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->appendTitle($this->translate->_("infraredList"));
        $equipmentModel = new EquipmentModel();
        $equipment = $equipmentModel->getList(array_merge($input, ['project_id' => $this->user['project_id'], 'type' => EquipmentModel::TYPE_INFRARED]));
        $this->view->filter = $input;
        $this->view->equipment = $equipment;
    }

    /**
     * MarkActionName(红外设备编辑)
     * 
     * @return mixed
     */
    public function infraredhandleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            set_time_limit(0);
            $rules = array(
                'equipment_infrared_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
                'infraredWidth' => array(
                    'filter' => FILTER_VALIDATE_FLOAT,
                    'options' => [
                        'min_range' => 90
                    ],
                ),
                'infraredHeight' => array(
                    'filter' => FILTER_VALIDATE_FLOAT,
                    'options' => [
                        'min_range' => 90
                    ],
                ),
                'screenWidth' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1080,
                        'max_range' => 1920
                    ],
                ),
                'screenHeight' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1080,
                        'max_range' => 1920
                    ],
                ),
                'backgroundImage' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'remark' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'clearAreaData' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 1
                    ],
                    'default' => 0
                ),
                'upateEquipment' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 1
                    ],
                    'default' => 0
                ),
                'images' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'video' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
            );
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101', $filter->getErrMsg());
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $equipmentInfraredModel = new EquipmentInfraredModel();
            $equipmentInfraredDetails = $equipmentInfraredModel->getDetailsById($input['equipment_infrared_id']);
            if (!$equipmentInfraredDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }

            $params = $uploadImages = $deleteObjects = [];
            if (!empty($input['backgroundImage']) && strpos($input['backgroundImage'], '/') === 0) {
                if (!empty($equipmentInfraredDetails['equipment_infrared_backgroundImage'])) {
                    $deleteObjects[] = $equipmentInfraredDetails['equipment_infrared_backgroundImage'];
                }
                $ossClient = new OSS\OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                $ossClient->setConnectTimeout(5);
                $objectName = ltrim($input['backgroundImage'], '/');
                $params['equipment_infrared_backgroundImage'] = $objectName;
                try {
                    $ossClient->putObject(self::$DefaultBucket, $objectName, file_get_contents(APP_PATH . 'public/' . $objectName));
                } catch (Exception $e) {
                    $this->logger->addMessage('oss upload err:' . $e->getLine() . ' ' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                    $this->resultModel->setResult('601', $e->getMessage());
                    return $this->resultModel->output();
                }
            }
            if (!empty($input['video']) && strpos($input['video'], '/') === 0) {
                if (!empty($equipmentInfraredDetails['equipment_infrared_video'])) {
                    $deleteObjects[] = $equipmentInfraredDetails['equipment_infrared_video'];
                }
                if (!isset($ossClient)) {
                    $ossClient = new OSS\OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);
                }
                $objectName = ltrim($input['video'], '/');
                $params['equipment_infrared_video'] = $objectName;
                try {
                    $ossClient->putObject(self::$DefaultBucket, $objectName, file_get_contents(APP_PATH . 'public/' . $objectName));
                } catch (Exception $e) {
                    $this->logger->addMessage('oss upload err:' . $e->getLine() . ' ' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                    $this->resultModel->setResult('601', $e->getMessage());
                    return $this->resultModel->output();
                }
            }
            if (!empty($input['images'])) {
                $input['images'] = explode(',', rtrim($input['images'], ','));
                if (!isset($ossClient)) {
                    $ossClient = new OSS\OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);
                }
                $oldImages = [];
                if (!empty($equipmentInfraredDetails['equipment_infrared_images'])) {
                    $oldImages = explode(',', $equipmentInfraredDetails['equipment_infrared_images']);
                }
                if (!empty($oldImages)) {
                    foreach ($input['images'] as $v) {
                        if (strpos($v, '/') === 0) {
                            $uploadImages[] = $v;
                            continue;
                        }
                        $_key = array_search($v, $oldImages);
                        if ($_key) {
                            unset($oldImages[$_key]);
                        }
                    }
                    $deleteObjects = array_merge($deleteObjects, $oldImages);
                } else {
                    $uploadImages = array_merge($uploadImages, $input['images']);
                }
                if (!empty($uploadImages)) {
                    try {
                        foreach ($uploadImages as $v) {
                            $_v = ltrim($v, '/');
                            $ossClient->putObject(self::$DefaultBucket, $_v, file_get_contents(APP_PATH . 'public/' . $_v));
                        }
                    } catch (Exception $e) {
                        $this->logger->addMessage('oss upload err:' . $e->getLine() . ' ' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                        $this->resultModel->setResult('601', $e->getMessage());
                        return $this->resultModel->output();
                    }
                }
                $params['equipment_infrared_images'] = '';
                foreach ($input['images'] as $v) {
                    $_v = ltrim($v, '/');
                    $params['equipment_infrared_images'] .= $_v . ',';
                }
                $params['equipment_infrared_images'] = rtrim($params['equipment_infrared_images'], ',');
            } else {
                $input['equipment_infrared_images'] = '';
                if (!empty($params['equipment_infrared_images'])) {
                    $deleteObjects = array_merge($deleteObjects, explode(',', $params['equipment_infrared_images']));
                }
            }
            $params['equipment_infrared_width'] = $input['infraredWidth'];
            $params['equipment_infrared_height'] = $input['infraredHeight'];
            $params['equipment_infrared_screen_width'] = $input['screenWidth'];
            $params['equipment_infrared_screen_height'] = $input['screenHeight'];
            $equipmentClone = EquipmentModel::cloneResult(new EquipmentModel(), $equipmentInfraredDetails);
            $cloneDetails = EquipmentInfraredModel::cloneResult($equipmentInfraredModel, $equipmentInfraredDetails);
            $this->db->begin();
            try {
                $equipmentClone->update(['equipment_remark' => $input['remark']]);
                $cloneDetails->update($params);
                if ($input['clearAreaData'] == 1) {
                    $this->db->query('delete from ' . DB_PREFIX . 'equipment_infrared_area WHERE equipment_infrared_id=?', [$equipmentInfraredDetails['equipment_infrared_id']]);
                }
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->resultModel->setResult('102', $e->getMessage());
                return $this->resultModel->output();
            }

        //    if (!empty($deleteObjects)) {
        //        if (!isset($ossClient)) {
        //            $ossClient = new OSS\OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
        //            $ossClient->setConnectTimeout(5);
        //        }
        //        try {
        //            $ossClient->deleteObjects(self::$DefaultBucket, $deleteObjects);
        //        } catch (Exception $e) {
        //            $this->logger->addMessage('oss delete err:' . $e->getLine() . ' ' . $e->getMessage(),
        //                Phalcon\Logger::CRITICAL);
        //        }
        //    }

            if ($input['upateEquipment'] == 1) {
                $equipmentInfraredAreaModel = new EquipmentInfraredAreaModel();
                $equipmentInfraredArea = $equipmentInfraredAreaModel->getList(['equipment_infrared_id' => $equipmentInfraredDetails['equipment_infrared_id']]);
                if (empty($equipmentInfraredArea['data'])) {
                    $this->resultModel->setResult('-1', 'areas');
                    return $this->resultModel->output();
                }
                $staticBaseUri = $this->url->getStaticBaseUri();
                $content = [
                    'type' => 'infrared',
                    'cmd' => 'updateInfrared',
                    'infraredAreas' => [],
                    'source_update' => $equipmentInfraredDetails['equipment_source_update'],
                    'backgroundImg' => [],
                    'video' => [],
                ];
                if (!empty($cloneDetails->equipment_infrared_video)) {
                    $content['video'][] = $this->url->getStaticBaseUri() . $cloneDetails->equipment_infrared_video;
                }

                if (!empty($cloneDetails->equipment_infrared_images)) {
                    $_images = explode(',', $cloneDetails->equipment_infrared_images);
                    foreach ($_images as $v) {
                        $content['backgroundImg'][] = $this->url->getStaticBaseUri() . $v;
                    }
                }
                foreach ($equipmentInfraredArea['data'] as $v) {
                    if ($v['equipment_infrared_area_type'] == 2) {
                        $_content = explode(',', $v['equipment_infrared_area_content']);
                        $newContent = [];
                        foreach ($_content as $f) {
                            if ($f == '') {
                                continue;
                            }
                            $newContent[] = $staticBaseUri . $f;
                        }
                        if (empty($newContent)) {
                            continue;
                        }
                        $v['equipment_infrared_area_content'] = $newContent;
                    }
                    $content['infraredAreas'][] = [
                        'type' => $v['equipment_infrared_area_type'],
                        'startPoint_x' => $v['equipment_infrared_area_startPoint_x'],
                        'startPoint_y' => $v['equipment_infrared_area_startPoint_y'],
                        'endPoint_x' => $v['equipment_infrared_area_endPoint_x'],
                        'endPoint_y' => $v['equipment_infrared_area_endPoint_y'],
                        'file' => empty($v['equipment_infrared_area_file']) ? '' : $staticBaseUri . $v['equipment_infrared_area_file'],
                        'content' => empty($v['equipment_infrared_area_content']) ? '' : $v['equipment_infrared_area_content'],
                    ];
                }

                $multiRequest = new Roger\Request\MultiRequest();
                $post_data = [
                    'type' => 'tag',
                    'content' => json_encode($content),
                    'to' => $equipmentInfraredDetails['equipment_project_id'] . '|equipment|' . $equipmentInfraredDetails['equipment_code'],
                    'project' => $equipmentInfraredDetails['equipment_project_id'],
                ];
                $multiRequest->addRequest(new Roger\Request\Request($this->view->setting['socketUrl'], Roger\Request\Request::POST, $post_data));
                $multiRequest->execute();
                $sendResult = $multiRequest->getResult();
                $this->logger->addMessage('result:' . $sendResult . ' ' . json_encode($post_data));
                $this->resultModel->setResult('0', $sendResult);
                return $this->resultModel->output();
            }


//            if (CACHING) {
//                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
//                    [
//                        DoctorScheduleModel::class . 'getList' . $input['department_id'],
//                        DoctorScheduleModel::class . 'getList' . $this->user['project_id']
//                    ]);
//            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = [
            'id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
            ],
        ];
        $filter = new FilterModel($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->setTitle($this->translate->_("equipmentInfraredHandle"));
        $equipmentModel = new EquipmentModel();
        $equipmentDetails = $equipmentModel->getDetailsById($input['id']);
        if (!$equipmentDetails) {
            $this->alert($this->resultModel->getMsg('-1'));
        }
        $_equipmentInfraredArea = [];
        if (!empty($equipmentDetails['equipment_infrared_id'])) {
            $equipmentInfraredAreaModel = new EquipmentInfraredAreaModel();
            $equipmentInfraredArea = $equipmentInfraredAreaModel->getList(['equipment_infrared_id' => $equipmentDetails['equipment_infrared_id']]);

            foreach ($equipmentInfraredArea['data'] as $v) {
                $_equipmentInfraredArea[$v['equipment_infrared_area_id']] = $v;
            }

        }
        $this->view->areaListJson = json_encode($_equipmentInfraredArea);

        $this->view->filter = $input;
        $this->view->equipmentDetails = $equipmentDetails;
    }


//    public function ajaxschedulelistAction()
//    {
//        if ($this->request->isPost() && $this->request->isAjax()) {
//            $rules = array(
//                'start' => array(
//                    'filter' => FILTER_SANITIZE_STRING,
//                    'required'
//                ),
//                'end' => array(
//                    'filter' => FILTER_SANITIZE_STRING,
//                    'required'
//                ),
//                'department_id' => [
//                    'filter' => FILTER_VALIDATE_INT,
//                    'options' => [
//                        'min_range' => 1
//                    ],
//                ],
//                'doctor_id' => [
//                    'filter' => FILTER_VALIDATE_INT,
//                    'options' => [
//                        'min_range' => 1
//                    ],
//                    'default' => null
//                ],
//            );
//            $filter = new FilterModel ($rules);
//            if (!$filter->isValid($this->request->getPost())) {
//                $this->resultModel->setResult('101');
//                return $this->resultModel->output();
//            }
//            $input = $filter->getResult();

//            $doctorScheduleModel = new DoctorScheduleModel();
//            $doctorSchedule = $doctorScheduleModel->getListSimple([
//                'department_id' => $input['department_id'],
//                'startTime' => $input['start'] - 28800,
//                'endTime' => $input['end'] - 28800,
//                'project_id' => $this->user['project_id'],
//                'doctor_id' => $input['doctor_id'],
//            ]);
//            $result = ['res' => []];
//            foreach ($doctorSchedule['data'] as $v) {
//                $result['res'][] = [
//                    'title' => $v['doctor_name'],
//                    'start' => date('Y-m-d H:i', $v['doctor_schedule_start']),
//                    'end' => date('Y-m-d H:i', $v['doctor_schedule_end']),
//                    'schedule_id' => $v['doctor_schedule_id'],
//                    'event' => $v['doctor_schedule_event'],
//                    'event_level' => $v['doctor_schedule_event_level'],
//                    'dId' => $v['doctor_id'],
//                    'job' => $v['doctor_job_title'],
//                    'date' => $v['doctor_schedule_date'],
//                ];
//            }
//            $this->resultModel->setResult('0', $result);
//            return $this->resultModel->output();
//        }
//    }

//    public function ajaxdoctorlistAction()
//    {
//        if ($this->request->isPost() && $this->request->isAjax()) {
//            $rules = [
//                'department_id' => [
//                    'filter' => FILTER_VALIDATE_INT,
//                    'options' => [
//                        'min_range' => 1
//                    ],
//                ],
//            ];
//            $filter = new FilterModel ($rules);
//            if (!$filter->isValid($this->request->getPost())) {
//                $this->resultModel->setResult('101');
//                return $this->resultModel->output();
//            }
//            $input = $filter->getResult();

//            $doctorModel = new DoctorModel();
//            $doctor = $doctorModel->clientGetListSimple($input);
//            $result = [];
//            foreach ($doctor['data'] as $v) {
//            }
//            $this->resultModel->setResult('0', $doctor['data']);
//            return $this->resultModel->output();
//        }
//    }

    /**
     * 更新排版表
     * 
     * @return json 
     */
    public function ajaxscheduleupdateAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],
                'startTime' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1400000000
                    ],
                ],
                'endTime' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1400000000
                    ],
                ],
                'date' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],
            ];
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            if ($input['endTime'] <= $input['startTime']) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $doctorScheduleModel = new DoctorScheduleModel();
            $doctorScheduleDetails = $doctorScheduleModel->getDetailsById($input['id']);
            if (!$doctorScheduleDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $doctorModel = new DoctorModel();
            $doctorDetails = $doctorModel->getDetailsById($doctorScheduleDetails->doctor_id);
            if (!$doctorDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($doctorDetails->project_id != $this->user['project_id']) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $doctorScheduleDetails->doctor_schedule_start = $input['startTime'];
            $doctorScheduleDetails->doctor_schedule_end = $input['endTime'];
            $doctorScheduleDetails->doctor_schedule_date = $input['date'];
            $oldDetails = $doctorScheduleDetails->toArray();
            $this->db->begin();
            try {
                $doctorScheduleDetails->update();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->db->commit();
            if (CACHING) {
                $this->cache->clean(
                    CacheBase::CLEANING_MODE_TAG,
                    [DoctorScheduleModel::class . 'getList' . $doctorScheduleDetails->doctor_schedule_department_id]
                );
                $this->cache->delete(CacheBase::makeTag(DoctorScheduleModel::class . 'getDetailsById', $doctorScheduleDetails->doctor_schedule_id));
            }
            $this->logger->addMessage(
                json_encode($doctorScheduleDetails->toArray()) . ' Old:' . json_encode($oldDetails),
                Phalcon\Logger::NOTICE
            );

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxscheduleequipmentupdateAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $equipmentModel = new EquipmentModel();
            $equipment = $equipmentModel->getListSimple(['e_project_id' => $this->user['project_id']]);
            if (!empty($equipment['data'])) {
                $settingModel = new SettingModel();
                $setting = $settingModel->getByKeys(['socketUrl']);
                $content = [
                    'cmd' => 'updateResource'
                ];
                $multiRequest = new Roger\Request\MultiRequest();
                $refreshCode = [];
                foreach ($equipment['data'] as $v) {
                    $refreshCode[] = $v['equipment_code'];
                    $post_data = [
                        'type' => 'tag',
                        'content' => json_encode($content),
                        'to' => $this->user['project_id'] . '|equipment|' . $v['equipment_code'],
                        'project' => $this->user['project_id'],
                    ];
                    $multiRequest->addRequest(new Roger\Request\Request(
                        $setting['socketUrl'],
                        Roger\Request\Request::POST,
                        $post_data
                    ));
                }
                $multiRequest->execute();
                $this->logger->addMessage($multiRequest->getResult() . ' ' . json_encode($refreshCode));
            }

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxscheduledeleteAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],
            ];
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $doctorScheduleModel = new DoctorScheduleModel();
            $doctorScheduleDetails = $doctorScheduleModel->getDetailsById($input['id']);
            if (!$doctorScheduleDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $doctorModel = new DoctorModel();
            $doctorDetails = $doctorModel->getDetailsById($doctorScheduleDetails->doctor_id);
            if (!$doctorDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($doctorDetails->project_id != $this->user['project_id']) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $this->db->begin();
            try {
                $doctorScheduleDetails->delete();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->db->commit();
            if (CACHING) {
                $this->cache->clean(
                    CacheBase::CLEANING_MODE_TAG,
                    [DoctorScheduleModel::class . 'getList' . $doctorScheduleDetails->doctor_schedule_department_id]
                );
                $this->cache->delete(CacheBase::makeTag(
                    DoctorScheduleModel::class . 'getDetailsById',
                    $doctorScheduleDetails->doctor_schedule_id
                ));
            }
            $this->logger->addMessage('Old:' . json_encode($doctorScheduleDetails->toArray()), Phalcon\Logger::NOTICE);

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    /**
     * MarkActionName(设备列表)
     * @return mixed
     */

    public function listAction()
    {
        $rules = [
            'type' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => null
            ],
            'page' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => 1
            ],
            'psize' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => 10
            ],
            'usePage' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 0,
                    'max_range' => 1
                ],
                'default' => 0
            ],

        ];
        $filter = new FilterModel($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->appendTitle($this->translate->_('DoctorList'));
        $doctorModel = new DoctorModel();
        $doctor = $doctorModel->clientGetListSimple(['project_id' => $this->user['project_id']] + $input);

        $departmentModel = new DoctorDepartmentModel();
        $departmentList = $departmentModel->clientGetList($this->user['project_id']);
        $tree = new TreeModel();
        $tree->setTree($departmentList->toArray(), 'department_id', 'department_pid', 'department_name');
        $this->view->departmentList = $tree->getOptions();
        $this->view->doctorList = $doctor['data'];
        $this->view->filter = $input;
    }

    /**
     * MarkActionName(医生添加/编辑)
     * @return mixed
     */

    public function handleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'doctor_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ],
                'doctor_name' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],
                'department_id' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],
                'job_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],
                'doctor_intro' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ],
                'doctor_photo' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ],
                'entry_time' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ],
                'status' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1,
                        'max_range' => 3
                    ],
                ],
            ];
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
            $ossClient->setConnectTimeout(5);
            if ($input['doctor_photo'] != '' && strpos($input['doctor_photo'], '/') == 0) {
                $objectName = ltrim($input['doctor_photo'], '/');
                $input['doctor_photo'] = $objectName;
                try {
                    $ossClient->putObject(
                        self::$DefaultBucket,
                        $objectName,
                        file_get_contents(APP_PATH . 'public/' . $objectName)
                    );
                    $input['doctor_photo_source'] = self::SourceOss;
                    @unlink(APP_PATH . 'public/' . $objectName);
                } catch (Exception $e) {
                    $this->logger->addMessage(
                        'oss upload > doctor_photo: ' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL
                    );
                    $input['doctor_photo_source'] = self::SourceLocale;
                }
            }
            $doctorModel = new DoctorModel();
            $params = [
                'doctor_id' => $input['doctor_id'],
                'doctor_name' => $input['doctor_name'],
                'doctor_relation' => $input['department_id'],
                'doctor_job_id' => $input['job_id'],
                'doctor_intro' => $input['doctor_intro'],
                'doctor_photo' => $input['doctor_photo'],
                'project_id' => $this->user['project_id'],
                'doctor_entry_time' => empty($input['entry_time']) ? null : strtotime($input['entry_time']),
                'doctor_status' => $input['status'],
                'doctor_photo_source' => $input['doctor_photo_source'],
            ];
            if (!empty($input['doctor_id'])) {
                $this->logger->appendTitle('update');
                $doctorDetails = $doctorModel->getDetailsById($input['doctor_id']);
                $oldDetails = $doctorDetails->toArray();
                try {
                    $doctorDetails->update($params);
                } catch (Exception $e) {
                    $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                    if (isset($objectName)) {
                        switch ($params['doctor_photo_source']) {
                            case self::SourceOss:
                                try {
                                    $ossClient->deleteObject(self::$DefaultBucket, $objectName);
                                } catch (Exception $ex) {
                                    $this->logger->addMessage(
                                        'oss delete tmp dcotor photo:' . $ex->getMessage(),
                                        Phalcon\Logger::CRITICAL
                                    );
                                }
                                break;
                            case self::SourceLocale:
                                @unlink(APP_PATH . 'public/' . $objectName);
                                break;
                        }
                    }

                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }
                if (isset($objectName)) {
                    switch ($params['doctor_photo_source']) {
                        case self::SourceOss:
                            try {
                                $ossClient->deleteObject(self::$DefaultBucket, $oldDetails['doctor_photo']);
                            } catch (Exception $ex) {
                                $this->logger->addMessage(
                                    'oss delete old dcotor photo:' . $ex->getMessage(),
                                    Phalcon\Logger::CRITICAL
                                );
                            }
                            break;
                        case self::SourceLocale:
                            @unlink(APP_PATH . 'public/' . $oldDetails['doctor_photo']);
                            break;
                    }
                }
                $this->logger->addMessage(
                    'client_id:' . $this->user['client_id'] . ' update doctor: ' . json_encode(
                        $params,
                        JSON_UNESCAPED_UNICODE
                    ) . ' old details:' . json_encode($oldDetails, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::NOTICE
                );
                if (CACHING) {
                    $this->cache->delete(CacheBase::makeTag(
                        DoctorModel::class . 'clientGetDetailsSimple',
                        $input['doctor_id']
                    ));
                }
            } else {
                $this->logger->appendTitle('create');
                try {
                    $doctorModel->create($params);
                } catch (Exception $e) {
                    if ($e->getCode() == '23505') {
                        $doctorModel->refreshPrimaryId();
                        try {
                            $doctorModel->create($params);
                        } catch (Exception $e) {
                            $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                        }
                    } else {
                        $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                    }
                    if (isset($objectName)) {
                        switch ($input['doctor_photo_source']) {
                            case self::SourceOss:
                                try {
                                    $ossClient->deleteObject(self::$DefaultBucket, $objectName);
                                } catch (Exception $ex) {
                                    $this->logger->addMessage(
                                        'oss delete tmp dcotor photo:' . $ex->getMessage(),
                                        Phalcon\Logger::CRITICAL
                                    );
                                }
                                break;
                            case self::SourceLocale:
                                @unlink(APP_PATH . 'public/' . $objectName);
                                break;

                        }
                    }

                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }
                $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            }

            if (CACHING) {
                $departmentId = explode(',', $params['doctor_relation']);
                $removedTag = [DoctorModel::class . 'getList' . $this->user['project_id']];
                foreach ($departmentId as $v) {
                    $removedTag[] = DoctorModel::class . 'getList' . $v;
                }
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, $removedTag);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = [
            'doctor_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => null
            ],
        ];
        $filter = new FilterModel($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $departmentModel = new DoctorDepartmentModel();
        $departmentList = $departmentModel->clientGetList($this->user['project_id']);
        $departmentList = $departmentList->toArray();
        if (!empty($input['doctor_id'])) {
            $doctorModel = new DoctorModel();
            $doctorDetails = $doctorModel->clientGetDetailsSimple($input['doctor_id']);
            $_departmentArray = explode(',', $doctorDetails['doctor_relation']);
            foreach ($departmentList as $k => $v) {
                if (in_array($v['department_id'], $_departmentArray)) {
                    $departmentList[$k]['selected'] = 'selected="selected"';
                } else {
                    $departmentList[$k]['selected'] = '';
                }
            }
            $this->view->details = $doctorDetails;
        }
        $doctorJobModel = new DoctorJobModel();
        $doctorJobList = $doctorJobModel->clientGetList($this->user['project_id']);
        $this->view->departmentList = $departmentList;
        $this->view->jobList = $doctorJobList->toArray();
        $this->view->doctorStatusArray = $this->doctorStatusArray;
        $this->tag->appendTitle($this->translate->_('DoctorHandle'));
    }

    /**
     * MarkActionName(医生职称)
     * @return mixed
     */

    public function jobAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ],
                'name' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],
            ];
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $params = [
                'doctor_job_title' => $input['name'],
                'project_id' => $this->user['project_id'],
            ];
            $doctorJobModel = new DoctorJobModel();
            if (!empty($input['id'])) {
                $doctorJobDetails = $doctorJobModel->getDetailsByDoctorJobIdSimple($input['id']);
                if (!$doctorJobDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $this->logger->appendTitle('update');

            } else {
                $this->logger->appendTitle('create');
                $doctorJobDetails = [];
            }
            $cloneDetails = $doctorJobModel::cloneResult($doctorJobModel, $doctorJobDetails);
            $this->db->begin();
            try {
                $cloneDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage(
                    $e->getMessage() . ' ' . json_encode($params, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL
                );
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }

            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(
                    DoctorJobModel::class . 'getDetailsByDoctorJobIdSimple',
                    $input['id']
                ));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [DoctorJobModel::class . 'getList']);
            }

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $doctorJobModel = new DoctorJobModel();
        $doctorJobList = $doctorJobModel->clientGetList($this->user['project_id']);
        $jobList = $doctorJobList->toArray();
        $jobListJson = [];
        if (!empty($jobList)) {
            foreach ($jobList as $v) {
                $jobListJson[$v['doctor_job_id']] = $v;
            }
        }
        $this->view->jobList = $jobList;
        $this->view->jobListJson = json_encode($jobListJson);
        $this->tag->appendTitle($this->translate->_('DoctorJob'));
    }

//    public function ajaxdeleteAction()
//    {
//        if ($this->request->isPost() && $this->request->isAjax()) {
//            $rules = [
//                'doctor_id' => [
//                    'filter' => FILTER_VALIDATE_INT,
//                    'options' => [
//                        'min_range' => 1
//                    ],
//                ],
//            ];
//            $filter = new FilterModel ($rules);
//            if (!$filter->isValid($this->request->getPost())) {
//                $this->resultModel->setResult('101');
//                return $this->resultModel->output();
//            }
//            $input = $filter->getResult();
//            $this->logger = new LogModel(LOG_FILE_DIR . self::class . '.log');
//            $this->logger->setTitle('client_id:' . $this->user['client_id'] . ' ' . $this->request->getMethod() . ' ' . $this->dispatcher->getActionName());
//            $doctorModel = new DoctorModel();
//            $doctorDetails = $doctorModel->getDetailsById($input['doctor_id']);
//            if (!$doctorDetails) {
//                $this->resultModel->setResult('-1');
//                return $this->resultModel->output();
//            }
//            if ($doctorDetails->project_id != $this->user['project_id']) {
//                $this->resultModel->setResult('-1');
//                return $this->resultModel->output();
//            }
//            try {
//                $doctorDetails->delete();
//            } catch (Exception $e) {
//                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
//                
//                $this->resultModel->setResult('102');
//                return $this->resultModel->output();
//            }
//            switch ($doctorDetails->doctor_photo_source) {
//                case self::SourceOss:
//                    $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
//                    $ossClient->setConnectTimeout(5);
//                    try {
//                        $ossClient->deleteObject(self::$DefaultBucket, $doctorDetails->doctor_photo);
//                    } catch (Exception $ex) {
//                        $this->logger->addMessage('oss delete tmp dcotor photo:' . $ex->getMessage(),
//                            Phalcon\Logger::CRITICAL);
//                    }
//                    break;
//                case self::SourceLocale:
//                    @unlink(APP_PATH . 'public' . $doctorDetails->doctor_photo);
//                    break;
//            }
//            if (CACHING) {
//                $this->cache->delete(CacheBase::makeTag(DoctorModel::class . 'clientGetDetailsSimple',
//                    $doctorDetails->doctor_id));
//                $departmentId = explode(',', $doctorDetails->doctor_relation);
//                $removedTag = [DoctorModel::class . 'getList' . $this->user['project_id']];
//                foreach ($departmentId as $v) {
//                    $removedTag[] = DoctorModel::class . 'getList' . $v;
//                }
//                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, $removedTag);
//            }
//            $this->logger->addMessage(json_encode($doctorDetails->toArray(), JSON_UNESCAPED_UNICODE),
//                Phalcon\Logger::NOTICE);
//            
//            $this->resultModel->setResult('0');
//            return $this->resultModel->output();
//        }
//    }

    public function infraredareahandleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'equipment_infrared_area_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ),
                'equipment_infrared_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
                'equipment_infrared_area_coords' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'equipment_infrared_area_type' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
                'equipment_infrared_area_file' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'equipment_infrared_area_content' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'borderWidth' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
                'borderHeight' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
            );
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101', $filter->getErrMsg());
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $coords = explode(',', $input['equipment_infrared_area_coords']);
            if (count($coords) !== 4) {
                $this->resultModel->setResult('101', 'coords');
                return $this->resultModel->output();
            }

            $equipmentInfraredModel = new EquipmentInfraredModel();
            $equipmentInfraredDetails = $equipmentInfraredModel->getDetailsById($input['equipment_infrared_id']);
            if (!$equipmentInfraredDetails) {
                $this->resultModel->setResult('-1', 'infrared');
                return $this->resultModel->output();
            }

            switch ($input['equipment_infrared_area_type']) {
                case '1':
                case '2':
                case '4':
                    if (empty($input['equipment_infrared_area_content'])) {
                        $this->resultModel->setResult('101', 'content');
                        return $this->resultModel->output();
                    }
                    break;
            }

            $equipmentInfraredAreaModel = new EquipmentInfraredAreaModel();
            if (!is_null($input['equipment_infrared_area_id'])) {
                $equipmentInfraredAreaDetails = $equipmentInfraredAreaModel->getDetailsByAreaId($input['equipment_infrared_area_id']);
                if (!$equipmentInfraredAreaDetails) {
                    $this->resultModel->setResult('-1', 'infrared area');
                    return $this->resultModel->output();
                }
            } else {
                $equipmentInfraredAreaDetails = [];
            }

            $deleteObjects = $unlinkObjects = [];
            switch ($input['equipment_infrared_area_type']) {
                case '1':
                case '3':
                case '4':
                    if ($equipmentInfraredAreaDetails['equipment_infrared_area_type'] == 2) {
                        $deleteObjects = array_merge(explode(
                            ',',
                            $equipmentInfraredAreaDetails['equipment_infrared_area_content']
                        ), $deleteObjects);
                    }
                    break;
                case '2':
                    $input['equipment_infrared_area_content'] = rtrim($input['equipment_infrared_area_content'], ',');
                    $newObjectsContent = '';
                    $updateObjects = [];
                    $newObjects = explode(',', $input['equipment_infrared_area_content']);
                    if ($equipmentInfraredAreaDetails['equipment_infrared_area_type'] == 2) {
                        $oldObjects = explode(',', $equipmentInfraredAreaDetails['equipment_infrared_area_content']);
                        foreach ($oldObjects as $v) {
                            if ($v == '') {
                                continue;
                            }
                            if (!in_array($v, $newObjects)) {
                                $deleteObjects[] = $v;
                            }
                        }
                        foreach ($newObjects as $v) {
                            if ($v == '') {
                                continue;
                            }
                            if (in_array($v, $oldObjects)) {
                                $newObjectsContent .= $v . ',';
                                continue;
                            }
                            $newObjectsContent .= $v . ',';
                            $updateObjects[] = $v;
                        }
                    } else {
                        foreach ($newObjects as $v) {
                            if ($v == '') {
                                continue;
                            }
                            $newObjectsContent .= $v . ',';
                            $updateObjects[] = $v;
                        }
                    }
                    $input['equipment_infrared_area_content'] = rtrim($newObjectsContent, ',');
                    if (!empty($updateObjects)) {
                        $ossClient = new OSS\OssClient(
                            static::$AccessKeyId,
                            static::$AccessKeySecret,
                            static::$EndPoint
                        );
                        $ossClient->setConnectTimeout(5);
                        try {
                            foreach ($updateObjects as $v) {
                                $ossClient->putObject(
                                    self::$DefaultBucket,
                                    $v,
                                    file_get_contents(APP_PATH . 'public/' . $v)
                                );
                            }
                            foreach ($updateObjects as $v) {
                                $unlinkObjects[] = $v;
                            }
                        } catch (Exception $e) {
                            $this->logger->addMessage(
                                'oss upload err:' . $e->getLine() . ' ' . $e->getMessage(),
                                Phalcon\Logger::CRITICAL
                            );
                        }
                    }
                    break;
            }

            $borderPercent = $equipmentInfraredDetails['equipment_infrared_width'] / $equipmentInfraredDetails['equipment_infrared_height'];
            $screenPercent = $equipmentInfraredDetails['equipment_infrared_screen_width'] / $equipmentInfraredDetails['equipment_infrared_screen_height'];
            $newCoords = [];
            if ( ($borderPercent >= 1 && $screenPercent >= 1) || ($borderPercent <= 1 && $screenPercent <= 1)) {
                $newCoords[] = intval($coords[0] / $input['borderWidth'] * $equipmentInfraredDetails['equipment_infrared_screen_width']);
                $newCoords[] = intval($coords[1] / $input['borderHeight'] * $equipmentInfraredDetails['equipment_infrared_screen_height']);
                $newCoords[] = intval($coords[2] / $input['borderWidth'] * $equipmentInfraredDetails['equipment_infrared_screen_width']);
                $newCoords[] = intval($coords[3] / $input['borderHeight'] * $equipmentInfraredDetails['equipment_infrared_screen_height']);
            } else if ($borderPercent < 1 && $screenPercent > 1) {
                $newCoords[] = intval( ($input['borderHeight'] - $coords[3]) / $input['borderHeight'] * $equipmentInfraredDetails['equipment_infrared_screen_width']);
                $newCoords[] = intval($coords[0] / $input['borderWidth'] * $equipmentInfraredDetails['equipment_infrared_screen_height']);
                $newCoords[] = intval( ($input['borderHeight'] - $coords[1]) / $input['borderHeight'] * $equipmentInfraredDetails['equipment_infrared_screen_width']);
                $newCoords[] = intval($coords[2] / $input['borderWidth'] * $equipmentInfraredDetails['equipment_infrared_screen_height']);
            } else if ($borderPercent > 1 && $screenPercent < 1) {
                $newCoords[] = intval($coords[1] / $input['borderHeight'] * $equipmentInfraredDetails['equipment_infrared_screen_width']);
                $newCoords[] = intval(($input['borderWidth'] - $coords[2]) / $input['borderWidth'] * $equipmentInfraredDetails['equipment_infrared_screen_height']);
                $newCoords[] = intval($coords[3] / $input['borderHeight'] * $equipmentInfraredDetails['equipment_infrared_screen_width']);
                $newCoords[] = intval(($input['borderWidth'] - $coords[0]) / $input['borderWidth'] * $equipmentInfraredDetails['equipment_infrared_screen_height']);
            }

            $equipmentInfraredAreaDetails['equipment_infrared_area_startPoint_x'] = $newCoords[0];
            $equipmentInfraredAreaDetails['equipment_infrared_area_startPoint_y'] = $newCoords[1];
            $equipmentInfraredAreaDetails['equipment_infrared_area_endPoint_x'] = $newCoords[2];
            $equipmentInfraredAreaDetails['equipment_infrared_area_endPoint_y'] = $newCoords[3];
            if (!empty($input['equipment_infrared_area_file']) && strpos(
                $input['equipment_infrared_area_file'],
                '/'
            ) === 0) {
                if (!isset($ossClient)) {
                    $ossClient = new OSS\OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);
                }
                $objectName = ltrim($input['equipment_infrared_area_file'], '/');
                try {
                    $ossClient->putObject(
                        self::$DefaultBucket,
                        $objectName,
                        file_get_contents(APP_PATH . 'public/' . $objectName)
                    );
                    $input['equipment_infrared_area_file'] = $objectName;
                    $input['equipment_infrared_area_file_source'] = 'oss';
                    $unlinkObjects[] = $objectName;
                } catch (Exception $e) {
                    $this->logger->addMessage(
                        'oss upload err:' . $e->getLine() . ' ' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL
                    );
                    $input['equipment_infrared_area_file_source'] = 'locale';
                }
                if (!empty($equipmentInfraredAreaDetails['equipment_infrared_area_file'])) {
                    $deleteObjects[] = $equipmentInfraredAreaDetails['equipment_infrared_area_file'];
                }
            } elseif (empty($input['equipment_infrared_area_file']) && !empty($equipmentInfraredAreaDetails['equipment_infrared_area_file'])) {
                if ($equipmentInfraredAreaDetails['equipment_infrared_area_file_source'] == 'oss') {
                    $deleteObjects[] = $equipmentInfraredAreaDetails['equipment_infrared_area_file'];
                } else {
                    $unlinkObjects[] = $equipmentInfraredAreaDetails['equipment_infrared_area_file'];
                }
                $input['equipment_infrared_area_file_source'] = null;
            }

            $cloneDetails = EquipmentInfraredAreaModel::cloneResult(
                $equipmentInfraredAreaModel,
                $equipmentInfraredAreaDetails
            );
            $infraredCloneDetails = EquipmentModel::cloneResult(new EquipmentModel(), $equipmentInfraredDetails);
            $this->db->begin();
            try {
                $infraredCloneDetails->update(['equipment_source_update' => time()]);
                $cloneDetails->save($input);
                $this->db->commit();
            } catch (Exception $e) {
                $this->resultModel->setResult('102', $e->getMessage());
                return $this->resultModel->output();
            }

            if (!empty($deleteObjects)) {
                if (!isset($ossClient)) {
                    $ossClient = new OSS\OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);
                }
                try {
                    $ossClient->deleteObjects(self::$DefaultBucket, $deleteObjects);
                } catch (Exception $e) {
                    $this->logger->addMessage(
                        'oss delete object err:' . $e->getLine() . ' ' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL
                    );
                }
            }

            if (!empty($unlinkObjects)) {
                foreach ($unlinkObjects as $v) {
                    @unlink(APP_PATH . 'public/' . $v);
                }
            }
            $result = $cloneDetails->toArray();
            unset($result['equipment_infrared_area_endPoint_x'], $result['equipment_infrared_area_endPoint_y'], $result['equipment_infrared_area_startPoint_x'], $result['equipment_infrared_area_startPoint_y']);
            $this->resultModel->setResult('0', $result);
            return $this->resultModel->output();
        }
        $rules = [
            'id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
            ],
        ];
        $filter = new FilterModel($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->setTitle($this->translate->_("equipmentInfraredAreaHandle"));
        $equipmentModel = new EquipmentModel();
        $equipmentDetails = $equipmentModel->getDetailsById($input['id']);
        if (!$equipmentDetails || $equipmentDetails['equipment_project_id'] != $this->user['project_id']) {
            $this->alert($this->resultModel->getMsg('-1'));
        }
        $_equipmentInfraredArea = [];
        if (!empty($equipmentDetails['equipment_infrared_id'])) {
            $equipmentInfraredAreaModel = new EquipmentInfraredAreaModel();
            $equipmentInfraredArea = $equipmentInfraredAreaModel->getList(['equipment_infrared_id' => $equipmentDetails['equipment_infrared_id']]);
            foreach ($equipmentInfraredArea['data'] as $v) {
                $_equipmentInfraredArea[$v['equipment_infrared_area_id']] = $v;
            }
        }
        $mapModel = new MapModel();
        $map = $mapModel->getListSimple(['project_id' => $equipmentDetails['equipment_project_id']]);
        $mapList = [];
        foreach ($map['data'] as $v) {
            $mapList[] = ['map_id' => $v['map_id'], 'map_name' => $v['map_name']];
        }
        $this->view->mapListJson = json_encode($mapList);
        $this->view->areaListJson = json_encode($_equipmentInfraredArea);
        $this->view->filter = $input;
        $this->view->equipmentDetails = $equipmentDetails;
    }

    public function ajaxinfraredarearemoveAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
            );
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $equipmentInfraredAreaModel = new EquipmentInfraredAreaModel();
            $equipmentInfraredAreaDetails = $equipmentInfraredAreaModel->getDetailsByAreaId($input['id']);
            if (!$equipmentInfraredAreaDetails) {
                $this->resultModel->setResult('-1', 'infrared area');
                return $this->resultModel->output();
            }
            if ($equipmentInfraredAreaDetails['equipment_project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('-1', 'no access');
                return $this->resultModel->output();
            }

            $cloneDetails = EquipmentInfraredAreaModel::cloneResult(
                $equipmentInfraredAreaModel,
                $equipmentInfraredAreaDetails
            );
            $this->db->begin();
            try {
                $cloneDetails->delete();
                $this->db->commit();
            } catch (Exception $e) {
                $this->resultModel->setResult('102', $e->getMessage());
                return $this->resultModel->output();
            }
            switch ($equipmentInfraredAreaDetails['equipment_infrared_area_type']) {
                case 1:
                case 3:
                    if (!empty($equipmentInfraredAreaDetails['equipment_infrared_area_file'])) {
                        $ossClient = new OSS\OssClient(
                            static::$AccessKeyId,
                            static::$AccessKeySecret,
                            static::$EndPoint
                        );
                        $ossClient->setConnectTimeout(5);
                        $objectName = ltrim($equipmentInfraredAreaDetails['equipment_infrared_area_file'], '/');
                        try {
                            $ossClient->deleteObject(self::$DefaultBucket, $objectName);
                        } catch (Exception $e) {
                            $this->logger->addMessage(
                                'equipment_infrared_area_file deleteObject err:' . $e->getMessage(),
                                Phalcon\Logger::CRITICAL
                            );
                        }
                    }
                    break;
                case 2:
                    $ossClient = new OSS\OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);
                    if (!empty($equipmentInfraredAreaDetails['equipment_infrared_area_file'])) {
                        $objectName = ltrim($equipmentInfraredAreaDetails['equipment_infrared_area_file'], '/');
                        try {
                            $ossClient->deleteObject(self::$DefaultBucket, $objectName);
                        } catch (Exception $e) {
                            $this->logger->addMessage(
                                'equipment_infrared_area_file deleteObject err:' . $e->getMessage(),
                                Phalcon\Logger::CRITICAL
                            );
                        }
                    }
                    if (!empty($equipmentInfraredAreaDetails['equipment_infrared_area_content'])) {
                        $files = explode(',', $equipmentInfraredAreaDetails['equipment_infrared_area_content']);
                        try {
                            $ossClient->deleteObjects(self::$DefaultBucket, $files);
                        } catch (Exception $e) {
                            $this->logger->addMessage(
                                'equipment_infrared_area_file deleteObjects err:' . $e->getMessage(),
                                Phalcon\Logger::CRITICAL
                            );
                        }
                    }
                    break;
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxinfraredareaupdateAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                // equipment_infrared_id
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
            );
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $equipmentInfraredModel = new EquipmentInfraredModel();
            $equipmentInfraredDetails = $equipmentInfraredModel->getDetailsById($input['id']);
            if (!$equipmentInfraredDetails) {
                $this->resultModel->setResult('-1', 'infrared');
                return $this->resultModel->output();
            }
            if ($equipmentInfraredDetails['equipment_project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('-1', 'no access');
                return $this->resultModel->output();
            }
            $equipmentInfraredAreaModel = new EquipmentInfraredAreaModel();
            $equipmentInfraredArea = $equipmentInfraredAreaModel->getList(['equipment_infrared_id' => $equipmentInfraredDetails['equipment_infrared_id']]);
            if (empty($equipmentInfraredArea['data'])) {
                $this->resultModel->setResult('-1', 'areas');
                return $this->resultModel->output();
            }
            $staticBaseUri = $this->url->getStaticBaseUri();
            $content = [
                'type' => 'infrared',
                'cmd' => 'updateInfrared',
                'infraredAreas' => [],
                'source_update' => $equipmentInfraredDetails['equipment_source_update'],
                'backgroundImg' => [],
                'video' => [],
            ];
            foreach ($equipmentInfraredArea['data'] as $v) {
                if ($v['equipment_infrared_area_type'] == 2) {
                    $_content = explode(',', $v['equipment_infrared_area_content']);
                    $newContent = [];
                    foreach ($_content as $f) {
                        if ($f == '') {
                            continue;
                        }
                        $newContent[] = $staticBaseUri . $f;
                    }
                    if (empty($newContent)) {
                        continue;
                    }
                    $v['equipment_infrared_area_content'] = $newContent;
                }
                $content['infraredAreas'][] = [
                    'type' => $v['equipment_infrared_area_type'],
                    'startPoint_x' => $v['equipment_infrared_area_startPoint_x'],
                    'startPoint_y' => $v['equipment_infrared_area_startPoint_y'],
                    'endPoint_x' => $v['equipment_infrared_area_endPoint_x'],
                    'endPoint_y' => $v['equipment_infrared_area_endPoint_y'],
                    'file' => empty($v['equipment_infrared_area_file']) ? '' : $staticBaseUri . $v['equipment_infrared_area_file'],
                    'content' => empty($v['equipment_infrared_area_content']) ? '' : $v['equipment_infrared_area_content'],
                ];
            }

            $multiRequest = new Roger\Request\MultiRequest();
            $post_data = [
                'type' => 'tag',
                'content' => json_encode($content),
                'to' => $equipmentInfraredDetails['equipment_project_id'] . '|equipment|' . $equipmentInfraredDetails['equipment_code'],
                'project' => $equipmentInfraredDetails['equipment_project_id'],
            ];
            $multiRequest->addRequest(new Roger\Request\Request(
                $this->view->setting['socketUrl'],
                Roger\Request\Request::POST,
                $post_data
            ));
            $multiRequest->execute();
            $sendResult = $multiRequest->getResult();
            $this->logger->addMessage('result:' . $sendResult . ' ' . json_encode($post_data));
            $this->resultModel->setResult('0', $sendResult);
            return $this->resultModel->output();
        }
    }

    public function ajaxequipmentupdateAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                // equipment_id
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
            );
            $filter = new FilterModel($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $equipmentModel = new EquipmentModel();
            $equipmentDetails = $equipmentModel->getDetailsById($input['id']);
            if (!$equipmentDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $content = [
                'cmd' => 'updateResource'
            ];
            $multiRequest = new Roger\Request\MultiRequest();
            $post_data = [
                'type' => 'tag',
                'content' => json_encode($content),
                'to' => $this->user['project_id'] . '|equipment|' . $equipmentDetails['equipment_code'],
                'project' => $this->user['project_id'],
            ];
            $multiRequest->addRequest(new Roger\Request\Request(
                $setting['socketUrl'],
                Roger\Request\Request::POST,
                $post_data
            ));
            $multiRequest->execute();
            $sendResult = $multiRequest->getResult();
            $this->logger->addMessage('result:' . $sendResult . ' ' . json_encode($post_data));
            $this->resultModel->setResult('0', $sendResult);
            return $this->resultModel->output();
        }
    }

}
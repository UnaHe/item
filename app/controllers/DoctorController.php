<?php
use OSS\OssClient;

/**
 * MarkControllerName(医生管理)
 *
 */
class DoctorController extends ControllerBase
{
    private $doctorStatusArray = ['1' => '正常', '2' => '休假', '3' => '进修', '0' => '离职'];

    /**
     * MarkActionName(医生排班|ajaxscheduleupdate|ajaxscheduledelete|ajaxscheduleequipmentupdate)
     * @return mixed
     */
    public function scheduleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'department_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
                'doctor_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
                'times' => array(
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
            $_date = explode(' - ', $input['times']);
            if (isset($_date[0]) && isset($_date[1])) {
                $timeStart = strtotime(substr($_date[0], 0, 10));
                $timeEnd = strtotime(substr($_date[1], 0, 10)) + 86400;
                if (!$timeStart || !$timeEnd) {
                    $this->resultModel->setResult('101');
                    return $this->resultModel->output();
                }
            } else {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }

            $doctorScheduleModel = new DoctorScheduleModel();
            $doctorSchedule = $doctorScheduleModel->getListSimple([
                'startTime' => $timeStart,
                'endTime' => $timeEnd,
                'orderBy' => 'ds.doctor_schedule_start ASC',
                'doctor_id' => $input['doctor_id']
            ]);
            $doctorScheduleList = $doctorSchedule['data'];
            $doctorModel = new DoctorModel();
            $doctorDetails = $doctorModel->clientGetDetailsSimple($input['doctor_id']);
            if (!$doctorDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }

            $startDate = explode(' ', $_date[0]);
            $endDate = explode(' ', $_date[1]);
            $startTime = strtotime($startDate[0]);
            $endTime = strtotime($endDate[0]);
            $days = ($endTime - $startTime) / 86400;
            $params = [];
            foreach (range(0, $days) as $v) {
                $_startTime = $startTime + $v * 86400;
                $_startDate = date('Y/m/d', $_startTime);
                $_scheduleStart = strtotime($_startDate . ' ' . $startDate[1]);
                $_scheduleEnd = strtotime($_startDate . ' ' . $endDate[1]);
                if ($_scheduleEnd > $_scheduleStart) {
                    foreach ($doctorScheduleList as $d) {
                        if ($d['doctor_schedule_date'] != $_startDate) {
                            continue;
                        }
                        $dateProcessed = 1;
                        echo $d['doctor_schedule_id'] . "\n";
                        echo '$_scheduleStart:' . $_scheduleStart . ' ' . $d['doctor_schedule_start'] . "\n";
                        echo '$_scheduleEnd:' . $_scheduleEnd . ' ' . $d['doctor_schedule_end'] . "\n";
                        if ($_scheduleStart >= $d['doctor_schedule_start'] && $_scheduleEnd <= $d['doctor_schedule_end']) {
                            break;
                        } elseif ($_scheduleEnd < $d['doctor_schedule_start'] || $_scheduleStart > $d['doctor_schedule_end']) {
                            echo "create:" . $d['doctor_schedule_id'] . "\n";
                            if (!isset($params[$_scheduleStart . $_scheduleEnd])) {
                                $params[$_scheduleStart . $_scheduleEnd] = [
                                    'doctor_id' => $d['doctor_id'],
                                    'doctor_schedule_start' => $_scheduleStart,
                                    'doctor_schedule_end' => $_scheduleEnd,
                                    'doctor_schedule_date' => $_startDate,
                                    'doctor_schedule_department_id' => $d['doctor_schedule_department_id'],
                                ];
                            }
                        } elseif ($_scheduleStart >= $d['doctor_schedule_start'] && $_scheduleEnd <= $d['doctor_schedule_end']) {
                            unset($params[$_scheduleStart . $_scheduleEnd]);
                            continue;
                        } elseif ($_scheduleStart <= $d['doctor_schedule_start']) {
                            $_param = [
                                'doctor_id' => $d['doctor_id'],
                                'doctor_schedule_date' => $d['doctor_schedule_date'],
                                'doctor_schedule_department_id' => $d['doctor_schedule_department_id'],
                                'doctor_schedule_id' => $d['doctor_schedule_id'],
                                'doctor_schedule_start' => $_scheduleStart,
                                'doctor_schedule_end' => $d['doctor_schedule_end'],
                            ];
                            if ($_scheduleEnd > $d['doctor_schedule_end']) {
                                $_param['doctor_schedule_end'] = $_scheduleEnd;
                            }
                            if (isset($params[$_scheduleStart . $_scheduleEnd]['doctor_id'])) {
                                unset($params[$_scheduleStart . $_scheduleEnd]);
                            }
                            $params[$_scheduleStart . $_scheduleEnd][] = $_param;
                        } elseif ($_scheduleEnd >= $d['doctor_schedule_end']) {
                            $_param = [
                                'doctor_id' => $d['doctor_id'],
                                'doctor_schedule_date' => $d['doctor_schedule_date'],
                                'doctor_schedule_department_id' => $d['doctor_schedule_department_id'],
                                'doctor_schedule_id' => $d['doctor_schedule_id'],
                                'doctor_schedule_start' => $d['doctor_schedule_start'],
                                'doctor_schedule_end' => $_scheduleEnd,
                            ];
                            if ($_scheduleStart < $d['doctor_schedule_start']) {
                                $_param['doctor_schedule_start'] = $_scheduleStart;
                            }
                            if (isset($params[$_scheduleStart . $_scheduleEnd]['doctor_id'])) {
                                unset($params[$_scheduleStart . $_scheduleEnd]);
                            }
                            $params[$_scheduleStart . $_scheduleEnd][] = $_param;
                        }
                    }
                    if (!isset($dateProcessed)) {
                        $params = [
                            'doctor_id' => $doctorDetails['doctor_id'],
                            'doctor_schedule_start' => $_scheduleStart,
                            'doctor_schedule_end' => $_scheduleEnd,
                            'doctor_schedule_date' => $_startDate,
                            'doctor_schedule_department_id' => $input['department_id'],
                        ];
                        $this->db->begin();
                        try {
                            $doctorScheduleModel->create($params);
                            $this->logger->addMessage('create new:' . json_encode($doctorScheduleModel->toArray()) . ' from:' . json_encode($params),
                                Phalcon\Logger::NOTICE);
                            unset($params, $dateProcessed, $doctorScheduleModel->doctor_schedule_id);
                        } catch (Exception $e) {
                            $this->db->rollback();
                            $this->logger->addMessage('create new:' . $e->getMessage(), Phalcon\Logger::CRITICAL);

                            $this->resultModel->setResult('102');
                            return $this->resultModel->output();
                        }
                        $this->db->commit();
                    }
                }
            }
            if (!empty($params)) {
                $this->db->begin();
                $params = array_values($params)[0];
                if (isset($params['doctor_id'])) {
                    $_details = $doctorScheduleModel::cloneResult($doctorScheduleModel, $params);
                    try {
                        $_details->update();
                        $this->logger->addMessage('create:' . json_encode($params), Phalcon\Logger::NOTICE);
                    } catch (Exception $e1) {
                        $this->db->rollback();
                        $this->logger->addMessage('create:' . $e1->getMessage(), Phalcon\Logger::CRITICAL);

                        $this->resultModel->setResult('102');
                        return $this->resultModel->output();
                    }
                } elseif (isset($params[0])) {
                    $updateSchedule = $params[0];
                    $paramsCount = count($params);
                    if ($paramsCount > 1) {
                        $updateSchedule['doctor_schedule_end'] = $params[$paramsCount - 1]['doctor_schedule_end'];
                        unset($params[0]);
                        foreach ($params as $v) {
                            $_details = $doctorScheduleModel::cloneResult($doctorScheduleModel, $v);
                            try {
                                $_details->delete();
                                $this->logger->addMessage('delete:' . json_encode($v), Phalcon\Logger::NOTICE);
                            } catch (Exception $e1) {
                                $this->db->rollback();
                                $this->logger->addMessage('delete:' . $e1->getMessage(), Phalcon\Logger::CRITICAL);

                                $this->resultModel->setResult('102');
                                return $this->resultModel->output();
                            }
                        }
                    }
                    $_details = $doctorScheduleModel::cloneResult($doctorScheduleModel, $updateSchedule);
                    try {
                        $_details->update();
                        $this->logger->addMessage('update:' . json_encode($updateSchedule), Phalcon\Logger::NOTICE);
                    } catch (Exception $e1) {
                        $this->db->rollback();
                        $this->logger->addMessage('update:' . $e1->getMessage(), Phalcon\Logger::CRITICAL);

                        $this->resultModel->setResult('102');
                        return $this->resultModel->output();
                    }
                }
                $this->db->commit();
            }
            $this->logger->addMessage('finish:' . json_encode($input));


            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [
                        DoctorScheduleModel::class . 'getList' . $input['department_id'],
                        DoctorScheduleModel::class . 'getList' . $this->user['project_id']
                    ]);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = [
            'department_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => null
            ],
            'date' => [
                'filter' => FILTER_SANITIZE_STRING,
                'default' => ''
            ],
        ];
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->appendTitle($this->translate->_("DoctorSchedule"));
        $departmentModel = new DoctorDepartmentModel();
        $departmentList = $departmentModel->getListSimple($this->user['project_id']);
        $tree = new TreeModel ();
        $tree->setTree($departmentList->toArray(), 'department_id', 'department_pid', 'department_name');
        $this->view->departmentList = $tree->getOptions();
        $timeStart = mktime(0, 0, 0);
        $timeEnd = $timeStart + 86400 * 7 - 1;
        $sevenDaysAfter = mktime(0, 0, 0) + 86400 * 7 - 1;
        $defaultDate = date('Y/m/d') . ' - ' . date('Y/m/d', $sevenDaysAfter);
        if (!empty($input['date']) && !empty($input['department_id'])) {
            $_date = explode(' - ', $input['date']);
            if (isset($_date[0]) && isset($_date[1])) {
                $_timeStart = strtotime($_date[0]);
                $_timeEnd = strtotime($_date[1]) + 86400 - 1;
                if ($_timeStart && $_timeEnd && $_timeEnd > $_timeStart) {
                    $timeStart = $_timeStart;
                    $timeEnd = $_timeEnd;
                    $defaultDate = $input['date'];
                }
            }
            $doctorScheduleModel = new DoctorScheduleModel();
            $doctorSchedule = $doctorScheduleModel->getListSimple([
                'startTime' => $timeStart,
                'endTime' => $timeEnd,
                'orderBy' => 'ds.doctor_schedule_start ASC',
                'department_id' => $input['department_id']
            ]);
            $doctorScheduleList = $doctorSchedule['data'];
            $days = floor(($timeEnd - $timeStart) / 86400);
            $times = [];
            foreach (range(0, $days) as $d) {
                $_dayStart = $timeStart + $d * 86400;
                $_dayEnd = $_dayStart + 86400 - 1;
                $_dateKey = date('Y/m/d', $_dayStart);
                if (!isset($times[$_dateKey])) {
                    $times[$_dateKey] = [];
                }

                foreach ($doctorScheduleList as $k => $v) {
                    if ($v['doctor_schedule_start'] >= $_dayStart && $v['doctor_schedule_end'] <= $_dayEnd) {
                        if (!isset($times[$_dateKey][$v['doctor_id']])) {
                            $times[$_dateKey][$v['doctor_id']] = ['time' => []];
                        }
                        $times[$_dateKey][$v['doctor_id']]['name'] = $v['doctor_name'];
                        $times[$_dateKey][$v['doctor_id']]['time'][] = [
                            'times' => date('G:i', $v['doctor_schedule_start']) . ' - ' . date('G:i',
                                    $v['doctor_schedule_end']),
                            'id' => $v['doctor_schedule_id']
                        ];
                        unset($doctorScheduleList[$k]);
                    }
                }
            }
            $this->view->times = $times;
            $doctorModel = new DoctorModel();
            $doctor = $doctorModel->clientGetListSimple(['department_id' => $input['department_id']]);
            $this->view->doctorList = $doctor['data'];
        }

        $this->view->filter = $input;
        $this->view->defaultDate = $defaultDate;
    }

    public function scheduleviewAction()
    {
        $this->tag->appendTitle($this->translate->_("DoctorSchedule"));
        $rules = array(
            'doctor_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
            ],
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'), '/doctor/list');
        }
        $input = $filter->getResult();
        $doctorModel = new DoctorModel();
        $doctorDetails = $doctorModel->clientGetDetailsSimple($input['doctor_id']);
        if (!$doctorDetails) {
            $this->alert($this->resultModel->getMsg('-1'), '/doctor/list');
        }

        if ($doctorDetails['project_id'] != $this->user['project_id']) {
            $this->alert($this->resultModel->getMsg('-1'), '/doctor/list');
        }
        $_doctor_relation = explode(',', $doctorDetails['doctor_relation']);
        $_department = explode(',', $doctorDetails['department']);
        $departmentList = array_combine($_doctor_relation, $_department);
        $this->view->doctorDetails = $doctorDetails;
        $this->view->departmentList = $departmentList;
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
//
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
//
//            $doctorModel = new DoctorModel();
//            $doctor = $doctorModel->clientGetListSimple($input);
//            $result = [];
//            foreach ($doctor['data'] as $v) {
//            }
//            $this->resultModel->setResult('0', $doctor['data']);
//            return $this->resultModel->output();
//        }
//    }
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
            $filter = new FilterModel ($rules);
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
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [DoctorScheduleModel::class . 'getList' . $doctorScheduleDetails->doctor_schedule_department_id]);
                $this->cache->delete(CacheBase::makeTag(DoctorScheduleModel::class . 'getDetailsById',
                    $doctorScheduleDetails->doctor_schedule_id));
            }
            $this->logger->addMessage(json_encode($doctorScheduleDetails->toArray()) . ' Old:' . json_encode($oldDetails),
                Phalcon\Logger::NOTICE);

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxscheduleequipmentupdateAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $equipmentModel = new EquipmentModel();
            $equipment = $equipmentModel->getListSimple(['e_project_id'=>$this->user['project_id']]);
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
                    $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'],
                        Roger\Request\Request::POST,
                        $post_data));
                }
                $multiRequest->execute();
                $this->logger->addMessage($multiRequest->getResult().' '.json_encode($refreshCode));
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
            $filter = new FilterModel ($rules);
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
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [DoctorScheduleModel::class . 'getList' . $doctorScheduleDetails->doctor_schedule_department_id]);
                $this->cache->delete(CacheBase::makeTag(DoctorScheduleModel::class . 'getDetailsById',
                    $doctorScheduleDetails->doctor_schedule_id));
            }
            $this->logger->addMessage('Old:' . json_encode($doctorScheduleDetails->toArray()), Phalcon\Logger::NOTICE);

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    /**
     * MarkActionName(医生列表)
     * @return mixed
     */

    public function listAction()
    {
        $rules = [
            'department_id' => [
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
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->appendTitle($this->translate->_('DoctorList'));
        $doctorModel = new DoctorModel();
        $doctor = $doctorModel->clientGetListSimple(['project_id' => $this->user['project_id']] + $input);

        $departmentModel = new DoctorDepartmentModel();
        $departmentList = $departmentModel->clientGetList($this->user['project_id']);
        $tree = new TreeModel ();
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
            $filter = new FilterModel ($rules);
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
                    $ossClient->putObject(self::$DefaultBucket, $objectName,
                        file_get_contents(APP_PATH . 'public/' . $objectName));
                    $input['doctor_photo_source'] = self::SourceOss;
                    @unlink(APP_PATH . 'public/' . $objectName);
                } catch (Exception $e) {
                    $this->logger->addMessage('oss upload > doctor_photo: ' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL);
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
                                    $this->logger->addMessage('oss delete tmp dcotor photo:' . $ex->getMessage(),
                                        Phalcon\Logger::CRITICAL);
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
                                $this->logger->addMessage('oss delete old dcotor photo:' . $ex->getMessage(),
                                    Phalcon\Logger::CRITICAL);
                            }
                            break;
                        case self::SourceLocale:
                            @unlink(APP_PATH . 'public/' . $oldDetails['doctor_photo']);
                            break;
                    }
                }
                $this->logger->addMessage('client_id:' . $this->user['client_id'] . ' update doctor: ' . json_encode($params,
                        JSON_UNESCAPED_UNICODE) . ' old details:' . json_encode($oldDetails, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::NOTICE);
                if (CACHING) {
                    $this->cache->delete(CacheBase::makeTag(DoctorModel::class . 'clientGetDetailsSimple',
                        $input['doctor_id']));
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
                                    $this->logger->addMessage('oss delete tmp dcotor photo:' . $ex->getMessage(),
                                        Phalcon\Logger::CRITICAL);
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
        $filter = new FilterModel ($rules);
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
            $filter = new FilterModel ($rules);
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
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($params, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }

            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(DoctorJobModel::class . 'getDetailsByDoctorJobIdSimple',
                    $input['id']));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [DoctorJobModel::class . 'getList']);
            }

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $doctorJobModel = new DoctorJobModel();
        $doctorJobList = $doctorJobModel->clientGetList($this->user['project_id']);
        $jobList = $doctorJobList->toArray();
        $jobListJson = [];
        if (!empty ($jobList)) {
            foreach ($jobList as $v) {
                $jobListJson [$v ['doctor_job_id']] = $v;
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

}
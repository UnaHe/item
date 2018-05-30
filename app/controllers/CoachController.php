<?php


class CoachController extends ControllerBase
{
    public function coachlistAction()
    {
        $rules = [
            'coach_id' => [
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
        $input['project_id'] = $this->user['project_id'];
        $coachModel = new CoachModel();
        $coachList = $coachModel->getList($input);
        $coachList = (isset($coachList) && !empty($coachList)) ? $coachList->toArray() : [];
        $this->view->coachList = $coachList;
        $this->view->filter = $input;
        $this->tag->appendTitle('教练信息');
    }

    public function coachhandleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'coach_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ],
                'coach_name' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],
                'coach_age' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 18,
                        'max_range' => 80
                    ],
                    'required'
                ],
                'coach_seniority' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 60
                    ],
                    'required'
                ],
                'coach_gender' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 2
                    ],
                    'required'

                ],
                'coach_intro' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'default' => null
                ],
                'coach_photo' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => null
                ],
            ];
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $coachModel = new CoachModel();
            $params = [
                'coach_id' => !empty($input['coach_id']) ? $input['coach_id'] : null,
                'coach_name' => $input['coach_name'],
                'coach_photo' => $input['coach_photo'],
                'coach_age' => $input['coach_age'],
                'coach_intro' => $input['coach_intro'],
                'project_id' => $this->user['project_id'],
                'coach_seniority' => $input['coach_seniority'],
                'coach_gender' => $input['coach_gender'],
            ];
            if (!empty($input['coach_id'])) {
                $coachDetails = $coachModel->getList($input);
                $oldCoachDetails = (isset($coachDetails) && !empty($coachDetails)) ? $coachDetails->toArray()[0] : [];
                $this->logger->appendTitle('update');
                $this->db->begin();
                try {
                    $coachDetails->update($params);
                    if (!empty($oldCoachDetails) && $oldCoachDetails['coach_photo'] !== $input['coach_photo']) {
                        @unlink(APP_PATH . 'public' . $oldCoachDetails['coach_photo']);
                    }
                } catch (Exception $e) {
                    $this->db->rollback();
                    $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }
                $this->db->commit();
                $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE) . ' old coach details:' . json_encode($oldCoachDetails, JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);

            } else {
                $this->logger->appendTitle('create');
                $this->db->begin();
                try {
                    $coachModel->create($params);
                } catch (Exception $exception) {
                    $this->db->rollback();
                    $this->logger->addMessage($exception->getMessage(), Phalcon\Logger::CRITICAL);
                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }
                $this->db->commit();
                $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = [
            'coach_id' => [
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
        if (!empty($input['coach_id'])) {
            $input['project_id'] = $this->user['project_id'];
            $coachModel = new CoachModel();
            $coachDetails = $coachModel->getList($input);
            $coachDetails = (isset($coachDetails) && !empty($coachDetails)) ? $coachDetails->toArray()[0] : [];
            $this->view->coachDetails = $coachDetails;
        }
        $this->tag->appendTitle('添加/编辑教练信息');
    }

    public function coachdeleteAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'coach_id' => [
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
            $input['project_id'] = $this->user['project_id'];
            $coachModel = new CoachModel();
            $coachModelPending = new CoachPendingModel();
            $coachDetails = $coachModel->getList($input);
            $pendingDetails = $coachModelPending->getList($input);
            if (!$coachDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $this->db->begin();
            $this->logger->appendTitle('delete');
            try {
                $coachDetails->delete();
                @unlink(APP_PATH . 'public' . $coachDetails->toArray()[0]['coach_photo']);
            } catch (Exception $eCoach) {
                $this->db->rollback();
                $this->logger->addMessage($eCoach->getMessage(), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->logger->addMessage(json_encode($coachDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            if (!empty($pendingDetails)) {
                $this->logger->appendTitle('delete');
                try {
//                    $pendingDetails->delete();
                    $coachModelPending->deletePending($input);
                } catch (Exception $ePending) {
                    $this->db->rollback();
                    $this->logger->addMessage($ePending->getMessage(), Phalcon\Logger::CRITICAL);
                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }
                $this->logger->addMessage(json_encode($pendingDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            }
            $this->db->commit();
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function pendingAction()
    {
        $rules = [
            'coach_pending_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => null
            ],
            'coach_id' => [
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
        $coachPendingModel = new CoachPendingModel();
        $input['project_id'] = $this->user['project_id'];
        $coachPendingList = $coachPendingModel->getList($input);
        $coachPendingList = isset($coachPendingList) && !empty($coachPendingList) ? $coachPendingList->toArray() : [];
        $coachModel = new CoachModel();
        $params['project_id'] = $input['project_id'];
        $coachList = $coachModel->getList($params);
        $coachList = isset($coachList) && !empty($coachList) ? $coachList->toArray() : [];
        $this->view->coachPendingList = $coachPendingList;
        $this->view->coachlist = $coachList;
        $this->view->filter = $input;
        $this->tag->appendTitle('评分信息');
    }

    public function pendinghandleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'coach_pending_id_ok' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ],
                'coach_pending_id_delete' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ],
            ];
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $input['project_id'] = $this->user['project_id'];
            $coachPendingModel = new CoachPendingModel();
            $coachModel = new CoachModel();
            if (!empty($input['coach_pending_id_ok'])) {
                $input['coach_pending_id'] = $input['coach_pending_id_ok'];
                $pendingDetails = $coachPendingModel->getScore($input);
                $pendingDetails = isset($pendingDetails) && !empty($pendingDetails) ? $pendingDetails->toArray()[0] : [];
                $oldPendingDetails = $pendingDetails;
                if (!empty($pendingDetails)) {
                    $score = ($pendingDetails['coach_score'] * $pendingDetails['evaluate_count'] + $pendingDetails['coach_pending_score']) / ($pendingDetails['evaluate_count'] + 1);
                    $score = sprintf("%.2f", $score);
                    $params['coach_id'] = $pendingDetails['coach_id'];
                    $params['coach_score'] = $score;
                    $params['evaluate_count'] = $pendingDetails['evaluate_count'] + 1;
                    $params['project_id'] = $pendingDetails['project_id'];
                    $params['coach_pending_id'] = $pendingDetails['coach_pending_id'];
                    $this->logger->appendTitle('update and delete');
                    $this->db->begin();
                    try {
                        $coachModel->updateInfo($params);
                        $coachPendingModel->deletePending($params);
                    } catch (Exception $exception) {
                        $this->db->rollback();
                        $this->logger->addMessage($exception->getMessage(), Phalcon\Logger::CRITICAL);
                        $this->resultModel->setResult('102');
                        return $this->resultModel->output();
                    }
                    $this->db->commit();
                    $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE) . 'old data:' . json_encode($oldPendingDetails, JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
                } else {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
            } else if (!empty($input['coach_pending_id_delete'])) {
                $input['coach_pending_id'] = $input['coach_pending_id_delete'];
                $this->logger->appendTitle('delete');
                $this->db->begin();
                try {
                    $coachPendingModel->deletePending($input);
                } catch (Exception $e) {
                    $this->db->rollback();
                    $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }
                $this->db->commit();
                $this->logger->addMessage(json_encode($coachPendingModel->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            } else {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }
}
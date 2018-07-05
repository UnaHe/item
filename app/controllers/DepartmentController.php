<?php
/**
 * Created by PhpStorm.
 * User: 何杨涛
 * Date: 2018/7/2
 * Time: 10:18
 */

class DepartmentController extends ControllerBase
{
    /**
     * 部门列表.
     */
    public function listAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'department_id' => [
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
            $departmentModel = new DoctorDepartmentModel();
            $departmentDetails = $departmentModel->getDetailsById($input['department_id']);
            if (!$departmentDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($departmentDetails->project_id != $this->user['project_id']) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $departmentList = $departmentModel->getListSimple($this->user['project_id']);
            $tree = new TreeModel ();
            $list = $departmentList->toArray();

            $tree->setTree($list, 'department_id', 'department_pid', 'department_name');
            $children = $tree->getChilds($departmentDetails->department_id);

            $delDepartment = [$departmentDetails->department_id];
            if (!empty($children)) {
                $delDepartment = array_merge($delDepartment, $children);
            }
            $replacePattern = '';
            $this->db->begin();
            try {
                foreach ($delDepartment as $v) {
                    $_details = $departmentModel::cloneResult($departmentModel, ['department_id' => $v]);
                    $_details->delete();
                    $replacePattern .= '.' . $v . '|' . $v . '|' . $v . '.|';
                }
                $replacePattern = rtrim($replacePattern, '|');
                $doctorUpSql = 'update ' . DB_PREFIX . "doctor set doctor_relation=regexp_replace(doctor_relation,'" . $replacePattern . "','') where project_id=" . $this->user['project_id'];
                $this->db->query($doctorUpSql);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->db->commit();
            $this->logger->addMessage(json_encode($departmentDetails->toArray(), JSON_UNESCAPED_UNICODE) . ' childs:' . json_encode($tree->getArrayList($departmentDetails->department_id), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);

            if (CACHING) {
                $removedTag = [
                    DoctorDepartmentModel::class . 'getList' . $this->user['project_id'],
                    DoctorModel::class . 'getList' . $this->user['project_id']
                ];
                foreach ($delDepartment as $v) {
                    $removedTag[] = DoctorModel::class . 'getList' . $v;
                }
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, $removedTag);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }

        $rules = array(
            'project_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => $this->user['project_id'],
            ),
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
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();

        $departmentModel = new DoctorDepartmentModel();
        $departmentList = $departmentModel->getList($input);

        $this->tag->appendTitle($this->translate->_('DepartmentList'));
        $this->view->departmentList = $departmentList;
    }

    public function handleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'department_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    )
                ),
                'department_name' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'department_pid' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    ),
                    'default' => 0
                ),
                'map_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    ),
                ),
                'department_intro' => array(
                    'filter' => FILTER_UNSAFE_RAW,
                    'default' => ''
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $input['project_id'] = $this->user['project_id'];

            $departmentModel = new DoctorDepartmentModel();
            if ($input['department_id'] > 0) {
                $list = $departmentModel->clientGetList($this->user['project_id'])->toArray();
                $tree = new TreeModel ();
                $tree->setTree($list, 'department_id', 'department_id_pid', 'department_id_name');
                $childs = $tree->getChilds($input['department_id']);
                if ($input['department_pid'] == $input['department_id'] || in_array($input['department_pid'], $childs)) {
                    $this->resultModel->setResult('503');
                    return $this->resultModel->output();
                }
                $departmentDetails = $departmentModel->getDetailsById($input['department_id']);
                if (!$departmentDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $oldDepartmentDetails = $departmentDetails->toArray();
                if($oldDepartmentDetails['map_id'] !== $input['map_id']){ //如果修改了地图，则清空map_gid
                    $input['map_gid'] = '';
                }
            }
            $this->db->begin();
            try {
                if (isset($departmentDetails)) {
                    $this->logger->appendTitle('update');
                    $departmentDetails->update($input);
                    if (CACHING) {
                        $this->cache->delete(CacheBase::makeTag(DoctorDepartmentModel::class . 'getDetailsById', $input['department_id']));
                    }
                } else {
                    unset($input['department_id']);
                    $this->logger->appendTitle('create');
                    $departmentModel->create($input);
                }
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage(json_encode($input) . ' error:' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->db->commit();
            $this->logger->addMessage(json_encode($input, JSON_UNESCAPED_UNICODE) . (isset($oldDepartmentDetails) ? ' Old:' . json_encode($oldDepartmentDetails, JSON_UNESCAPED_UNICODE) : ''), Phalcon\Logger::NOTICE);

            if (CACHING) {
                $removedTag = [
                    DoctorDepartmentModel::class . 'getList' . $this->user['project_id'],
                    DoctorModel::class . 'getList' . $this->user['project_id']
                ];
                if (!empty($input['department_id'])) {
                    $removedTag[] = DoctorModel::class . 'getList' . $input['department_id'];
                }
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, $removedTag);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }

        $rules = array(
            'department_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 0
                ),
                'default' => 0
            ),
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->resultModel->setResult('101');
            return $this->resultModel->output();
        }
        $input = $filter->getResult();

        $departmentModel = new DoctorDepartmentModel();
        if (!empty($input['department_id'])) {
            $departmentDetails = $departmentModel->getDetailsById($input['department_id']);
            if (!$departmentDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $this->view->departmentDetails = $departmentDetails->toArray();
        }

        $departmentList = $departmentModel->clientGetList($this->user['project_id']);
        $tree = new TreeModel ();
        $list = $departmentList->toArray();
        $tree->setTree($list, 'department_id', 'department_pid', 'department_name');
        $this->view->departmentList = $tree->getOptions();

        $mapModel = new MapModel();
        $mapList = $mapModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->mapList = $mapList['data'];

        $this->tag->appendTitle($this->translate->_('DepartmentHandle'));
        $this->view->filter = $input;
    }

}
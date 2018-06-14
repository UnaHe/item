<?php
/**
 * MarkControllerName(科室管理)
 *
 */
class DoctordepartmentController extends ControllerBase
{
    /**
     * MarkActionName(科室信息|ajaxdeletedepartment)
     * @return mixed
     */
    public function departmentAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'department_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    )
                ),
                'name' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'pid' => array(
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
                'intro' => array(
                    'filter' => FILTER_UNSAFE_RAW,
                    'default' => ''
                ),
//                'sort_order' => array(
//                    'filter' => FILTER_VALIDATE_INT,
//                    'options' => array(
//                        'min_range' => 0
//                    ),
//                    'default' => 0
//                )
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $params = [];
            $params['project_id'] = $this->user['project_id'];
            $params['department_name'] = $input['name'];
            $params['department_pid'] = $input['pid'];
            $params['department_intro'] = $input['intro'];
            $params['map_id'] = $input['map_id'];

            $departmentModel = new DoctorDepartmentModel();
            if ($input['department_id'] > 0) {
                $list = $departmentModel->clientGetList($this->user['project_id'])->toArray();
                $tree = new TreeModel ();
                $tree->setTree($list, 'department_id', 'department_id_pid', 'department_id_name');
                $childs = $tree->getChilds($input['department_id']);
                if ($input['pid'] == $input['department_id'] || in_array($input['pid'], $childs)) {
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
                    $params['map_gid'] = '';
                }
            }
            $this->db->begin();
            try {
                if (isset($departmentDetails)) {
                    $this->logger->appendTitle('update');
                    $departmentDetails->update($params);
                    if (CACHING) {
                        $this->cache->delete(CacheBase::makeTag(DoctorDepartmentModel::class . 'getDetailsById',
                            $input['department_id']));
                    }
                } else {
                    $this->logger->appendTitle('create');
                    $departmentModel->create($params);
                }
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage(json_encode($params) . ' error:' . $e->getMessage(),
                    Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->db->commit();
            $this->logger->addMessage(json_encode($params,
                    JSON_UNESCAPED_UNICODE) . (isset($oldDepartmentDetails) ? ' Old:' . json_encode($oldDepartmentDetails,
                        JSON_UNESCAPED_UNICODE) : ''), Phalcon\Logger::NOTICE);

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




        $this->tag->appendTitle($this->translate->_('DoctorDepartmentInfo'));
        $departmentModel = new DoctorDepartmentModel();
        $departmentList = $departmentModel->clientGetList($this->user['project_id']);
        $tree = new TreeModel ();
        $list = $departmentList->toArray();
        $tree->setTree($list, 'department_id', 'department_pid', 'department_name');
        $this->view->departmentList = $tree->getOptions();
        $cateJson = [];
        if (!empty ($list)) {
            foreach ($list as $v) {
                $cateJson [$v ['department_id']] = [
                    'name' => $v['department_name'],
                    'pid' => $v['department_pid'],
                    'intro' => $v['department_intro'],
                    'map_id' => $v['map_id']
                ];
            }
        }
        $this->view->cates = json_encode($cateJson, JSON_UNESCAPED_UNICODE);
        $mapModel = new MapModel();
        $mapList = $mapModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->mapList = $mapList['data'];
    }

    public function ajaxdeletedepartmentAction()
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
            $childs = $tree->getChilds($departmentDetails->department_id);

            $delDepartment = [$departmentDetails->department_id];
            if (!empty($childs)) {
                $delDepartment = array_merge($delDepartment, $childs);
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
            $this->logger->addMessage(json_encode($departmentDetails->toArray(),
                    JSON_UNESCAPED_UNICODE) . ' childs:' . json_encode($tree->getArrayList($departmentDetails->department_id),
                    JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);

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
    }

    /**
     * MarkActionName(科室列表|ajaxgetpolygonsbymap|ajaxdepartmentmapsubmit)
     * @return mixed
     */
    public function listAction()
    {
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
//            'usePage' => array(
//                'filter' => FILTER_VALIDATE_INT,
//                'options' => array(
//                    'min_range' => 0,
//                    'max_range' => 1
//                ),
//                'default' => 1
//            )
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->appendTitle($this->translate->_('DoctorDepartmentList'));
        $departmentModel = new DoctorDepartmentModel();
        $departmentList = $departmentModel->getList($input);

        $projectModel = new ProjectModel();
        $projectDetails = $projectModel->getDetailsByProjectId($this->user['project_id']);

        //地图配置
        if (!empty($projectDetails->project_client_map)) {
            $this->view->clientMapSetting = explode(',', $projectDetails->project_client_map);
        }else{
            $this->view->clientMapSetting = ['18', '21', '0.7', '1.2'];
        }
        $this->view->imageType = $projectDetails->project_map_tile_type ? $projectDetails->project_map_tile_type : 'jpg';
        $this->view->departmentList = $departmentList;
    }

    public function ajaxgetpolygonsbymapAction()
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
            if (!$filter->isValid($this->request->getQuery())) {
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
            if ($departmentDetails->project_id != $this->user['project_id'] || empty($departmentDetails->map_id)) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }

            $mapPolygonModel = new MapPolygonModel();
            $mapPolygonList = $mapPolygonModel->getListByAvailable(['map_id'=>$departmentDetails->map_id,'project_id'=>$this->user['project_id']]);
            if (!$mapPolygonList) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $data = [
                'polygon_list' => $mapPolygonList,
                'map_id' => $departmentDetails->map_id,
                'map_gid' => explode(',',$departmentDetails->map_gid),
            ];
            $this->resultModel->setResult('0',$data);
            return $this->resultModel->output();
        }
    }

    public function ajaxdepartmentmapsubmitAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'department_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],
                'polygon' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags'  => FILTER_FORCE_ARRAY,
                    'default' => [],
                ]
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
            if ($departmentDetails->project_id != $this->user['project_id'] || empty($departmentDetails->map_id)) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $params = [
//                'map_gid' => $input['department_id'],
                'map_gid' => !empty($input['polygon']) ? implode(',',$input['polygon']) : '',
            ];
            $this->logger->appendTitle('update');
            $this->db->begin();
            try {
                $departmentDetails->update($params);
                if (CACHING) {
                    $this->cache->delete(CacheBase::makeTag(DoctorDepartmentModel::class . 'getDetailsById',
                        $input['department_id']));
                }
            }catch (Exception $e){
                $this->db->rollback();
                $this->logger->addMessage(json_encode($params) . ' error:' . $e->getMessage(),
                    Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->db->commit();
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
    }
}
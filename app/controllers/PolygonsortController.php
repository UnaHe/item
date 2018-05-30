<?php

/**
 * MarkControllerName(关键字排序)
 *
 */
class PolygonsortController extends ControllerBase
{
    public function ajaxpushpolygonsortAction()
    {
        if ($this->request->isAjax() && $this->request->isPost()) {
            $rules = [
                'project_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ]
            ];

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            if ($input['project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $itemProjectModel = new ItemProjectModel();
            $itemProject = $itemProjectModel->getList(['item_account_id' => $input['id']]);
            $this->resultModel->setResult('0', $itemProject['data']);
            return $this->resultModel->output();
        }
    }

    /**
     * MarkActionName(关键字排序列表)
     * @return mixed
     */
    public function listAction()
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
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->setTitle($this->translate->_("polygonsortList"));
        $input['project_id'] = $this->user['project_id'];

        $polygonSortModel = new PolygonsortModel();
        $polygonSort = $polygonSortModel->getList($input);

        $this->view->filter = $input;
        $this->view->polygonSort = $polygonSort;
    }

    /**
     * MarkActionName(点位排序操作)
     * @return mixed
     */
    public function handleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            set_time_limit(0);
            $rules = array(
                'project_polygon_sort_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ),
                'timeBucket' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'map_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1,
                    ],
                ),
                'map_gid' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101', $filter->getErrMsg());
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $timeBucket = explode('-', $input['timeBucket']);
            $startTime = strtotime($timeBucket[0]);
            if (!$startTime) {
                $this->resultModel->setResult('104');
                return $this->resultModel->output();
            }
            $endTime = strtotime($timeBucket[1]);
            if (!$endTime) {
                $this->resultModel->setResult('105');
                return $this->resultModel->output();
            }
            $input['project_polygon_sort_startTime'] = $startTime;
            $input['project_polygon_sort_endTime'] = $endTime;

            $polygonsortModel = new PolygonsortModel();
            if (!empty($input['project_polygon_sort_id'])){
                $polygonsortDetails = $polygonsortModel->getDetailsById($input['project_polygon_sort_id']);
                if (!$polygonsortDetails){
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
            }else{
                $polygonsortDetails = ['project_id' => $this->user['project_id']];
            }
            $cloneDetails = $polygonsortModel::cloneResult($polygonsortModel, $polygonsortDetails);
            $this->db->begin();
            try {
                $cloneDetails->save($input);
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->resultModel->setResult('102', $e->getMessage());
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
                'default' => null
            ],
        ];
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->setTitle($this->translate->_("polygonsortHandle"));

        $polygonsortModel = new PolygonsortModel();
        if (!is_null($input['id'])) {
            $polygonsortDetails = $polygonsortModel->getDetailsById($input['id']);
            if (!$polygonsortDetails) {
                $this->alert($this->resultModel->getMsg('-1'));
            }
        } else {
            $polygonsortDetails = PolygonsortModel::cloneResult($polygonsortModel, [])->toArray();
        }

        $this->view->details = $polygonsortDetails;

        $mapModel = new MapModel();
        $map = $mapModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->mapList = $map['data'];

        $this->view->filter = $input;

    }
}
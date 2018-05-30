<?php
/**
 * MarkControllerName(地图管理)
 *
 */
class MapController extends ControllerBase
{
    /**
     * MarkActionName(地图管理|notice,ajaxsendnotice|ajaxsetibeaconrssi|polygon,ajaxgetbymapid|ajaxlinestring)
     */
    public function manageAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'map_id' => array(
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
                    )
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
            }
            $this->db->begin();
            if (isset($departmentDetails)) {
                $return = $departmentDetails->update($params);
            } else {
                $return = $departmentModel->create($params);
            }
            if ($return == false) {
                $this->db->rollback();
                $this->logger->addMessage(json_encode($params) . ' error:' . $this->logger->parsePhalconErrMsg($return->getMessages()),
                    Phalcon\Logger::CRITICAL);
                
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->db->commit();
            $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = array(
            'map_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
            )
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->appendTitle($this->translate->_('MapManage'));
        $mapModel = new MapModel();
        $map = $mapModel->getListSimple(['project_id' => $this->user['project_id']]);
        $mapList = $map['data'];
        if (!is_null($input['map_id'])) {
            foreach ($mapList as $v) {
                if ($v['map_id'] == $input['map_id']) {
                    $touched = 1;
                }
            }
            if (!isset($touched)) {
                $this->alert($this->resultModel->getMsg('101'), '/map/manage');
            }
        }
        $this->view->mapList = $mapList;
        $projectModel = new ProjectModel();
        $projectDetails = $projectModel->getDetailsByProjectId($this->user['project_id']);
        if (!is_null($input['map_id'])) {
            $equipmentModel = new EquipmentModel();
            $equipment = $equipmentModel->getListSimple([
                'project_id' => $this->user['project_id'],
                'map_id' => $input['map_id']
            ]);
            if (!empty($equipment['data'])) {
                $equipmentJson = [];
                foreach ($equipment['data'] as $v) {
                    $equipmentJson[] = [
                        'equipment_id' => $v['equipment_id'],
                        'map_point_name' => $v['map_point_name'],
                        'code' => $v['equipment_code'],
                        'online' => $v['equipment_online'],
                        'point' => $v['point']
                    ];
                }
                $this->view->equipmentList = $equipmentJson;
            }
            $ibeaconModel = new IbeaconModel();
            $ibeacon = $ibeaconModel->getListSimple(['map_id' => $input['map_id']]);
            if (!empty($ibeacon['data'])) {
                $ibeaconJson = [];
                foreach ($ibeacon['data'] as $v) {
                    $ibeaconJson[] = [
                        'ibeacon_id' => $v['ibeacon_id'],
                        'map_point_name' => $v['map_point_name'],
                        'online' => $v['ibeacon_online'],
                        'point' => $v['point'],
                        'rssi' => $v['ibeacon_rssi'],
                        'major' => $v['ibeacon_wx_major'],
                        'minor' => $v['ibeacon_wx_minor'],
                        'map_id' => $v['ibeacon_map_id'],
                        'point_id' => $v['point_id'],
                    ];
                }
                $this->view->ibeaconList = $ibeaconJson;
            }
        }
        if (!empty($projectDetails->project_client_map)) {
            $this->view->clientMapSetting = explode(',', $projectDetails->project_client_map);
        }
        $this->view->filter = $input;
    }

    public function ajaxsetibeaconrssiAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'ibeacon_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'rssi' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => -80,
                        'max_range' => -40
                    )
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $ibeaconModel = new IbeaconModel();
            $ibeaconDetails = $ibeaconModel->getDetailsByIdSimple($input['ibeacon_id']);
            if (!$ibeaconDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $details = $ibeaconModel::cloneResult($ibeaconModel, $ibeaconDetails);
            $details->ibeacon_rssi = $input['rssi'];
            $this->db->begin();
            try {
                $details->update();
                $this->db->commit();
                $this->logger->addMessage(' ' . json_encode($input) . ' old:' . json_encode($ibeaconDetails,
                        JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input), Phalcon\Logger::CRITICAL);
                
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxsetpathAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'congestion_level' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1,
                        'max_range' => 5
                    )
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $mapLineStringModel = new MapLineStringModel();
            $mapLineStringDetails = $mapLineStringModel->getDetailsByMapLineStringIdSimple($input["id"]);
            if (!$mapLineStringDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $mapLineStringDetails["congestion_level"] = $input["congestion_level"];
            $mapLineString = $mapLineStringModel::cloneResult($mapLineStringModel, $mapLineStringDetails);
//            $mapLineString->congestion_level = $input["congestion_level"];
            if ($mapLineString->update() == false) {
                $this->resultModel->setResult('102', $mapLineString->getMessages());
                return $this->resultModel->output();
            }
//            $mapLineStringList = $mapLineStringModel->getListSimple(["map_id" => $mapLineString->map_id]);
//            $mapLineStringList = $mapLineStringList["data"]->toArray();
////            var_dump($mapLineStringList);die();
////            $stModel = new StModel();
//            $linestringJson = [
//                "type" => "FeatureCollection",
//                "crs" => ["type" => "name", "properties" => ["name" => "urn:ogc:def:crs:EPSG::4326"]],
//                "features" => []
//            ];
//            foreach ($mapLineStringList as $k => $v) {
//                $linestringJson['features'][] = [
//                    'type' => 'Feature',
//                    'properties' => [
//                        'map_id' => $v['map_id'],
//                        'id' => $v['map_linestring_id'],
//                        'i' => $v['i'],
//                        'j' => $v['j'],
//                        'congestion_level' => $v['congestion_level'],
//                    ],
//                    'geometry' => ['type' => 'LineString', 'coordinates' => $v["coordinates"]]
//                ];
//            }
            $content = [
                'cmd' => 'lineUpdate',
                'context' => [
                    'type' => 'Feature',
                    'properties' => [
                        'map_id' => $mapLineStringDetails['map_id'],
                        'id' => $mapLineStringDetails['map_linestring_id'],
                        'i' => $mapLineStringDetails['i'],
                        'j' => $mapLineStringDetails['j'],
                        'congestion_level' => $mapLineStringDetails['congestion_level'],
                    ],
                    'geometry' => ['type' => 'LineString', 'coordinates' => $mapLineStringDetails["coordinates"]]
                ],
            ];
            $linestringJson = json_encode($content);
            $linestringJson = str_replace('"[[', '[[', $linestringJson);
            $linestringJson = str_replace(']]"', ']]', $linestringJson);
            $post_data = [
                'type' => "all",
                'content' => $linestringJson,
//                'to' => "mobile",
                'project' => $this->user['project_id'],
            ];


            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest = new Roger\Request\MultiRequest();
            $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();
            echo json_encode($input);
            die();
        }
        $rules = array(
            'mapId' => array(
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
        $this->tag->appendTitle($this->translate->_('MapManage'));
        $mapModel = new MapModel();
        $map = $mapModel->getListSimple(["project_id" => $this->user["project_id"]]);
        $this->view->mapList = $map["data"];
        $input["mapId"] = is_null($input['mapId']) ? $this->view->mapList[0]["map_id"] : $input['mapId'];

        $warningModel = new WarningModel();
        $warningList = $warningModel->getListSimple(["status_not" => 2]);
        $this->view->warningList = $warningList["data"];
        $this->view->filter = $input;
    }

    public function ajaxlinestringAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'mapId' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $mapLineStringModel = new MapLineStringModel();
            $mapLineString = $mapLineStringModel->getListSimple(["map_id" => $input["mapId"]]);
            $linestringJson = [
                "type" => "FeatureCollection",
                "crs" => ["type" => "name", "properties" => ["name" => "urn:ogc:def:crs:EPSG::4326"]],
                "features" => []
            ];
            foreach ($mapLineString["data"] as $k => $v) {
                $linestringJson['features'][] = [
                    'type' => 'Feature',
                    'properties' => [
                        'map_id' => $v['map_id'],
                        'id' => $v['map_linestring_id'],
                        'i' => $v['i'],
                        'j' => $v['j'],
                        'congestion_level' => $v['congestion_level'],
                    ],
                    'geometry' => ['type' => 'LineString', 'coordinates' => $v["coordinates"]]
                ];
            }
            $linestringJson = json_encode($linestringJson);
            $linestringJson = str_replace('"[[', '[[', $linestringJson);
            $linestringJson = str_replace(']]"', ']]', $linestringJson);
            die($linestringJson);
        }
        $this->resultModel->setResult('101');
        return $this->resultModel->output();
    }

    public function warningAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'warningId' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'op' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1,
                        'max_range' => 3,
                    )
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $warningModel = new WarningModel();
            $warningDetails = $warningModel->getDetailsSimple($input['warningId']);
            if (!$warningDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $warningDetails["warning_status"] = $input["op"];
            $warning = $warningModel::cloneResult($warningModel, $warningDetails);
            if (!$warning->update()) {
                $this->resultModel->setResult('102', $warning->getMessages());
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0', $warningDetails);
            unset($warningDetails["staff_id"], $warningDetails["warning_time"]);
            $content = [
                'cmd' => 'warningUpdate',
                'context' => $warningDetails,
            ];
            $post_data = [
                'type' => "tag",
                'content' => json_encode($content),
                'to' => $this->user['project_id'] . "|mobile|1",
                'project' => $this->user['project_id'],
            ];


            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest = new Roger\Request\MultiRequest();
            $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();

            return $this->resultModel->output();
        }
        $rules = array(
            'map_id' => array(
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
        $this->tag->appendTitle('救援管理');
        $mapModel = new MapModel();
        $map = $mapModel->getListSimple(["project_id" => $this->user["project_id"]]);
        $this->view->mapList = $map["data"];
        $projectModel = new ProjectModel();
        $projectDetails = $projectModel->getDetailsByProjectId($this->user['project_id']);
        if (!empty($projectDetails->project_client_map)) {
            $this->view->clientMapSetting = explode(',', $projectDetails->project_client_map);
        }
        $warningModel = new WarningModel();
        $warningList = $warningModel->getListSimple(['project_id'=>$this->user['project_id'],'status_not'=>2]);
        $warningListArr = [];
        foreach ($warningList['data'] as $v){
            $warningListArr[$v['warning_id']] = $v;
        }
        $this->view->warningListJson = json_encode($warningListArr);
        $this->view->warningList = $warningList['data'];
        $this->view->filter = $input;
    }

    public function ajaxgetpolygonAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'mapId' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
//            $naviInfo = $this->session->navigationInfo;
            $mapPolygonModel = new MapPolygonModel();
            $mapPolygon = $mapPolygonModel->getListSimple(['map_id' => $input['mapId'], 'target' => true]);
//            $stModel = new StModel();
            $polygonJsonString = [
                "type" => "FeatureCollection",
                "crs" => ["type" => "name", "properties" => ["name" => "urn:ogc:def:crs:EPSG::3857"]],
                "features" => []
            ];
            foreach ($mapPolygon['data'] as $k => $v) {
                if (!empty($v['name']) && !empty($v['gid'])) {
                    $_coordinates = $v['coordinates'];
                    $_coordinates = str_replace('(', '', $_coordinates);
                    $_coordinates = str_replace(')', '', $_coordinates);
//                if ($naviInfo['rotate'] > 0) {
//                    $_rotateGeom = $stModel->rotate($v['geom'], $naviInfo['rotate']);
//                    $_coordinates = $stModel->asText($_rotateGeom);
//                    $_coordinates = str_replace('POLYGON((', '[[[', $_coordinates);
//                    $_coordinates = str_replace('))', ']]]', $_coordinates);
//                    $_coordinates = str_replace(',', '],[', $_coordinates);
//                    $_coordinates = str_replace(' ', ',', $_coordinates);
//                }
                    $polygonJsonString['features'][] = [
                        'type' => 'Feature',
                        'properties' => [
                            'name' => $v['name'],
                            'gid' => $v['gid'],
                            'map_id' => $v['map_id'],
                            'target_map_id' => $v['target_map_id'],
                            'centroid' => $v['centroid'],
                            'id' => $v['map_polygon_id'],
                            'company_intro' => $v['company_intro'],
                            'context' => $v['context'],
                            'context_status' => $v['context_status']

                        ],
                        'geometry' => ['type' => 'Polygon', 'coordinates' => $_coordinates]
                    ];
                }
            }
            $polygonJson = json_encode($polygonJsonString);
            $polygonJson = str_replace('"[[[', '[[[', $polygonJson);
            $polygonJson = str_replace(']]]"', ']]]', $polygonJson);
            echo $polygonJson;
        }
    }

    public function ajaxsetpolygonAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'status' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'context' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $mapPolygonModel = new MapPolygonModel();
            $mapPolygon = $mapPolygonModel->getDetailsByMapPolygonIdSimple($input["id"]);
            if (!$mapPolygon) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $mapPolygon["context_status"] = $input['status'];
            $mapPolygon["context"] = $input['context'];
            $mapPolygonDetails = $mapPolygonModel::cloneResult($mapPolygonModel, $mapPolygon);
            if (!$mapPolygonDetails->update()) {
                $this->resultModel->setResult('102', $mapPolygonDetails->getMessages());
                return $this->resultModel->output();
            }
            $_coordinates = $mapPolygon['coordinates'];
            $_coordinates = str_replace('(', '', $_coordinates);
            $_coordinates = str_replace(')', '', $_coordinates);
            $content = [
                'cmd' => 'polygonUpdate',
                'context' => [
                    'type' => 'Feature',
                    'properties' => [
                        'name' => $mapPolygon['name'],
                        'gid' => $mapPolygon['gid'],
                        'map_id' => $mapPolygon['map_id'],
                        'target_map_id' => $mapPolygon['target_map_id'],
                        'centroid' => $mapPolygon['centroid'],
                        'id' => $mapPolygon['map_polygon_id'],
                        'company_intro' => $mapPolygon['company_intro'],
                        'context' => $mapPolygon['context'],
                        'context_status' => $mapPolygon['context_status']
                    ],
                    'geometry' => ['type' => 'Polygon', 'coordinates' => $_coordinates]
                ],
            ];
            $polygonJson = json_encode($content);
            $polygonJson = str_replace('"[[[', '[[[', $polygonJson);
            $polygonJson = str_replace(']]]"', ']]]', $polygonJson);
            $post_data = [
                'type' => "all",
                'content' => $polygonJson,
                'project' => $this->user['project_id'],
            ];

            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest = new Roger\Request\MultiRequest();
            $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();
            die(json_encode($mapPolygonDetails));
        }
    }

    public function evacuateAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $content = [
                'cmd' => 'evacuateUpdate',
            ];
            $post_data = [
                'type' => "all",
                'content' => json_encode($content),
                'project' => $this->user['project_id'],
            ];

            $settingModel = new SettingModel();
            $setting = $settingModel->getByKeys(['socketUrl']);
            $multiRequest = new Roger\Request\MultiRequest();
            $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $this->resultModel->setResult('-1');
        return $this->resultModel->output();
    }
}
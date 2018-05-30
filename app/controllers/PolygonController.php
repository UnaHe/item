<?php

class PolygonController extends ControllerBase
{
    public function ajaxgetlistbymapidAction()
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
            $mapPolygonModel = new MapPolygonModel();
            $mapPolygon = $mapPolygonModel->getListSimple([
                'map_id' => $input['mapId'],
                'target' => true,
                'context' => 'spots',
            ]);
            $polygonList = [];
            foreach ($mapPolygon['data'] as $v) {
                if (!empty($v['map_gid'])) {
                    $polygonList[] = ['name'=>$v['name'],'map_gid'=>$v['map_gid']];
                }
            }
            $this->resultModel->setResult('0',$polygonList);
            return $this->resultModel->output();
        }
    }

    public function ajaxgetbymapidAction()
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
            $mapPolygonModel = new MapPolygonModel();
            $mapPolygon = $mapPolygonModel->getListSimple([
                'map_id' => $input['mapId'],
                'target' => true,
            ]);
            $polygonList = $mapPolygon['data'];
            $polygonJsonString = [
                "type" => "FeatureCollection",
                "crs" => ["type" => "name", "properties" => ["name" => "urn:ogc:def:crs:EPSG::3857"]],
                "features" => []
            ];
            foreach ($polygonList as $k => $v) {
                $_coordinates = $v['coordinates'];
                $_coordinates = str_replace('(', '', $_coordinates);
                $_coordinates = str_replace(')', '', $_coordinates);
                if (!is_null($v['gid']) && !is_null($v['name'])) {
                    $polygonJsonString['features'][] = [
                        'type' => 'Feature',
                        'properties' => [
                            'name' => $v['name'],
                            'map_gid' => $v['map_gid'],
                            'centroid' => $v['centroid'],
                            'id' => $v['map_polygon_id'],
                            'company_intro' => $v['company_intro'] //zc

                        ],
                        'geometry' => ['type' => 'Polygon', 'coordinates' => $_coordinates]
                    ];
                }
            }
            $polygonJson = json_encode($polygonJsonString);
            $polygonJson = str_replace('"[[[', '[[[', $polygonJson);
            $polygonJson = str_replace(']]]"', ']]]', $polygonJson);
            die($polygonJson);
        }
    }

    public function ajaxsetpolygoncontentAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'polygonId' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'map_gid' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'content' => array(
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
            $companyMessageModel = new CompanyMessageModel();
            $companyMessageDetails = $companyMessageModel->getDetailsByMapGid($input['map_gid']);
            $params = ['company_intro' => $input['content']];
            if (!$companyMessageDetails) {
                $companyMessageDetails = [];
                $params['users_user_name'] = $input['map_gid'];
            }

            $updateDetails = $companyMessageModel::cloneResult($companyMessageModel, $companyMessageDetails);
            $this->db->begin();
            try {
                $updateDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode($input, JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input, JSON_UNESCAPED_UNICODE), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }
}
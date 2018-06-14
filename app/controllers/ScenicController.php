<?php

use OSS\OssClient;

/**
 * MarkControllerName(景区管理)
 *
 */
class ScenicController extends ControllerBase
{
    /**
     * MarkActionName(景点列表)
     */
    public function spotsAction()
    {
        $rules = array(
            'map_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
            ),
            'category_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
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
            'usePage' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 0,
                    'max_range' => 1
                ),
                'default' => 1
            ),
            'keywords' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'default' => ''
            ),
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $this->tag->appendTitle($this->translate->_('ScenicSpots'));
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
            $mapPolygonCategoryModel = new MapPolygonCategoryModel();
            $categoryList = $mapPolygonCategoryModel->getList(null,$this->user['project_id']);
            if (!is_null($input['category_id'])) {
                unset($touched);
                foreach ($categoryList as $v) {
                    if ($v['map_polygon_category_id'] == $input['category_id']) {
                        $touched = 1;
                        break;
                    }
                }
                if (!isset($touched)) {
                    $this->alert($this->resultModel->getMsg('101'), '/map/manage');
                }
            }
            $this->view->categoryList = $categoryList;
        }
        $this->view->mapList = $mapList;

        if (!is_null($input['map_id'])) {
            $input['context'] = 'spots';
            $mapPolygonModel = new MapPolygonModel();
            $this->view->mapPolygon = $mapPolygonModel->getListSimple($input);
        }
        $this->view->base_url = 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/';
        $this->view->filter = $input;
    }

    /**
     * MarkActionName(景点详情编辑)
     */
    public function spothandleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    )
                ),
                'thumbnailUrl' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'imageUrl' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'videoUrl' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'voiceUrl' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
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
            $mapPolygonModel = new MapPolygonModel();
            $mapPolygonDetails = $mapPolygonModel->getDetailsByMapPolygonIdSimple($input['id']);
            if (!$mapPolygonDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }

            $mapPolygonDescriptionModel = new MapPolygonDescriptionModel();
            $mapPolygonDescriptionDetails = $mapPolygonDescriptionModel->getDetailsByMapGidSimple($mapPolygonDetails['map_gid']);
            if (!$mapPolygonDescriptionDetails) {
                $this->logger->appendTitle('create');
                $mapPolygonDescriptionDetails = [];
            } else {
                $this->logger->appendTitle('update');
                $delete_resource = [];
                if(!empty($mapPolygonDescriptionDetails['map_polygon_description_thumbnail']) && $mapPolygonDescriptionDetails['map_polygon_description_thumbnail'] != $input['thumbnailUrl']){
                    $delete_resource[] = $mapPolygonDescriptionDetails['map_polygon_description_thumbnail'];
                }
                if(!empty($mapPolygonDescriptionDetails['map_polygon_description_video']) && $mapPolygonDescriptionDetails['map_polygon_description_video'] != $input['videoUrl']){
                    $delete_resource[] = $mapPolygonDescriptionDetails['map_polygon_description_video'];
                }
                if(!empty($mapPolygonDescriptionDetails['map_polygon_description_voice']) && $mapPolygonDescriptionDetails['map_polygon_description_voice'] != $input['voiceUrl']){
                    $delete_resource[] = $mapPolygonDescriptionDetails['map_polygon_description_voice'];
                }

            }
            $new_resource = [];
            if($input['thumbnailUrl'] != '' && strpos($input['thumbnailUrl'], '/') === 0){
                $new_resource['map_polygon_description_thumbnail'] = $input['thumbnailUrl'];
            }
            if($input['videoUrl'] != '' && strpos($input['videoUrl'], '/') === 0){
                $new_resource['map_polygon_description_video'] = $input['videoUrl'];
            }
            if($input['voiceUrl'] != '' && strpos($input['voiceUrl'], '/') == 0){
                $new_resource['map_polygon_description_voice'] = $input['voiceUrl'];
            }

            $mapPolygonDescriptionDetails['map_gid'] = $mapPolygonDetails['map_gid'];
            $mapPolygonDescriptionDetails['map_polygon_description_content'] = $input['content'];
//            $mapPolygonDescriptionDetails['map_polygon_image'] = $input['imageUrl'];
            $mapPolygonDescriptionDetails['map_polygon_description_thumbnail'] = $input['thumbnailUrl'];
            $mapPolygonDescriptionDetails['map_polygon_description_video'] = $input['videoUrl'];
            $mapPolygonDescriptionDetails['map_polygon_description_voice'] =  $input['voiceUrl'];

            $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
            $ossClient->setConnectTimeout(5);
//            //上传新资源
            if ($new_resource) {
                foreach ($new_resource as $type=>$url){
                    $objectName = ltrim($url, '/');
                    try {
                        $ossClient->putObject(self::$DefaultBucket, $objectName, file_get_contents(APP_PATH . 'public/' . $objectName));
                        $mapPolygonDescriptionDetails[$type] = $objectName;
                        @unlink(APP_PATH . 'public/' . $objectName);
                    } catch (Exception $e) {
                        //删除上传失败的资源
                        $this->logger->addMessage('oss upload > '.$type.': ' . $e->getMessage(),
                            Phalcon\Logger::CRITICAL);
                        $this->resultModel->setResult('701');
                        return $this->resultModel->output();
                    }
                }
            }
            //相册资源
            if(!empty($input['imageUrl'])){
                $input['imageUrl'] = explode(',',rtrim($input['imageUrl'],','));
                $oldImages = $uploadImages = [];
                if (!empty($mapPolygonDescriptionDetails['map_polygon_image'])) {
                    $oldImages = explode(',', $mapPolygonDescriptionDetails['map_polygon_image']);
                }
                if(!empty($oldImages)){
                    $mapPolygonDescriptionDetails['map_polygon_image'] = '';
                    foreach ($input['imageUrl']  as $v){
                        if (strpos($v, '/') === 0) { //新增的
                            $uploadImages[] = $v;
                            continue;
                        }
                        $_key = array_search($v, $oldImages);
                        if ($_key !== false) { //不删除的
                            unset($oldImages[$_key]);
                            $mapPolygonDescriptionDetails['map_polygon_image'] =   $mapPolygonDescriptionDetails['map_polygon_image'] ? $mapPolygonDescriptionDetails['map_polygon_image'].','.$v : $mapPolygonDescriptionDetails['map_polygon_image'].$v;
                        }
                    }
                    $delete_resource = array_merge($delete_resource, $oldImages);
                }else{
                    $uploadImages = $input['imageUrl'];
                }
                if (!empty($uploadImages)) {
                    try {
                        if (!isset($ossClient)) {
                            $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                            $ossClient->setConnectTimeout(5);
                        }
                        foreach ($uploadImages as $v) {
                            $objectName = ltrim($v, '/');
                            $ossClient->putObject(self::$DefaultBucket, $objectName, file_get_contents(APP_PATH . 'public/' . $objectName));

                            $mapPolygonDescriptionDetails['map_polygon_image'] =  $mapPolygonDescriptionDetails['map_polygon_image'] ? $mapPolygonDescriptionDetails['map_polygon_image'].','.$objectName : $mapPolygonDescriptionDetails['map_polygon_image'].$objectName;
                            @unlink(APP_PATH . 'public/' . $objectName);
                        }
                    } catch (Exception $e) {
                        $this->logger->addMessage('oss upload err:' . $e->getLine() . ' ' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                        $this->resultModel->setResult('701', $e->getMessage());
                        return $this->resultModel->output();
                    }
                }
            }else{
                if (!empty($mapPolygonDescriptionDetails['map_polygon_image'])) {
                    $delete_resource = array_merge($delete_resource, explode(',', $mapPolygonDescriptionDetails['map_polygon_image']));
                }
                $mapPolygonDescriptionDetails['map_polygon_image'] = '';
            }

            $mapPolygonDescriptionDetails['map_polygon_image'] = $mapPolygonDescriptionDetails['map_polygon_image'] ? rtrim($mapPolygonDescriptionDetails['map_polygon_image'],',') : '';
            $cloneDetails = $mapPolygonDescriptionModel::cloneResult($mapPolygonDescriptionModel, $mapPolygonDescriptionDetails);
            $this->db->begin();
//            print_r($mapPolygonDescriptionDetails);exit;
            try {
                //提交信息
                $cloneDetails->save();
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                //删除资源
                if ($new_resource || $uploadImages) {
                    $new_resource = array_merge($new_resource,$uploadImages);
                    foreach ($new_resource as $k=>$v){
                        $objectName = ltrim($url, '/');
                        $new_resource[$k] = $objectName;
                    }
                    if (!isset($ossClient)) {
                        $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                        $ossClient->setConnectTimeout(5);
                    }
                    try {
                        $ossClient->deleteObjects(self::$DefaultBucket, $new_resource);
                        $this->logger->addMessage('oss delete tmp '.$type.':' . json_encode($objectName,JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
                    } catch (Exception $ex) {
                        $this->logger->addMessage('oss delete tmp '.$type.' err:' . $ex->getMessage(),
                            Phalcon\Logger::CRITICAL);
                    }
                }
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }

            //如果有新的资源则删除以前的资源数据

            if ($delete_resource) {
                if (!isset($ossClient)) {
                    $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);
                }
                foreach ($delete_resource as $k=>$v){
                    try {
                        $ossClient->deleteObject(self::$DefaultBucket, $v);
                        $this->logger->addMessage('oss delete old '.$k.':' . $v, Phalcon\Logger::NOTICE);
                    } catch (Exception $ex) {
                        $this->logger->addMessage('oss delete old '.$k.' err:' . $ex->getMessage(),
                            Phalcon\Logger::CRITICAL);
                    }
                }
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }else{
            $rules = array(
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                )
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getQuery())) {
                $this->alert($this->resultModel->getMsg('101'));
            }

            $input = $filter->getResult();
            $this->tag->appendTitle($this->translate->_('SpotHandle'));
            $mapPolygonModel = new MapPolygonModel();
            $mapPolygonDetails = $mapPolygonModel->getDetailsByMapPolygonIdSimple($input['id']);
            if (!$mapPolygonDetails) {
                $this->alert($this->resultModel->getMsg('-1'));
            }

            $this->view->base_url= $base_url = 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/';

            $mapPolygonDetails['map_polygon_image_arr'] = $mapPolygonDetails['map_polygon_image'] ?  explode(',',$mapPolygonDetails['map_polygon_image']) : [];
            $mapPolygonDetails['map_polygon_image'] = $mapPolygonDetails['map_polygon_image'] ? $mapPolygonDetails['map_polygon_image'].',' : '';
            $this->view->mapPolygonDetails = $mapPolygonDetails;
            $this->view->filter = $input;
        }
    }

    /**
     * MarkActionName(路线方案删除)
     */

    public function routedeleteAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
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
            $scenicRouteModel = new ScenicRouteModel();
            $scenicRouteDetails = $scenicRouteModel->getDetailsByScenicRouteIdSimple($input['id']);
            if (!$scenicRouteDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($scenicRouteDetails['project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $cloneDetails = $scenicRouteModel::cloneResult($scenicRouteModel, $scenicRouteDetails);
            $this->db->begin();
            try {
                $cloneDetails->delete();
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input), Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [ScenicRouteModel::class . 'getList' . $scenicRouteDetails['project_id']]);
                $this->cache->delete(CacheBase::makeTag(ScenicRouteModel::class . 'getDetailsByScenicRouteIdSimple', $input['id']));
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    /**
     * MarkActionName(路线方案列表)
     */
    public function routeAction()
    {
        $rules = array(
            'category_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
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
        $input['project_id'] = $this->user['project_id'];
        $scenicRouteModel = new ScenicRouteModel();
        $this->view->scenicRoute = $scenicRouteModel->getListSimple($input);
        $scenicRouteCategoryModel = new ScenicRouteCategoryModel();
        $scenicRouteCategoryList = $scenicRouteCategoryModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->scenicRouteCategoryList = $scenicRouteCategoryList;
        $this->view->filter = $input;
    }

    /**
     * MarkActionName(路线方案编辑)
     */
    public function routehandleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    ),
                    'default' => null
                ),
                'category_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                ),
                'name' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
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
            $scenicRouteModel = new ScenicRouteModel();
            if (!is_null($input['id'])) {
                $scenicRouteDetails = $scenicRouteModel->getDetailsByScenicRouteIdSimple($input['id']);
                if (!$scenicRouteDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
            } else {
                $scenicRouteDetails = [];
            }
            $input['data'] = rtrim($input['data'], ';');
            $data = explode(';', $input['data']);
            if (count($data) < 1) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $pointModel = new MapPointModel();
            $stModel = new StModel();

            //通过传入的mapId（楼层编码）和gid（点面匹配编码）获取对应polygon(面)所有point(节点)的endId(节点编码)
            //这里gid通常为通过点击确定终点所获得面的编码，获取的$pointList通常为目标区域的门所在的点。

            $distance = 0;
            $totalDistance = 0;
            foreach ($data as $k => $v) {
                if (!isset($data[$k + 1])) break;
                $v = explode(',', $v);
                $startPoint = $pointModel->getListSimple([
                    'gid' => $v[2],
                    'map_id' => $v[1]
                ]);
                if (isset($data[$k + 1])) {
                    $endV = explode(',', $data[$k + 1]);
                    $endPoint = $pointModel->getListSimple([
                        'gid' => $endV[2],
                        'map_id' => $endV[1]
                    ]);
                }
                if (isset($startPoint['data'][0]) && isset($endPoint) && isset($endPoint['data'][0])) {
                    $pathPoints = $stModel->getSameMapPoints($startPoint['data'][0]['map_id'],
                        $startPoint['data'][0]['id'],
                        $endPoint['data'][0]['id']);
                    if (!empty($pathPoints)) {
                        foreach ($pathPoints as $pv) {
                            $distance += intval($pv['cost']);
                        }
                    }
                    $data[$k] .= ','.$distance;
                    $totalDistance+=$distance;
                    $distance = 0;
                }
            }
            $data = implode(';',$data);
            $timeText = '';

            if ($totalDistance > 0) {
                $timeText = ceil($totalDistance / 66) . ' ' . $this->translate->_('Minite');
                if ($totalDistance < 1000) {
                    $totalDistance .= ' ' . $this->translate->_('Meter');
                } else {
                    $totalDistance = number_format($totalDistance / 1000, 2) . ' ' . $this->translate->_('Km');
                }
            }

            $scenicRouteDetails['scenic_route_category_id'] = $input['category_id'];
            $scenicRouteDetails['scenic_route_content'] = $data;
            $scenicRouteDetails['scenic_route_name'] = $input['name'];
            $scenicRouteDetails['scenic_route_distance'] = $totalDistance;
            $scenicRouteDetails['scenic_route_time'] = $timeText;

            $cloneDetails = $scenicRouteModel::cloneResult($scenicRouteModel, $scenicRouteDetails);
            $this->db->begin();
            try {
                $cloneDetails->save();
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }

            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [ScenicRouteModel::class . 'getList' . $this->user['project_id']]);
                if (!is_null($input['id'])) $this->cache->delete(CacheBase::makeTag(ScenicRouteModel::class . 'getDetailsByScenicRouteIdSimple', $input['id']));
            }

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = array(
            'id' => array(
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
        $scenicRouteModel = new ScenicRouteModel();
        if (!is_null($input['id'])) {
            $scenicRouteDetails = $scenicRouteModel->getDetailsByScenicRouteIdSimple($input['id']);
            if (!$scenicRouteDetails) {
                $this->alert($this->resultModel->getMsg('-1'));
            }
        } else {
            $scenicRouteDetails = $scenicRouteModel::cloneResult($scenicRouteModel, [])->toArray();
        }

        $mapPolygonModel = new MapPolygonModel();
        $mapPolygon = $mapPolygonModel->getListSimple(['context' => 'spots','project_id' => $this->user['project_id']]);
        $mapPolygonList = $mapPolygon['data'];
        $this->tag->appendTitle($this->translate->_('ScenicRouteHandle'));
        $scenicRouteCategoryModel = new ScenicRouteCategoryModel();
        $scenicRouteCategoryList = $scenicRouteCategoryModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->scenicRouteCategoryList = $scenicRouteCategoryList;
        if (!empty($scenicRouteDetails['scenic_route_content'])) {
            $routeContentId = $routeContent = [];
            $_content = explode(';', $scenicRouteDetails['scenic_route_content']);
            foreach ($_content as $v) {
                $v = explode(',', $v);
                $routeContentId[] = $v[0];
                $routeContent[] = $v;
            }
            foreach ($mapPolygonList as $k => $v) {
                if (in_array($v['map_polygon_id'], $routeContentId)) {
                    $mapPolygonList[$k]['checked'] = 1;
                }
            }
            $this->view->routeContent = $routeContent;
        }

        $this->view->mapPolygonList = $mapPolygonList;
        $this->view->scenicRouteDetails = $scenicRouteDetails;
    }

    /**
     * MarkActionName(路线方案分类)
     */

    public function routecategoryAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'category_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                    'default' => null
                ),
                'sort_order' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    )
                ),
                'name' => array(
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
            $scenicRouteCategoryModel = new ScenicRouteCategoryModel();
            if (!is_null($input['category_id'])) {
                $scenicRouteCategoryDetails = $scenicRouteCategoryModel->getDetailsByScenicRouteCategoryIdSimple($input['category_id']);
                if (!$scenicRouteCategoryDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
            } else {
                $scenicRouteCategoryDetails = ['project_id' => $this->user['project_id']];
            }
            $scenicRouteCategoryDetails['scenic_route_category_name'] = $input['name'];
            $scenicRouteCategoryDetails['scenic_route_category_sort_order'] = $input['sort_order'];
            $cloneDetails = $scenicRouteCategoryModel::cloneResult($scenicRouteCategoryModel, $scenicRouteCategoryDetails);
            $this->db->begin();
            try {
                $cloneDetails->save();
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray()), Phalcon\Logger::CRITICAL);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($cloneDetails->toArray()), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }

            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [ScenicRouteCategoryModel::class . 'getList' . $this->user['project_id']]);
                if (!is_null($input['category_id'])) $this->cache->delete(CacheBase::makeTag(ScenicRouteCategoryModel::class . 'getDetailsByScenicRouteCategoryIdSimple', $input['category_id']));
            }


            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $this->tag->appendTitle($this->translate->_('ScenicRouteCategory'));
        $scenicRouteCategoryModel = new ScenicRouteCategoryModel();
        $this->view->scenicRouteCategoryList = $scenicRouteCategoryModel->getListSimple(['project_id' => $this->user['project_id']]);
    }

    /**
     * MarkActionName(景区活动)
     */
    public function eventsAction()
    {
        $rules = array(
            'startTime' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
            ),
            'endTime' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
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
        $input['project_id'] = $this->user['project_id'];
        $projectEventsModel = new ProjectEventsModel();
        $projectEvents = $projectEventsModel->newGetListSimple($input);
        $this->view->projectEvents = $projectEvents;
        $this->view->filter = $input;
        $this->tag->appendTitle($this->translate->_('ScenicEvents'));
    }

    /**
     * MarkActionName(景区活动编辑)
     */
    public function eventshandleAction()
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
                'title' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'title_en' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'map_gid' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'timeRange' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'topImage' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ),
                'content_cn' => array(
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ),
                'content_en' => array(
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

            $timeRange = explode(' - ',$input['timeRange']);
            $startTime = strtotime($timeRange[0]);
            $endTime = strtotime($timeRange[1]);
            if (!$startTime || !$endTime){
                $this->resultModel->setResult('702');
                return $this->resultModel->output();
            }

            $projectEventsModel = new ProjectEventsModel();
            if (!is_null($input['id'])) {
                $projectEventsDetails = $projectEventsModel->getDetailsByProjectEventsIdSimple($input['id']);
                if (!$projectEventsDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $oldImage = $projectEventsDetails['project_events_top_image'];
                $this->logger->appendTitle('update');
            } else {
                $this->logger->appendTitle('create');
                $projectEventsDetails = ['project_id'=>$this->user['project_id']];
            }

            $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
            $ossClient->setConnectTimeout(5);
            if ($input['topImage'] != '' && strpos($input['topImage'], '/') == 0) {
                $objectName = ltrim($input['topImage'], '/');
                $projectEventsDetails['project_events_top_image'] = $objectName;
                try {
                    $ossClient->putObject(self::$DefaultBucket, $objectName,
                        file_get_contents(APP_PATH . 'public/' . $objectName));
                    @unlink(APP_PATH . 'public/' . $objectName);
                } catch (Exception $e) {
                    $this->logger->addMessage('oss upload > project_events_top_image: ' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL);
                    $this->resultModel->setResult('701');
                    return $this->resultModel->output();
                }
            }
            $projectEventsDetails['project_events_map_gid'] = $input['map_gid'];
            $projectEventsDetails['project_events_theme'] = $input['title'];
            $projectEventsDetails['project_events_theme_en'] = $input['title_en'];
            $projectEventsDetails['project_events_content'] = $input['content_cn'];
            $projectEventsDetails['project_events_content_en'] = $input['content_en'];
            $projectEventsDetails['project_events_time_start'] = $startTime;
            $projectEventsDetails['project_events_time_end'] = $endTime;

            $cloneDetails = $projectEventsModel::cloneResult($projectEventsModel, $projectEventsDetails);
            $this->db->begin();
            try {
                $cloneDetails->save();
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                if (isset($objectName)) {
                    try {
                        $ossClient->deleteObject(self::$DefaultBucket, $objectName);
                        $this->logger->addMessage('oss delete tmp events image:' . $objectName, Phalcon\Logger::NOTICE);
                    } catch (Exception $ex) {
                        $this->logger->addMessage('oss delete tmp events image err:' . $ex->getMessage(),
                            Phalcon\Logger::CRITICAL);
                    }
                }
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }

            if (isset($objectName) && !empty($oldImage)) {
                try {
                    $ossClient->deleteObject(self::$DefaultBucket, $oldImage);
                    $this->logger->addMessage('oss delete old events image:' . $oldImage, Phalcon\Logger::NOTICE);
                } catch (Exception $ex) {
                    $this->logger->addMessage('oss delete old events image err:' . $ex->getMessage(),
                        Phalcon\Logger::CRITICAL);
                }
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = array(
            'id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null
            ),
        );
        $filter = new FilterModel ($rules);
        $filter->isValid($this->request->getQuery());

        $input = $filter->getResult();
        $projectEventsModel = new ProjectEventsModel();
        if (!is_null($input['id'])) {
            $projectEventsDetails = $projectEventsModel->getDetailsByProjectEventsIdSimple($input['id']);
            if (!$projectEventsDetails) {
                $this->alert($this->resultModel->getMsg('101'));
            }
            $projectEventsDetails['timeRange'] = date('Y/m/d H:i',$projectEventsDetails['project_events_time_start']).' - '.date('Y/m/d H:i',$projectEventsDetails['project_events_time_end']);
        } else {
            $projectEventsDetails = $projectEventsModel::cloneResult($projectEventsModel, [])->toArray();
            $projectEventsDetails['timeRange'] = '';
        }
        $this->view->projectEventsDetails = $projectEventsDetails;
        $this->tag->appendTitle($this->translate->_('ScenicEventsHandle'));
        $mapPolygonModel = new MapPolygonModel();
        $this->view->mapPolygon = $mapPolygonModel->getListSimple(['project_id' => $this->user['project_id'], 'context' => 'spots']);
    }

    /**
     * MarkActionName(景区活动删除)
     */

    public function eventsdeleteAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'id' => array(
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
            $projectEventsModel = new ProjectEventsModel();
            $projectEventsDetails = $projectEventsModel->getDetailsByProjectEventsIdSimple($input['id']);
            if (!$projectEventsDetails){
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }

            if ($projectEventsDetails['project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $cloneDetails = $projectEventsModel::cloneResult($projectEventsModel, $projectEventsDetails);
            $this->db->begin();
            try {
                $cloneDetails->delete();
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::NOTICE);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($input), Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [ProjectEventsModel::class . 'getList' . $this->user['project_id']]);
                $this->cache->delete(CacheBase::makeTag(ProjectEventsModel::class . 'getDetailsByScenicRouteIdSimple', $input['id']));
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }
}
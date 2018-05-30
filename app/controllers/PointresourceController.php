<?php
use OSS\OssClient;

/**
 * MarkControllerName(轮播图管理)
 */
class PointresourceController extends ControllerBase
{
    /**
     * MarkActionName(轮播图管理|ajaxremovetmpresource|ajaxgetresourcebymappointid|ajaxgetequipmentbymapid)
     */
    public function handleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            set_time_limit(0);
            $rules = array(
                'equipment_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                ),
                'point_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                ),
                'map_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                ),
                'imageSrc' => array(
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
            $equipmentModel = new EquipmentModel();
            $equipmentDetails = $equipmentModel->getDetailsByIdSimple($input['equipment_id']);
            if (!$equipmentDetails){
                $this->resultModel->setResult('104');
                return $this->resultModel->output();
            }
            $imageSrc = rtrim($input['imageSrc'], '|');
            $imageSrc = explode('|', $imageSrc);
            if (empty($imageSrc)) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $updateImages = [];
            $params = [
                'point_resource_image_client' => '',
            ];
            foreach ($imageSrc as $v) {
                if ($v == '') {
                    continue;
                }

                $start = strpos($v, 'upload');
                $objectName = substr($v, $start);
                if (strpos($v, '/') !== 0) {
                    $params['point_resource_image_client'] .= $objectName . ',' . self::SourceOss . '|';
                    continue;
                }
                $updateImages[] = $objectName;
            }
            $pointResourceModel = new PointResourceModel();
            $pointResourceDetails = $pointResourceModel->getDetailsByMapIdAndIdSimple($input['map_id'],
                $input['point_id']);
            if (!$pointResourceDetails) {
                $params['point_id'] = $input['point_id'];
                $params['map_id'] = $input['map_id'];
                $this->logger->appendTitle('create');
            } else {
                $this->logger->appendTitle('update');
            }

            if (!empty($updateImages)) {
                $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                $ossClient->setConnectTimeout(5);
                foreach ($updateImages as $v) {

                    try {
                        $ossClient->putObject(self::$DefaultBucket, $v,
                            file_get_contents(APP_PATH . 'public/' . $v));
                        $params['point_resource_image_client'] .= $v . ',' . self::SourceOss . '|';
                        @unlink(APP_PATH . 'public/' . $v);
                        $this->logger->addMessage('oss upload > image:' . $v,
                            Phalcon\Logger::NOTICE);
                    } catch (Exception $e) {
                        $this->logger->addMessage('oss upload > image err:' . $e->getMessage(),
                            Phalcon\Logger::CRITICAL);
                        $params['point_resource_image_client'] .= $v . ',' . self::SourceLocale . '|';
                    }
                }
            }

            $params['point_resource_image_client'] = rtrim($params['point_resource_image_client'], '|');
            $updateDetails = $pointResourceModel::cloneResult($pointResourceModel,
                ($pointResourceDetails ? $pointResourceDetails : []));

            $this->db->begin();
            try {
                $updateDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode($params),
                    Phalcon\Logger::NOTICE);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                if (!empty($updateImages)) {
                    try {
                        $ossClient->deleteObjects(self::$DefaultBucket, $updateImages);
                    } catch (Exception $e) {
                        $this->logger->addMessage('oss deleteObjects:' . $e->getMessage(),
                            Phalcon\Logger::CRITICAL);
                    }
                }

                $this->resultModel->setResult('102');
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
            $multiRequest->addRequest(new Roger\Request\Request($setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();
            $sendResult = $multiRequest->getResult();
            $this->logger->addMessage('equipment push result:' . $sendResult . ' ' . json_encode($post_data));
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(PointResourceModel::class . 'getDetailsByMapIdAndIdSimple', [
                    $input['map_id'],
                    $input['point_id']
                ]));
            }

            $this->resultModel->setResult('0',$sendResult);
            return $this->resultModel->output();
        }
        $mapModel = new MapModel();
        $map = $mapModel->getListSimple(['project_id' => $this->user['project_id']]);
        $this->view->mapList = $map['data'];

        $projectModel = new ProjectModel();
        $project = $projectModel->getList();
        $this->view->projectList = $project['data'];

        $this->view->pageTitle = $this->translate->_('PointResourceManage');
        $this->tag->appendTitle($this->view->pageTitle);
    }

    public function ajaxremovetmpresourceAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'url' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                )
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $start = strpos($input['url'], 'upload');
            $objectName = substr($input['url'], $start);
            if (preg_match('/' . self::$DefaultBucket . '/', $input['url'])) {
                $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                $ossClient->setConnectTimeout(5);
                try {
                    $ossClient->deleteObject(self::$DefaultBucket, $objectName);
                } catch (Exception $e) {
                    $this->logger->addMessage('oss deleteObject:' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL);
                }
            } else {
                $resourcePath = APP_PATH . 'public/' . $objectName;
                @unlink($resourcePath);
            }
            $this->logger->addMessage('url:' . $input['url']);

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxgetresourcebymappointidAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'point_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'map_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                )
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $pointResourceModel = new PointResourceModel();
            $pointResourceDetails = $pointResourceModel->getDetailsByMapIdAndIdSimple($input['map_id'],
                $input['point_id']);
            $result = ['image' => []];
            if ($pointResourceDetails) {
                $_image = explode('|', $pointResourceDetails['point_resource_image_client']);
                foreach ($_image as $v) {
                    if ($v == '') {
                        continue;
                    }
                    $v = explode(',', $v);
                    if ($v[1] == self::SourceOss) {
                        $result['image'][] = $this->url->getStaticBaseUri() . $v[0];
                    } else {
                        $result['image'][] = '/' . $v[0];
                    }
                }
            }
            $this->resultModel->setResult('0', $result);
            return $this->resultModel->output();
        }
    }

    public function ajaxgetequipmentbymapidAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'map_id' => array(
                    'flag' => FILTER_SANITIZE_STRING,
                    'required',
                ),
            );

            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $equipmentModel = new EquipmentModel();
            $equipment = $equipmentModel->getListSimple([
                'project_id' => $this->user['project_id'],
                'map_id' => $input['map_id']
            ]);
            $result = [];
            foreach ($equipment['data'] as $v) {
                $result[] = [
                    'equipment_id' => $v['equipment_id'],
                    'map_point_name' => $v['map_point_name'],
                    'id' => $v['point_id'],
                    'map_id' => $v['map_id']
                ];
            }
            $this->resultModel->setResult('0', $result);
            return $this->resultModel->output();
        }
    }

}
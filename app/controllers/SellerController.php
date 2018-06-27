<?php
use OSS\OssClient;

/**
 * MarkControllerName(商家管理)
 */
class SellerController extends ControllerBase
{
    /**
     * MarkActionName(商家编辑)
     * @return mixed
     * @throws \OSS\Core\OssException
     */
    public function sellerhandleAction(){
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'seller_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],
                'seller_card_img' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ],
                'seller_shop_name' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],
                'seller_name' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],

                'seller_tel' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required'
                ],

                'goods_category_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],

                'scale_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],
                'seller_status' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 3
                    ],
                ],
                'seller_profile' => [
                    'filter' => FILTER_UNSAFE_RAW,
                    'default' => ''
                ],

            ];
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $SellerModel = new SellerModel();
            $sellerDetails = $SellerModel->getDetailsById($input['seller_id']);
            if(empty($sellerDetails)){
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
            $ossClient->setConnectTimeout(5);
            if ($input['seller_card_img'] != '' && strpos($input['seller_card_img'], '/') == 0) {
                $objectName = ltrim($input['seller_card_img'], '/');
                $input['seller_card_img'] = $objectName;
                try {
                    $ossClient->putObject(self::$DefaultBucket, $objectName,
                        file_get_contents(APP_PATH . 'public/' . $objectName));
                    $input['seller_photo_source'] = self::SourceOss;
                    @unlink(APP_PATH . 'public/' . $objectName);
                } catch (Exception $e) {
                    $this->logger->addMessage('oss upload > seller_card_img: ' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL);
                    $input['seller_photo_source'] = self::SourceLocale;
                }
            }
            $params = [
                'seller_id' => $input['seller_id'],
                'seller_shop_name' => $input['seller_shop_name'],
                'seller_name' => $input['seller_name'],
                'seller_tel' => $input['seller_tel'],
                'seller_profile' => $input['seller_profile'],
                'seller_card_img' => $input['seller_card_img'],
                'goods_category_id' => $input['goods_category_id'],
                'scale_id' => $input['scale_id'],
                'seller_status' => $input['seller_status'],
                'seller_create_at' => time(),
                'seller_photo_source' => $input['seller_photo_source']?$input['seller_photo_source']:$sellerDetails['seller_photo_source'],
            ];
            $this->logger->appendTitle('update');
            $cloneDetails = $SellerModel::cloneResult($SellerModel, $sellerDetails);
            $this->db->begin();

            try {
                $cloneDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($params, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                if($objectName != '' || isset($objectName)){
                    switch ($params['seller_photo_source']) {
                        case self::SourceOss:
                            try {
                                $ossClient->deleteObject(self::$DefaultBucket, $objectName);
                                $this->logger->addMessage(json_encode($params, JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
                            } catch (Exception $ex) {
                                $this->logger->addMessage('oss delete tmp seller photo:' . $ex->getMessage(),
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
                switch ($params['seller_photo_source']) {
                    case self::SourceOss:
                        try {
                            $ossClient->deleteObject(self::$DefaultBucket, $sellerDetails['seller_card_img']);
                            $this->logger->addMessage(json_encode($sellerDetails, JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
                        } catch (Exception $ex) {
                            $this->logger->addMessage('oss delete old seller photo:' . $ex->getMessage(),
                                Phalcon\Logger::CRITICAL);
                        }
                        break;
                    case self::SourceLocale:
                        @unlink(APP_PATH . 'public/' . $sellerDetails['seller_card_img']);
                        break;
                }
            }
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(SellerModel::class . 'getDetailsById',
                    $input['seller_id']));
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $rules = [
            'seller_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
            ],
        ];
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $scaleModel = new ScaleModel();
        $scaleList = $scaleModel->getScaleList($this->user['project_id']);
        $GoodsCategoryModel = new GoodsCategoryModel();
        $GoodsCategoryList = $GoodsCategoryModel->getGoodsCategoryList($this->user['project_id']);
        $SellerModel = new SellerModel();
        $sellerDetails = $SellerModel->getInfoById($input['seller_id']);
        $this->view->details = $sellerDetails;
        $this->view->scaleList = $scaleList;
        $this->view->goodscategoryList = $GoodsCategoryList;
        $this->tag->appendTitle($this->translate->_('账号编辑'));
    }

    /**
     * MarkActionName(商家列表|ajaxchangepassword|recommend)
     * @return mixed
     */
    public function sellerlistAction()
    {

        $rules = [
            'seller_status' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => 0
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
                'default' => 1
            ],

        ];
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $input['project_id'] = $this->user['project_id'];
        $this->tag->appendTitle($this->translate->_('账号列表'));
        $sellerModel = new SellerModel();
        $seller = $sellerModel->getListByStatus($input);
        $this->view->sellerList = $seller['data'];
        $this->view->pageCount = $seller['pageCount'];
        $this->view->status = $input['seller_status'];
    }

    public function recommendAction(){

        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'seller_staff_intro' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => null
                ],

                'seller_shop_img' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => null
                ],

                'seller_id' => [
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
            $sellerModel = new SellerModel();
            $sellerDetails = $sellerModel->getDetailsById($input['seller_id']);
            if (!$sellerDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($sellerDetails['project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if($input['seller_staff_intro'] == ''){
                $params = [
                    'seller_shop_img' => $input['seller_shop_img'],
                    'seller_create_at' => time(),
                ];
            }elseif($input['seller_shop_img'] == ''){
                $params = [
                    'seller_staff_intro' => $input['seller_staff_intro'],
                    'seller_create_at' => time(),
                ];
            }
            $cloneDetails = $sellerModel::cloneResult($sellerModel, $sellerDetails);
            $this->db->begin();
            try {
                $cloneDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode(array_merge($params,$sellerDetails), JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode(array_merge($params,$sellerDetails), JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxchangepasswordAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'seller_id' => [
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
            $sellerModel = new SellerModel();
            $sellerDetails = $sellerModel->getDetailsById($input['seller_id']);
            if (!$sellerDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($sellerDetails['project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $security = new \Phalcon\Security();
            $seller_password = $security->hash('123456');
            $params = [
                'seller_password' => $seller_password,
                'seller_create_at' => time(),
            ];
            $cloneDetails = $sellerModel::cloneResult($sellerModel, $sellerDetails);

            $this->db->begin();
            try {
                $cloneDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode(array_merge($params,$sellerDetails), JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode(array_merge($params,$sellerDetails), JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }


    /**
     * MarkActionName(商家规模)
     * @return mixed
     */
    public function scaleAction(){
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'scale_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ],
                'scale_name' => [
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
                'scale_name' => $input['scale_name'],
                'project_id' => $this->user['project_id'],
                'scale_create_at'=>time()
            ];
            $scaleModel = new ScaleModel();
            if (!empty($input['scale_id'])) {
                $scalebDetails = $scaleModel->getDetailsById($input['scale_id']);
                if (!$scalebDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $this->logger->appendTitle('update');

            } else {
                $this->logger->appendTitle('create');
                $scalebDetails = [];
            }
            $cloneDetails = $scaleModel::cloneResult($scaleModel, $scalebDetails);
            $this->db->begin();
            try {
                $cloneDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($params, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(scaleModel::class . 'getDetailsById',
                    $input['scale_id']));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [scaleModel::class . 'getScaleList']);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $scaleModel = new ScaleModel();
        $scaleList = $scaleModel->getScaleList($this->user['project_id']);
        $scaleListJson = [];
        if(!empty($scaleList)){
            foreach ($scaleList as $v) {
                $scaleListJson [$v ['scale_id']] = $v;
            }
        }
        $this->view->scaleList = $scaleList;
        $this->view->scaleListJson = json_encode($scaleListJson);
        $this->tag->appendTitle($this->translate->_('商家规模'));
    }

    /**
     * MarkActionName(商家类型)
     * @return mixed
     */
    public function goodscategoryAction(){
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'goods_category_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => null
                ],
                'goods_name' => [
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
                'goods_name' => $input['goods_name'],
                'project_id' => $this->user['project_id'],
                'goods_create_at'=>time()
            ];

            $GoodsCategoryModel = new GoodsCategoryModel();
            if (!empty($input['goods_category_id'])) {
                $goodsCategoryDetails = $GoodsCategoryModel->getGoodsCategoryById($input['goods_category_id']);
                if (!$goodsCategoryDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $this->logger->appendTitle('update');

            } else {
                $this->logger->appendTitle('create');
                $goodsCategoryDetails = [];
            }
            $cloneDetails = $GoodsCategoryModel::cloneResult($GoodsCategoryModel, $goodsCategoryDetails);
            $this->db->begin();
            try {
                $cloneDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($params, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(GoodsCategoryModel::class . 'getGoodsCategoryById',
                    $input['goods_category_id']));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG, [scaleModel::class . 'getGoodsCategoryList']);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $GoodsCategoryModel = new GoodsCategoryModel();
        $GoodsCategoryList = $GoodsCategoryModel->getGoodsCategoryList($this->user['project_id']);
        $GoodsCategoryListJson = [];
        if (!empty($GoodsCategoryList)) {
            foreach ($GoodsCategoryList as $v) {
                $GoodsCategoryListJson [$v ['goods_category_id']] = $v;
            }
        }
        $this->view->GoodsCategoryList = $GoodsCategoryList;
        $this->view->GoodsCategoryListJson = json_encode($GoodsCategoryListJson);
        $this->tag->appendTitle($this->translate->_('商家主营类别'));
    }

    /**
     * MarkActionName(商家促销列表 |ajaxchangestatus|ajaxdeletepromotion)
     * @return mixed
     */
    public function promotionlistAction(){
        $rules = [
            'seller_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => null
            ],
            'expire' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1
                ],
                'default' => null
            ],
            'promotion_status' => [
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
                'default' => 1
            ],

        ];
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $input['project_id'] = $this->user['project_id'];
        $this->tag->appendTitle($this->translate->_('促销列表'));
        $promotionModel = new WxPromotionModel();
        $promotion = $promotionModel->getPromotionListByStatus($input);
        $this->view->promotionList = $promotion['data'];
        $this->view->pageCount = $promotion['pageCount'];
        $this->view->filter = $input;
    }

    public function ajaxchangestatusAction(){
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'wx_promotion_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ],
                'promotion_status' => [
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
            $promotionModel = new WxPromotionModel();
            $promotionDetails = $promotionModel->getDetailsById($input['wx_promotion_id']);
            if (!$promotionDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($promotionDetails['project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $params = [
                'wx_promotion_id' => $input['wx_promotion_id'],
                'promotion_status' => $input['promotion_status'],
                'promotion_create_at' => time(),
            ];
            $cloneDetails = $promotionModel::cloneResult($promotionModel, $promotionDetails);
            $this->db->begin();
            try {
                $cloneDetails->save($params);
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxdeletepromotionAction(){
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'wx_promotion_id' => [
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
            $promotionModel = new WxPromotionModel();
            $promotionDetails = $promotionModel->getDetailsById($input['wx_promotion_id']);
            if (!$promotionDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            if ($promotionDetails['project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $params = [
                'wx_promotion_id' => $input['wx_promotion_id'],
            ];
            $cloneDetails = $promotionModel::cloneResult($promotionModel, $promotionDetails);
            $this->db->begin();
            try {
                $cloneDetails->delete($params);
                @unlink(APP_PATH . 'public/' . $promotionDetails['promotion_img']);
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE), Phalcon\Logger::INFO);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage() . ' ' . json_encode($cloneDetails->toArray(), JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

}
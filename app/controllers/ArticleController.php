<?php
use OSS\OssClient;

/**
 * MarkControllerName(文章管理)
 */
class ArticleController extends ControllerBase
{
    /**
     * MarkActionName(文章添加/编辑)
     * @return mixed
     * @throws \OSS\Core\OssException
     */
    public function handleAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'article_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    ),
                    'default' => null,
                ),
                'article_category_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'article_title' => array(
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ),
                'article_icon' => array(
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => ''
                ),
                'article_digest' => array(
                    'filter' => FILTER_UNSAFE_RAW,
                    'default'=>''
                ),
                'article_content' => array(
                    'filter' => FILTER_UNSAFE_RAW,
                    'required'
                ),
                'article_status' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0,
                        'max_range' => 1
                    ),
                    'default' => 1
                ),
                'article_sort_order' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'default' => 0
                ),
                'article_locale' => array(
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
            $articleModel = new ArticleModel();
            if (!empty($input['article_id'])) {
                $articleDetails = $articleModel->clientGetDetailsByArticleIdSimple($input ['article_id']);
                if (!$articleDetails || $articleDetails['project_id'] != $this->user['project_id']) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $input['article_create_at'] = $articleDetails['article_create_at'];
                $input['article_icon_source'] = $articleDetails['article_icon_source'];
            } else {
                $input['article_create_at'] = time();
            }
            $input['project_id'] = $this->user['project_id'];
            $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
            $ossClient->setConnectTimeout(5);
            if ($input['article_icon'] != '' && strpos($input['article_icon'], '/') == 0) {
                $objectName = ltrim($input['article_icon'], '/');
                try {
                    $ossClient->putObject(self::$DefaultBucket, $objectName,
                        file_get_contents(APP_PATH . 'public/' . $objectName));
                    $input['article_icon'] = $objectName;
                    $input['article_icon_source'] = self::SourceOss;
                    @unlink(APP_PATH . 'public/' . $objectName);
                } catch (Exception $e) {
                    $this->logger->addMessage('oss upload > article icon:' . $e->getMessage(),
                        Phalcon\Logger::CRITICAL);
                    $input['article_icon'] = '/' . $objectName;
                    $input['article_icon_source'] = self::SourceLocale;
                }
            }
            if (!empty($input['article_id'])) {
                $this->logger->appendTitle('update');
                $details = $articleDetails;
            } else {
                $this->logger->appendTitle('create');
                $details = [];
            }
            $cloneDetails = $articleModel::cloneResult($articleModel, $details);
            $this->db->begin();

            try {
                if (empty($input['article_id'])) {
                    $articleModel->refreshArticleId();
                }
                $cloneDetails->save($input);
                $this->db->commit();
                $this->logger->addMessage(json_encode($cloneDetails->toArray()), Phalcon\Logger::CRITICAL);
            } catch (Exception $e) {
                $this->db->rollback();
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(ArticleModel::class . 'clientGetDetailsByArticleIdSimple',
                    $articleModel->article_id));
            }
            if (isset($articleDetails) && isset($objectName)) {
                switch ($articleDetails['article_icon_source']) {
                    case self::SourceOss:
                        try {
                            $ossClient->deleteObject(self::$DefaultBucket, $articleDetails['article_icon']);
                        } catch (Exception $e) {
                            $this->logger->addMessage('oss delete old > article icon:' . $e->getMessage(),
                                Phalcon\Logger::CRITICAL);
                        }
                        break;
                    case self::SourceLocale:
                        @unlink(APP_PATH . 'public' . $articleDetails['article_icon']);
                        break;
                }

            }
            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [ArticleModel::class . 'getList' . $this->user['project_id']]);
            }

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }

        $this->tag->appendTitle($this->translate->_("ArticleHandle"));
        $rules = array(
            'article_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null,
            ),'article_category_id' => array(
                'filter' => FILTER_VALIDATE_INT,
                'options' => array(
                    'min_range' => 1
                ),
                'default' => null,
            )
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            $this->alert($this->resultModel->getMsg('101'));
        }
        $input = $filter->getResult();
        $articleModel = new ArticleModel();
        if (!is_null($input['article_id'])) {
            $articleDetails = $articleModel->clientGetDetailsByArticleIdSimple($input ['article_id']);
            if (!$articleDetails) {
                $this->alert($this->resultModel->getMsg('-1'));
            }

        }else{
            $articleDetails = $articleModel::cloneResult($articleModel , [])->toArray();
            if (!is_null($input['article_category_id'])){
                $articleDetails['article_category_id'] = $input['article_category_id'];
            }
        }
        $this->view->details = $articleDetails;
        $projectModel = new ProjectModel();
        $projectList = $projectModel->getList();
        $categoryModel = new ArticleCategoryModel();
        $tree = new TreeModel ();
        $tree->setTree($categoryModel->getList(null, $this->user['project_id'],1), 'article_category_id', 'pid', 'name');
        $this->view->projectlist = $projectList;
        $this->view->options = $tree->getOptions();
        $this->view->filter = $input;
    }

    /**
     * MarkActionName(文章列表|ajaxdelete)
     * @return mixed
     */

    public function listAction()
    {
        $this->tag->appendTitle($this->translate->_('ArticleList'));
        $rules = array(
            'article_category_id' => array(
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
        $categoryModel = new ArticleCategoryModel();
        if (!empty($input['article_category_id'])) {
            $categoryDetails = $categoryModel->getDetailsById($input['article_category_id']);
            if ($categoryDetails) $this->view->categoryDetails  = $categoryDetails->toArray();
        }
        $tree = new TreeModel ();
        $tree->setTree($categoryModel->getList(null, $this->user['project_id']), 'article_category_id', 'pid', 'name');
        $articleModel = new ArticleModel();
        $input['project_id'] = $this->user['project_id'];
        $article = $articleModel->clientGetListSimple($input);
        $this->view->list = $article['data'];
        $this->view->pageCount = $article['pageCount'];
        $this->view->articleCategory = $tree->getOptions();
        $this->view->filter = $input;
    }

    /**
     * MarkActionName(文章分类)
     * @return mixed
     */

    public function categoryAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'article_category_id' => array(
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
                'sort_order' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0
                    ),
                    'default' => 0
                )
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $params = $input;
            $params['project_id'] = $this->user['project_id'];
            unset($params['article_category_id']);

            $categoryModel = new ArticleCategoryModel();
            if (!empty($input['article_category_id'])) {
                $list = $categoryModel->getList(null, $this->user['project_id']);
                $tree = new TreeModel ();
                $tree->setTree($list, 'article_category_id', 'pid', 'name');
                $childs = $tree->getChilds($input['article_category_id']);
                if ($input['pid'] == $input['article_category_id'] || in_array($input['pid'], $childs)) {
                    $this->resultModel->setResult('502');
                    return $this->resultModel->output();
                }
                $categoryDetails = $categoryModel->getDetailsById($input['article_category_id']);
                if (!$categoryDetails) {
                    $this->resultModel->setResult('-1');
                    return $this->resultModel->output();
                }
                $this->logger->appendTitle('update');
                $oldCategoryDetails = $categoryDetails->toArray();
                try {
                    $categoryDetails->update($params);
                } catch (Exception $e) {
                    $this->logger->addMessage($this->logger->parsePhalconErrMsg($categoryDetails->getMessages()),
                        Phalcon\Logger::CRITICAL);

                    $this->resultModel->setResult('102');
                    return $this->resultModel->output();
                }

                $this->logger->addMessage(json_encode($categoryDetails->toArray(),
                        JSON_UNESCAPED_UNICODE) . ' Old:' . json_encode($oldCategoryDetails, JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::NOTICE);
                if (CACHING) {
                    $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                        [
                            ArticleModel::class . 'getList' . $categoryDetails->article_category_id,
                            ArticleModel::class . 'getList' . $this->user['project_id']
                        ]);
                }
            } else {
                $this->logger->appendTitle('create');
                try {
                    $categoryModel->create($params);
                } catch (Exception $e) {
                    if ($e->getCode() == '23505') {
                        $categoryModel->refreshArticleCategoryId();
                        try {
                            $categoryModel->create($params);
                        } catch (Exception $e) {
                            $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);

                            $this->resultModel->setResult('102');
                            return $this->resultModel->output();
                        }
                    } else {
                        $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);

                        $this->resultModel->setResult('102');
                        return $this->resultModel->output();
                    }
                }

                $this->logger->addMessage(json_encode($categoryModel->toArray(), JSON_UNESCAPED_UNICODE),
                    Phalcon\Logger::NOTICE);
            }

            if (CACHING) {
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [ArticleCategoryModel::class . 'getList' . $this->user['project_id']]);
            }
            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
        $this->tag->appendTitle($this->translate->_('ArticleCategory'));
        $categoryModel = new ArticleCategoryModel();
        $list = $categoryModel->getList(null, $this->user['project_id']);
        $tree = new TreeModel ();
        $tree->setTree($list, 'article_category_id', 'pid', 'name');
        $this->view->options = $tree->getOptions();
        $cateJson = [];
        if (!empty ($list)) {
            foreach ($list as $v) {
                $cateJson [$v ['article_category_id']] = $v;
            }
        }
        $this->view->cates = json_encode($cateJson, JSON_UNESCAPED_UNICODE);
    }

    public function ajaxdeleteAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'article_id' => array(
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
            $articleModel = new ArticleModel();
            $articleDetails = $articleModel->getDetailsById($input ['article_id']);
            if (!$articleDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            try {
                $articleDetails->delete();
            } catch (Exception $e) {
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);

                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (!empty($articleDetails->article_icon)) {
                $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                $ossClient->setConnectTimeout(5);
                switch ($articleDetails->article_icon_source) {
                    case self::SourceOss:
                        try {
                            $ossClient->deleteObject(self::$DefaultBucket, $articleDetails->article_icon);
                        } catch (Exception $e) {
                            $this->logger->addMessage('oss delete > article icon: ' . $e->getMessage(),
                                Phalcon\Logger::CRITICAL);
                        }
                        break;
                    case self::SourceLocale:
                        @unlink(APP_PATH . 'public' . $articleDetails->article_icon);
                        break;
                }
            }
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(ArticleModel::class . 'clientGetDetailsByArticleIdSimple',
                    $articleDetails->article_id));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [ArticleModel::class . 'getList' . $this->user['project_id']]);
            }
            $this->logger->addMessage(json_encode($articleDetails->toArray(), JSON_UNESCAPED_UNICODE),
                Phalcon\Logger::NOTICE);

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }
}
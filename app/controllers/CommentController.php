<?php

/**
 * MarkControllerName(评论管理)
 */
class CommentController extends ControllerBase
{
    /**
     * MarkActionName(评论列表|ajaxdelete|ajaxchangestatus)
     * @return mixed
     */

    public function listAction()
    {
        $this->tag->appendTitle($this->translate->_('ArticleList'));
        $rules = array(
            'comment_obj' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'default' => ''
            ),
            'comment_score' => array(
                'filter' => FILTER_VALIDATE_INT,
                'default' => ''
            ),
            'keywords' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'default' => ''
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
        $typeModel = new CommentTypeModel();
        $typeList = $typeModel->getListSimple();
        if($this->user['projectDetails']['project_modules_client_modules']){
            $modules = explode(',', $this->user['projectDetails']['project_modules_client_modules']);
            foreach ($typeList as $k=>$v){
                if($k == 'polygon'){
                    continue;
                }elseif(in_array($k,$modules) === false){
                    unset($typeList[$k]);
                }
            }
        }

        if(!empty($input['comment_obj']) && array_key_exists($input['comment_obj'],$typeList) !== false){
            $input['project_id'] = $this->user['project_id'];
            $commentModel = new CommentModel();
            $CommentList = $commentModel->getList($input);

            $this->view->list = $CommentList['data'];
            $this->view->pageCount = $CommentList['pageCount'];
        }

        $this->view->typeList = $typeList;
        $this->view->filter = $input;
    }

    /**
     * MarkActionName(评论分类)
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
                'comment_id' => array(
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
            $CommentModel = new CommentModel();
            $commentDetails = $CommentModel->getDetailsById($input ['comment_id']);
            if (!$commentDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            try {
                $commentDetails->delete();
            } catch (Exception $e) {
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(commentModel::class . 'clientGetDetailsBycommentIdSimple',
                    $commentDetails->comment_id));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [commentModel::class . 'getList' . $this->user['project_id']]);
            }
            $this->logger->addMessage(json_encode($commentDetails->toArray(), JSON_UNESCAPED_UNICODE),
                Phalcon\Logger::NOTICE);

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }

    public function ajaxchangestatusAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                'comment_id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 1
                    )
                ),
                'comment_status' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array(
                        'min_range' => 0,
                        'max_range' => 1
                    )
                )
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $CommentModel = new CommentModel();
            $commentDetails = $CommentModel->getDetailsById($input ['comment_id']);
            if (!$commentDetails) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $params = [
                'comment_id' => $input['comment_id'],
                'comment_status' => $input['comment_status'] == 1 ? 0 : 1
            ];
            try {
                $commentDetails->update($params);
            } catch (Exception $e) {
                $this->logger->addMessage($e->getMessage(), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
            if (CACHING) {
                $this->cache->delete(CacheBase::makeTag(commentModel::class . 'getDetailsById',
                    $commentDetails->comment_id));
                $this->cache->clean(CacheBase::CLEANING_MODE_TAG,
                    [commentModel::class . 'getList' . $this->user['project_id']]);
            }
            $this->logger->addMessage(json_encode($commentDetails->toArray(), JSON_UNESCAPED_UNICODE),
                Phalcon\Logger::NOTICE);

            $this->resultModel->setResult('0');
            return $this->resultModel->output();
        }
    }
}
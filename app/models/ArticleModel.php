<?php

class ArticleModel extends ModelBase
{
    public function getDetailsById($articleId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $articleId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'article_id = ?1',
                    'bind' => array(1 => $articleId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getList(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $bindParams = array();
            if (!empty($params ['article_category_id'])) {
                $where .= ' WHERE a.article_category_id=?';
                $bindParams [] = $params ['article_category_id'];
            }
            if (!empty ($params ['keywords'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_title LIKE ?';
                $bindParams [] = '%' . $params ['keywords'] . '%';
            }
            if (!empty($params ['status'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_status=?';
                $bindParams [] = $params ['status'];
            }

            if (!empty($params ['project_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['locale'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_locale=?';
                $bindParams [] = $params ['locale'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $offset = $offset < 0 ? 0 : $offset;
                $limit = ' limit ' . $params ['psize'] . ' offset ' . $offset;
            }
            $sql = 'SELECT a.*,ac.name as cate_name FROM ' . DB_PREFIX . 'article as a LEFT JOIN ' . DB_PREFIX . 'article_category as ac ON a.article_category_id=ac.article_category_id' . $where . ' order by a.article_sort_order DESC,a.article_id DESC' . $limit;
//                        echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
//                       var_dump($result);exit;
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['article_category_id']
                ));
            }
        }
        return $result;
    }

    public function getListSimple($params)
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $bindParams = array();
            if (!empty($params ['article_category_id'])) {
                $where .= ' WHERE a.article_category_id=?';
                $bindParams [] = $params ['article_category_id'];
            }
            if (!empty($params ['article_locale'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_locale=?';
                $bindParams [] = $params ['article_locale'];
            }

            $sql = 'SELECT a.article_id,a.article_title,a.article_icon,a.article_digest,a.article_create_at,ac.name as cate_name FROM ' . DB_PREFIX . 'article as a LEFT JOIN ' . DB_PREFIX . 'article_category as ac ON a.article_category_id=ac.article_category_id' . $where . ' order by a.article_sort_order DESC,a.article_id DESC';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
//                       var_dump($result);exit;
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['article_category_id']
                ));
            }
        }
        return $result;
    }

    //川大根据学院名字 获得详细信息

    public function getDetailsByArticle($article_title)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByArticle', $article_title);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'article_title = ?1',
                    'bind' => array(1 => $article_title)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function clientGetListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $bindParams = array();
            if (!empty($params ['article_category_id'])) {
                $where .= ' WHERE a.article_category_id=?';
                $bindParams [] = $params ['article_category_id'];
            }
            if (!empty ($params ['keywords'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_title LIKE ?';
                $bindParams [] = '%' . $params ['keywords'] . '%';
            }
            if (!empty($params ['status'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_status=?';
                $bindParams [] = $params ['status'];
            }

            if (!empty($params ['project_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['locale'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_locale=?';
                $bindParams [] = $params ['locale'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(a.article_id) FROM ' . DB_PREFIX . 'article as a' . $where;
                $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sqlCount, $bindParams));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = ceil($count / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT a.*,ac.name as cate_name FROM ' . DB_PREFIX . 'article as a LEFT JOIN ' . DB_PREFIX . 'article_category as ac ON a.article_category_id=ac.article_category_id' . $where . ' order by a.article_sort_order DESC,a.article_id DESC' . $limit;
//                        echo $sql;die;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList' . @$params ['project_id'],
                    self::class . 'getList' . @$params ['article_category_id'],
                ));
            }
        }
        return $result;
    }

    public function clientGetDetailsByArticleIdSimple($articleId)
    {
        $tag = CacheBase::makeTag(self::class . 'clientGetDetailsByArticleIdSimple', $articleId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT a.*,ac.name as cate_name,ac.project_id FROM ' . DB_PREFIX . 'article as a LEFT JOIN ' . DB_PREFIX . 'article_category as ac ON a.article_category_id=ac.article_category_id WHERE a.article_id=?';
//                        echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$articleId]));
            $result = $result->valid() ? $result->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function refreshArticleId()
    {
        $this->db->query("select setval('n_article_article_id_seq' , (select max(article_id) from " . DB_PREFIX . "article))");
    }

    public function getSource()
    {
        return DB_PREFIX . 'article';
    }
}
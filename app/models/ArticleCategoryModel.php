<?php

class ArticleCategoryModel extends ModelBase
{
    public function getDetailsById($category_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $category_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst([
                'conditions' => 'article_category_id = ?1',
                'bind' => [1 => $category_id],
            ]);
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getList($pid = null, $project_id = null, $status = null)
    {
        $tag = CacheBase::makeTag(self::class . 'getList', [$pid, $project_id, $status]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = array();
            if (!is_null($pid)) {
                $where .= ' WHERE pid=?';
                $bindParams [] = $pid;
            }

            if (!is_null($project_id)) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' project_id=?';
                $bindParams [] = $project_id;
            }
            if (!is_null($status)) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' status=?';
                $bindParams [] = $status;
            }

            $sql = 'SELECT * FROM ' . DB_PREFIX . 'article_category' . $where . ' ORDER BY sort_order DESC,article_category_id ASC';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList' . $project_id
                ));
            }
        }
        return $result;
    }

    public function getDetailsByCategoryIdSimple($categoryId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByCategoryIdSimple', $categoryId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . $this->getSource() . ' WHERE article_category_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $categoryId
                )));
            if ($result->valid()) {
                $result = $result->toArray()[0];
            } else {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function refreshArticleCategoryId()
    {
        $this->db->query("select setval('n_article_category_article_category_id_seq' , (select max(article_category_id) from " . DB_PREFIX . "article_category))");
    }

    public function getSource()
    {
        return DB_PREFIX . 'article_category';
    }
}
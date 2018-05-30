<?php

class ScenicRouteCategoryModel extends ModelBase
{

    public function initialize()
    {
    }

    /**
     * 查询面及对应的地图、项目详情
     * @param $mapGid
     * @return bool|array
     */
    public function getDetailsByScenicRouteCategoryIdSimple($categoryId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByScenicRouteCategoryIdSimple', $categoryId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . $this->getSource() . ' WHERE scenic_route_category_id=? limit 1';
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

    /**
     * @param array $params
     * @return array
     */
    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = [];
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            $sql = 'SELECT * FROM ' . $this->getSource() . ' ' . $where.' order by scenic_route_category_sort_order DESC';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params ['project_id'],
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'scenic_route_category';
    }
}
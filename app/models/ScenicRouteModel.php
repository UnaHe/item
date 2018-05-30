<?php

class ScenicRouteModel extends ModelBase
{

    public function initialize()
    {
    }

    /**
     * 查询面及对应的地图、项目详情
     * @param $mapGid
     * @return bool|array
     */
    public function getDetailsByScenicRouteIdSimple($routeId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByScenicRouteIdSimple', $routeId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT src.*,sr.*,p.project_id,p.project_name FROM ' . $this->getSource() . ' as sr LEFT JOIN ' . DB_PREFIX . 'scenic_route_category as src ON sr.scenic_route_category_id=src.scenic_route_category_id LEFT JOIN ' . DB_PREFIX . 'project as p ON p.project_id=src.project_id WHERE scenic_route_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $routeId
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
            $where = $limit = '';
            $bindParams = [];
            $pageCount = 1;
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE src.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (!empty($params ['category_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.scenic_route_category_id=?';
                $bindParams [] = $params ['category_id'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(sr.*) FROM ' . $this->getSource() . ' as sr LEFT JOIN ' . DB_PREFIX . 'scenic_route_category as src ON sr.scenic_route_category_id=src.scenic_route_category_id' . $where;
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
            $sql = 'SELECT src.*,sr.* FROM ' . $this->getSource() . ' as sr LEFT JOIN ' . DB_PREFIX . 'scenic_route_category as src ON sr.scenic_route_category_id=src.scenic_route_category_id' . $where.$limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
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
        return DB_PREFIX . 'scenic_route';
    }
}
<?php

class MapModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetails($map_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetails' , $map_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT m.*,p.project_name,p.project_id FROM ' . DB_PREFIX . 'map as m LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id WHERE map_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, array(
                $map_id
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

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by m.map_sort_order DESC,m.map_id ASC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE m.project_id=?';
                $bindParams [] = $params ['project_id'];
                $countWhere .= 'project_id=' . $params['project_id'];
            }
            if (isset($params ['map_pid']) && !is_null($params ['map_pid'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' m.map_pid=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'map_pid=' . $params['map_pid'] . '';
                $bindParams [] = $params ['map_pid'];
            }
            if (isset($params ['map_level']) && !is_null($params ['map_level'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' m.map_level=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'map_level=' . $params['map_level'] . '';
                $bindParams [] = $params ['map_level'];
            }
            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by '.$params ['orderBy'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($countWhere) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT m.*,p.project_name FROM ' . DB_PREFIX . 'map as m LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList' . @$params ['project_id']
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'map';
    }
}
<?php

class MapPointPanoramaModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetailsByIdAndMapIdSimple($id, $map_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdAndMapIdSimple', [$id, $map_id]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT mpp.* FROM ' . DB_PREFIX . 'map_point_panorama as mpp LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (mpp.id=mp.id AND mpp.map_id=mp.map_id) WHERE mpp.id=? AND mpp.map_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [
                    $id,
                    $map_id
                ]));
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
            $where = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by mpp.map_point_panorama_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['map_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mpp.map_id=?';
                $bindParams [] = $params ['map_id'];
            }
            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(mpp.map_point_panorama_id) FROM ' . DB_PREFIX . 'map_point_panorama as mpp LEFT JOIN ' . DB_PREFIX . 'map as m ON mpp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id' . $where;
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
            $sql = 'SELECT mpp.*,mp.name FROM ' . DB_PREFIX . 'map_point_panorama as mpp LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (mpp.id=mp.id AND mpp.map_id=mp.map_id) LEFT JOIN ' . DB_PREFIX . 'map as m ON mpp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
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
        return DB_PREFIX . 'map_point_panorama';
    }
}
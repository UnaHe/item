<?php

class PointResourceModel extends ModelBase
{
    /**
     * @param $mapId
     * @param $Id
     * @return array|bool
     */
    public function getDetailsByMapIdAndIdSimple($mapId, $Id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapIdAndIdSimple', [$mapId, $Id]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT pr.*,m.map_name,mp.name as map_point_name FROM ' . DB_PREFIX . 'point_resource as pr LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pr.map_point_id=mp.map_point_id LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id WHERE pr.map_id=? AND pr.point_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$mapId, $Id]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByIdSimple($pointResourceId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdSimple', $pointResourceId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT pr.*,m.map_name,mp.name as map_point_name FROM ' . DB_PREFIX . 'point_resource as pr LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pr.map_point_id=mp.map_point_id LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id WHERE pr.point_resource_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$pointResourceId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByMapPointIdSimple($mapPointId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapPointIdSimple', $mapPointId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT pr.*,m.map_name,mp.name as map_point_name FROM ' . DB_PREFIX . 'point_resource as pr LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pr.map_point_id=mp.map_point_id LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id WHERE pr.map_point_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$mapPointId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
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
            $orderBy = ' order by pr.point_resource_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(pr.point_resource_id) FROM ' . DB_PREFIX . 'point_resource as pr LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pr.map_id=mp.map_id AND pr.id=mp.id LEFT JOIN ' . DB_PREFIX . 'map as m ON pr.map_id=m.map_id' . $where;
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
            $sql = 'SELECT pr.*,m.map_name,mp.name as map_point_name FROM ' . DB_PREFIX . 'point_resource as pr LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pr.map_id=mp.map_id AND pr.id=mp.id LEFT JOIN ' . DB_PREFIX . 'map as m ON pr.map_id=m.map_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id'],
                ));
            }
        }
        return $result;
    }

    public function refreshPrimaryId()
    {
        $this->db->query("select setval('n_point_resource_point_resource_id_seq' , (select max(point_resource_id) from " . $this->getSource() . "))");
    }

    public function getSource()
    {
        return DB_PREFIX . 'point_resource';
    }
}
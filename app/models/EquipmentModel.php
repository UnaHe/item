<?php

class EquipmentModel extends ModelBase
{

    const TYPE_INFRARED = 'infrared';
    /**
     * @param $equipmentId
     * @return bool | array
     */
    public function getDetailsById($equipmentId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $equipmentId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as e LEFT JOIN '.DB_PREFIX.'equipment_infrared as ei ON e.equipment_id=ei.equipment_id WHERE e.equipment_id=?';
            $sql = sprintf($sqlTemplate, "ei.*,e.*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$equipmentId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }
    public function getDetailsByCode($code)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByCode', $code);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'equipment_code = ?1',
                    'bind' => array(1 => $code)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByCodeSimple($code)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByCodeSimple', $code);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT e.*,m.project_id,mp.*,m.map_name FROM ' . DB_PREFIX . 'equipment as e LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (e.equipment_point_id=mp.id AND e.equipment_map_id=mp.map_id) LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id WHERE e.equipment_code=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$code]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $equipmentId
     * @return bool|array
     */

    public function getDetailsByIdSimple($equipmentId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdSimple', $equipmentId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT e.*,m.project_id,mp.*,m.map_name FROM ' . DB_PREFIX . 'equipment as e LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (e.equipment_point_id=mp.id AND e.equipment_map_id=mp.map_id) LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id WHERE e.equipment_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$equipmentId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
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

    public function getList(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as e LEFT JOIN '.DB_PREFIX.'equipment_infrared as ei ON e.equipment_id=ei.equipment_id';
            $bindParams = [];
            $pageCount = 1;
            $orderBy = ' order by e.equipment_id DESC';

            if (!empty($params ['project_id'])) {
                $where .= ' WHERE e.equipment_project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['type'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' e.equipment_type=?';
                $bindParams [] = $params ['type'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $res = $this->sqlLimit($sqlTemplate , 'COUNT(e.equipment_id)' , $where , $bindParams, $params['page'],$params['psize']);
                $limit = $res['limit'];
                $pageCount = $res['pageCount'];
            }
            $sql = sprintf($sqlTemplate, "ei.*,e.*") . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, [
                    self::class . 'getList',
                ]);
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
            $orderBy = ' order by e.equipment_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['e_project_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' e.equipment_project_id=?';
                $bindParams [] = $params ['e_project_id'];
            }

            if (!empty($params ['id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' e.equipment_point_id=?';
                $bindParams [] = $params ['map_id'];
            }

            if (!empty($params ['map_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' e.equipment_map_id=?';
                $bindParams [] = $params ['map_id'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(e.equipment_id) FROM ' . DB_PREFIX . 'equipment as e LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (e.equipment_point_id=mp.id AND e.equipment_map_id=mp.map_id) LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id' . $where;
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
            $sql = 'SELECT e.*,mp.name as map_point_name,mp.map_id,mp.point,p.project_name,p.project_id,m.map_name,mp.id as point_id FROM ' . DB_PREFIX . 'equipment as e LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (e.equipment_point_id=mp.id AND e.equipment_map_id=mp.map_id) LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id' . $where . $orderBy . $limit;
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

    public function getDetailsByIdAndMapIdSimple($id, $mapId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdAndMapIdSimple', [$id, $mapId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT e.*,m.project_id,mp.*,m.map_name FROM ' . DB_PREFIX . 'equipment as e LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (e.equipment_point_id=mp.id AND e.equipment_map_id=mp.map_id) LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id WHERE e.equipment_point_id=? AND e.equipment_map_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$id, $mapId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getCountSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getCountSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = [];
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            $sqlCount = 'SELECT count(e.equipment_id) FROM ' . DB_PREFIX . 'equipment as e LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (e.equipment_point_id=mp.id AND e.equipment_map_id=mp.map_id) LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id' . $where;
            $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sqlCount, $bindParams));
            $result = $countRes->valid() ? $countRes->toArray()[0]['count'] : 0;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id'],
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'equipment';
    }
}
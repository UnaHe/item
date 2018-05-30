<?php

class IbeaconModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetailsByIdSimple($ibeaconId)
    {
        $tag = self::makeTag(self::class . 'getDetailsByIdSimple', $ibeaconId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT i.*,iw.*,mp.name as map_point_name,mp.map_id,mp.map_point_id,mp.point,m.map_name,p.project_id,p.project_name,ig.ibeacon_group_name,mpp.map_point_panorama FROM ' . DB_PREFIX . 'ibeacon as i LEFT JOIN ' . DB_PREFIX . 'equipment as e ON i.equipment_id=e.equipment_id LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON ((i.equipment_id IS NOT NULL AND mp.id=e.equipment_point_id AND mp.map_id=e.equipment_map_id) OR (i.equipment_id IS NULL AND i.ibeacon_point_id=mp.id AND i.ibeacon_map_id=mp.map_id)) LEFT JOIN '.DB_PREFIX.'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id LEFT JOIN '.DB_PREFIX.'map as m ON mp.map_id=m.map_id LEFT JOIN '.DB_PREFIX.'project as p ON m.project_id=p.project_id LEFT JOIN '.DB_PREFIX.'ibeacon_wx as iw ON iw.ibeacon_wx_device_id=i.ibeacon_wx_device_id LEFT JOIN '.DB_PREFIX.'ibeacon_group as ig ON ig.ibeacon_group_id=iw.ibeacon_group_id LEFT JOIN '.DB_PREFIX.'map_point_panorama as mpp ON (mp.id=mpp.id AND mp.map_id=mpp.map_id) WHERE i.ibeacon_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$ibeaconId]));
            $result = $result->valid()?$result->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByMajorAndMinorId($major, $minor)
    {
        $tag = self::makeTag(self::class . 'getDetailsByMajorAndMinorId', [$major, $minor]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'major = ?1 AND minor=?2',
                    'bind' => array(1 => $major, 2 => $minor)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getListSimple(array $params = [])
    {
        $tag = self::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $pageCount = 1;
            $order = 'i.ibeacon_id ASC';
            $bindParams = array();
            if (isset ($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE ig.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset ($params ['map_id']) && $params ['map_id'] > 0) {
                $where .= (empty ($where) ? ' WHERE' : ' AND ') . ' mp.map_id=?';
                $bindParams [] = $params ['map_id'];
            }

            if (isset ($params ['group_id']) && $params ['group_id'] > 0) {
                $where .= (empty ($where) ? ' WHERE' : ' AND ') . ' iw.ibeacon_group_id=?';
                $bindParams [] = $params ['group_id'];
            }

            if (isset ($params ['datatype']) && $params ['datatype'] != '') {
                $where .= (empty ($where) ? ' WHERE' : ' AND ') . ' md.map_data_type=?';
                $bindParams [] = $params ['datatype'];
            }
            if (isset ($params ['orderby']) && $params ['orderby'] != '') {
                $order = $params ['orderby'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(i.ibeacon_id) FROM ' . DB_PREFIX . 'ibeacon as i LEFT JOIN ' . DB_PREFIX . 'equipment as e ON i.equipment_id=e.equipment_id LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON ((i.equipment_id IS NOT NULL AND mp.id=e.equipment_point_id AND mp.map_id=e.equipment_map_id) OR (i.equipment_id IS NULL AND i.ibeacon_point_id=mp.id AND i.ibeacon_map_id=mp.map_id)) LEFT JOIN '.DB_PREFIX.'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id LEFT JOIN '.DB_PREFIX.'map as m ON mp.map_id=m.map_id LEFT JOIN '.DB_PREFIX.'project as p ON m.project_id=p.project_id LEFT JOIN '.DB_PREFIX.'ibeacon_wx as iw ON iw.ibeacon_wx_device_id=i.ibeacon_wx_device_id' . $where;
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
            $sql = 'SELECT i.*,mpp.map_point_panorama,mp.name as map_point_name,mp.name_en as map_point_name_en,mp.map_id,mp.map_point_id,mp.point,mp.id as point_id,mp.geom,m.map_name,p.project_name,ig.ibeacon_group_name,iw.* FROM ' . DB_PREFIX . 'ibeacon as i LEFT JOIN ' . DB_PREFIX . 'equipment as e ON i.equipment_id=e.equipment_id LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON ((i.equipment_id IS NOT NULL AND mp.id=e.equipment_point_id AND mp.map_id=e.equipment_map_id) OR (i.equipment_id IS NULL AND i.ibeacon_point_id=mp.id AND i.ibeacon_map_id=mp.map_id)) LEFT JOIN '.DB_PREFIX.'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id LEFT JOIN '.DB_PREFIX.'map as m ON mp.map_id=m.map_id LEFT JOIN '.DB_PREFIX.'project as p ON m.project_id=p.project_id LEFT JOIN '.DB_PREFIX.'ibeacon_wx as iw ON iw.ibeacon_wx_device_id=i.ibeacon_wx_device_id LEFT JOIN '.DB_PREFIX.'ibeacon_group as ig ON ig.ibeacon_group_id=iw.ibeacon_group_id LEFT JOIN '.DB_PREFIX.'map_point_panorama as mpp ON (mp.id=mpp.id AND mp.map_id=mpp.map_id)' . $where . ' order by ' . $order . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList'.@$params['project_id'],
                    self::class . 'getList'.@$params['group_id'],
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'ibeacon';
    }
}
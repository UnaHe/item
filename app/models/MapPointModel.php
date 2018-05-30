<?php

class MapPointModel extends ModelBase
{
    public function initialize()
    {
    }

    public function reset()
    {
        unset($this->map_point_id);
    }

    /**根据mapPointId点位主键查询点位详情
     * @param $mapPointId 点位主键 int
     * @return 点位信息，地图名称及英文名称，对应面唯一编码，面主键，点位对应项目详情，全景图信息
     */

    public function getDetailsByMapPointIdSimple($mapPointId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapPointIdSimple', $mapPointId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT mp.*,m.map_name,nmp.map_gid,m.map_name_en,nmp.map_polygon_id,p.*,mpp.map_point_panorama,mpp.map_point_panorama_id FROM ' . DB_PREFIX . 'map_point as mp LEFT JOIN n_map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id LEFT JOIN '.DB_PREFIX.'map_point_panorama as mpp ON (mp.id=mpp.id AND mp.map_id=mpp.map_id) WHERE mp.map_point_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $mapPointId
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
     * 根据gid, map_id, id 等字段搜索点位详情
     * @param array $params
     * @return 点位详情，同getDetailsByMapPointIdSimple
     */

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;

            //gid
            if (isset($params ['gid']) && !is_null($params ['gid'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.gid=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'gid="' . $params['gid'] . '"';
                $bindParams [] = $params ['gid'];
            }

            //map_id
            if (isset($params ['map_id']) && !is_null($params ['map_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.map_id=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'map_id="' . $params['map_id'] . '"';
                $bindParams [] = $params ['map_id'];
            }

            //id
            if (isset($params ['id']) && !is_null($params ['id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.id=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'id="' . $params['id'] . '"';
                $bindParams [] = $params ['id'];
            }

            //type
            if (isset($params ['type']) && !empty($params ['type'])) {
                $type = '';
                foreach ($params['type'] as $v) {
                    $type .= "'" . $v . "',";
                }
                $type = rtrim($type, ',');
                $where .= (empty($where) ? ' WHERE' : ' AND') . " mp.type in (" . $type . ")";
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . " type in (" . $type . ")";
            }

            //当name为空时
            if (isset($params ['nameIsEmpty'])) {
                if ($params ['nameIsEmpty'] === 0) {
                    $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.name IS NOT NULL';
                    $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'name IS NOT NULL';
                } elseif ($params ['nameIsEmpty'] === 1) {
                    $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.name IS NULL';
                    $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'name IS NULL';
                }

            }

            //name
            if (isset($params ['name']) && !empty($params ['name'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " mp.name LIKE '%" . $params ['name'] . "%'";
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . " name LIKE '%" . $params ['name'] . "%'";
            }

            //keywords
            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $intKeywords = (int)$params ['keywords'];
                $whereExtra = $countWhereExtra = '';
                if ($intKeywords !== 0) {
                    $whereExtra = " OR mp.id='" . $intKeywords . "' OR mp.gid='" . $intKeywords . "'";
                    $countWhereExtra = " OR id='" . $intKeywords . "' OR gid='" . $intKeywords . "'";
                }

                $where .= (empty($where) ? ' WHERE' : ' AND') . " (mp.name LIKE '%" . $params ['keywords'] . "%'" . $whereExtra . ")";
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . " (name LIKE '%" . $params ['keywords'] . "%'" . $countWhereExtra . ")";
            }

            //label
            if (isset($params ['label']) && !empty($params ['label'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.label=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'label="' . $params['label'] . '"';
                $bindParams [] = $params ['label'];
            }

            //guardTarget
            if (isset($params ['guardTarget'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.guard_target=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'guard_target="' . $params['guardTarget'] . '"';
                $bindParams [] = $params ['guardTarget'];
            }

            //project_id
            if (isset($params ['project_id']) && !empty($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            //usePage
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($countWhere) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }

            $sql = 'SELECT mp.*,mpp.map_point_panorama,p.* FROM ' . DB_PREFIX . 'map_point as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'map_point_panorama as mpp ON (mp.id=mpp.id AND mp.map_id=mpp.map_id) LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id' . $where . ' order by mp.map_point_id DESC' . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList' . @$params ['map_id'],
                    self::class . 'getList' . @$params ['project_id']
                ));
            }
        }
        return $result;
    }

    /**
     * @param $gid 面编码 int
     * @param $mapId  楼层编码 int
     * @return 点位详情
     */
    public function getDetailsByIdAndMapGIdSimple($gid, $mapId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdAndMapGIdSimple', [$gid, $mapId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . DB_PREFIX . 'map_point WHERE gid=? AND map_id=? and id is not null limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $gid,
                    $mapId
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
     * @param $id 点位编码
     * @param $mapId 楼层编码
     * @return 点位详情（），对应面信息point（polygon），全景图信息（panorama），地图信息（map），项目信息（project）
     */
    public function getDetailsByIdAndMapIdSimple($id, $mapId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdAndMapIdSimple', [$id, $mapId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT mp.*,m.map_name,m.map_pid,nmp.map_gid,m.map_name_en,nmp.map_polygon_id,p.*,mpp.map_point_panorama,mpp.map_point_panorama_id,m.map_legend FROM ' . DB_PREFIX . 'map_point as mp LEFT JOIN n_map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id LEFT JOIN ' . DB_PREFIX . 'map_point_panorama as mpp ON (mp.id=mpp.id AND mp.map_id=mpp.map_id) WHERE mp.id=? AND mp.map_id=? limit 1';
//            echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $id,
                    $mapId
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

    public function getSource()
    {
        return DB_PREFIX . 'map_point';
    }
}
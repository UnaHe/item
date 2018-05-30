<?php

class PanoramaHotspotsModel extends ModelBase
{
    public function initialize()
    {
    }

    /**根据全景图热点id获取全景图详情
     * @param $hotspotsId
     * @return 全景图详情（n_panorama_hotspots）
     */

    public function getDetailsByHotspotsIdSimple($hotspotsId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByHotspotsIdSimple', $hotspotsId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT ph.* FROM ' . DB_PREFIX . 'panorama_hotspots as ph LEFT JOIN ' . DB_PREFIX . 'map as m ON ph.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id WHERE ph.panorama_hotspots_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [
                    $hotspotsId
                ]));
            $result = $result->valid() ? $result->toArray()[0] : false;
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
            $orderBy = ' order by ph.panorama_hotspots_id DESC';
            if (!empty($params ['project_id'])) {
                $where .= ' WHERE m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['point_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ph.point_id=?';
                $bindParams [] = $params ['point_id'];
            }
            if (!empty($params ['map_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ph.map_id=?';
                $bindParams [] = $params ['map_id'];
            }
            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(ph.panorama_hotspots_id) FROM ' . DB_PREFIX . 'panorama_hotspots as ph LEFT JOIN ' . DB_PREFIX . 'map as m ON ph.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id' . $where;
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
            $sql = 'SELECT ph.* FROM ' . DB_PREFIX . 'panorama_hotspots as ph LEFT JOIN ' . DB_PREFIX . 'map as m ON ph.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList' . @$params ['panorama_id'],
                    self::class . 'getList' . @$params ['project_id'],
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'panorama_hotspots';
    }
}
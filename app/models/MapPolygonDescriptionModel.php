<?php

class MapPolygonDescriptionModel extends ModelBase
{

    public function initialize()
    {
    }

    /**
     * 查询面及对应的地图、项目详情
     * @param $mapGid
     * @return bool|array
     */
    public function getDetailsByMapGidSimple($mapGid)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapGidSimple', $mapGid);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . $this->getSource() . ' WHERE map_gid=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $mapGid
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
        return DB_PREFIX . 'map_polygon_description';
    }
}
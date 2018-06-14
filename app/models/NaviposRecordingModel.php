<?php

class NaviposRecordingModel extends ModelBase
{
    const TAG_PREFIX = 'NaviposRecordingModel_';

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = [];
            $pageCount = 1;
            if (isset($params ['project_id']) && !empty($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' m1.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset($params ['map_id']) && !empty($params ['map_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' (m1.map_id=? OR m2.map_id=?)';
                $bindParams [] = $params ['map_id'];
                $bindParams [] = $params ['map_id'];
            }
            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " (nr.start_map_gid=? OR nr.end_map_gid=? OR mp1.name like '%" . $params ['keywords'] . "%' OR mp2.name like '%" . $params ['keywords'] . "%')";
                $bindParams [] = $params ['keywords'];
                $bindParams [] = $params ['keywords'];
            }
            if (isset($params['startTime']) && !empty($params['startTime'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' nr.navipos_time>=?';
                $bindParams [] = $params ['startTime'];
            }
            if (isset($params['endTime']) && !empty($params['endTime'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' nr.navipos_time<?';
                $bindParams [] = $params ['endTime'];
            }
//            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
//                $sqlCount = 'SELECT count(f.forward_id) FROM ' . DB_PREFIX . 'forward as f LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON f.id=mp.id and f.map_id=mp.map_id LEFT JOIN ' . DB_PREFIX . 'map as m ON f.map_id=m.map_id' . $where;
//                $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
//                    $this->getReadConnection()->query($sqlCount, $bindParams));
//                $count = $countRes->toArray()[0]['count'];
//                $pageCount = ceil($count / $params ['psize']);
//                if ($params ['page'] > $pageCount && $pageCount > 0) {
//                    $params ['page'] = $pageCount;
//                }
//                $offset = ($params ['page'] - 1) * $params ['psize'];
//                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
//            }
            $sql = 'SELECT nr.*,m1.map_name as map_name_start,mp1.name as name_start,m2.map_name as map_name_end,mp2.name as name_end FROM ' . DB_PREFIX . 'navipos_recording as nr LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp1 ON nr.start_map_gid=mp1.map_gid LEFT JOIN ' . DB_PREFIX . 'map as m1 ON mp1.map_id=m1.map_id LEFT JOIN '.DB_PREFIX.'map_polygon as mp2 ON nr.end_map_gid=mp2.map_gid LEFT JOIN '.DB_PREFIX.'map as m2 ON mp2.map_id=m2.map_id' . $where . ' order by nr.navipos_time DESC' . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
//            if (CACHING) {
//                $this->cache->save($tag, $result, 864000, null, array(
//                    self::class . 'getList'
//                ));
//            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'navipos_recording';
    }

    /**
     * 导航次数(前十).
     * @param array $params
     * @return array|bool
     */
    public function getNavigationTop($params =[])
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getNavigationTop', json_encode($params));
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = [];

            if(isset($params['project_id']) && !empty($params['project_id'])){
                $where .= (empty($where)?' WHERE':' AND').' m.project_id = ?';
                $bindParams[] = $params['project_id'];
            }

            if(isset($params['startTime']) && !empty($params['startTime'])){
                $where .= (empty($where)?' WHERE':' AND').' nr.navipos_time >= ?';
                $bindParams[] = $params['startTime'];
            }

            if(isset($params['endTime']) && !empty($params['endTime'])){
                $where .= (empty($where)?' WHERE':' AND').' nr.navipos_time <= ?';
                $bindParams[] = $params['endTime'];
            }

            $sql = "SELECT concat(start_map_gid, ' --> ', end_map_gid) AS mapid, concat(start_name, ' --> ', end_name) AS mapname, count(*) as mapcount FROM n_navipos_recording AS nr LEFT JOIN n_map_polygon AS mp ON nr.start_map_gid = mp.map_gid LEFT JOIN n_map AS m ON mp.map_id = m.map_id" . $where . " GROUP BY mapid, mapname ORDER BY mapcount DESC LIMIT 10";

            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));

            $result = $data->valid() ? $data->toArray() : false;

            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * 导航目的地排序(前十).
     * @param array $params
     * @return array|bool
     */
    public function getDestinationTop($params =[])
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDestinationTop', json_encode($params));
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = [];

            if(isset($params['project_id']) && !empty($params['project_id'])){
                $where .= (empty($where)?' WHERE':' AND').' m.project_id = ?';
                $bindParams[] = $params['project_id'];
            }

            if(isset($params['startTime']) && !empty($params['startTime'])){
                $where .= (empty($where)?' WHERE':' AND').' nr.navipos_time >= ?';
                $bindParams[] = $params['startTime'];
            }

            if(isset($params['endTime']) && !empty($params['endTime'])){
                $where .= (empty($where)?' WHERE':' AND').' nr.navipos_time <= ?';
                $bindParams[] = $params['endTime'];
            }

            $sql = "SELECT end_map_gid, end_name, COUNT ( * ) AS mapcount FROM n_navipos_recording AS nr LEFT JOIN n_map_polygon AS mp ON nr.start_map_gid = mp.map_gid LEFT JOIN n_map AS m ON mp.map_id = m.map_id" . $where . " GROUP BY end_map_gid, end_name ORDER BY mapcount DESC LIMIT 10";

            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));

            $result = $data->valid() ? $data->toArray() : false;

            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

}
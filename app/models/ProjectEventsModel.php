<?php

class ProjectEventsModel extends ModelBase
{
    public function initialize()
    {
    }

    public function reset()
    {
        unset($this->project_events_id);
    }

    /**
     * @param $eventsId
     * @return bool|array
     */
    public function getDetailsByProjectEventsIdSimple($eventsId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectEventsIdSimple', $eventsId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT pe.*,p.project_id,p.project_name,mp.name as map_polygon_name FROM ' . $this->getSource() . ' as pe LEFT JOIN ' . DB_PREFIX . 'project as p ON p.project_id=pe.project_id LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp ON pe.project_events_map_gid=mp.map_gid WHERE pe.project_events_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $eventsId
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
     * allow params keys: gid map_id id label
     * @param array $params
     * @return bool
     */

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = [];
            if (isset($params ['projectId']) && !is_null($params ['projectId'])) {
                $where .= ' WHERE pe.project_id=?';
                $bindParams [] = $params ['projectId'];
            }
            if (isset($params['startTime']) && $params['startTime']){
                $where .= empty($where)?' WHERE':' AND'.' pe.project_events_time_start>=?';
                $bindParams [] = $params ['startTime'];
            }
            if (isset($params['endTime']) && $params['endTime']){
                $where .= empty($where)?' WHERE':' AND'.' pe.project_events_time_end<?';
                $bindParams [] = $params ['endTime'];
            }
            $sql = 'SELECT pe.*,mp.name as map_polygon_name FROM ' . $this->getSource() . ' as pe LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp ON pe.project_events_map_gid=mp.map_gid' . $where . ' order by pe.project_events_time_start ASC';
//            echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    /**
     * @param array $params
     * @return array
     *
     */
    public function newGetListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $orderBy = 'pe.project_events_id DESC';
            $bindParams = [];
            $pageCount = 1;
            if (!empty($params ['project_id'])) {
                $where .= ' WHERE pe.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (!empty($params['startTime'])) {
                $where .= empty($where) ? ' WHERE' : ' AND' . ' pe.project_events_time_start>=?';
                $bindParams [] = $params ['startTime'];
            }
            if (!empty($params['endTime'])) {
                $where .= empty($where) ? ' WHERE' : ' AND' . ' pe.project_events_time_end<?';
                $bindParams [] = $params ['endTime'];
            }

            if (!empty($params['map_gid'])) {
                $where .= empty($where) ? ' WHERE' : ' AND' . ' pe.project_events_map_gid=?';
                $bindParams [] = $params ['map_gid'];
            }

            if (!empty($params['orderBy'])) {
                $orderBy = $params['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(pe.*) FROM ' . $this->getSource() . ' as pe' . $where;
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
            $sql = 'SELECT pe.*,mp.name as map_polygon_name,mp.map_id,mp.gid FROM ' . $this->getSource() . ' as pe LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp ON pe.project_events_map_gid=mp.map_gid' . $where . ' order by ' . $orderBy.$limit;
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
        return DB_PREFIX . 'project_events';
    }
}
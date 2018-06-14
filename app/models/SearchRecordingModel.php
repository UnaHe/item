<?php
class SearchRecordingModel extends ModelBase {
    const TAG_PREFIX = 'SearchRecordingModel_';

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = [];
            $pageCount = 1;
            if (isset($params ['project_id']) && !empty($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " sr.search_words like '%" . $params ['keywords'] . "%'";
            }
            if (isset($params['startTime']) && !empty($params['startTime'])){
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.search_time>=?';
                $bindParams [] = $params ['startTime'];
            }
            if (isset($params['endTime']) && !empty($params['endTime'])){
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.search_time<?';
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
            $sql = 'SELECT sr.*,p.project_name FROM '.DB_PREFIX.'search_recording as sr LEFT JOIN ' . DB_PREFIX . 'project as p ON p.project_id=sr.project_id' . $where . ' order by sr.search_recording_id DESC' . $limit;
//            echo $sql;die();
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

	public function getSource() {
		return DB_PREFIX . 'search_recording';
	}

    /**
     * 搜索关键字（前十）
     * @param array $params
     * @return array|bool
     */
	public function getSearchTop($params = [])
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getSearchTop', json_encode($params));
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = [];

            if(isset($params['project_id']) && !empty($params['project_id'])){
                $where .= (empty($where)?' WHERE':' AND').' project_id = ?';
                $bindParams[] = $params['project_id'];
            }

            if(isset($params['startTime']) && !empty($params['startTime'])){
                $where .= (empty($where)?' WHERE':' AND').' search_time >= ?';
                $bindParams[] = $params['startTime'];
            }

            if(isset($params['endTime']) && !empty($params['endTime'])){
                $where .= (empty($where)?' WHERE':' AND').' search_time <= ?';
                $bindParams[] = $params['endTime'];
            }

            $sql = "SELECT search_words, count(search_words) as searchcount FROM n_search_recording" . $where . " GROUP BY search_words ORDER BY searchcount DESC LIMIT 10";

            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));

            $result = $data->valid() ? $data->toArray() : false;

            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

}
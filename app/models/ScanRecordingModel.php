<?php
class ScanRecordingModel extends ModelBase {
	const TAG_PREFIX = 'ScanRecordingModel_';

	public function reset()	{
		unset($this->scan_recording_id);
	}

    /**
     * @param array $params
     * @return array
     */

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = [];
            $pageCount = 1;
            if (isset($params ['project_id']) && !empty($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset($params ['map_id']) && !empty($params ['map_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' f.map_id=?';
                $bindParams [] = $params ['map_id'];
            }
            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " (f.map_id=? OR f.id=? OR f.forward_id=? OR mp.name like '%" . $params ['keywords'] . "%')";
                $bindParams [] = $params ['keywords'];
                $bindParams [] = $params ['keywords'];
                $bindParams [] = $params ['keywords'];
            }
            if (isset($params['startTime']) && !empty($params['startTime'])){
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.scan_time>=?';
                $bindParams [] = $params ['startTime'];
            }
            if (isset($params['endTime']) && !empty($params['endTime'])){
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.scan_time<?';
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
            $sql = 'SELECT f.*,sr.scan_time,mp.name,m.map_name FROM '.DB_PREFIX.'scan_recording as sr LEFT JOIN ' . DB_PREFIX . 'forward as f ON f.forward_id=sr.forward_id LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON f.id=mp.id and f.map_id=mp.map_id LEFT JOIN ' . DB_PREFIX . 'map as m ON f.map_id=m.map_id' . $where . ' order by f.forward_id DESC' . $limit;
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
	/**
	 * @param $forward_id
	 * @return bool|\Phalcon\Mvc\Model\Resultset\Simple
	 */
	public function getCountByForwardId($forward_id){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getCountByForwardId_' . $forward_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$sql = 'select count(*) from n_scan_recording WHERE forward_id = ?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, array($forward_id)));
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getCount($time,$duration,array $condition = array()){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getCount'.$time.'_'.$duration,$condition);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$bindParams = array();
			$where = $duration == 'all'?'':' WHERE sr.scan_time>'.$time.' and sr.scan_time<'.($time+$duration);
			if(isset($condition['project_id']) && !empty($condition['project_id'])){
				$where .= (empty($where)?' WHERE':' AND').' m.project_id=?';
				$bindParams[] = $condition['project_id'];
			}
			if(isset($condition['forward_id']) && !empty($condition['forward_id'])){
				$where .= (empty($where)?' WHERE':' AND').' f.forward_id=?';;
				$bindParams[] = $condition['forward_id'];
			}
			$sql = 'SELECT COUNT (*) FROM n_scan_recording AS sr LEFT JOIN n_forward AS f ON sr.forward_id = f.forward_id LEFT JOIN n_map AS M ON f.map_id = M .map_id' . $where;
//			echo $sql;die;
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	/**
	 * @param $time
	 * @param $duration
	 * @param $forward_id
	 * @return bool|\Phalcon\Mvc\Model\Resultset\Simple
	 */
	public function getRecordingBytime($time,$duration,array $condition = array()){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getRecordingByDay'.$time.'_'.$duration,$condition);
		$result = CACHING ? $this->cache->get($tag) : false;

//		print_r($result);die;
		if (!$result) {
			$bindParams = array();
			$where = $duration == 'all'?'':' WHERE sr.scan_time>'.$time.' and sr.scan_time<'.($time+$duration);
			if(isset($condition['project_id']) && !empty($condition['project_id'])){
				$where .= (empty($where)?' WHERE':' AND').' m.project_id=?';
				$bindParams[] = $condition['project_id'];
			}
			if(isset($condition['forward_id']) && !empty($condition['forward_id'])){
				$where .= (empty($where)?' WHERE':' AND').' f.forward_id=?';;
				$bindParams[] = $condition['forward_id'];
			}
			$sql = 'SELECT sr.scan_time FROM n_scan_recording AS sr LEFT JOIN n_forward AS f ON sr.forward_id = f.forward_id LEFT JOIN n_map AS M ON f.map_id = M .map_id'.$where.' ORDER BY sr.scan_time';

//			echo $sql;die;
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));
			$result = $result->toArray();
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getRecordingOrderByForwardId($params){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getRecordingOrderByForwardId',$params);
		$result = CACHING ? $this->cache->get($tag) : false;
//		print_r($result);die;
		if (!$result) {
			$bindParams = array();
			$where = $params['duration_time'] == 'all'?'':' WHERE sr.scan_time>'.$params['start_time'].' and sr.scan_time<'.($params['start_time']+$params['duration_time']);
			if(isset($params['project_id']) && !empty($params['project_id'])){
				$where .= (empty($where)?' WHERE':' AND').' m.project_id=?';
				$bindParams[] = $params['project_id'];
			}
			$sql = 'SELECT sr.forward_id, name,m.map_name FROM n_scan_recording AS sr LEFT JOIN n_forward AS f ON sr.forward_id = f.forward_id LEFT JOIN n_map AS M ON f.map_id = M .map_id '.$where.' GROUP BY sr.forward_id,name,m.map_name ORDER BY sr.forward_id';
//			echo $sql;die;
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));
			if($result->valid()){
				$result = $result->toArray();
			}else{
				$result = false;
			}
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getCountAboutDay($params){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getCountAboutDay',$params);
		$result = CACHING ? $this->cache->get($tag) : false;
//		print_r($result);die;
		if (!$result) {
			$bindParams = array();
			$where = ' WHERE sr.scan_time>'.$params['start_time'].' and sr.scan_time<'.($params['start_time']+$params['duration_time']);
			if(isset($params['project_id']) && !empty($params['project_id'])){
				$where .= (empty($where)?' WHERE':' AND').' m.project_id=?';
				$bindParams[] = $params['project_id'];
			}
			$sql = 'SELECT sr.forward_id,name,count(sr.forward_id) FROM n_scan_recording AS sr LEFT JOIN n_forward AS f ON sr.forward_id = f.forward_id LEFT JOIN n_map AS M ON f.map_id = M .map_id '.$where.' GROUP BY sr.forward_id,name ORDER BY sr.forward_id';
//			echo $sql;die;
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));
			if($result->valid()){
				$result = $result->toArray();
			}else{
				$result = false;
			}
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}


	public function getSource() {
		return DB_PREFIX . 'scan_recording';
	}

    /**
     * 获取点位扫码次数(前十).
     * @param array $params
     * @return array|bool
     */
	public function getScanTop($params = [])
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getScanTop', json_encode($params));
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = [];

            if(isset($params['project_id']) && !empty($params['project_id'])){
                $where .= (empty($where)?' WHERE':' AND').' m.project_id = ?';
                $bindParams[] = $params['project_id'];
            }

            if(isset($params['startTime']) && !empty($params['startTime'])){
                $where .= (empty($where)?' WHERE':' AND').' sr.scan_time >= ?';
                $bindParams[] = $params['startTime'];
            }

            if(isset($params['endTime']) && !empty($params['endTime'])){
                $where .= (empty($where)?' WHERE':' AND').' sr.scan_time <= ?';
                $bindParams[] = $params['endTime'];
            }

            $sql = 'SELECT sr.forward_id, sr.name AS forward_name, COUNT(*) AS forward_count FROM n_scan_recording AS sr LEFT JOIN n_forward AS f ON sr.forward_id = f.forward_id  LEFT JOIN n_map AS m ON f.map_id = m .map_id' . $where . ' GROUP BY sr.forward_id, sr.name  ORDER BY forward_count DESC  LIMIT 10';

            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));

            $result = $data->valid() ? $data->toArray() : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

}
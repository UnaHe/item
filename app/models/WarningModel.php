<?php

class WarningModel extends ModelBase
{
	public function initialize()
	{
	}

	public function getDetailsSimple($warningId)
	{
		$tag = CacheBase::makeTag(self::class . 'getDetails_' , $warningId);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$sql = 'SELECT w.* FROM ' . DB_PREFIX . 'warning as w WHERE warning_id=? limit 1';
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, [$warningId]));
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

	public function getListSimple(array $params = [])
	{
		$tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$where = $countWhere = $limit = '';
			$bindParams = array();
			$pageCount = 1;
            $orderBy = ' order by w.warning_time ASC';
			if (isset($params ['status']) && !is_null($params ['status'])) {
				$where .= ' WHERE w.warning_status=?';
				$bindParams [] = $params ['status'];
			}
            if (isset($params ['status_not']) && !is_null($params ['status_not'])) {
                $where .= ' WHERE w.warning_status!=?';
                $bindParams [] = $params ['status_not'];
            }
			if (isset($params ['staff_id']) && !is_null($params ['staff_id'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' w.staff_id=?';
				$bindParams [] = $params ['staff_id'];
			}
			if (!empty($params ['project_id'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' m.project_id=?';
				$bindParams [] = $params ['project_id'];
			}
            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by '.$params ['orderBy'];
            }
			if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(w.*) FROM ' . DB_PREFIX . 'warning as w LEFT JOIN '.DB_PREFIX.'map as m ON w.map_id=m.map_id' . $where;
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
			$sql = 'SELECT w.* FROM ' . DB_PREFIX . 'warning as w LEFT JOIN '.DB_PREFIX.'map as m ON w.map_id=m.map_id' . $where . $orderBy . $limit;
			$data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
			$result['data'] = $data->toArray();
			$result['pageCount'] = $pageCount;
			if (CACHING) {
				$this->cache->save($tag, $result, 864000, null, array(
					self::class . 'getList',
				));
			}
		}
		return $result;
	}

	public function getSource()
	{
		return DB_PREFIX . 'warning';
	}
}
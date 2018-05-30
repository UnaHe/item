<?php

class VisitLogModel extends ModelBase
{
	const TAG_PREFIX = 'VisitLogModel_';

	public function initialize()
	{
	}

	public function reset()
	{
		unset($this->visit_log_id);
	}

	public function getDetailsByVisitLogId($visitLogId)
	{
		$tag = self::TAG_PREFIX . 'getDetailsByVisitLogId' . $visitLogId;
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'visit_log_id = ?1',
					'bind' => array(1 => $visitLogId)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getDetailsByMapPointIdSimple($mapPointId)
	{
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByMapPointId' , $mapPointId);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$sql = 'SELECT mp.*,m.map_name,p.project_name,p.project_id,p.project_entrance FROM ' . DB_PREFIX . 'map_point as mp LEFT JOIN '.DB_PREFIX.'map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id WHERE mp.map_point_id=? limit 1';
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, array(
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

	public function getListSimple(array $params = [])
	{
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getListSimple', $params);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$where = $countWhere = $limit = '';
			$bindParams = array();
			$pageCount = 1;
			if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
				$pageCount = ceil($this->count($countWhere) / $params ['psize']);
				if ($params ['page'] > $pageCount && $pageCount > 0) {
					$params ['page'] = $pageCount;
				}
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
			}
			$sql = 'SELECT* FROM ' . $this->getSource() . $where . ' order by visit_log_id DESC' . $limit;
			$data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
			$result['data'] = $data;
			$result['pageCount'] = $pageCount;
			if (CACHING) {
				$this->cache->save($tag, $result, 864000, null, array(
					self::TAG_PREFIX . 'getList',
				));
			}
		}
		return $result;
	}

	public function getSource()
	{
		return DB_PREFIX . 'visit_log';
	}
}
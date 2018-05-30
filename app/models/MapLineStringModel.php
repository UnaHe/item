<?php

class MapLineStringModel extends ModelBase
{
	public function initialize()
	{
	}

	public function reset()
	{
		unset($this->map_linestring_id);
	}

	public function getDetailsByMapLineStringIdSimple($lineStringId)
	{
		$tag = CacheBase::makeTag(self::class . 'getDetailsByMapLineStringIdSimple' , $lineStringId);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$sql = 'SELECT ml.*,m.map_name,p.project_name,p.project_id,p.project_entrance FROM ' . DB_PREFIX . 'map_linestring as ml LEFT JOIN '.DB_PREFIX.'map as m ON ml.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id WHERE ml.map_linestring_id=? limit 1';
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, array(
				$lineStringId
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
		$tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$where = $countWhere = $limit = '';
			$bindParams = array();
			$pageCount = 1;
			if (isset($params ['map_id']) && !is_null($params ['map_id'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' ml.map_id=?';
				$countWhere .= (empty($countWhere) ? '' : ' AND ') . 'map_id="' . $params['map_id'] . '"';
				$bindParams [] = $params ['map_id'];
			}
			if (isset($params ['congestion_level_not']) && !is_null($params ['congestion_level_not'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' ml.congestion_level!=?';
				$countWhere .= (empty($countWhere) ? '' : ' AND ') . 'congestion_level!="' . $params['congestion_level_not'] . '"';
				$bindParams [] = $params ['congestion_level_not'];
			}
			if (isset($params ['congestion_level']) && !is_null($params ['congestion_level'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' ml.congestion_level=?';
				$countWhere .= (empty($countWhere) ? '' : ' AND ') . 'congestion_level="' . $params['congestion_level'] . '"';
				$bindParams [] = $params ['congestion_level'];
			}
            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $intKeywords = (int) $params ['keywords'];
                if ($intKeywords!==0){
                    $where .= (empty($where) ? ' WHERE' : ' AND') . " (ml.i='".$params ['keywords']."' OR ml.j='".$params ['keywords']."')";
                    $countWhere .= (empty($countWhere) ? '' : ' AND ') . " (i='".$params ['keywords']."' OR j='".$params ['keywords']."')";
                }
            }
			if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
				$pageCount = ceil($this->count($countWhere) / $params ['psize']);
				if ($params ['page'] > $pageCount && $pageCount > 0) {
					$params ['page'] = $pageCount;
				}
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
			}
			$sql = 'SELECT ml.* FROM ' . DB_PREFIX . 'map_linestring as ml'  . $where . ' order by ml.map_linestring_id DESC' . $limit;
			$data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
			$result['data'] = $data->toArray();
			$result['pageCount'] = $pageCount;
			if (CACHING) {
				$this->cache->save($tag, $result, 864000, null, array(
					self::class . 'getList',
					self::class . 'getList' . @$params ['map_id']
				));
			}
		}
		return $result;
	}

	public function getSource()
	{
		return DB_PREFIX . 'map_linestring';
	}
}
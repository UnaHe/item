<?php
class WordsPolygonModel extends ModelBase {
	const TAG_PREFIX = 'WordsPolygon_';
	public function reset()
	{
	    unset($this->words_polygon_id);

	}

    public function getDetailsByWordsPolygonId($wordsPolygonId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByWordsPolygonId', $wordsPolygonId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(array(
                'conditions' => 'words_polygon_id=?1',
                'bind' => [1 => $wordsPolygonId]
            ));
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
	public function getdetails(array $arr = []) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX.'getdetails',$arr);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
		    $bindParams = array();
		    $where = '';
	        if (isset($arr ['words_id']) && !empty($arr ['words_id'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' words_id=?';
				$bindParams [] = $arr ['words_id'];
			}
			if (isset($arr ['map_gid']) && !empty($arr ['map_gid'])) {
			    $where .= (empty($where) ? ' WHERE' : ' AND') . ' map_gid=?';
			    $bindParams [] = $arr ['map_gid'];
			}
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'words_polygon '.$where;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			if (CACHING) {
				$this->cache->save($tag, $result, 864000, null, array(
					self::TAG_PREFIX . 'getList',
					self::TAG_PREFIX . 'getList_' . @$arr ['words_id'],
				));
			}
		}
		return $result;
	}
	public function getListByMapGid($map_gid) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX.'getListByMapGid',$map_gid);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT sw.* FROM ' . DB_PREFIX . 'words_polygon as wp LEFT JOIN ' . DB_PREFIX . 'search_words sw ON sw.words_id = wp.words_id WHERE wp.map_gid =?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, [$map_gid] ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getListByMapGid'
				) );
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
            $orderBy = ' order by wp.words_polygon_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['map_gid']) && !empty($params ['map_gid'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " wp.map_gid=?";
                $bindParams [] = $params ['map_gid'];
            }
            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(wp.words_polygon_id) FROM ' . DB_PREFIX . 'words_polygon as wp LEFT JOIN ' . DB_PREFIX . 'search_words as sw ON wp.words_id=sw.words_id LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp ON mp.map_gid=wp.map_gid left JOIN ' . DB_PREFIX . 'map as m ON m.map_id=mp.map_id' . $where.' GROUP BY wp.map_gid';
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
            $sql = 'SELECT wp.*,sw.words_name,mp.name,mp.map_id,mp.gid FROM ' . DB_PREFIX . 'words_polygon as wp LEFT JOIN ' . DB_PREFIX . 'search_words as sw ON wp.words_id=sw.words_id LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp ON mp.map_gid=wp.map_gid left JOIN ' . DB_PREFIX . 'map as m ON m.map_id=mp.map_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id'],
                    self::class . 'getList' . @$params['department_id'],
                ));
            }
        }
        return $result;
    }
	public function getSource() {
		return DB_PREFIX . 'words_polygon';
	}
}

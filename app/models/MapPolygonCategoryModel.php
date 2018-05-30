<?php
class MapPolygonCategoryModel extends ModelBase {
	const TAG_PREFIX = 'MapPolygonCategoryModel_';
	public function getDetails($version_id) {
		$tag = self::TAG_PREFIX . 'getDetails_' . $version_id;
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'app_version WHERE version_id=? limit 1';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
					$version_id 
			) ) );
			if ($result->valid ()) {
				$result = $result->toArray ()[0];
			} else {
				$result = false;
			}
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	public function getList($pid = null , $project_id=null) {
		$tag = self::TAG_PREFIX . 'getList_' . $pid;
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = '';
			$bindParams = array ();
			if (! is_null ( $pid )) {
				$where .= ' WHERE pid=?';
				$bindParams [] = $pid;
			}

			if (! is_null ( $project_id )) {
				$where .= ($where == '' ? ' WHERE' : ' AND') . ' project_id=?';
				$bindParams [] = $project_id;
			}

			$sql = 'SELECT * FROM ' . DB_PREFIX . 'map_polygon_category'.$where ;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql,$bindParams) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList' 
				) );
			}
		}
//                var_dump($result);exit;
		return $result;
	}
	public function getSource() {
		return DB_PREFIX . 'map_polygon_category';
	}
}
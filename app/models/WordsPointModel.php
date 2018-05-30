<?php
class WordsPointModel extends ModelBase {
	const TAG_PREFIX = 'WordsPoint_';
	public function reset()
	{
	    unset($this->words_point_id);

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
			if (isset($arr ['map_point_id']) && !empty($arr ['map_point_id'])) {
			    $where .= (empty($where) ? ' WHERE' : ' AND') . ' map_point_id=?';
			    $bindParams [] = $arr ['map_point_id'];
			}
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'words_point '.$where;
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
	public function getListByPointId($point_id) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX.'getListByPointId',$point_id);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT sw.* FROM ' . DB_PREFIX . 'words_point as wp LEFT JOIN ' . DB_PREFIX . 'search_words sw ON sw.words_id = wp.words_id WHERE wp.map_point_id =?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, [$point_id] ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getListByPointId'
				) );
			}
		}
		return $result;
	}
	public function getSource() {
		return DB_PREFIX . 'words_point';
	}
}

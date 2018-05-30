<?php
class ComplainModel extends ModelBase {
	const TAG_PREFIX = 'ComplainModel_';
    public function initialize()
    {
        $this->hasOne("map_data_id", "MapData", "map_data_id");
    }

    public function getDetailsByComplainId($complainId) {
        $tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByComplainId', $complainId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'complain_id = ?1',
                    'bind' => array(1 => $complainId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

	public function getList(array $params = array()) {
		$tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByProductId', $params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = $limit = '';
			$order = 'n.complain_id DESC';
			$map_id = null;
			$bindParams = array ();
			if (isset ( $params ['map_data_id'] ) && ! is_null ( $params ['map_data_id'] )) {
				$where .= ' WHERE n.map_data_id=?';
				$bindParams [] = $params ['map_data_id'];
			}
			if (isset ( $params ['status'] ) && ! is_null ( $params ['status'] )) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND').' n.complain_status=?';
				$bindParams [] = $params ['status'];
			}
            if (isset ( $params ['orderby'] ) && !empty($params ['orderby'])) {
				$order = $params['orderby'];
            }
			if (isset ( $keywords ) && $keywords != '') {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_name LIKE "%'.$keywords.'%"';
			}
			if (isset ( $params ['project_id'] ) && $params ['project_id'] > 0) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND').' m.project_id=?';
				$bindParams [] = $params ['project_id'];
			}
//			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
//				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit 0,50';
//			}
			$sql = 'SELECT n.*,md.map_data_name FROM ' . DB_PREFIX . 'complain as n LEFT JOIN '.DB_PREFIX.'map_data as md ON n.map_data_id=md.map_data_id LEFT JOIN '.DB_PREFIX.'map as m ON md.map_id=m.map_id' . $where . 'order by ' . $order . $limit;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
						self::TAG_PREFIX . 'getList_'.(isset($params ['project_id'])?$params ['project_id']:null),
				) );
			}
		}
		return $result;
	}

	public function getSource() {
		return DB_PREFIX . 'complain';
	}
}
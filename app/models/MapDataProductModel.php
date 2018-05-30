<?php
class MapDataProductModel extends ModelBase {
	const TAG_PREFIX = 'MapDataProductModel_';
    public function initialize()
    {
        $this->hasOne("map_data_id", "MapData", "map_data_id");
    }

    public function getDetailsByProductId($productId) {
        $tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByProductId', $productId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'map_data_product_id = ?1',
                    'bind' => array(1 => $productId)
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
			$order = 'mdp.map_data_product_id DESC';
			$map_id = null;
			$bindParams = array ();
			if (isset ( $params ['map_data_id'] ) && ! is_null ( $params ['map_data_id'] )) {
				$where .= ' WHERE mdp.map_data_id=?';
				$bindParams [] = $params ['map_data_id'];
			}
//            if (isset ( $params ['map_id'] ) && !is_null($params ['map_id'])) {
//                $where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' m.map_id=?';
//                $bindParams [] = $params ['map_id'];
//            }
			if (isset ( $keywords ) && $keywords != '') {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_name LIKE "%'.$keywords.'%"';
			}
//			if (isset ( $params ['orderby'] ) && $params ['orderby'] != '') {
//				$order = $params ['orderby'];
//			}
//			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
//				$offset = ($params ['page'] - 1) * $params ['psize'];
//				$limit = ' limit ' . $offset . ',' . $params ['psize'];
//			}
			$sql = 'SELECT mdp.*,md.map_data_name FROM ' . DB_PREFIX . 'map_data_product as mdp LEFT JOIN '.DB_PREFIX.'map_data as md ON mdp.map_data_id=md.map_data_id' . $where . 'order by ' . $order . $limit;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
						self::TAG_PREFIX . 'getList_'.(isset($params ['map_data_id'])?$params ['map_data_id']:null),
				) );
			}
		}
		return $result;
	}

	public function getSource() {
		return DB_PREFIX . 'map_data_product';
	}
}
<?php
class BuyerModel extends ModelBase {
	const TAG_PREFIX = 'BuyerModel_';
	public function getDetailsById($buyerId) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $buyerId);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'buyer_id = ?1',
					'bind' => array(1 => $buyerId)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

        /**
         * 得到buyer表中的status为0的数据
         */
        public function getDetailsByStatus($status){
            $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $status);
            $result = CACHING ? $this->cache->get($tag) : false;
            if (!$result) {
			$result = $this->find(
				array(
					'conditions' => 'buyer_status = ?1',
					'bind' => array(1 => $status)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
        }




	public function getList(array $params) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = $limit = $countWhere = '';
			$bindParams = array ();
			if (isset($params ['map_polygon_category_id']) && ! empty( $params ['map_polygon_category_id'] )) {
				$where .= ($where?' AND ':' WHERE ').' a.map_polygon_category_id=?';
				$bindParams [] = $params ['map_polygon_category_id'];
			}
			if (isset($params ['project_id']) && ! empty( $params ['project_id'] )) {
			    $where .= ($where?' AND ':' WHERE ').' ac.project_id=?';
			    $bindParams [] = $params ['project_id'];
			}
			if (isset($params ['buyer_status']) && ! empty( $params ['buyer_status'] )) {
			    $where .= ($where?' AND ':' WHERE ').' a.buyer_status=?';
			    $bindParams [] = $params ['buyer_status'];
			}
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$offset = ($params ['page'] - 1) * $params ['psize'];
                $offset = $offset<0?0:$offset;
				$limit = ' limit ' . $params ['psize'].' offset '.$offset;
			}
			$sql = 'SELECT a.*,ac.map_polygon_category_name as cate_name,ac.project_id FROM ' . DB_PREFIX . 'buyer as a LEFT JOIN ' . DB_PREFIX . 'map_polygon_category as ac ON a.map_polygon_category_id=ac.map_polygon_category_id '.$where.'order by a.goods_create_at desc'.$limit;
			$countsql = 'SELECT  count(a.buyer_id) as pagecount FROM ' . DB_PREFIX . 'buyer as a LEFT JOIN ' . DB_PREFIX . 'map_polygon_category as ac ON a.map_polygon_category_id=ac.map_polygon_category_id '.$where;
			$data = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$pageCount = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $countsql, $bindParams ) );
			$result['data'] = $data->toArray ();
			$result['pageCount'] = ceil($pageCount->toArray()[0]['pagecount']/$params['psize']);
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
						self::TAG_PREFIX . 'getList_' . $params ['map_polygon_category_id']
				) );
			}
		}
		return $result;
	}

        public function getOne( $buyerId) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $buyerId);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT a.*,ac.map_polygon_category_name as cate_name FROM ' . DB_PREFIX . 'buyer as a LEFT JOIN ' . DB_PREFIX . 'map_polygon_category as ac ON a.map_polygon_category_id=ac.map_polygon_category_id where buyer_id ='."'$buyerId'";
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
						self::TAG_PREFIX . 'getList_' . $params ['map_polygon_category_id']
				) );
			}
		}
		return $result;
	}



	public function getSource() {
		return DB_PREFIX . 'buyer';
	}
}
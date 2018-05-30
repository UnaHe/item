<?php

class UserOrderModel extends ModelBase
{
	const TAG_PREFIX = 'UserOrderModel_';
	public function getListByMapDataId($map_data_id)
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getListByMapDataId', $map_data_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$sql = 'SELECT uo.*,c.* FROM ' . DB_PREFIX . 'user_order as uo LEFT JOIN '.DB_PREFIX.'coupon as c ON uo.user_order_product_id=c.coupon_id WHERE uo.map_data_id=?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, array($map_data_id)));
			$result = $result->toArray();
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getDetailsByUserOrderId($userOrderId)
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByUserOrderId', $userOrderId);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$sql = 'SELECT uo.*,c.* FROM ' . DB_PREFIX . 'user_order as uo LEFT JOIN '.DB_PREFIX.'coupon as c ON uo.user_order_product_id=c.coupon_id WHERE uo.user_order_id=?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, array($userOrderId)));
			$result = $result?$result->toArray()[0]:false;
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getDetailsObjectByUserOrderId($userOrderId)
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getDetailsObjectByUserOrderId', $userOrderId);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			if (!$result) {
				$result = $this->findFirst(
					array(
						'conditions' => 'user_order_id = ?1',
						'bind' => array(1 => $userOrderId)
					)
				);
				if (CACHING) {
					$this->cache->save($tag, $result);
				}
			}
		}
		return $result;
	}

	public function getList(array $params = array())
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getList', $params);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$bindParams = array();
			$where = $limit = $order = '';
			if (isset($params['orderby']) && $params['orderby']!=''){
				$order = $params['orderby'];
			}
			if (isset($params['map_data_id']) && $params['map_data_id']>0){
				$where = ' WHERE map_data_id=?';
				$bindParams[] = $params['map_data_id'];
			}
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $offset . ',' . $params ['psize'];
			}
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'coupon' . $where.' '.$order.$limit;
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
			$result = $result->toArray();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
					self::TAG_PREFIX . 'getList',
					self::TAG_PREFIX . 'getList_'.(isset($params['user_id'])?$params['user_id']:null),
				) );
			}
		}
		return $result;
	}

	public function getSource()
	{
		return DB_PREFIX . 'user_order';
	}
}
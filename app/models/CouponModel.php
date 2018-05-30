<?php

class CouponModel extends ModelBase
{
	const TAG_PREFIX = 'CouponModel_';
	public function getListByMapGid($map_gid)
	{
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getListByMapGid', $map_gid);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->find(
				array(
					'conditions' => 'users_user_name = ?1',
					'bind' => array(1 => $map_gid)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		$result = $result->toArray();
		return $result;
	}

	/**
	 * get pending number
	 * @param $project_id
	 * @return bool|\Phalcon\Mvc\Model\Resultset\Simple
	 */
	public function getPendingNumber($project_id){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getPendingNumber', $project_id);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if(!$result){
			$sql = 'SELECT count(*) from n_coupon as c LEFT JOIN n_users_user as uu on c.users_user_name = uu.users_user_name WHERE c.coupon_status = 2 and uu.project_id = ?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array($project_id) ) );
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
			if($result->valid()){
				$result = $result->toArray()[0];
			}else{
				return false;
			}
		}
		return $result;
	}

	/**
	 * get pending list
	 * @param $project_id
	 * @return bool|\Phalcon\Mvc\Model\Resultset\Simple
	 */
	public function getPendingList($project_id,$coupon_status =null){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getPendingList', $project_id);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if(!$result){
			$where = ' WHERE uu.project_id = ? ';
			$bindParams = [$project_id];
			if(!is_null($coupon_status)){
				$where .= ' and c.coupon_status = ? ';
				$bindParams[] = $coupon_status;
			}
			$sql = 'SELECT c.* from n_coupon as c LEFT JOIN n_users_user as uu on c.users_user_name = uu.users_user_name ' . $where;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
			if($result->valid()){
				$result = $result->toArray();
			}else{
				return false;
			}
		}
		return $result;
	}

	public function getDetailsByCouponId($couponId)
	{
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByCouponId', $couponId);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'coupon_id = ?1',
					'bind' => array(1 => $couponId)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getList(array $params = array())
	{
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$bindParams = array();
			$where = $limit = $order = '';
			if (isset($params['project_id']) && $params['project_id']>0){
				$where .= (empty($where)?' WHERE':' AND').' mpc.project_id=?';
				$bindParams[] = $params['project_id'];
			}
            if (isset($params['username'])){
                $where .= (empty($where)?' WHERE':' AND').' c.users_user_name=?';
                $bindParams[] = $params['username'];
            }
            if (isset($params['map_id']) && $params['map_id']>0){
                $where .= (empty($where)?' WHERE':' AND').' mp.map_id=?';
                $bindParams[] = $params['map_id'];
            }
			if (isset($params['category']) && $params['category']>0){
                $where .= (empty($where)?' WHERE':' AND').' c.coupon_category_id=?';
                $bindParams[] = $params['category'];
            }
            if (isset($params['status']) && $params['status']>=0){
                $where .= (empty($where)?' WHERE':' AND').' c.coupon_status=?';
                $bindParams[] = $params['status'];
            }
            if (isset($params['coupon_id']) && $params['coupon_id']>=0){
                $where .= (empty($where)?' WHERE':' AND').' c.coupon_id=?';
                $bindParams[] = $params['coupon_id'];
            }

             $time = time();
             $whereTime = ' AND c.coupon_expire_time >='.$time.' AND '.$time.'>= c.coupon_start_time';

			$sql = 'SELECT c.*,mp.centroid FROM ' . DB_PREFIX . 'coupon as c LEFT JOIN '.DB_PREFIX.'map_polygon as mp ON c.users_user_name=mp.map_gid LEFT JOIN '.DB_PREFIX.'map_polygon_category as mpc ON mpc.map_polygon_category_id=c.map_polygon_category_id '.$where.$whereTime;

			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
			$result = $result->toArray();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
					self::TAG_PREFIX . 'getList',
				) );
			}
		}
		return $result;
	}

	public function getSource()
	{
		return DB_PREFIX . 'coupon';
	}
}
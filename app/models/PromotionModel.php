<?php

class PromotionModel extends ModelBase
{
	const TAG_PREFIX = 'PromotionModel_';
	public function getListByMapDataId($map_data_id)
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getListByMapDataId', $map_data_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->find(
				array(
					'conditions' => 'map_data_id = ?1',
					'order' => 'promotion_sort_order DESC',
					'bind' => array(1 => $map_data_id)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getDetailsByPromotionId($promotion_id)
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByPromotionId', $promotion_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'promotion_id = ?1',
					'bind' => array(1 => $promotion_id)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getDetailsByMapDataIdAndTop($map_data_id, $top , $status='')
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByMapDataIdAndTop', array($map_data_id, $top,$status));
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$conditions = 'map_data_id = ?1 AND promotion_top=?2';
			$bindParams = array(
				1 => $map_data_id,
				2 => $top,
			);
			if (!empty($status)){
				$conditions .= ' AND promotion_status=?3';
				$bindParams[3] = $status;
			}
			$result = $this->findFirst(
				array(
					'conditions' => $conditions,
					'bind' => $bindParams
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getListByProjectId($project_id,$keywords='', $promotionStatus = NULL , $exPromotionStatus=NULL)
	{
		$tag = self::makeTag(self::TAG_PREFIX . 'getListByProjectId', array($project_id,$keywords,$promotionStatus));
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$bindParams = array($project_id);
			$where = ' WHERE m.project_id=?';
			if (!is_null($promotionStatus)) {
				$bindParams[] = $promotionStatus;
				$where .= ' AND p.promotion_status=?';
			}
			if (!is_null($exPromotionStatus)) {
				$bindParams[] = $exPromotionStatus;
				$where .= ' AND p.promotion_status!=?';
			}
			if (!empty($keywords)){
				$bindParams[] = "%".$keywords."%";
				$where .= ' AND p.promotion_title like ?';
			}
			$sql = 'SELECT p.*,md.map_data_name FROM ' . DB_PREFIX . 'promotion as p LEFT JOIN ' . DB_PREFIX . 'map_data as md ON p.map_data_id=md.map_data_id LEFT JOIN ' . DB_PREFIX . 'map as m ON md.map_id=m.map_id' . $where;
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
			$result = $result->toArray();
			if (CACHING) {
				$this->cache->save($tag, $result);
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
			$where = $limit = '';
			if (isset($params['project_id']) && $params['project_id']>0){
				$where = ' WHERE m.project_id=?';
				$bindParams[] = $params['project_id'];
			}
			if (isset ( $params ['status'] ) && $params ['status'] > 0 ) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' p.promotion_status=?';
				$bindParams [] = $params ['status'];
			}
			if (isset ( $params ['exstatus'] ) && $params ['exstatus'] > 0 ) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' p.promotion_status!=?';
				$bindParams [] = $params ['exstatus'];
			}
			if (isset ( $params ['starttime'] ) && $params ['starttime'] === 1 ) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' p.promotion_starttime>?';
				$bindParams [] = time();
			}
			if (isset ( $params ['keywords'] ) && $params ['keywords'] !='' ) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' p.promotion_title like ?';
				$bindParams [] = "%".$params ['keywords']."%";
			}
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $offset . ',' . $params ['psize'];
			}
			$sql = 'SELECT p.*,md.map_data_name FROM ' . DB_PREFIX . 'promotion as p LEFT JOIN ' . DB_PREFIX . 'map_data as md ON p.map_data_id=md.map_data_id LEFT JOIN ' . DB_PREFIX . 'map as m ON md.map_id=m.map_id' . $where.$limit;
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
		return DB_PREFIX . 'promotion';
	}
}
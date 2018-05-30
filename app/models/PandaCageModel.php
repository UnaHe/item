<?php

class PandaCageModel extends ModelBase
{
	const TAG_PREFIX = 'PandaCageModel_';

	/**
	 * @param $department_id
	 * @return bool
	 */
	public function getDetailsById($cage_id){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById'. $cage_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'cage_id = ?1',
					'bind' => array(1 => $cage_id)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	/**
	 * @param $map_gid
	 * @return bool
	 */
	public function getDetailsByMapGid($map_gid){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByMapGid_'.$map_gid);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'map_gid = ?1',
					'bind' => array(1 => $map_gid)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	/**
	 * @return bool
	 */
	public function getListSimple($project_id){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getListSimple'.$project_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->find(
				array(
					'conditions' => 'project_id = ?1',
					'bind' => array(1 => $project_id)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getList($params){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList',$params);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$where = $countWhere = $limit = '';
			if(isset ( $params ['project_id'] ) && !empty($params['project_id'])){
				$where .= ' WHERE project_id =?';
				$bindParams [] = $params ['project_id'];
				$countWhere = 'project_id= ' . $params['project_id'];
			}
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$pageCount = ceil($this->count($countWhere) / $params ['psize']);
				if ($params ['page'] > $pageCount && $pageCount > 0) {
					$params ['page'] = $pageCount;
				}
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
			}
			$sql = 'select * from n_panda_cage '.$where.$limit;
			$data = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			if($data->valid()){
				$result['data'] = $data->toArray();
			}else{
				$result['data'] = [];
			}
			$result['pageCount'] = $pageCount;
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getSource()
	{
		return DB_PREFIX . 'panda_cage';
	}
}

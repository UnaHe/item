<?php

class PandaModel extends ModelBase
{
	const TAG_PREFIX = 'PandaModel_';

	/**
	 * @param $doctor_id
	 * @return bool
	 */
	public function getDetailsById($panda_id){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $panda_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'panda_id = ?1',
					'bind' => array(1 => $panda_id)
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
			$where = '';
			if (isset($params['project_id']) && $params['project_id']>0){
				$where .= (empty($where)?' WHERE':' AND').' p.project_id=?';
				$bindParams[] = $params['project_id'];
			}
			if (isset($params['cage_id']) && $params['cage_id']>0){
				$where .= (empty($where)?' WHERE':' AND').' p.cage_id=?';
				$bindParams[] = $params['cage_id'];
			}
			$sql = 'SELECT p.*,pc.cage_name from n_panda as p LEFT JOIN n_panda_cage as pc on p.cage_id = pc.cage_id '.$where;
//			echo $sql;die;
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql,$bindParams));
			if(!$result->valid()){
				$result = false;
			}
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getSource()
	{
		return DB_PREFIX . 'panda';
	}
}

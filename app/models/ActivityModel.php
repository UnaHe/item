<?php
class ActivityModel extends ModelBase {
	const TAG_PREFIX = 'ActivityModel_';
	public function getDetailsById($activity_id){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $activity_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'activity_id = ?1',
					'bind' => array(1 => $activity_id)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

	public function getListByProjectId($project_id){
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getListByProjectId', $project_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->find(
				array(
					'conditions' => 'project_id = ?1',
					'bind' => array(1 => $project_id)
				)
			);
			$result = $result->toArray();
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

    public function getActivity(array $params = array())
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getActivity', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $bindParams = array();
            $where = $limit = $order = '';
            if (isset($params['project_id']) && $params['project_id']>0){
                $where .= (empty($where)?' WHERE':' AND').' a.project_id=?';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['activity_id']) && $params['activity_id']>0){
                $where .= (empty($where)?' WHERE':' AND').' a.activity_id=?';
                $bindParams[] = $params['activity_id'];
            }

            $time = time();
            $whereTime = ' AND a.activity_end_time >='.$time.' AND '.$time.'>= a.activity_start_time';

            $sql = 'SELECT a.* FROM ' . DB_PREFIX . 'activity as a '.$where.$whereTime;

            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save ( $tag, $result, 864000, null, array (
                    self::TAG_PREFIX . 'getActivity',
                ) );
            }
        }
        return $result;
    }


	public function getSource() {
		return DB_PREFIX . 'activity';
	}
}
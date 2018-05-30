<?php

class AccessModel extends ModelBase
{
	public function getList($role , $project_id=0)
	{
		$tag = CacheBase::makeTag(self::class . 'getList', [$role,$project_id]);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->find(
				[
					'conditions' => 'role=?1 and project_id=?2',
					'bind' => array(1 => $role, 2 => $project_id),
                    'order'=>'resource DESC,operation DESC'
				]
			);
			$result = $result->toArray();
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}
	

	public function getSource()
	{
		return DB_PREFIX . 'item_access';
	}
}

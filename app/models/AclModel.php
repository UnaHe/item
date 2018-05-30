<?php

class AclModel extends ModelBase
{
	const TAG_PREFIX = 'AclModel_';
	public function reset()
	{
		unset($this->id);

	}

	public function getList($role , $system , $project_id=0)
	{
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', [$role,$system,$project_id]);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->find(
				array(
					'conditions' => 'role=?1 and system=?2 and project_id=?3',
					'bind' => array(1 => $role, 2 => $system ,3 => $project_id),
                    'order'=>'resource DESC,operation DESC'
				)
			);
			$result = $result->toArray();
//			print_r($result);die;
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}
	

	public function getSource()
	{
		return DB_PREFIX . 'acl';
	}
}

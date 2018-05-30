<?php
class WorkerModel extends ModelBase {
	const TAG_PREFIX = 'WorkerModel_';
	public function getDetailsByMobile($mobile) {
		$tag = self::makeTag(self::TAG_PREFIX.'getDetailsByMobile',$mobile);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT w.* FROM ' . DB_PREFIX . 'worker as w LEFT JOIN ' . DB_PREFIX . 'worker_category as wc ON w.worker_category_id=wc.worker_category_id WHERE w.worker_mobile=? limit 1';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, [$mobile] ) );
			$result = $result->toArray ();
			$result = empty($result)?false:$result[0];
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}
	public function getList(array $params) {
		$tag = self::makeTag(self::TAG_PREFIX.'getList',$params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $offset . ',' . $params ['psize'];
			}
			$sql = 'SELECT mu.*,mug.name as man_user_group_name,mug.role as man_user_group_role FROM ' . DB_PREFIX . 'mp_user as mu LEFT JOIN ' . DB_PREFIX . 'mp_user_group as mug ON mu.group_id=mug.id ORDER BY id DESC' . $limit;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList' 
				) );
			}
		}
		return $result;
	}
	public function getSource() {
		return DB_PREFIX . 'worker';
	}
}

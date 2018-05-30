<?php
class MpGroup extends ModelBase {
	const TAG_PREFIX = 'MpGroupModel_';
	public function getGroups($includeAdmin=true) {
		$tag = self::TAG_PREFIX . 'getGroups';
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = '';
			if (!$includeAdmin){
				$where = ' WHERE role!="administrator"';
			}
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'mp_user_group'.$where;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	public function getSource() {
		return DB_PREFIX . 'mp_user_group';
	}
}

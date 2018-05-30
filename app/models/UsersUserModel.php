<?php
class UsersUserModel extends ModelBase {
	const TAG_PREFIX = 'UsersUserModel_';
	public function getDetailsByName($name) {
            //用户名+模型+方法 md5 加密
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetails_' ,$name);
//                var_dump($tag);exit;

		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT uu.*,uug.name as user_group_name,uug.role as users_user_group_role,p.project_name FROM ' . DB_PREFIX . 'users_user as uu LEFT JOIN ' . DB_PREFIX . 'users_user_group as uug ON uu.users_user_group_id=uug.id LEFT JOIN ' . DB_PREFIX . 'project as p ON p.project_id = uu.project_id WHERE uu.users_user_name=? limit 1';
//                    $sql = 'SELECT uu.* FROM ' . DB_PREFIX . 'users_user as uu WHERE uu.users_user_name=? limit 1';
            
                        $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
					$name
			) ) );
			$result = $result->valid()?$result->toArray ()[0]:false;
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
        public function getDetailsByUName($name) {
     
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetails_' ,$name);
//                var_dump($tag);exit;

		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT uu.*,uug.name as user_group_name,uug.role as users_user_group_role,p.project_name FROM ' . DB_PREFIX . 'users_user as uu LEFT JOIN ' . DB_PREFIX . 'users_user_group as uug ON uu.users_user_group_id=uug.id LEFT JOIN ' . DB_PREFIX . 'project as p ON p.project_id = uu.project_id WHERE uu.users_user_name=? limit 1';
//                    $sql = 'SELECT uu.* FROM ' . DB_PREFIX . 'users_user as uu WHERE uu.users_user_name=? limit 1';
            
                        $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
					$name
			) ) );
//			$result = $result->valid()?$result->toArray ()[0]:false;
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	public function getList(array $params) {
		$tag = CacheBase::TAG_PREFIX . 'getList_' . implode ( '_', $params );
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
	public function getGroups($includeAdmin=true) {
		$tag = CacheBase::TAG_PREFIX . 'getGroups';
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = '';
			if (!$includeAdmin){
				$where = ' WHERE role!="administrator"';
			}
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'users_user_group'.$where;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	public function getSource() {
		return DB_PREFIX . 'users_user';
	}
}

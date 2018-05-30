<?php
class MpUserModel extends ModelBase
{
    public function getDetailsByOpenidSimple($openId) {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByOpenidSimple' ,$openId);
        $result = CACHING ? $this->cache->get ( $tag ) : false;
        if (! $result) {
            $sql = 'SELECT mu.*,mug.name as mp_user_group_name,mug.role as mp_user_group_role FROM ' . DB_PREFIX . 'mp_user as mu LEFT JOIN ' . DB_PREFIX . 'mp_user_group as mug ON mu.mp_user_group_id=mug.id WHERE mu.mp_user_wx_openid=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
                $openId
            ) ) );
            $result = $result->valid()?$result->toArray ()[0]:false;
            if (CACHING) {
                $this->cache->save ( $tag, $result );
            }
        }
        return $result;
    }
	public function getDetailsByNameSimple($name) {
		$tag = CacheBase::makeTag(self::class . 'getDetailsByNameSimple' ,$name);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT mu.*,mug.name as mp_user_group_name,mug.role as mp_user_group_role FROM ' . DB_PREFIX . 'mp_user as mu LEFT JOIN ' . DB_PREFIX . 'mp_user_group as mug ON mu.mp_user_group_id=mug.id WHERE mu.mp_user_name=? limit 1';
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
	public function getListSimple(array $params=[]) {
        $tag = CacheBase::makeTag(self::class . 'getListSimple' ,$params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
            $pageCount = 1;
            $limit = '';
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(mp_user_id) FROM ' . DB_PREFIX . 'mp_user';
                $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sqlCount));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = ceil($count / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
			}
			$sql = 'SELECT mu.*,mug.name as group_name,mug.role as group_role FROM ' . DB_PREFIX . 'mp_user as mu LEFT JOIN ' . DB_PREFIX . 'mp_user_group as mug ON mu.mp_user_group_id=mug.id ORDER BY mu.mp_user_id DESC' . $limit;
			$data = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql ) );
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::class . 'getList' 
				) );
			}
		}
		return $result;
	}
	public function getSource() {
		return DB_PREFIX . 'mp_user';
	}
}

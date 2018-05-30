<?php
class ShowDeviceModel extends ModelBase {
	public function getDetailsByCode($showDeviceCode) {
		$tag = CacheBase::makeTag(self::class . 'getDetailsByCode', $showDeviceCode);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'show_device_code = ?1',
					'bind' => array(1 => $showDeviceCode)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by sd.show_device_create_at DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE (? = any(string_to_array(sd.show_device_project,\',\')) OR sd.show_device_project IS NULL)';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['status'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' sd.show_device_status=?';
                $bindParams [] = $params ['status'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = "SELECT count(sd.show_device_code) from ".DB_PREFIX."show_device as sd" . $where;

                $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sqlCount, $bindParams));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = ceil($count / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = "select sd.*,array_to_string(array(select CONCAT(p.project_id,'.',p.project_name) from ".DB_PREFIX."project as p where p.project_id::text = any(string_to_array(sd.show_device_project,','))),',') as projects from ".DB_PREFIX."show_device as sd" . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id'],
                ));
            }
        }
        return $result;
    }

	public function getSource() {
		return DB_PREFIX . 'show_device';
	}
}
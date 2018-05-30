<?php
class ClientGroupModel extends ModelBase
{
    public function getDetailsByClientGroupId($clientGroupId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByClientGroupId', $clientGroupId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'client_group_id = ?1',
                    'bind' => array(1 => $clientGroupId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    public function getList(array $params=[])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $pageCount = 1;
            $parameter = array(
                'order' => 'client_group_id DESC'
            );
            $parameterCount = null;
            if (!is_null($params ['project_id'])) {
                $parameter['conditions'] = 'project_id=?1';
                $parameter['bind'][1] = $params ['project_id'];
                $parameterCount = 'project_id="' . $params ['project_id'] . '"';
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($parameterCount) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $parameter['limit'] = $params ['psize'];
                $parameter['offset'] = $offset;
            }
            $data = $this->find($parameter);
            $result['pageCount'] = $pageCount;
            $result['data'] = $data->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id']
                ));
            }
        }
        return $result;
    }

	public function getSource() {
		return DB_PREFIX . 'client_group';
	}
}

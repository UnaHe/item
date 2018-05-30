<?php
class ProjectModel extends ModelBase {

    public static $projectTypes = ['hospital','scenic','mall','station'];
	/**
	 * @param int $projectId
	 * @return object
	 *
	 * */
    public function getDetailsByProjectId($projectId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectId', $projectId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as p LEFT JOIN '.DB_PREFIX.'project_modules as pm ON p.project_id=pm.project_id WHERE p.project_id=?';
            $sql = sprintf($sqlTemplate, "pm.*,p.*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$projectId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }
	/**
	 * Add log message
	 *
	 * @param array $params
	 * @return array
	 */

	public function getList(array $params = [])
	{
		$tag = CacheBase::makeTag(self::class . 'getList', $params);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$pageCount = 1;
			$parameter = array(
				'order' => 'project_id DESC'
			);

			if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
				$pageCount = ceil($this->count(null) / $params ['psize']);
				if ($params ['page'] > $pageCount && $pageCount > 0) {
					$params ['page'] = $pageCount;
				}
				$offset = ($params ['page']-1) * $params ['psize'];
				$parameter['limit'] = $params ['psize'];
				$parameter['offset'] = $offset<=0?0:$offset;
			}
			$data = $this->find($parameter);
			$result['pageCount'] = $pageCount;
			$result['data'] = $data->toArray();
			if (CACHING) {
				$this->cache->save($tag, $result, 864000, null, array(
					self::class . 'getList',
				));
			}
		}
		return $result;
	}

	public function getListByMerchant(){
		$tag = CacheBase::makeTag(self::class . 'getListByMerchant');
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$sql = 'SELECT P.project_id,p.project_name,m.merchant_id FROM n_project AS P LEFT JOIN n_merchant AS M ON P .project_id = M .project_id WHERE M.merchant_group_id = 1 OR M.merchant_group_id IS NULL GROUP BY p.project_id,m.merchant_id ORDER BY P.project_id';
			$result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql));
			if(!$result->valid()){
				$result = false;
			}
			if (CACHING) {
				$this->cache->save($tag, $result, 864000, null, array(
					self::class . 'getList',
				));
			}
		}
		return $result;
	}


	public function getSource() {
		return DB_PREFIX . 'project';
	}
}
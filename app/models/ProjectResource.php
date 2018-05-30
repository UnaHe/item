<?php
class ProjectResource extends ModelBase {
	const TAG_PREFIX = 'ProjectResourceModel_';


	public function getDetails($resource_id) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetails_' . $resource_id);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$result = $this->findFirst([
			   'conditions' => 'resource_id = ?1',
			   'bind' => [1=>$resource_id]
			]);
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	
	public function getlistByPath($resource_path) {
	    $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByPath_' . $resource_path);
	    $result = CACHING ? $this->cache->get ( $tag ) : false;
	    if (! $result) {
	        $result = $this->find([
	            'conditions' => 'resource_path = ?1',
	            'bind' => [1=>$resource_path]
	        ]);
	        if (CACHING) {
	            $this->cache->save ( $tag, $result );
	        }
	    }
	    return $result;
	}
	public function getList(array $params = array()) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            
			if (isset ( $params ['resource_category'] ) && ! is_null ( $params ['resource_category'] ) && !empty($params ['resource_category'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' pr.resource_category=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'resource_category="' . $params['resource_category'] . '"';
                $bindParams [] = $params ['resource_category'];
			}
			if (isset ( $params ['project_id'] ) && ! is_null ( $params ['project_id'] ) && !empty($params ['resource_category'])) {
				$where .= (empty($where) ? ' WHERE' : ' AND') . ' pr.project_id=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'project_id="' . $params['project_id'] . '"';
                $bindParams [] = $params ['project_id'];
			}
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $offset . ',' . $params ['psize'];
			}
			if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
			    $pageCount = ceil($this->count($countWhere) / $params ['psize']);
			    if ($params ['page'] > $pageCount && $pageCount > 0) {
			        $params ['page'] = $pageCount;
			    }
			    $offset = ($params ['page'] - 1) * $params ['psize'];
			    $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
			}
			$sql = 'SELECT pr.*,p.project_name,mp.name,mp.map_polygon_id FROM ' . DB_PREFIX . 'project_resource as pr left join ' . DB_PREFIX . 'project as p on pr.project_id = p.project_id left join ' . DB_PREFIX . 'map_polygon as mp on pr.map_gid = mp.map_gid ' . $where . 'order by resource_upload_time desc' . $limit;
			$data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql, $bindParams));
			$result['data'] = $data;
            $result['pageCount'] = $pageCount;
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
						self::TAG_PREFIX . 'getList_' . $params ['resource_category'],
						self::TAG_PREFIX , 'getLIST_' . $params ['resource_category'] .'_'.$params ['project_id'],
				) );
			}
		}
		return $result;
	}

	
	/**
	 * 获取表名
	 * @see Phalcon\Mvc.Model::getSource()
	 */
	public function getSource() {
		return DB_PREFIX . 'project_resource';
	}
}
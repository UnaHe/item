<?php
class MapDataCategoryModel extends ModelBase {
	const TAG_PREFIX = 'MapDataCategoryModel_';
    public function initialize()
    {
        $this->belongsTo("map_data_category_id", "MapData", "map_data_category_id");
        $this->belongsTo("project_id", "project", "project_id");
    }
    
    
	/**
	 * 通过项目id获取分类列表
	 */
    public function getListByProjectId($project_id){
        $tag = self::makeTag(self::TAG_PREFIX . 'getListByProjectId', $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
          	$sql = 'SELECT mdc.*,p.project_name FROM '.DB_PREFIX.'map_data_category mdc LEFT JOIN '.DB_PREFIX.'project p ON mdc.project_id = p.project_id where mdc.project_id =?';
          	$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
          			$project_id
          	) ) );
          	$result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    
   
    /**
     * 获取所有地图分类列表
     */
    public function getlist(){
    	$tag = self::makeTag(self::TAG_PREFIX . 'getlist','');
    	$result = CACHING ? $this->cache->get($tag) : false;
    	if(!$result){
    		$sql = 'SELECT mdc.*,p.project_name FROM '.DB_PREFIX.'map_data_category mdc LEFT JOIN '.DB_PREFIX.'project p ON mdc.project_id = p.project_id';
    		$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql ) );
			$result = $result->toArray ();
    		if (CACHING) {
    			$this->cache->save($tag, $result);
    		}
    	}
    	return $result;
    }
    
    
    /**
     * 根据map_data_category_id获取单条数据
     */
	public function getDetails($map_data_category_id) {
		$tag = self::makeTag(self::TAG_PREFIX . 'getDetails',$map_data_category_id);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT mdc.*,p.project_name FROM '.DB_PREFIX.'map_data_category mdc LEFT JOIN '.DB_PREFIX.'project p ON mdc.project_id = p.project_id where mdc.map_data_category_id =?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
					$map_data_category_id 
			) ) );
			$result = $result->toArray()[0];
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	
	/**
	 * 获取单个字段
	 */
	public function checkname($project_id,$name){
		$tag = self::makeTag(self::TAG_PREFIX . 'checkname', [$project_id,$name]);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = MapDataCategoryModel::findFirst(
						array(
							'conditions'=>'project_id = ?1 AND map_data_category_name = ?2',
							'bind'=>array(
										1 	=> $project_id,
										2	=> $name,
									)
						)
					);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}
	
	
	
	/**
	 * 获取表名
	 * @see Phalcon\Mvc.Model::getSource()
	 */
	public function getSource() {
		return DB_PREFIX . 'map_data_category';
	}
}
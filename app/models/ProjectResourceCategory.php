<?php
class ProjectResourceCategory extends ModelBase {
	const TAG_PREFIX = 'ProjectResourceCategoryModel_';
	public function initialize()
	{
		$this->belongsTo("project_resource_category_id", "ProjectResource", "project_resource_category_id");
	}
	
	
	/**
	 * 获取分类列表
	 */
	public function getList($project_id = null) {
		$tag = self::TAG_PREFIX . 'getList_' . $project_id;
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = '';
			$bindParams = array ();
			if (! is_null ( $project_id )) {
				$where .= ' WHERE project_id=?';
				$bindParams [] = $project_id;
			}
			$sql = 'SELECT * FROM ' . DB_PREFIX . 'project_resource_category' . $where . ' ORDER BY project_resource_category_sort_order DESC,project_resource_category_id ASC';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList_' . $project_id
				) );
			}
		}
		return $result;
	}
	
	
	/**
	 * 查询单条资源分类信息
	 */
	public function getDetails($project_resource_category_id) {
		$tag = self::TAG_PREFIX . 'getDetails_' . $project_resource_category_id;
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT * FROM '.DB_PREFIX.'project_resource_category where project_resource_category_id =?';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
					$project_resource_category_id 
			) ) );
			$result = $result->toArray()[0];
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	/**
	 * 检测同项目下是否有此分类名
	 */
	public function checkresourcename($project_id,$name){
		
		$tag = self::makeTag(self::TAG_PREFIX . 'checkresourcename_', $project_id);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = ProjectResourceCategory::findFirst(
					array(
							'conditions'=>'project_id = ?1 AND project_resource_category_name = ?2',
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
		return DB_PREFIX . 'project_resource_category';
	}
}
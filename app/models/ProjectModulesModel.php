<?php

class ProjectModulesModel extends ModelBase
{
    public function initialize()
    {
    }


    /**
     * 获取分类列表
     */
    public function getList($project_id = null)
    {
        $tag = self::class . 'getList_' . $project_id;
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $bindParams = array();
            if (!is_null($project_id)) {
                $where .= ' WHERE project_id=?';
                $bindParams [] = $project_id;
            }
            $sql = 'SELECT * FROM ' . DB_PREFIX . 'project_resource_category' . $where . ' ORDER BY project_resource_category_sort_order DESC,project_resource_category_id ASC';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList_' . $project_id
                ));
            }
        }
        return $result;
    }


    public function getDetailsByProjectId($projectId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectId', $projectId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . DB_PREFIX . 'project_modules where project_id =?';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$projectId]
                ));
            $result = $result->valid()?$result->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * 检测同项目下是否有此分类名
     */
    public function checkresourcename($project_id, $name)
    {

        $tag = self::makeTag(self::class . 'checkresourcename_', $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = ProjectResourceCategory::findFirst(
                array(
                    'conditions' => 'project_id = ?1 AND project_resource_category_name = ?2',
                    'bind' => array(
                        1 => $project_id,
                        2 => $name,
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
    public function getSource()
    {
        return DB_PREFIX . 'project_modules';
    }
}
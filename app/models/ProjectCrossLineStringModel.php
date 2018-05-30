<?php

class ProjectCrossLineStringModel extends ModelBase
{
    public function initialize()
    {
    }

    public function reset()
    {
        unset($this->project_cross_linestring_id);
    }


    public function getDetailsByProjectCrossLineStringId($projectCrossLineStringId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectCrossLineStringId', $projectCrossLineStringId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'project_cross_linestring_id = ?1',
                    'bind' => array(1 => $projectCrossLineStringId)
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
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' pcl.project_id=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'project_id="' . $params['project_id'] . '"';
                $bindParams [] = $params ['project_id'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($countWhere) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT pcl.* FROM ' . DB_PREFIX . 'project_cross_linestring as pcl' . $where . ' order by pcl.project_cross_linestring_id DESC' . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . @$params ['project_id']
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'project_cross_linestring';
    }
}
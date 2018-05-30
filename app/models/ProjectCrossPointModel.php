<?php

class ProjectCrossPointModel extends ModelBase
{
    public function initialize()
    {
    }

    public function reset()
    {
        unset($this->project_cross_point_id);
    }

    public function getDetailsByProjectCrossPointId($projectCrossPointId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectCrossPointId', $projectCrossPointId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'project_cross_point_id = ?1',
                    'bind' => array(1 => $projectCrossPointId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    public function getDetailsByIdAndProjectId($id,$projectId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdAndProjectId', [$id,$projectId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'id = ?1 and project_id=?2',
                    'bind' => array(1 => $id, 2=>$projectId)
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
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' pcp.project_id=?';
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
            $sql = 'SELECT pcp.* FROM ' . DB_PREFIX . 'project_cross_point as pcp' . $where . ' order by pcp.project_cross_point_id DESC' . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data;
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . @$params ['map_id']
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'project_cross_point';
    }
}
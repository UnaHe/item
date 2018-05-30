<?php

class ProjectDeviceModel extends ModelBase
{
    /**
     * @param $projectId
     * @param bool $bind
     * @return bool|\Phalcon\Mvc\Model\ResultsetInterface
     */
    public function getListByProjectId($projectId, $bind = false)
    {
        $tag = CacheBase::makeTag(self::class . 'getListByProjectId', [$projectId, $bind]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'project_id = ?1' . (!$bind ? ' AND map_point_id IS NULL' : ' AND map_point_id IS NOT NULL'),
                    'bind' => array(1 => $projectId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $projectDeviceId
     * @return bool|\Phalcon\Mvc\Model
     */

    public function getDetailsByProjectDeviceId($projectDeviceId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectDeviceId', $projectDeviceId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {
                $result = $this->findFirst(
                    array(
                        'conditions' => 'project_device_id = ?1',
                        'bind' => array(1 => $projectDeviceId)
                    )
                );
            }catch (Exception $e){
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByProjectDeviceIdSimple($projectDeviceId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectDeviceIdSimple', $projectDeviceId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT pd.*,mp.name as point_name,m.map_name FROM ' . DB_PREFIX . 'project_device as pd LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pd.map_point_id=mp.map_point_id LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=mp.map_id WHERE pd.project_device_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$projectDeviceId]));
            $result = $result->toArray();
            $result = empty($result) ? [] : $result[0];
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByMapPointId($mapPointId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapPointId', $mapPointId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'map_point_id=?1',
                    'bind' => [1 => $mapPointId]
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    
    public function getListBySameIdAndMapId($id,$map_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getListBySameIdAndMapId', [$id,$map_id]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'id=?1 and map_id=?2',
                    'bind' => [1 => $id,2=>$map_id]
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getList(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $bindParams = [];
            $where = $countWhere = $limit = $order = '';
            $pageCount = 1;
            if (isset($params['project_id']) && $params['project_id'] > 0) {
                $where .= ' WHERE pd.project_id=?';
                $bindParams[] = $params['project_id'];
                $countWhere = 'project_id="' . $params['project_id'] . '"';
            }
            if (isset($params['bind'])) {
                if ($params['bind'] == 1) {
                    $where .= (empty($where) ? ' WHERE' : ' AND') . ' pd.map_point_id IS NOT NULL';
                    $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'map_point_id IS NOT NULL';
                } elseif ($params['bind'] == 0) {
                    $where .= (empty($where) ? ' WHERE' : ' AND') . ' pd.map_point_id IS NULL';
                    $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'map_point_id IS NULL';
                }
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($countWhere) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT pd.*,mp.name as point_name,m.map_name FROM ' . DB_PREFIX . 'project_device as pd LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pd.map_id=mp.map_id and pd.id = mp.id LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=mp.map_id' . $where . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data;
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . @$params['project_id'],
                ));
            }
        }
        return $result;
    }

    public function getListByMapPointId($mapPointId)
    {
        $tag = CacheBase::makeTag(self::class . 'getListByMapPointId', $mapPointId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $bindParams = [];
            $where = ' WHERE pd.map_point_id=?';
            $bindParams[] = $mapPointId;
            $sql = 'SELECT pd.*,mp.name as point_name,m.map_name FROM ' . DB_PREFIX . 'project_device as pd LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON pd.map_point_id=mp.map_point_id LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=mp.map_id' . $where;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $mapPointId,
                ));
            }
        }
        return $result;
    }

    public function reset()
    {
        unset($this->project_device_id);
    }

    public function getSource()
    {
        return DB_PREFIX . 'project_device';
    }
}
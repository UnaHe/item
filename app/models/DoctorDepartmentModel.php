<?php

class DoctorDepartmentModel extends ModelBase
{
    /**
     * @param $department_id
     * @return bool|\Phalcon\Mvc\Model
     */
    public function getDetailsById($department_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById_' . $department_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'department_id = ?1',
                    'bind' => array(1 => $department_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $map_gid
     * @return bool
     */
    public function getDetailsByMapGid($map_gid)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapGid_' . $map_gid);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'select * from n_doctor_department  where \'' . $map_gid . '\' = any (string_to_array("map_gid",\',\'))';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            if (!$result->valid()) {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function getListSimple($project_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple' . $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'project_id = ?1',
                    'bind' => array(1 => $project_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getList($params)
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = $orderBy = '';
            if (isset ($params ['project_id']) && !empty($params['project_id'])) {
                $where .= (empty($where) ? ' WHERE ' : ' AND ') . ' d.project_id =?';
                $bindParams [] = $params ['project_id'];
                $countWhere = 'project_id= ' . $params['project_id'];
            }
            if (isset ($params ['map_id']) && !empty($params['map_id'])) {
                $where .= (empty($where) ? ' WHERE ' : ' AND ') . ' m.map_id =?';
                $bindParams [] = $params ['map_id'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($countWhere) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
                $result['pageCount'] = $pageCount;
            }
            $orderBy = ' ORDER BY d.department_id';
            $sql = 'select d.*,m.map_name from n_doctor_department as d join n_map as m on d.map_id = m.map_id ' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            if ($data->valid()) {
                $result['data'] = $data->toArray();
            } else {
                $result['data'] = [];
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getListInIdStr($idStr, $project_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getListInIdStr_' . $idStr . $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'select department_name from n_doctor_department WHERE department_id in (' . $idStr . ') and project_id = ' . $project_id;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            if ($result->valid()) {
                $result = $result->toArray();
            } else {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function clientGetList($project_id)
    {
        $tag = CacheBase::makeTag(self::class . 'clientGetList', $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'project_id = ?1',
                    'bind' => array(1 => $project_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . $project_id
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'doctor_department';
    }
}

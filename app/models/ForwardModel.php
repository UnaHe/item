<?php

class ForwardModel extends ModelBase
{
    public function reset()
    {
        unset($this->forward_id);
    }

    /**
     * @param $forward_id
     * @return bool|\Phalcon\Mvc\Model
     */
    public function getDetailsById($forward_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $forward_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'forward_id = ?1',
                    'bind' => array(1 => $forward_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByForwardId($forward_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByForwardId', $forward_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT f.*,mp.name,map_name,p.* FROM n_forward AS f LEFT JOIN n_map_point AS mp ON f.map_id = mp.map_id AND f.ID = mp.ID LEFT JOIN n_map AS m ON mp.map_id = m.map_id left JOIN n_project as p on m.project_id = p.project_id WHERE f.forward_id =?';
//			echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$forward_id]));
            if ($result->valid()) {
                $result = $result->toArray()[0];
            } else {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $mapPointId
     * @return bool|\Phalcon\Mvc\Model
     */

    public function getDetailsByIdAndMapId($Id, $mapId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByIdAndMapId', [$Id, $mapId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'id = ?1 and map_id = ?2',
                    'bind' => array(1 => $Id, 2 => $mapId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param array $params
     * @return array
     */

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = [];
            $pageCount = 1;
            if (isset($params ['project_id']) && !empty($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' m.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset($params ['id']) && !empty($params ['id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' f.id=?';
                $bindParams [] = $params ['id'];
            }
            if (isset($params ['map_id']) && !empty($params ['map_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' f.map_id=?';
                $bindParams [] = $params ['map_id'];
            }
            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " (f.map_id=? OR f.id=? OR f.forward_id=? OR mp.name like '%" . $params ['keywords'] . "%')";
                $bindParams [] = $params ['keywords'];
                $bindParams [] = $params ['keywords'];
                $bindParams [] = $params ['keywords'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(f.forward_id) FROM ' . DB_PREFIX . 'forward as f LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON f.id=mp.id and f.map_id=mp.map_id LEFT JOIN ' . DB_PREFIX . 'map as m ON f.map_id=m.map_id' . $where;
                $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sqlCount, $bindParams));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = ceil($count / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT f.*,mp.name,mp.id,mp.map_point_id,m.map_name,p.* FROM ' . DB_PREFIX . 'forward as f LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON f.id=mp.id and f.map_id=mp.map_id LEFT JOIN ' . DB_PREFIX . 'map as m ON f.map_id=m.map_id LEFT JOIN '.DB_PREFIX.'project as p ON m.project_id=p.project_id' . $where . ' order by f.forward_id DESC' . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList'.@$params['project_id']
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'forward';
    }

    /**
     * 获取项目列表.
     * @param array $params
     * @return bool
     */
    public function wxGetProjectList($params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'wxGetProjectList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $pageCount = 1;

            $where = '';
            $orders = ' ORDER BY p.project_id DESC';
            $bindParams = [];
            $sqlTemplate = 'SELECT %s FROM (
                                SELECT m.project_id AS m_project_id, max(f.forward_id) AS f_forward_id 
                                FROM ' . $this->getSource() . ' AS f 
                                INNER JOIN n_map AS m ON f.map_id = m.map_id 
                                GROUP BY m.project_id) AS a 
                            LEFT JOIN n_project AS p ON a.m_project_id = p.project_id';

            if (isset($params['order']) && $params['order'] !== '') {
                $orders = ' ORDER BY ' . $params['order'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $res = $this->sqlLimit($sqlTemplate , 'COUNT(p.project_id)' , $where , $bindParams, $params['page'],$params['psize']);
                $limit = $res['limit'];
                $pageCount = $res['pageCount'];
            }

            $sql = sprintf($sqlTemplate, 'a.f_forward_id, p.project_id, p.project_name') . $where . $orders . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple(null, $this, $this->getReadConnection()->query($sql, $bindParams));

            $result['data'] = $data->valid() ? $data->toArray() : false;
            $result['pageCount'] = $pageCount;

            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'wxGetProjectList',
                ));
            }
        }
        return $result;
    }

}
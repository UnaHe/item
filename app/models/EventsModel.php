<?php

class EventsModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetailsByOpenidAndProjectIdSimple($openid,$projectId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByOpenidAndProjectIdSimple', [$openid,$projectId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT c.*,p.*,cg.client_group_name,cg.client_group_role,pm.project_modules_client_modules FROM ' . DB_PREFIX . 'client as c LEFT JOIN ' . DB_PREFIX . 'project as p ON c.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'project_modules as pm ON pm.project_id=c.project_id LEFT JOIN ' . DB_PREFIX . 'client_group as cg ON cg.client_group_id=c.client_group_id WHERE c.client_wx_openid=? AND c.project_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$openid,$projectId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . $projectId
                ));
            }
        }
        return $result;
    }

    public function getDetailsByClientIdSimple($clientId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByClientIdSimple', $clientId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT c.*,p.*,cg.client_group_name,cg.client_group_role,pm.project_modules_client_modules FROM ' . DB_PREFIX . 'client as c LEFT JOIN ' . DB_PREFIX . 'project as p ON c.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'project_modules as pm ON pm.project_id=c.project_id LEFT JOIN ' . DB_PREFIX . 'client_group as cg ON cg.client_group_id=c.client_group_id WHERE c.client_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$clientId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getList(array $params)
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $pageCount = 1;
            $parameter = array(
                'order' => 'client_id DESC'
            );
            $parameterCount = null;
            if (!is_null($params ['project_id'])) {
                $parameter['conditions'] = 'project_id=?1';
                $parameter['bind'][1] = $params ['project_id'];
                $parameterCount = 'project_id="' . $params ['project_id'] . '"';
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($parameterCount) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $parameter['limit'] = $params ['psize'];
                $parameter['offset'] = $offset;
            }
            $data = $this->find($parameter);
            $result['pageCount'] = $pageCount;
            $result['data'] = $data->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id']
                ));
            }
        }
        return $result;
    }

    public function getDetailsByAccountAndProjectIdSimple($account,$projectId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByAccountAndProjectIdSimple', [$account,$projectId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT c.*,p.*,cg.client_group_name,cg.client_group_role,pm.project_modules_client_modules FROM ' . DB_PREFIX . 'client as c LEFT JOIN ' . DB_PREFIX . 'project as p ON c.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'project_modules as pm ON pm.project_id=c.project_id LEFT JOIN ' . DB_PREFIX . 'client_group as cg ON cg.client_group_id=c.client_group_id WHERE c.client_account=? AND c.project_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$account,$projectId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . $projectId
                ));
            }
        }
        return $result;
    }

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by e.events_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE mp.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(e.events_id) FROM ' . DB_PREFIX . 'events as e' . $where;
                $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sqlCount, $bindParams));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = $count>0?ceil($count / $params ['psize']):1;
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT e.*,p.project_name,p.project_id,m.map_name FROM ' . $this->getSource() . ' as e LEFT JOIN ' . DB_PREFIX . 'map as m ON e.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp ON e.id=c.project_id LEFT JOIN ' . DB_PREFIX . 'client_group as cg ON cg.client_group_id=c.client_group_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id'],
                ));
            }
        }
        return $result;
    }


    public function getSource()
    {
        return DB_PREFIX . 'events';
    }
}

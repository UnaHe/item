<?php

class IbeaconWxModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetailsByDeviceId($deviceId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByDeviceId', $deviceId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'ibeacon_wx_device_id = ?1',
                    'bind' => array(1 => $deviceId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    public function getDetailsByDeviceIdSimple($deviceId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByDeviceIdSimple', $deviceId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT i.*,iw.*,ig.ibeacon_group_name,p.project_name,p.project_id,mp.name as map_point_name,mp.map_id FROM ' . DB_PREFIX . 'ibeacon_wx as iw LEFT JOIN ' . DB_PREFIX . 'ibeacon_group as ig ON ig.ibeacon_group_id=iw.ibeacon_group_id LEFT JOIN ' . DB_PREFIX . 'ibeacon as i ON i.ibeacon_wx_device_id=iw.ibeacon_wx_device_id LEFT JOIN ' . DB_PREFIX . 'project as p ON ig.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (mp.id=i.ibeacon_point_id AND mp.map_id=i.ibeacon_map_id) WHERE iw.ibeacon_wx_device_id=?';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$deviceId]));
            $result = $data->valid()?$data->toArray()[0]:false;
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
            $result = $this->find();
            $result = $result->toArray();
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
            $where = $limit = '';
            $pageCount = 1;
            $order = 'iw.ibeacon_wx_device_id DESC';
            $bindParams = array();
            if (isset ($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE ig.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $intKeywords = (int)$params ['keywords'];
                $whereExtra = '';
                if ($intKeywords !== 0) {
                    $whereExtra = " OR mp.id='" . $intKeywords . "' OR mp.gid='" . $intKeywords . "' OR iw.ibeacon_wx_major='" . $intKeywords . "' OR iw.ibeacon_wx_minor='" . $intKeywords . "' OR iw.ibeacon_group_id='" . $intKeywords . "'";
                }

                $where .= (empty($where) ? ' WHERE' : ' AND') . " (mp.name LIKE '%" . $params ['keywords'] . "%'" . $whereExtra . ")";
            }
            if (isset ($params ['orderby']) && $params ['orderby'] != '') {
                $order = $params ['orderby'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(iw.ibeacon_wx_device_id) FROM ' . DB_PREFIX . 'ibeacon_wx as iw LEFT JOIN ' . DB_PREFIX . 'ibeacon_group as ig ON ig.ibeacon_group_id=iw.ibeacon_group_id LEFT JOIN ' . DB_PREFIX . 'ibeacon as i ON i.ibeacon_wx_device_id=iw.ibeacon_wx_device_id LEFT JOIN ' . DB_PREFIX . 'project as p ON ig.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (mp.id=i.ibeacon_point_id AND mp.map_id=i.ibeacon_map_id)' . $where;
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
            $sql = 'SELECT i.*,iw.*,ig.ibeacon_group_name,p.project_name,mp.name as map_point_name,mp.map_point_id FROM ' . DB_PREFIX . 'ibeacon_wx as iw LEFT JOIN ' . DB_PREFIX . 'ibeacon_group as ig ON ig.ibeacon_group_id=iw.ibeacon_group_id LEFT JOIN ' . DB_PREFIX . 'ibeacon as i ON i.ibeacon_wx_device_id=iw.ibeacon_wx_device_id LEFT JOIN ' . DB_PREFIX . 'project as p ON ig.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'map_point as mp ON (mp.id=i.ibeacon_point_id AND mp.map_id=i.ibeacon_map_id)' . $where . ' order by ' . $order . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList' . @$params['project_id'],
                    self::class . 'getList' . @$params['group_id'],
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'ibeacon_wx';
    }
}
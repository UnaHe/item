<?php

class IbeaconWxApplyModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetailsByApplyIdSimple($applyId)
    {
        $tag = self::makeTag(self::class . 'getDetailsByApplyId', $applyId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT iwa.*,p.project_name,p.project_id FROM ' . $this->getSource() . ' as iwa LEFT JOIN ' . DB_PREFIX . 'ibeacon_group as ig ON ig.ibeacon_group_id=iwa.ibeacon_group_id LEFT JOIN ' . DB_PREFIX . 'project as p ON ig.project_id=p.project_id WHERE iwa.ibeacon_wx_apply_id = ?';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$applyId]));
            $result = $data->valid()?$data->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getList(array $params = [])
    {
        $tag = self::makeTag(self::class . 'getList', $params);
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
            $where = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by iwa.ibeacon_wx_apply_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE ig.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(iwa.ibeacon_wx_apply_id) FROM ' . $this->getSource() . ' as iwa LEFT JOIN ' . DB_PREFIX . 'ibeacon_group as ig ON ig.ibeacon_group_id=iwa.ibeacon_group_id LEFT JOIN ' . DB_PREFIX . 'project as p ON ig.project_id=p.project_id' . $where;
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

            $sql = 'SELECT iwa.*,p.project_name,p.project_id FROM ' . $this->getSource() . ' as iwa LEFT JOIN ' . DB_PREFIX . 'ibeacon_group as ig ON ig.ibeacon_group_id=iwa.ibeacon_group_id LEFT JOIN ' . DB_PREFIX . 'project as p ON ig.project_id=p.project_id' . $where . $orderBy.$limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'ibeacon_wx_apply';
    }
}
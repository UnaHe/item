<?php

class IbeaconGroupModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetailsByProjectIdAndGroupName($projectId, $groupName)
    {
        $tag = self::makeTag(self::class . 'getDetailsByProjectIdAndGroupName', [$projectId, $groupName]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'project_id = ?1 AND ibeacon_group_name = ?2',
                    'bind' => array(1 => $projectId, 2 => $groupName)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByGroupName($groupName)
    {
        $tag = self::makeTag(self::class . 'getDetailsByGroupName', $groupName);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'ibeacon_group_name = ?1',
                    'bind' => array(1 => $groupName)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByGroupId($groupId)
    {
        $tag = self::makeTag(self::class . 'getDetailsByGroupId', $groupId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'ibeacon_group_id = ?1',
                    'bind' => array(1 => $groupId)
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
            $orderBy = ' order by ig.ibeacon_group_id DESC';
            if (!empty($params ['project_id'])) {
                $where .= ' WHERE ig.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            $sql = 'SELECT ig.*,p.* FROM ' . DB_PREFIX . 'ibeacon_group as ig LEFT JOIN ' . DB_PREFIX . 'project as p ON ig.project_id=p.project_id' . $where . $orderBy;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $data->toArray();
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
        return DB_PREFIX . 'ibeacon_group';
    }
}
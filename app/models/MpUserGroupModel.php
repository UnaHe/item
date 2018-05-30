<?php

class MpUserGroupModel extends ModelBase
{
    public function getListSimple()
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . DB_PREFIX . 'mp_user_group';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $data->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'
                ));
            }
        }
        return $result;
    }

    public function getDetailsByGroupIdSimple($groupId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByGroupIdSimple', $groupId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . DB_PREFIX . 'mp_user_group WHERE id=?';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$groupId]));
            $result = $data->valid()?$data->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'mp_user_group';
    }
}

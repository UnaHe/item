<?php

class SettingModel extends ModelBase
{
    public function getList()
    {
        $tag = CacheBase::makeTag(self::class . 'getList');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getDetailsByKey($key)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByKey', $key);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                [
                    'conditions' => 'key = ?1',
                    'bind' => [1 => $key]
                ]
            );
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getByKeys(array $key = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getByKeys', $key);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $keys = '';
            if (!empty($key)) {
                foreach ($key as $v) {
                    $keys .= "'$v',";
                }
                $keys = rtrim($keys, ',');
            }
            $result = $this->find(
                [
                    'conditions' => 'key in (' . $keys . ')'
                ]
            );
            $return = [];
            if (!empty($result)) {
                foreach ($result as $v) {
                    $return[$v->key] = $v->value;
                }
            }
            $result = $return;
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
        return DB_PREFIX . 'setting';
    }
}
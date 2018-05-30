<?php

class EquipmentVersionModel extends ModelBase
{
    public function getLastVersion($category)
    {
        $tag = CacheBase::makeTag(self::class . 'getLastVersion', $category);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                [
                    'conditions' => 'category=?1',
                    'order' => 'equipment_version_id DESC',
                    'bind' => array(1 => $category)
                ]
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'equipment_version';
    }
}
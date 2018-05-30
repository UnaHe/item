<?php

class EquipmentSoftwareModel extends ModelBase
{
    public function getLastVersion($category)
    {
        $tag = CacheBase::makeTag(self::class . 'getLastVersion', $category);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                [
                    'conditions' => 'equipment_software_category=?1',
                    'order' => 'equipment_software_id DESC',
                    'bind' => array(1 => $category)
                ]
            );
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
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by equipment_software_id DESC';
            if (isset($params ['category']) && !empty($params ['category'])) {
                $where .= ' WHERE equipment_software_category=?';
                $bindParams [] = $params ['category'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(equipment_software_id) FROM ' . DB_PREFIX . 'equipment_software' . $where;
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
            $sql = 'SELECT * FROM ' . DB_PREFIX . 'equipment_software' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'
                ));
            }
        }
        return $result;
    }

    public function getDetailsByEquipmentSoftwareId($equipment_software_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByEquipmentSoftwareId', $equipment_software_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                [
                    'conditions' => 'equipment_software_id=?1',
                    'bind' => array(1 => $equipment_software_id)
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
        return DB_PREFIX . 'equipment_software';
    }
}
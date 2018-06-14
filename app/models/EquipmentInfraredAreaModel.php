<?php

class EquipmentInfraredAreaModel extends ModelBase
{
    public function initialize()
    {
    }

    /**
     * @param $areaId
     * @return bool | array
     */
    public function getDetailsByAreaId($areaId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByAreaId', $areaId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as eia LEFT JOIN '.DB_PREFIX.'equipment_infrared as ei ON eia.equipment_infrared_id=ei.equipment_infrared_id LEFT JOIN '.DB_PREFIX.'equipment as e ON ei.equipment_id=e.equipment_id WHERE eia.equipment_infrared_area_id=?';
            $sql = sprintf($sqlTemplate, "e.equipment_project_id,e.equipment_code,ei.*,eia.*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$areaId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
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
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as eia';
            $bindParams = [];
            $pageCount = 1;
            $orderBy = ' order by eia.equipment_infrared_area_id DESC';

            if (!empty($params ['type'])) {
                $where .= ' WHERE eia.equipment_infrared_area_type=?';
                $bindParams [] = $params ['type'];
            }

            if (!empty($params ['equipment_infrared_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' eia.equipment_infrared_id=?';
                $bindParams [] = $params ['equipment_infrared_id'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $res = $this->sqlLimit($sqlTemplate , 'COUNT(eia.equipment_infrared_area_id)' , $where , $bindParams, $params['page'],$params['psize']);
                $limit = $res['limit'];
                $pageCount = $res['pageCount'];
            }
            $sql = sprintf($sqlTemplate, "eia.*") . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, [
                    self::class . 'getList',
                ]);
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'equipment_infrared_area';
    }
}
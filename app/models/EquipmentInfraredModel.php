<?php

class EquipmentInfraredModel extends ModelBase
{
    public function initialize()
    {
    }

    /**
     * @param $infraredId
     * @return bool | array
     */
    public function getDetailsById($infraredId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $infraredId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as ei LEFT JOIN '.DB_PREFIX.'equipment as e ON ei.equipment_id=e.equipment_id LEFT JOIN '.DB_PREFIX.'project as p ON p.project_id=e.equipment_project_id WHERE ei.equipment_infrared_id=?';
            $sql = sprintf($sqlTemplate, "p.project_name,e.*,ei.*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$infraredId]));
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
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as eii';
            $bindParams = [];
            $pageCount = 1;
            $orderBy = ' order by br.book_rules_id DESC';

            if (!empty($params ['type'])) {
                $where .= ' WHERE e.equipment_project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (!empty($params ['type'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' e.equipment_type=?';
                $bindParams [] = $params ['type'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $res = $this->sqlLimit($sqlTemplate , 'COUNT(br.book_rules_id)' , $where , $bindParams, $params['page'],$params['psize']);
                $limit = $res['limit'];
                $pageCount = $res['pageCount'];
            }
            $sql = sprintf($sqlTemplate, "br.*") . $where . $orderBy . $limit;
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
        return DB_PREFIX . 'equipment_infrared';
    }
}
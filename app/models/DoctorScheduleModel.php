<?php

class DoctorScheduleModel extends ModelBase
{
    public function getDetailsById($id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                [
                    'conditions' => 'doctor_schedule_id = ?1',
                    'bind' => array(1 => $id)
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
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by ds.doctor_schedule_start ASC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE d.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset($params ['startTime']) && !is_null($params ['startTime'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ds.doctor_schedule_start>=?';
                $bindParams [] = $params ['startTime'];
            }
            if (isset($params ['endTime']) && !is_null($params ['endTime'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ds.doctor_schedule_end<=?';
                $bindParams [] = $params ['endTime'];
            }
            if (isset($params ['doctor_id']) && !is_null($params ['doctor_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ds.doctor_id=?';
                $bindParams [] = $params ['doctor_id'];
            }
            if (isset($params ['date']) && !is_null($params ['date'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ds.doctor_schedule_date=?';
                $bindParams [] = $params ['date'];
            }

            if (isset($params ['department_id']) && !is_null($params ['department_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " ds.doctor_schedule_department_id=?";
                $bindParams [] = $params ['department_id'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = ' order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(ds.*) FROM ' . DB_PREFIX . 'doctor_schedule as ds LEFT JOIN ' . DB_PREFIX . 'doctor as d ON ds.doctor_id=d.doctor_id' . $where;
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
            $sql = 'SELECT d.*,ds.*,dd.department_name FROM ' . DB_PREFIX . 'doctor_schedule as ds LEFT JOIN ' . DB_PREFIX . 'doctor as d ON ds.doctor_id=d.doctor_id LEFT JOIN '.DB_PREFIX.'doctor_department as dd ON ds.doctor_schedule_department_id=dd.department_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params['project_id'],
                    self::class . 'getList' . @$params['department_id'],
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'doctor_schedule';
    }
}

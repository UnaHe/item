<?php

class DoctorModel extends ModelBase
{
    const TAG_PREFIX = 'DoctorModel_';

    /**
     * @param $doctor_id
     * @return bool
     */
    public function getDetailsById($doctor_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $doctor_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
             $sql = "select d.*,dj.doctor_job_title from " . DB_PREFIX ."doctor as d LEFT JOIN " . DB_PREFIX . "doctor_job as dj ON d.doctor_job_id=dj.doctor_job_id WHERE d.doctor_id=?";
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$doctor_id]));
            $result = $data->valid() ? $data: false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getListSimple($project_id)
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getListSimple' . $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'project_id = ?1',
                    'bind' => array(1 => $project_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getListByDepartmentId($department_id)
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getListByDepartmentId_' . $department_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'select dd.department_name,d.* ,dj.doctor_job_title from n_doctor_department as dd,n_doctor as d LEFT JOIN '.DB_PREFIX.'doctor_job as dj on dj.doctor_job_id = d.doctor_job_id WHERE dd.department_id = ' . $department_id . ' and \'' . $department_id . '\' = any (string_to_array("doctor_relation",\',\'))';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            if (!$result->valid()) {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDoctorListByDepartmentIdSimple($department_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getListByDepartmentId_', $department_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'select dd.department_name,d.* from n_doctor_department as dd,n_doctor as d WHERE dd.department_id = ? and ? = any (string_to_array("doctor_relation",\',\'))';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$department_id, $department_id]));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @return string | array
     */

    public function clientGetListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'clientGetListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by d.doctor_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE d.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['department_id']) && !is_null($params ['department_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . " ? = any(string_to_array(d.doctor_relation,','))";
                $bindParams [] = $params ['department_id'];
            }
            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(d.*) FROM ' . DB_PREFIX . 'doctor as d' . $where;
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
            $sql = "select d.*,array_to_string(array(select dd.department_name from n_doctor_department as dd where dd.department_id::text = any(string_to_array(d.doctor_relation,','))),',') as department,dj.doctor_job_title,dj.doctor_job_color from n_doctor as d LEFT JOIN " . DB_PREFIX . "doctor_job as dj ON d.doctor_job_id=dj.doctor_job_id" . $where . $orderBy . $limit;
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

    public function clientGetDetailsSimple($doctorId)
    {
        $tag = CacheBase::makeTag(self::class . 'clientGetDetailsSimple', $doctorId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = "select d.*,array_to_string(array(select dd.department_name from n_doctor_department as dd where dd.department_id::text = any(string_to_array(d.doctor_relation,','))),',') as department,dj.doctor_job_title,dj.doctor_job_color from n_doctor as d LEFT JOIN " . DB_PREFIX . "doctor_job as dj ON d.doctor_job_id=dj.doctor_job_id WHERE d.doctor_id=?";
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$doctorId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function refreshPrimaryId(){
        $this->db->query("select setval('n_doctor_doctor_id_seq' , (select max(doctor_id) from ".$this->getSource()."))");
    }

    public function getSource()
    {
        return DB_PREFIX . 'doctor';
    }
}

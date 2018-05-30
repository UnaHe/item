<?php

class ProjectFeatureModel extends ModelBase
{
    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by pf.project_feature_id DESC';
            if (isset($params ['type']) && !empty($params ['type'])) {
                $where .= ' WHERE pf.project_feature_type=?';
                $bindParams [] = $params ['type'];
            }

            if (isset($params ['feature']) && !empty($params ['feature'])) {
                $features = explode(',',$params ['feature']);
                $inFeature = '';
                foreach($features as $v){
                    $inFeature .="'".$v."',";
                }
                $inFeature = rtrim($inFeature,',');
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' pf.project_feature_id IN ('.$params ['feature'].')';
            }

            if (!empty($params ['keywords'])) {
                $where .= (empty($where) ? " WHERE" : " AND") . " pf.project_feature_name LIKE '%".$params['keywords']."%'";
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(pf.project_feature_id) FROM ' . $this->getSource() . ' as pf' . $where;
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
            $sql = 'select pf.*,pf1.project_feature_name as override_name from ' . $this->getSource() . ' as pf LEFT JOIN '.$this->getSource().' as pf1 ON pf.project_feature_override=pf1.project_feature_id' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList' . @$params['project_id'],
                ));
            }
        }
        return $result;
    }

    public function getDetailsByFeatureIdSimple($featureId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByFeatureIdSimple', $featureId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'select pf.* from ' . $this->getSource() . ' as pf WHERE pf.project_feature_id=?';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$featureId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getDetailsByProjectIdSimple($projectId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectIdSimple', $projectId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = "select pt.*,p.* from " . DB_PREFIX . "project_theme as pt LEFT JOIN " . DB_PREFIX . "project as p ON pt.project_id=p.project_id WHERE pt.project_id=?";
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$projectId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function refreshPrimaryId()
    {
        $this->db->query("select setval('n_doctor_doctor_id_seq' , (select max(doctor_id) from " . $this->getSource() . "))");
    }

    public function getSource()
    {
        return DB_PREFIX . 'project_feature';
    }
}

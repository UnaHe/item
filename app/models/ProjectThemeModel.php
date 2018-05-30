<?php

class ProjectThemeModel extends ModelBase
{
    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by pt.project_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE pt.project_id=?';
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
                $sqlCount = 'SELECT count(pt.project_theme_id) FROM ' . DB_PREFIX . 'project_theme as pt' . $where;
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
            $sql = "select pt.*,p.* from ".DB_PREFIX.'project_theme as pt LEFT JOIN '.DB_PREFIX.'project as p ON pt.project_id=p.project_id' . $where . $orderBy . $limit;
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

    public function getDetailsByThemeIdSimple($themeId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByThemeIdSimple', $themeId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = "select pt.*,p.* from ".DB_PREFIX."project_theme as pt LEFT JOIN ".DB_PREFIX."project as p ON pt.project_id=p.project_id WHERE pt.project_theme_id=?";
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$themeId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getDetailsByProjectIdAndTplSimple($projectId,$tpl)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectIdAndTplSimple', [$projectId,$tpl]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = "select pt.*,p.* from ".DB_PREFIX."project_theme as pt LEFT JOIN ".DB_PREFIX."project as p ON pt.project_id=p.project_id WHERE pt.project_id=? AND pt.project_theme_tpl=?";
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$projectId,$tpl]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function refreshPrimaryId(){
        $this->db->query("select setval('n_doctor_doctor_id_seq' , (select max(doctor_id) from ".$this->getSource()."))");
    }

    public function getSource()
    {
        return DB_PREFIX . 'project_theme';
    }
}

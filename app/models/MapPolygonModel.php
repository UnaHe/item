<?php

class MapPolygonModel extends ModelBase
{

    public function initialize()
    {
    }

    public function reset()
    {
        unset($this->map_polygon_id);
    }

    /** findFirst使用phalcon内置函数搜索数据库中最符合的结果
     * @param $map_gid
     * @return bool|\Phalcon\Mvc\Model
     */
    public function getDetailsByMapGid($map_gid)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapGid', $map_gid);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(array(
                'conditions' => 'name is not null and map_gid =?1',
                'bind' => [1 => $map_gid]
            ));
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByMapIdAndGid($map_id, $gid)
    {

        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapIdAndGid', $map_id . '_' . $gid);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(array(
                'conditions' => 'name is not null and map_id =?1 and gid =?2',
                'bind' => [1 => $map_id, 2 => $gid]
            ));
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    //查询面及对应的地图、项目详情
    public function getDetailsByMapPolygonIdSimple($polygonId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapPolygonIdSimple', $polygonId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT mpd.*,mp.*,m.map_name,m.map_name_en,p.project_name,p.project_id,p.project_entrance,cm.* FROM ' . DB_PREFIX . 'map_polygon as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'company_message as cm ON cm.users_user_name=mp.map_gid LEFT JOIN ' . DB_PREFIX . 'map_polygon_description as mpd ON mp.map_gid=mpd.map_gid WHERE mp.map_polygon_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $polygonId
                )));
            if ($result->valid()) {
                $result = $result->toArray()[0];
            } else {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    //根据不同参数获取详情
    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            if (isset($params ['map_id']) && !is_null($params ['map_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.map_id=?';
                $bindParams [] = $params ['map_id'];
            }
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' p.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset($params ['target']) && is_bool($params['target'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.target=?';
                $bindParams [] = $params ['target'];
            }

            if (!empty($params ['context'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.context=?';
                $bindParams [] = $params ['context'];
            }

            if (!empty($params ['name_en'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.name_en=?';
                $bindParams [] = $params ['name_en'];
            }

            if (!empty($params ['seller_staff_intro'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.seller_staff_intro=?';
                $bindParams [] = $params ['seller_staff_intro'];
            }

            if (!empty($params ['seller_shop_img'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' sr.seller_shop_img=?';
                $bindParams [] = $params ['seller_shop_img'];
            }

            if (isset($params ['notType']) && !empty($params['notType'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.type!=?';
                $bindParams [] = $params ['notType'];
            }
            if (isset($params ['hasGid']) && $params['hasGid']==1) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.gid IS NOT NULL';
            }
            if (isset($params ['keywords']) && !empty($params ['keywords'])) {
                $intKeywords = (int)$params ['keywords'];
                $whereExtra = $countWhereExtra = '';
                if ($intKeywords !== 0) {
                    $whereExtra = ' OR mp.gid=\'' . $intKeywords . '\'';
                }
                $where .= (empty($where) ? ' WHERE' : ' AND') . " (mp.name LIKE '%" . $params ['keywords'] . "%' OR mp.type LIKE '%" . $params ['keywords'] . "%'" . $whereExtra . ")";
            }
            if (isset($params ['category_id']) && !empty($params['category_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.map_polygon_category_id=?';
                $bindParams [] = $params ['category_id'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(mp.map_polygon_id) FROM ' . DB_PREFIX . 'map_polygon as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id = m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p on m.project_id = p.project_id ' . $where;
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
            $sql = 'SELECT || FROM ' . DB_PREFIX . 'map_polygon as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id = m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p on m.project_id = p.project_id LEFT JOIN ' . DB_PREFIX . 'map_polygon_description as mpd ON mp.map_gid=mpd.map_gid LEFT JOIN ' . DB_PREFIX . 'seller as sr on mp.map_gid=sr.map_gid AND sr.project_id=p.project_id  LEFT JOIN ' . DB_PREFIX . 'company_message as cm on mp.map_gid = cm.users_user_name  ' . $where . ' order by mp.map_polygon_id ' . $limit;

            if (isset($params ['col']) && !empty($params['col'])) {
                $sql = str_replace('||' , $params['col'] , $sql);
                // $sql = sprintf($sql, $params['col']);
            }else{
                $sql = str_replace('||' ,"sr.*,mpd.*,mp.*,p.project_name,cm.company_intro" , $sql);
                // $sql = sprintf($sql, "sr.*,mpd.*,mp.*,p.project_name,cm.company_intro");
            }
//            echo $sql;exit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList' . @$params ['project_id'],
                    self::class . 'getList' . @$params ['map_id']
                ));
            }
        }
        return $result;
    }

    public function getListByAvailable(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListByAvailable', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = 'where mp.gid is not null and mp.name is not null';
            $bindParams = array();
            if (isset($params ['map_id']) && !is_null($params ['map_id'])) {
                $where .= ' AND mp.map_id=? ';
                $bindParams [] = $params ['map_id'];
            }
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' AND p.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset($params ['words']) && !is_null($params ['words'])) {
                $where .= " AND mp.name like '%" . $params ['words'] . "%'";
            }
            $sql = 'SELECT mp.*,p.project_name FROM ' . DB_PREFIX . 'map_polygon as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id = m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p on m.project_id = p.project_id ' . $where . ' order by mp.map_polygon_id ';
//             			echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getListByAvailable',
                    self::class . 'getListByAvailable_' . @$params ['words'] . '_' . $params ['project_id']
                ));
            }
        }
        return $result;
    }

    public function getListInMapGidString($map_gid_arr)
    {
        $tag = CacheBase::makeTag(self::class . 'getListInMapGidString', $map_gid_arr);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $str = join(",", $map_gid_arr);
            $inStr = "'" . str_replace(",", "','", $str) . "'";
            $sql = 'SELECT name,map_gid from n_map_polygon WHERE map_gid in ( ' . $inStr . ' )';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getListByAvailable',
                ));
            }
        }
        return $result;
    }

    /**
     * @param array $params
     * @return array|bool|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public function getMapAndGid(array $params = array())
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getMapAndGid', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $bindParams = array();
            $where = $limit = $order = '';
            if (isset($params['map_gid']) && $params['map_gid'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.map_gid=?';
                $bindParams[] = $params['map_gid'];
            }
            $sql = 'SELECT mp.* FROM ' . DB_PREFIX . 'map_polygon as mp' . $where;

            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::TAG_PREFIX . 'getMapAndGid',
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'map_polygon';
    }

    /**
     * new methods
     */

    public function getListHaveCompany(array $params)
    {
        $tag = CacheBase::makeTag(self::class . 'getListNew', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result['pageCount'] = 1;
            $limit = '';
            $where = ' WHERE m.project_id=? AND mp.map_gid IS NOT NULL';
            $countWhere = ' WHERE project_id=' . $params['project_id'] . ' AND map_gid IS NOT NULL';
            if (!empty($params['keywords'])) {
                $where .= " AND mp.map_gid like '%" . $params['keywords'] . "%' OR mp.name like '%" . $params['keywords'] . "%'";
                $countWhere .= " AND map_gid like '%" . $params['keywords'] . "%' OR name like '%" . $params['keywords'] . "%'";
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $countSql = 'select count(mp.map_polygon_id) from ' . DB_PREFIX . 'map_polygon as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=mp.map_id LEFT JOIN ' . DB_PREFIX . 'company_message as cm ON cm.users_user_name = mp.map_gid' . $countWhere;
                $countQuery = $this->db->query($countSql);
                $pageCount = $countQuery->fetchArray()[0];
                $result['pageCount'] = ceil($pageCount / $params['psize']);
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $offset = $offset < 0 ? 0 : $offset;
                $limit = ' limit ' . $params ['psize'] . ' offset ' . $offset;
            }
            $bindParams = [$params['project_id']];
            $sql = 'select m.map_name,mp.name,mp.map_polygon_id,cm.* from ' . DB_PREFIX . 'map_polygon as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=mp.map_id LEFT JOIN ' . DB_PREFIX . 'company_message as cm ON cm.users_user_name = mp.map_gid' . $where . $limit;
            $return = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $return->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'
                ));
            }
        }
        return $result;
    }

    public function getpolygonByDepartmentId($params)
    {
        $tag = CacheBase::makeTag(self::class . 'getpolygonByDepartmentId', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT m.map_name,mp. NAME,mp.map_id,mp.gid,mp.map_gid FROM n_map_polygon AS mp JOIN n_doctor_department AS dd ON mp.map_gid = ANY (string_to_array(dd.' . $params['option'] . ', \',\')) join n_map as m on mp.map_id = m.map_id WHERE dd.department_id = ' . $params['department_id'];
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            if (!$result->valid()) {
                $result = false;
            } else {
                $result = $result->toArray();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getpolygonByCageId($params)
    {
        $tag = CacheBase::makeTag(self::class . 'getpolygonByCageId', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT M .map_name,mp. NAME,mp.map_id,mp.gid,mp.map_gid FROM n_map_polygon AS mp JOIN n_panda_cage AS pc ON mp.map_gid = pc.map_gid JOIN n_map AS M ON mp.map_id = M .map_id WHERE pc.cage_id = ' . $params['cage_id'];
//			echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            if (!$result->valid()) {
                $result = false;
            } else {
                $result = $result->toArray();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    //关键词搜索对应面
    public function getListUseSearch($params)
    {
        $tag = CacheBase::makeTag(self::class . 'getpolygonByCageId', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = ' where mp.name is not null and mp.gid is not null ';
            $bindParams = array();
            if (isset($params['project_id']) && !empty($params['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' m.project_id=?';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['keywords']) && !empty($params['keywords'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' (mp.name like \'%' . $params['keywords'] . '%\' or mp.map_gid like \'%' . $params['keywords'] . '%\') ';
            }
            $sql = 'select mp.name,mp.map_gid,m.map_name from n_map_polygon as mp join n_map as m on mp.map_id = m.map_id ' . $where;
//			echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            if (!$result->valid()) {
                $result = [];
            } else {
                $result = $result->toArray();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByMapIdAndGidSimple($map_id, $gid)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByMapIdAndGidSimple', [$map_id, $gid]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT mpd.*,mp.*,m.map_name,m.map_name_en,p.project_name,p.project_id,p.project_entrance,cm.* FROM ' . DB_PREFIX . 'map_polygon as mp LEFT JOIN ' . DB_PREFIX . 'map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id LEFT JOIN ' . DB_PREFIX . 'company_message as cm ON cm.users_user_name=mp.map_gid LEFT JOIN ' . DB_PREFIX . 'map_polygon_description as mpd ON mp.map_gid=mpd.map_gid WHERE mp.map_id=? AND mp.gid=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $map_id, $gid
                )));
            if ($result->valid()) {
                $result = $result->toArray()[0];
            } else {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

}
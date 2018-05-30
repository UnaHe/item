<?php

class CompanyMessageModel extends ModelBase
{
    public function getDetailsByName($uuname)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByName', $uuname);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'users_user_name = ?1',
                    'bind' => array(1 => $uuname)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * get pending number
     * @param $project_id
     * @return bool|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public function getPendingNumber($project_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getPendingNumber', $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT count(*) from n_company_message as cm LEFT JOIN n_users_user as uu on cm.users_user_name = uu.users_user_name WHERE cm.company_status = 2 and uu.project_id = ?';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array($project_id)));
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
            if ($result->valid()) {
                $result = $result->toArray()[0];
            } else {
                return false;
            }
        }
        return $result;
    }

    /**
     * get pending list
     * @param $project_id
     * @return bool|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public function getPendingList($project_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getPendingList', $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT cm.*,i.path from n_company_message as cm LEFT JOIN n_users_user as uu on cm.users_user_name = uu.users_user_name left JOIN n_img as i on cm.users_user_name = i.users_user_name WHERE cm.company_status = 2 and uu.project_id = ?';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array($project_id)));
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
            if ($result->valid()) {
                $result = $result->toArray();
            } else {
                return false;
            }
        }
        return $result;
    }

    /**
     * get simple list by project_id
     * @param $project_id
     * @return array|bool|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public function getListByprojectId($project_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getListByprojectId', $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT mp.name,mp.map_gid,cm.company_name,mp.map_polygon_category_id FROM n_map_polygon as mp left join n_map_polygon_category as mpc on mp.map_polygon_category_id = mpc.map_polygon_category_id left join n_company_message as cm on mp.map_gid = cm.users_user_name WHERE mpc.project_id = ?';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array($project_id)));
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
            if ($result->valid()) {
                $result = $result->toArray();
            } else {
                return false;
            }
        }
        return $result;
    }

    public function getDetailsById($company_id)
    {
//            var_dump($uuname);exit;
        $tag = CacheBase::makeTag(self::class . 'getDetailsByName', $company_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'company_id = ?1',
                    'bind' => array(1 => $company_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsByMapGid($params)
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM  ' . DB_PREFIX . 'company_message where users_user_name=?';
//                        $sql = 'SELECT name FROM ' . DB_PREFIX . 'map_polygon';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$params]));
//                       var_dump($result);exit;
            $result = $result->valid()?$result->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getDetailsByMapGidToName($params)
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM  ' . DB_PREFIX . 'map_polygon where map_gid=' . "'$params'";
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['article_category_id']
                ));
            }
        }
        return $result;
    }

    public function getList(array $params)
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $limit = '';
            $bindParams = array();
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $offset = $offset < 0 ? 0 : $offset;
                $limit = ' limit ' . $params ['psize'] . ' offset ' . $offset;
            }
            $key_words = $params['key_words'];
            $key = !empty($key_words) ? 'and (mp.map_gid like \'%' . $key_words . '%\' or mp.name like \'%' . $key_words . '%\')' : ' ';
            $project_id = $params['project_id'];
            $sql = 'SELECT mp.name,mp.map_gid ,cm.company_name,mp.map_id,project_id FROM n_map_polygon as mp LEFT JOIN n_company_message as cm ON cm.users_user_name = mp.map_gid
                  LEFT JOIN n_map as np  on np.map_id= mp.map_id where map_gid is not null and name is not null  and project_id=' . $project_id . $key . ' order by map_polygon_id asc ' . $limit;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['article_category_id']
                ));
            }
        }
        return $result;
    }

    public function getListAll(array $params)
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $key_words = $params['key_words'];
            $key = !empty($key_words) ? 'and (mp.map_gid like \'%' . $key_words . '%\' or mp.name like \'%' . $key_words . '%\')' : ' ';
            $project_id = $params['project_id'];
            $sql = 'SELECT mp.name,mp.map_gid ,cm.company_name,mp.map_id,project_id FROM n_map_polygon as mp 
			LEFT JOIN n_company_message as cm ON cm.users_user_name = mp.map_gid LEFT JOIN n_map as np  on np.map_id= mp.map_id
			where map_gid is not null and name is not null and project_id=' . $project_id . $key . '  order by map_polygon_id asc ';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['article_category_id']
                ));
            }
        }
        return $result;
    }

    public function getFullList($params = array())
    {
        $tag = CacheBase::makeTag(self::class . 'getFullList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = 'where mp.name is not null and mp.gid is not null';
            $bindParams = array();
            if (isset($params['project_id']) && !empty($params['project_id'])) {
                $where .= ' and pc.project_id =? ';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['map_id']) && !empty($params['map_id'])) {
                $where .= ' and mp.map_id =? ';
                $bindParams[] = $params['map_id'];
            }
            if (isset($params['map_polygon_category_pid']) && !empty($params['map_polygon_category_pid'])) {
                $where .= ' and pc.map_polygon_category_pid =? ';
                $bindParams[] = $params['map_polygon_category_pid'];
            }
            if (isset($params['map_polygon_category_id']) && !empty($params['map_polygon_category_id'])) {
                $where .= ' and pc.map_polygon_category_id =? ';
                $bindParams[] = $params['map_polygon_category_id'];
            }

            $sql = 'SELECT	mp.map_polygon_id,mp.map_id,mp.gid,mp.centroid,pc.map_polygon_category_name,pc.map_polygon_category_id,cm.* FROM n_company_message AS cm LEFT JOIN n_map_polygon_category AS pc ON pc.map_polygon_category_id = cm.map_polygon_category_id LEFT JOIN n_map_polygon AS mp ON cm.users_user_name = mp.map_gid ' . $where . ' order by pc.map_polygon_category_id asc';

//			echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['project_id']
                ));
            }
        }
        return $result;
    }

    public function getFullListNew($params = array())
    {
        $tag = CacheBase::makeTag(self::class . 'getFullList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = 'where mp.name is not null and mp.gid is not null';
            $bindParams = array();
            if (isset($params['project_id']) && !empty($params['project_id'])) {
                $where .= ' and ma.project_id =? ';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['map_id']) && !empty($params['map_id'])) {
                $where .= ' and mp.map_id =? ';
                $bindParams[] = $params['map_id'];
            }
            if (isset($params['map_polygon_category_pid']) && !empty($params['map_polygon_category_pid'])) {
                $where .= ' and pc.map_polygon_category_pid =? ';
                $bindParams[] = $params['map_polygon_category_pid'];
            }
            if (isset($params['map_polygon_category_id']) && !empty($params['map_polygon_category_id'])) {
                $where .= ' and pc.map_polygon_category_id =? ';
                $bindParams[] = $params['map_polygon_category_id'];
            }
            if (isset($params['context']) && !empty($params['context'])) {
                $where .= ' and mp.context =? ';
                $bindParams[] = $params['context'];
            }

            $sql = 'SELECT mp.name,mp.name_en,mp.context,mp.map_polygon_id,mp.map_id,mp.gid,mp.centroid FROM n_map_polygon AS mp LEFT JOIN n_map AS ma ON mp.map_id = ma.map_id ' . $where . ' ORDER BY mp.map_polygon_id ASC';
//			echo $sql;die();
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['project_id']
                ));
            }
        }
        return $result;
    }

    public function getallByCategoryId($map_category_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getallByCategoryId', $map_category_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'select mp.map_polygon_id, mp.gid ,mp.map_id,cm.users_user_name,company_name from n_company_message as cm
					LEFT JOIN n_map_polygon as mp ON cm.users_user_name=mp.map_gid where cm.map_polygon_category_id=' . $map_category_id;
            //echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $map_category_id
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'company_message';
    }

}
<?php

class SellerModel extends ModelBase
{
    const TAG_PREFIX = 'SellerModel_';
    public function getDetailsByName($name){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByName', $name);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result1 = $this->findFirst(
                array(
                    'conditions' => 'map_gid = ?1',
                    'bind' => array(1 => $name)
                )
            );
            if($result1){
                if (CACHING) {
                    $this->cache->save($tag, $result1);
                }
                return $result1;
            }else{
                $result2 = $this->findFirst(
                    array(
                        'conditions' => 'seller_tel = ?1',
                        'bind' => array(1 => $name)
                    )
                );
                if($result2){
                    if (CACHING) {
                        $this->cache->save($tag, $result2);
                    }
                    return $result2;
                }
            }
        }

    }

    public function getListByStatus(array $params = []){
        $tag = CacheBase::makeTag(self::class . 'getListByStatus', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by s.seller_create_at ASC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' LEFT JOIN n_map_polygon as mp on mp.map_gid= s.map_gid LEFT JOIN n_map as m on m.map_id=mp.map_id WHERE m.project_id =?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['seller_status']) && !is_null($params ['seller_status'])) {
                $where .= (empty($where)?' WHERE':' AND').' s.seller_status=?';
                $bindParams [] = $params ['seller_status'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(s.*) FROM ' . DB_PREFIX . 'seller as s ' . $where ;
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
            $sql =  'SELECT s.*,mp.name,m.map_name FROM ' . DB_PREFIX . 'seller as s ' . $where  .$limit;

            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data?$data->toArray():false;
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getListByStatus' . @$params['project_id'],
                    self::class . 'getListByStatus' . @$params['seller_status'],
                ));
            }
        }
        return $result;
    }



    public function getDetailsById($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT s.*,m.project_id FROM n_seller as s LEFT JOIN n_map_polygon as mp on mp.map_gid= s.map_gid LEFT JOIN n_map as m on m.map_id=mp.map_id WHERE s.seller_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql,array($id)) );
            $result = $result->valid()?$result->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getInfoById($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getInfoById', $id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT s.*,mp.name,g.goods_name,sc.scale_name FROM n_seller as s LEFT JOIN n_map_polygon as mp on mp.map_gid= s.map_gid LEFT JOIN n_map as m on m.map_id=mp.map_id
            LEFT JOIN n_goods_category as g on s.goods_category_id= g.goods_category_id LEFT JOIN n_scale as sc on s.scale_id= sc.scale_id WHERE s.seller_id=? limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql,array($id)) );
            $result = $result->valid()?$result->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'seller';
    }
}
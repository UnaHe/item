<?php

class WxPromotionModel extends ModelBase
{
    const TAG_PREFIX = 'WxPromotionModel_';


    public function getPromotionListByStatus(array $params = []){
        $tag = CacheBase::makeTag(self::class . 'getPromotionListByStatus', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by wp.promotion_create_at DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= (empty($where)?' WHERE':' AND').' wp.project_id=?';
                $bindParams[] = $params['project_id'];
            }

            if (isset($params ['promotion_status']) && !is_null($params ['promotion_status'])) {
                $where .= (empty($where)?' WHERE':' AND').' wp.promotion_status=?';
                $bindParams [] = $params ['promotion_status'];
            }
            if (isset($params ['seller_id']) && !is_null($params ['seller_id'])) {
                $where .= (empty($where)?' WHERE':' AND').' wp.seller_id=?';
                $bindParams [] = $params ['seller_id'];
            }
            if (isset($params ['expire']) && !is_null($params ['expire'])) {
                //未过期
                if($params ['expire'] == 1){
                    $where .= (empty($where)?' WHERE':' AND').' wp.promotion_end_time>='.time();
                }elseif ($params ['expire'] == 2){
                    $where .= (empty($where)?' WHERE':' AND').' wp.promotion_end_time<'.time();
                }
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(wp.wx_promotion_id) FROM ' . DB_PREFIX . 'wx_promotion as wp' . $where.' AND wp.promotion_name IS NOT NULL';
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

            $sql = 'SELECT wp.* FROM ' . DB_PREFIX . 'wx_promotion as wp ' . $where.' AND wp.promotion_name IS NOT NULL' . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data?$data->toArray():false;
            $result['pageCount'] = $pageCount;
         
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getPromotionListByStatus' . @$params['project_id'],
                    self::class . 'getPromotionListByStatus' . @$params['promotion_status'],
                ));
            }
        }
        return $result;
    }



    public function getProjectIdByUserName($name){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $name);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sellerModel = new SellerModel();
            $map_gid = $sellerModel->getDetailsByName($name)->toArray()['map_gid'];
            $sql = 'SELECT m.project_id FROM ' . DB_PREFIX . 'map as m LEFT JOIN ' . DB_PREFIX . 'map_polygon as mp on mp.map_id = m.map_id LEFT JOIN  ' . DB_PREFIX . 'seller as s on s.map_gid = mp.map_gid where s.map_gid =?';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql,array($map_gid)));
            $result = $data->valid()?$data->toArray()[0]:false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;

    }

    public function getDetailsById($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'wx_promotion_id = ?1',
                    'bind' => array(1 => $id),
                )
            );
            $result = $result?$result->toArray():false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsBySellerId($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $time =  time();
            $sql = 'SELECT wx.* FROM ' . DB_PREFIX . 'wx_promotion as wx LEFT JOIN ' . DB_PREFIX . 'seller as s ON wx.seller_id = s.seller_id and wx.promotion_end_time >'.$time. 'where s.seller_id=? order by wx.promotion_start_time asc';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql,array($id)));
            $result = $data->valid()?$data->toArray():false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    

    public function getSource()
    {
        return DB_PREFIX . 'wx_promotion';
    }
}
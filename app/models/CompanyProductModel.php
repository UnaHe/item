<?php

class CompanyProductModel extends ModelBase
{
    public function initialize()
    {
    }

    public function reset()
    {
        unset($this->company_product_id);
    }

    /**
     * @param $companyProductId
     * @return bool|\Phalcon\Mvc\Model
     */

    public function getDetailsByCompanyProductId($companyProductId)
    {
        $tag = CacheBase::makeTag(self::class . 'companyProductId', $companyProductId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                [
                    'conditions' => 'company_product_id = ?1',
                    'bind' => [1 => $companyProductId]
                ]
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * allow params keys: gid map_id id label
     * @param array $params
     * @return bool
     */

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            if (isset($params ['company_id']) && !is_null($params ['company_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' cp.company_id=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'company_id="' . $params['company_id'] . '"';
                $bindParams [] = $params ['company_id'];
            }
            if (isset($params ['label']) && !empty($params ['label'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' mp.label=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'label="' . $params['label'] . '"';
                $bindParams [] = $params ['label'];
            }

            if (isset($params ['project_id']) && !empty($params ['project_id'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' cp.project_id=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'project_id="' . $params['project_id'] . '"';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['recommend']) && !empty($params ['recommend'])) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' cp.company_product_recommend=?';
                $countWhere .= (empty($countWhere) ? '' : ' AND ') . 'company_product_recommend="' . $params['recommend'] . '"';
                $bindParams [] = $params ['recommend'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($countWhere) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT cp.*,cm.users_user_name,cm.company_name,cm.company_name_en,mp.map_id,mp.gid,mp.map_polygon_id FROM ' . DB_PREFIX . 'company_product as cp LEFT JOIN ' . DB_PREFIX . 'company_message as cm ON cp.company_id=cm.company_id LEFT JOIN '.DB_PREFIX.'map_polygon as mp ON mp.map_gid=cm.users_user_name' . $where . ' order by cp.company_product_id DESC' . $limit;
//            echo $sql;die;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data;
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::TAG_PREFIX . 'getList',
                    self::TAG_PREFIX . 'getList_' . @$params ['project_id']
                ));
            }
        }
        return $result;
    }

    public function getDetailsByIdAndMapGIdSimple($gid, $mapId)
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByIdAndMapGIdSimple', [$gid, $mapId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM ' . DB_PREFIX . 'map_point WHERE gid=? AND map_id=? and id is not null limit 1';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $gid,
                    $mapId
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

    public function getDetailsByIdAndMapIdSimple($id, $mapId)
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByIdAndMapIdSimple', [$id, $mapId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT mp.*,m.map_name,nmp.map_gid,m.map_name_en,p.* FROM ' . DB_PREFIX . 'map_point as mp LEFT JOIN n_map as m ON mp.map_id=m.map_id LEFT JOIN ' . DB_PREFIX . 'project as p ON m.project_id=p.project_id left JOIN ' . DB_PREFIX . 'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id WHERE mp.id=? AND mp.map_id=? limit 1';
//            echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, array(
                    $id,
                    $mapId
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

    public function getSource()
    {
        return DB_PREFIX . 'company_product';
    }
}
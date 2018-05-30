<?php

class ItemProjectModel extends ModelBase
{
    public function initialize()
    {
    }

    /**
     * @param $rulesId
     * @return bool | array
     */
    public function getDetailsByUsername($username)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $username);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as ia LEFT JOIN '.DB_PREFIX.'item_account_group as iag ON ia.item_account_group_id=iag.item_account_group_id WHERE ia.item_account_username=?';
            $sql = sprintf($sqlTemplate, "iag.*,ia.*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$username]));
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
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as ip LEFT JOIN '.DB_PREFIX.'project as p ON p.project_id=ip.project_id';
            $bindParams = [];
            $pageCount = 1;
            $orderBy = ' order by ip.item_project_id DESC';

            if (!empty($params ['item_account_id'])) {
                $where .= ' WHERE ip.item_account_id=?';
                $bindParams [] = $params ['item_account_id'];
            }
            if (isset ($params ['item_project_status'])) {
                $where .= (empty ($where) ? ' WHERE' : ' AND ') . ' ip.item_project_status=?';
                $bindParams [] = $params ['item_project_status'];
            }


            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $limit = $this->sqlLimit($sqlTemplate , 'COUNT(ip.item_project_id)' , $where , $bindParams, $params['page'],$params['psize']);
            }
            $sql = sprintf($sqlTemplate, "p.project_name,p.project_status,ip.*") . $where . $orderBy . $limit;
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

    public function getDetailsByProjectIdAndAccountId($projectId,$itemAccountId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectId', $projectId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as ip LEFT JOIN '.DB_PREFIX.'item_account_group as iag ON iag.item_account_group_id=ip.item_account_group_id WHERE ip.project_id=? AND ip.item_account_id=?';
            $sql = sprintf($sqlTemplate, "ip.*,iag.*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$projectId,$itemAccountId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'item_project';
    }
}
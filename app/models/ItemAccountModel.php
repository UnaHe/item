<?php

class ItemAccountModel extends ModelBase
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
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as br';
            $bindParams = [];
            $pageCount = 1;
            $orderBy = ' order by br.book_rules_id DESC';

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $res = $this->sqlLimit($sqlTemplate , 'COUNT(br.book_rules_id)' , $where , $bindParams, $params['page'],$params['psize']);
                $limit = $res['limit'];
                $pageCount = $res['pageCount'];
            }
            $sql = sprintf($sqlTemplate, "br.*") . $where . $orderBy . $limit;
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

    public function getSource()
    {
        return DB_PREFIX . 'item_account';
    }
}
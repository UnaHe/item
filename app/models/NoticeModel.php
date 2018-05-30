<?php

class NoticeModel extends ModelBase
{
    public function initialize()
    {
    }

    public function getDetailsByNoticeIdSimple($noticeId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByNoticeIdSimple', $noticeId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT n.*,p.project_name,c.client_name,c.client_realname FROM ' . $this->getSource() . ' as n LEFT JOIN '.DB_PREFIX.'project as p ON n.project_id=p.project_id LEFT JOIN '.DB_PREFIX.'client as c ON n.client_id=c.client_id WHERE notice_id=? limit 1';
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$noticeId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'
                ));
            }
        }
        return $result;
    }

    public function getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by n.notice_id DESC';
            if (isset($params ['project_id']) && !is_null($params ['project_id'])) {
                $where .= ' WHERE n.project_id=?';
                $bindParams [] = $params ['project_id'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(n.notice_id) FROM ' . $this->getSource() . ' as n' . $where;
                $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sqlCount, $bindParams));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = $count>0?ceil($count / $params ['psize']):1;
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT n.*,p.project_name,c.client_name,c.client_realname FROM ' . $this->getSource() . ' as n LEFT JOIN ' . DB_PREFIX . 'project as p ON n.project_id=p.project_id LEFT JOIN '.DB_PREFIX.'client as c ON n.client_id=c.client_id' . $where . $orderBy . $limit;
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

    public function getSource()
    {
        return DB_PREFIX . 'notice';
    }
}
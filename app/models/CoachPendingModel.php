<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/26
 * Time: 14:56
 */
class CoachPendingModel extends ModelBase
{
    const TAG_PREFIX = 'CoachPendingModel_';

    public function getList($params)
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $limit = '';
            if (isset($params['project_id']) && $params['project_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' project_id=?';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['coach_id']) && $params['coach_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' coach_id=?';
                $bindParams[] = $params['coach_id'];
            }
            if (isset($params['coach_pending_id']) && $params['coach_pending_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' coach_pending_id=?';
                $bindParams[] = $params['coach_pending_id'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $offset = $offset < 0 ? 0 : $offset;
                $limit = ' limit ' . $params ['psize'] . ' offset ' . $offset;
            }
            $where .= (empty($where) ? ' WHERE' : ' AND') . ' evaluate_status=1';
            $sql = 'SELECT * from n_coach_pending ' . $where . ' ORDER BY create_at DESC' . $limit;

            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
           if (!$result->valid()) {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getScore($params)
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getScore', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            if (isset($params['project_id']) && $params['project_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ncp.project_id=?';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['coach_pending_id']) && $params['coach_pending_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' ncp.coach_pending_id=?';
                $bindParams[] = $params['coach_pending_id'];
            }
            $sql = 'SELECT nc.*, ncp.* FROM n_coach AS nc LEFT JOIN n_coach_pending AS ncp ON nc.coach_id = ncp.coach_id' . $where;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
            if (!$result->valid()) {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function deletePending($params)
    {
        $where = '';
        if (isset($params['coach_pending_id']) && $params['coach_pending_id'] > 0) {
            $where .= (empty($where) ? ' WHERE' : ' AND') . ' coach_pending_id=?';
            $bindParams[] = $params['coach_pending_id'];
        }
        if (isset($params['project_id']) && $params['project_id'] > 0) {
            $where .= (empty($where) ? ' WHERE' : ' AND') . ' project_id=?';
            $bindParams[] = $params['project_id'];
        }
        if (isset($params['coach_id']) && $params['coach_id'] > 0) {
            $where .= (empty($where) ? ' WHERE' : ' AND') . ' coach_id=?';
            $bindParams[] = $params['coach_id'];
        }
        $sql = "UPDATE n_coach_pending SET evaluate_status=0" . $where;
        $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'coach_pending';
    }

}
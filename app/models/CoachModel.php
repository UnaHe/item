<?php
use Phalcon\Mvc\Model;


class CoachModel extends Model
{
    const TAG_PREFIX = 'CoachModel_';

    public function getList($params)
    {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where =$limit= '';
            $bindParams = array();
            if (isset($params['project_id']) && $params['project_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' p.project_id=?';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['coach_id']) && $params['coach_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' p.coach_id=? ';
                $bindParams[] = $params['coach_id'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $offset = $offset < 0 ? 0 : $offset;
                $limit = ' limit ' . $params ['psize'] . ' offset ' . $offset;
            }
            $sql = 'SELECT p.* FROM n_coach AS p ' . $where .'ORDER BY p.coach_id asc'.$limit;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function updateInfo($params)
    {
        $where = '';
        if (isset($params['coach_score']) && $params['coach_score'] > 0) {
            $bindParams[] = $params['coach_score'];
        }
        if (isset($params['evaluate_count']) && $params['evaluate_count'] > 0) {
            $bindParams[] = $params['evaluate_count'];
        }
        if (isset($params['coach_id']) && $params['coach_id'] > 0) {
            $where .= (empty($where) ? ' WHERE' : ' AND') . ' coach_id=?';
            $bindParams[] = $params['coach_id'];
        }
        if (isset($params['project_id']) && $params['project_id'] > 0) {
            $where .= (empty($where) ? ' WHERE' : ' AND') . ' project_id=?';
            $bindParams[] = $params['project_id'];
        }
        $sql = 'UPDATE n_coach  SET coach_score=? ,evaluate_count=?' . $where;
        $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql, $bindParams));
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'coach';
    }
}
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

            // 关键字搜索.
            if (isset ( $params['keywords'] ) && $params['keywords'] != '') {
                $where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' (coach_name LIKE \'%'.$params['keywords'].'%\' OR coach_tel LIKE \'%'.$params['keywords'].'%\')';
            }

            // 是否审批通过的.
            if (isset($params['approval_status']) && $params['approval_status'] !== NULL) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' p.approval_status=?';
                $bindParams[] = $params['approval_status'];
            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $offset = $offset < 0 ? 0 : $offset;
                $limit = ' limit ' . $params ['psize'] . ' offset ' . $offset;
            }

            // 今天0点的时间戳.
            $time = strtotime(date('Y-m-d',time()));

            $sql = 'SELECT p.*, s.sign_in_point_id, mp.map_id, mp.gid
                    FROM n_coach AS p 
                    LEFT JOIN n_sign_in AS s ON p.coach_id = s.sign_in_coach_id AND s.sign_in_id IN (SELECT MAX(sign_in_id) FROM n_sign_in WHERE sign_in_create_at > '. $time .' GROUP BY sign_in_coach_id)
                    LEFT JOIN n_map_point AS mp ON mp.map_id = s.sign_in_point_map_id AND mp.id = s.sign_in_point_id
                    ' . $where .'
                    ORDER BY p.coach_id asc '.$limit;

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

    /**
     * 根据coachId查询教练.
     * @param $coachId
     * @return bool | array
     */
    public function getCoachByCoachId($coachId)
    {
        $tag = CacheBase::makeTag(self::class . 'getCoachByCoachId', $coachId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' WHERE coach_id = ?';
            $sql = sprintf($sqlTemplate, "*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple(
                null,
                $this,
                $this->getReadConnection()->query($sql, [$coachId])
            );
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

}
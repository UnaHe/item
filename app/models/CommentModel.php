<?php

class CommentModel extends ModelBase
{
    public function getDetailsById($Id)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsById', $Id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'comment_id = ?1',
                    'bind' => array(1 => $Id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
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
            $bindParams = array();
            if (!empty($params ['comment_obj'])) {
                $type_arr = $this->getCommentTypeList();
                $type_table = DB_PREFIX.$type_arr[$params ['comment_obj']]['table'];
                $join_id = $type_arr[$params ['comment_obj']]['join_id'];
                $where .= ' WHERE c.comment_obj=?';
                $bindParams [] = $params ['comment_obj'];
            }
            if (isset($params ['comment_obj_id']) && !empty($params ['comment_obj_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND').' c.'.$join_id.'=?';
                $bindParams [] = $params ['comment_obj_id'];
            }
            if (!empty ($params ['keywords'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' c.article_title LIKE ?';
                $bindParams [] = '%' . $params ['keywords'] . '%';
            }
            if (!empty($params ['comment_status'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' c.comment_status=?';
                $bindParams [] = $params ['comment_status'];
            }

            if (!empty($params ['project_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' c.project_id=?';
                $bindParams [] = $params ['project_id'];
            }
            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(c.comment_id) FROM ' . $this->getSource() . ' as c JOIN ' . $type_table . ' ON c.'.$join_id.'=' . $type_table . '.' . $type_arr[$params ['comment_obj']]['obj_id']  . $where ;
                $countRes = new Phalcon\Mvc\Model\Resultset\Simple(null, $this, $this->getReadConnection()->query($sqlCount,$bindParams));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = ceil($count / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT %s FROM ' . $this->getSource() . ' as c JOIN ' . $type_table . ' ON c.'.$join_id.'=' . $type_table . '.' . $type_arr[$params ['comment_obj']]['obj_id']  . $where . ' order by c.comment_created_at DESC' . $limit;
            if(isset($params['cols']) && !empty($params['cols'])){
                $sql = sprintf($sql,$params['cols']);
            }else{
                $sql = sprintf($sql,'c.*,'.$type_table.'.*');
            }
//            echo $sql;exit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, $bindParams));
            $result['pageCount'] = $pageCount;
            $result['data'] = $data->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                    self::class . 'getList_' . $params ['article_category_id']
                ));
            }
        }
        return $result;
    }

    public function getAverage($project_id,$comment_obj,$comment_obj_id)
    {
        $tag = CacheBase::makeTag(self::class . 'getAverage', $project_id.'_'.$comment_obj.'_'.$comment_obj_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $type_arr = $this->getCommentTypeList();
            $type_table = DB_PREFIX.$type_arr[$comment_obj]['table'];
            $join_id = $type_arr[$comment_obj]['join_id'];
            $where = "WHERE c.comment_status='1'AND c.project_id=? AND c.comment_obj=? AND c.{$join_id}=?";

            $sql = 'SELECT AVG(c.comment_score) FROM ' . $this->getSource() . ' as c '. $where;
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$project_id,$comment_obj,$comment_obj_id]));
            $result = $data->toArray()[0];
        }
        return $result;
    }

    public function getCommentTypeList(){
        return (new CommentTypeModel())->getListSimple();
    }

    public function getSource()
    {
        return DB_PREFIX . 'comment';
    }
}
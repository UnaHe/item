<?php
class ProjectModel extends ModelBase {

    public static $projectTypes = ['hospital','scenic','mall','station'];
    /**
     * @param int $projectId
     * @return object
     *
     * */

    public static $projectStatusArr = [
        'forbidden' => 0,  //禁用
        'normal' => 1, //正常
        'auditting' => 2, //审批中
        'uncommitted' => 3,  //未提交

    ];

    public static function getProjectStatusArr(){
        $status_arr = [];
        $status_arr[self::$projectStatusArr['forbidden']] = '禁用';
        $status_arr[self::$projectStatusArr['normal']] = '正常';
        $status_arr[self::$projectStatusArr['auditting']] = '审批中';
        $status_arr[self::$projectStatusArr['uncommitted']] = '待提交';
        return $status_arr;
    }
    public  function getProjectStatusStyleArr(){
        $status_arr = [];
        $status_arr[self::$projectStatusArr['forbidden']] = '<span class="label label-danger">禁用</span>';
        $status_arr[self::$projectStatusArr['normal']] = '<span class="label label-success">正常</span>';
        $status_arr[self::$projectStatusArr['auditting']] = '<span class="label label-warning">审批中</span>';
        $status_arr[self::$projectStatusArr['uncommitted']] = '<span class="label label-danger">待提交</span>';
        return $status_arr;
    }


    public function getDetailsByProjectId($projectId) {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectId' , $projectId);
        $result = CACHING ? $this->cache->get ( $tag ) : false;
        if (! $result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'project_id = ?1',
                    'bind' => array(1 => $projectId)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    /**
     * Add log message
     *
     * @param array $params
     * @return array
     */

    public function getList(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $pageCount = 1;
            $parameter = array(
                'order' => 'project_id DESC'
            );

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count(null) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page']-1) * $params ['psize'];
                $parameter['limit'] = $params ['psize'];
                $parameter['offset'] = $offset<=0?0:$offset;
            }
            $data = $this->find($parameter);
            $result['pageCount'] = $pageCount;
            $result['data'] = $data->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getListByMerchant(){
        $tag = CacheBase::makeTag(self::class . 'getListByMerchant');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT P.project_id,p.project_name,m.merchant_id FROM n_project AS P LEFT JOIN n_merchant AS M ON P .project_id = M .project_id WHERE M.merchant_group_id = 1 OR M.merchant_group_id IS NULL GROUP BY p.project_id,m.merchant_id ORDER BY P.project_id';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,$this->getReadConnection()->query($sql));
            if(!$result->valid()){
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    /**
     * 得到未给项目账号分配的项目
     * @param array $params
     * @return bool
     */
    public function  getListSimple(array $params = [])
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = $countWhere = $limit = $more_info  = '';
            $bindParams = array();
            $pageCount = 1;
            $orderBy = ' order by p.project_id DESC ';
            //审批中的项目
//            $where .= ($where == '' ? ' WHERE' : ' AND') . ' (au.curr_step=au.step_nums )';
//            $bindParams [] = '1';
//            $bindParams [] = 'project';

            if (isset($params ['project_status']) && !is_null($params ['project_status'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' p.project_status=?';
                $bindParams [] = $params ['project_status'];
                if($params ['project_status'] == self::$projectStatusArr['auditting']){
                    $where .= ($where == '' ? ' WHERE' : ' AND') . ' (au.curr_step=au.step_nums AND au.apply_status=? AND au.obj=?)';
                    $bindParams [] = '1';
                    $bindParams [] = 'project';
                }
            }

            if (isset($params ['project_created_by']) && !is_null($params ['project_created_by'])) {

                $where .= ($where == '' ? ' WHERE' : ' AND') . ' p.project_created_by=?';
                $bindParams [] = $params ['project_created_by'];
            }
            if (isset($params ['project_distributor_id']) && !is_null($params ['project_distributor_id'])) {

                $where .= ($where == '' ? ' WHERE' : ' AND') . ' p.project_distributor_id=?';
                $bindParams [] = $params ['project_distributor_id'];
            }

            if (isset($params ['parent_distributor_id']) && !is_null($params ['parent_distributor_id'])) {
                $where .= ($where == '' ? ' WHERE' : ' AND') . ' (d.distributor_id=? OR d.distributor_parent_id=?)';
                $bindParams [] = $params ['parent_distributor_id'];
                $bindParams [] = $params ['parent_distributor_id'];
            }

            if (isset($params ['orderBy']) && !is_null($params ['orderBy'])) {
                $orderBy = 'order by ' . $params ['orderBy'];
            }
            if (isset($params ['usePage']) && $params ['usePage'] == 1) {
                $sqlCount = 'SELECT count(p.project_id) FROM  ' . $this->getSource() .' as p  LEFT JOIN '.(new AuditModel())->getSource().' as au  ON au.obj_id = p.project_id '. $where;
                $countRes = new Phalcon\Mvc\Model\Resultset\Simple(null, $this, $this->getReadConnection()->query($sqlCount,$bindParams));
                $count = $countRes->toArray()[0]['count'];
                $pageCount = ceil($count / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page'] - 1) * $params ['psize'];
                $limit = ' limit ' . $params ['psize'] . ' OFFSET ' . $offset;
            }
            $sql = 'SELECT p.* ,au.* FROM ' . $this->getSource() . ' as p LEFT JOIN '.(new AuditModel())->getSource().' as au ON au.obj_id = p.project_id  LEFT JOIN  '.(new DistributorModel())->getSource().' as d ON d.distributor_id = p.project_distributor_id ' . $where . $orderBy . $limit;
            $data = new Phalcon\Mvc\Model\Resultset\Simple(null, $this, $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            $result['pageCount'] = $pageCount;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'
                ));
            }
        }
        return $result;
    }

    /**
     * 得到未给项目账号分配的项目
     * @param array $params
     * @return bool
     */
    public function getUnsetList($item_account_id)
    {
        if(!$item_account_id){
            return [];
        }
        $tag = CacheBase::makeTag(self::class . 'getUnsetList');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $bindParams = [];
            $where = 'WHERE  ip.project_id is NULL AND p.project_status <= \'1\' ORDER BY  p.project_id DESC';
            $sql = 'SELECT p.project_id,project_name  FROM  '.$this->getSource(). ' as p  LEFT JOIN ' . (new ItemProjectModel())->getSource() . " as ip on ip.project_id=p.project_id AND ip.item_account_id = {$item_account_id}" . $where ;
            $data = new Phalcon\Mvc\Model\Resultset\Simple(null, $this, $this->getReadConnection()->query($sql, $bindParams));
            $result['data'] = $data->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    /**
     * @param int $projectId
     * @return object
     *
     * */
    public function getDetailsByProjectIdBase($projectId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByProjectIdBase', $projectId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' as p LEFT JOIN '.DB_PREFIX.'project_modules as pm ON p.project_id=pm.project_id WHERE p.project_id=?';
            $sql = sprintf($sqlTemplate, "pm.*,p.*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$projectId]));
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }

    public function getSource() {
        return DB_PREFIX . 'project';
    }
}
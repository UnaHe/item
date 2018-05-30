<?php
class MapData extends ModelBase {
	const TAG_PREFIX = 'MapDataModel_';
	const TYPE_COMMON_POINT = 'commonPoint';
	const TYPE_CROSS_POINT = 'crossPoint';
	const POINT_ATTR_ONLYUP = 'Onlyup';
	const POINT_ATTR_ONLYDOWN = 'Onlydown';
	const navigationKey = '43ad4680da98dec7c5b179ff63d11488';
    public function initialize()
    {
        $this->hasOne("map_data_category_id", "MapDataCategoryModel", "map_data_category_id");
        $this->hasOne("map_id", "Map", "map_id");
    }

    public function getDetailsByMerchantId($merchant_id) {
        $tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByMerchantId', $merchant_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'merchant_id = ?1',
                    'bind' => array(1 => $merchant_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    public function getDetailsByMapDataId($map_data_id) {
        $tag = self::makeTag(self::TAG_PREFIX . 'getDetailsByMapDataId', $map_data_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'map_data_id = ?1',
                    'bind' => array(1 => $map_data_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
	public function getDetails($map_data_id) {
		$tag = self::TAG_PREFIX . 'getDetails_' . $map_data_id;
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT md.*,m.project_id,m.map_pid,m.map_name,m.map_legend,m.map_viewpoint,p.project_name,c.coupon_title,p.project_feature FROM ' . DB_PREFIX . 'map_data as md LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=md.map_id LEFT JOIN '.DB_PREFIX.'project as p ON m.project_id=p.project_id LEFT JOIN '.DB_PREFIX.'coupon as c ON c.map_data_id=md.map_data_id WHERE md.map_data_id=? limit 1';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
					$map_data_id 
			) ) );
			if ($result->valid ()) {
				$result = $result->toArray ()[0];
			} else {
				$result = false;
			}
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	public function getDetailsByType($map_id, $map_data_type , $map_data_attr=null) {
		$tag = self::TAG_PREFIX . 'getDetailsByType_' . $map_id . '_' . $map_data_type.'_'.$map_data_attr;
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT md.*,m.project_id,m.map_name FROM ' . DB_PREFIX . 'map_data as md LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=md
.map_id WHERE md.map_id=? AND md.map_data_type=?';
            $bindParams = array (
                $map_id,
                $map_data_type
            );
            if (!is_null($map_data_attr)){
                $sql .= ' AND (md.map_data_attr IS NULL OR md.map_data_attr=?)';
                $bindParams[] = $map_data_attr;
            }
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	public function getDetailsByLatlng($map_id, $latlng) {
		$tag = self::TAG_PREFIX . 'getDetailsByLatlng' . $map_id . '_' . md5($latlng);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$sql = 'SELECT md.*,m.project_id,m.map_name FROM ' . DB_PREFIX . 'map_data as md LEFT JOIN ' . DB_PREFIX . 'map as m ON m.map_id=md
.map_id WHERE
md.map_id=? AND md.map_data_content=? AND md.map_data_type="'.self::TYPE_CROSS_POINT.'" limit 1';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, array (
					$map_id,
					$latlng
			) ) );
			if ($result->valid()){
				$result = $result->toArray ()[0];
			}else{
				$result = false;
			}
			if (CACHING) {
				$this->cache->save ( $tag, $result );
			}
		}
		return $result;
	}
	public function getListByProject(array $params = array()) {
		if (isset ( $params ['keywords'] ) && $params ['keywords'] != '') {
			$keywords = $params['keywords'];
			$params['keywords'] = md5($params['keywords']);
		}
		if (isset ( $params ['tag'] ) && $params ['tag'] != '') {
			$tags = $params['tag'];
			$params['tag'] = md5($params['tag']);
		}
		$tag = self::TAG_PREFIX . 'getListByProject_' . implode ( '_', $params );
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = $limit = '';
			$order = 'map_data_id DESC';
			$map_id = null;
			$bindParams = array ();
			if (isset ( $params ['project_id'] ) && ! is_null ( $params ['project_id'] )) {
				$where .= ' WHERE m.project_id=?';
				$bindParams [] = $params ['project_id'];
			}
			if (isset ( $params ['datatype'] ) && $params ['datatype'] != '') {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' map_data_type=?';
				$bindParams [] = $params ['datatype'];
			}
            if (isset ( $params ['map_id'] ) && !is_null($params ['map_id'])) {
                $where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' m.map_id=?';
                $bindParams [] = $params ['map_id'];
            }

			if (isset ( $params ['useGroup'] ) && is_int($params ['useGroup']) ) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' map_data_is_group=?';
				if ($params ['useGroup']==1) {
					$bindParams [] = 1;
				}else{
					$bindParams [] = 0;
				}
			}
			if (isset ( $params ['status'] ) && !is_null($params ['status'])) {
                $where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_status=?';
                $bindParams [] = $params ['status'];
            }
			if (isset ( $params ['exstatus'] ) && !is_null($params ['exstatus'])) {
                $where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_status!=?';
                $bindParams [] = $params ['exstatus'];
            }
			if (isset ( $keywords ) && $keywords != '') {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_name LIKE "%'.$keywords.'%"';
			}
			if (isset ( $params ['category'] ) && !is_null($params ['category'])) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_category_id=?';
				$bindParams [] = $params ['category'];
			}
			if (isset ( $params ['orderby'] ) && $params ['orderby'] != '') {
				$order = $params ['orderby'];
			}
			if (isset ( $params [self::TYPE_COMMON_POINT] ) && $params [self::TYPE_COMMON_POINT] == 0) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' map_data_type!="' . self::TYPE_COMMON_POINT . '"';
			}
			if (isset ( $params [self::TYPE_CROSS_POINT] ) && $params [self::TYPE_CROSS_POINT] == 0) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' map_data_type!="' . self::TYPE_CROSS_POINT . '"';
			}
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $offset . ',' . $params ['psize'];
			}
			/* 3.26 add by 王东 --start */
			if (isset ( $params ['map_data_tag'] ) && !is_null($params ['map_data_tag'])) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_tag=?';
				$bindParams [] = $params ['map_data_tag'];
			}
			if (isset ( $params ['polygon'] )) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' map_data_type!="polygon"';
			}
			/* 3.26 add by 王东 --end */
			$sql = 'SELECT md.*,m.map_name FROM ' . DB_PREFIX . 'map_data as md LEFT JOIN '.DB_PREFIX.'map as m ON md.map_id=m.map_id' . $where . '
order by
' . $order . $limit;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
				) );
			}
		}
		return $result;
	}
	public function getList(array $params = array()) {
		$tag = ModelBase::makeTag(self::TAG_PREFIX . 'getList_' , $params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
			$where = $limit = '';
			$order = 'md.map_data_id DESC';
			$map_id = null;
			$bindParams = array ();
			if (isset ( $params ['map_id'] ) && ! is_null ( $params ['map_id'] )) {
				$where .= ' WHERE md.map_id=?';
				$bindParams [] = $map_id = $params ['map_id'];
			}
			if (isset ( $params ['useGroup'] ) && $params ['useGroup'] ==1 ) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_is_group=?';
				$bindParams [] = 1;
			}
			if (isset ( $params ['group_id'] ) && $params ['group_id'] >0 ) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_group_id=?';
				$bindParams [] = $params ['group_id'];
			}

			if (isset ( $params ['datatype'] ) && $params ['datatype'] != '') {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_type=?';
				$bindParams [] = $params ['datatype'];
			}
			if (isset ( $params ['orderby'] ) && $params ['orderby'] != '') {
				$order = $params ['orderby'];
			}
			if (isset ( $params [self::TYPE_COMMON_POINT] ) && $params [self::TYPE_COMMON_POINT] == 0) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_type!="' . self::TYPE_COMMON_POINT . '"';
			}
			if (isset ( $params [self::TYPE_CROSS_POINT] ) && $params [self::TYPE_CROSS_POINT] == 0) {
				$where .= (empty ( $where ) ? ' WHERE' : ' AND ') . ' md.map_data_type!="' . self::TYPE_CROSS_POINT . '"';
			}
			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
				$offset = ($params ['page'] - 1) * $params ['psize'];
				$limit = ' limit ' . $offset . ',' . $params ['psize'];
			}
			$sql = 'SELECT md.*,pd.project_device_id FROM ' . DB_PREFIX . 'map_data as md LEFT JOIN '.DB_PREFIX.'project_device as pd ON md.map_data_id=pd.map_data_id' . $where . ' order by ' . $order . $limit;
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
						self::TAG_PREFIX . 'getList_' . $map_id 
				) );
			}
		}
		return $result;
	}
	public static function createNavigationString($data){
		return CryptModel::encrypt($data, self::navigationKey);
	}
	public static function parseNavigationString($data){
		return CryptModel::decrypt($data, self::navigationKey);
	}
	public function getSource() {
		return DB_PREFIX . 'map_data';
	}
}
<?php
class ImgModel extends ModelBase {
	const TAG_PREFIX = 'ImgModel_';
	public function getDetailsByName($users_user_name) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByName', $users_user_name);
		$result = CACHING ? $this->cache->get($tag) : false;
		if (!$result) {
			$result = $this->findFirst(
				array(
					'conditions' => 'users_user_name = ?1',
					'bind' => array(1 => $users_user_name)
				)
			);
			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}
//	public function getList(array $params) {
////            var_dump($params['']);exit;
//		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
//		$result = CACHING ? $this->cache->get ( $tag ) : false;
//		if (! $result) {
//			$where = $limit = '';
//			$bindParams = array ();
//			if (! empty( $params ['article_category_id'] )) {
//				$where .= ' WHERE a.article_category_id=?';
//				$bindParams [] = $params ['article_category_id'];
//			}
//			if (! empty ( $params ['keywords'] )) {
//				$where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_title LIKE ?';
//				$bindParams [] = '%' . $params ['keywords'] . '%';
//			}
//			if (! empty( $params ['status'] )) {
//				$where .= ($where == '' ? ' WHERE' : ' AND') . ' a.article_status=?';
//				$bindParams [] = $params ['status'];
//			}
//
//			if (! empty( $params ['project_id'] )) {
//				$where .= ($where == '' ? ' WHERE' : ' AND') . ' a.project_id=?';
//				$bindParams [] = $params ['project_id'];
//			}
//
//			if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
//				$offset = ($params ['page'] - 1) * $params ['psize'];
//                $offset = $offset<0?0:$offset;
//				$limit = ' limit ' . $offset . ',' . $params ['psize'];
//			}
//			$sql = 'SELECT a.*,ac.name as cate_name FROM ' . DB_PREFIX . 'article as a LEFT JOIN ' . DB_PREFIX . 'article_category as ac ON a.article_category_id=ac.article_category_id' . $where . ' order by a.article_sort_order DESC,a.article_id DESC';
//                        
//			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bindParams ) );
////                       var_dump($result);exit;
//			$result = $result->toArray ();
//			if (CACHING) {
//				$this->cache->save ( $tag, $result, 864000, null, array (
//						self::TAG_PREFIX . 'getList',
//						self::TAG_PREFIX . 'getList_' . $params ['article_category_id'] 
//				) );
//			}
//		}
//		return $result;
//	}
	public function getSource() {
		return DB_PREFIX . 'img';
	}
}
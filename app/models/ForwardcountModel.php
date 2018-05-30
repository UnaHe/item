<?php
class ForwardcountModel extends ModelBase {
	const TAG_PREFIX = 'ForwardcountModel_';

        public function getByMapPointId(array $params) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
//			$where = $limit = '';
//			$bindParams = array ();
//                        if (isset ( $params ['usePage'] ) && $params ['usePage'] == 1) {
//				$offset = ($params ['page'] - 1) * $params ['psize'];
//                                $offset = $offset<0?0:$offset;
//				$limit = ' limit ' . $params ['psize'].' offset '.$offset;
//			}
                    $map_point_id  =$params['map_point_id'];
//			$sql = 'SELECT * FROM ' . DB_PREFIX . 'forward_count as fc where  map_point_id='."'$map_point_id'"  .' order by fc.scan_point_time ASC ';
                        $sql = 'SELECT  SUM(scan_point_count)  as scan_point_count ,SUM(click_point_count) as click_point_count,fc.scan_point_time ,fc.map_point_id FROM '.DB_PREFIX.'forward_count as fc  where  map_point_id='."'$map_point_id'"  .' GROUP BY scan_point_time ,map_point_id order by scan_point_time';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
//						self::TAG_PREFIX . 'getList_' . $params ['article_category_id'] 
				) );
			}
		}
		return $result;
	}
        
    public function getByMapPointIdToMap(array $params) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
            $map_point_id  =$params['map_point_id'];
            $key_words = $params['key_words'];;
            if($key_words != ''){
                $key_words2 = $params['key_words']+'86399';
            }else{
                $key_words2 = '';
            }
            $sql = 'SELECT  SUM(scan_point_count)  as scan_point_count ,SUM(click_point_count) as click_point_count,fc.scan_point_time ,fc.map_point_id FROM '.DB_PREFIX.'forward_count as fc  where  map_point_id='."'$map_point_id'"  .' and scan_point_time >= '."'$key_words'".' and scan_point_time <= '."'$key_words2'".' GROUP BY scan_point_time ,map_point_id order by scan_point_time';
			$result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql) );
			$result = $result->toArray ();
			if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getList',
				) );
			}
		}
		return $result;
	}

                 
        
	public function getSource() {
		return DB_PREFIX . 'forward_count';
	}
}
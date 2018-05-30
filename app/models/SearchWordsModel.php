<?php
class SearchWordsModel extends ModelBase {
	const TAG_PREFIX = 'SearchWords_';
	public function initialize()
	{
	    $this->db = $this->_dependencyInjector->get('db');
	}
	public function reset()
	{
	    unset($this->words_id);
	}
	 public function getdetailsByName($name) {
		$tag = CacheBase::makeTag(self::TAG_PREFIX.'getdetailsByName',$name);
		$result = CACHING ? $this->cache->get ( $tag ) : false;
		if (! $result) {
            $result = $this->findFirst(array(
                'conditions' => 'words_name = ?1',
                'bind' => array(1 => $name),
            ));

			if (CACHING) {
				$this->cache->save($tag, $result);
			}
		}
		return $result;
	}
	

	public function getGidListByWords(array $params) {
	    $tag = CacheBase::makeTag(self::TAG_PREFIX.'getGidListByWords',$params);
	    $result = CACHING ? $this->cache->get ( $tag ) : false;
	    if (! $result) {
	        $Where ='';
            $bind = array();
	        if(isset($params['words']) && $params['words'] != ''){
	            $Where = ($Where?' AND ':' WHERE ')." sw.words_name LIKE '%".$params['words']."%' ";
	        }
	        if(isset($params['project_id']) && $params['project_id'] != ''){
				$Where .= ($Where?' AND ':' WHERE')." p.project_id = ?";
				$bind[] = $params['project_id'];
			}
			if(isset($params['map_id']) && $params['map_id'] != ''){
				$Where .= ($Where?' AND ':' WHERE')." mp.map_id = ?";
				$bind[] = $params['map_id'];
			}
	        $sql = 'SELECT * FROM(SELECT *, ROW_NUMBER () OVER (PARTITION BY s.gid, s.map_id) AS rn FROM(SELECT mp. NAME,mp.gid,mp.map_id,mp.map_polygon_id FROM n_search_words AS sw LEFT JOIN n_words_polygon AS wp ON sw.words_id = wp.words_id LEFT JOIN n_map_polygon AS mp ON wp.map_gid = mp.map_gid LEFT JOIN n_map AS M ON mp.map_id = M .map_id LEFT JOIN n_project AS P ON M .project_id = P .project_id '.$Where.') AS s) T WHERE rn = 1';
 	        echo $sql;die;
	        $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql, $bind ) );
	        $result = $result->toArray ();
	        if (CACHING) {
				$this->cache->save ( $tag, $result, 864000, null, array (
						self::TAG_PREFIX . 'getGidListByWords'
				) );
			}
	    }
	    return $result;
	}
	
	//     没哟跨层项目算距离
	public function getCostBySameLayer($mapId,$startId,array $endId){
	    $tag = CacheBase::makeTag(self::class . 'getCostBySameLayer', [$mapId,$startId, $endId]);
	    $result = CACHING ? $this->cache->get($tag) : false;
	    if (!$result) {
	        $endId = implode(',',$endId);
// 	        echo 'SELECT res.cost,mp.map_id,nmp.gid,nmp.map_polygon_id,nmp.name,m.map_name,nmp.target_map_id,cm.company_name FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j ::integer as target, length::double precision as cost FROM n_map_linestring where map_id = ' . $mapId . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE ) as res JOIN n_map_point as mp on res.id2 = mp.id JOIN n_map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id join n_map as m on m.map_id = mp.map_id left JOIN n_company_message as cm on nmp.map_gid = cm.users_user_name ORDER BY COST';die;
	        try {
	            $result = $this->db->query('SELECT res.cost,mp.map_id,nmp.gid,nmp.map_polygon_id,nmp.name,m.map_name,nmp.target_map_id,cm.company_name FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j ::integer as target, length::double precision as cost FROM n_map_linestring where map_id = ' . $mapId . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE ) as res JOIN n_map_point as mp on res.id2 = mp.id JOIN n_map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id join n_map as m on m.map_id = mp.map_id left JOIN n_company_message as cm on nmp.map_gid = cm.users_user_name ORDER BY COST');
	            $result = $result->fetchAll();
	        }catch (Exception $e){
	            $result = false;
	        }
	
	        if (CACHING) {
	            $this->cache->save($tag, $result);
	        }
	    }
	    return $result;
	}

//     跨层项目算距离
    public function getCost($projectId,$startId,array $endId){
        $tag = CacheBase::makeTag(self::class . 'getCost', [$projectId,$startId, $endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $endId = implode(',',$endId);
//             echo 'SELECT res.cost,mp.map_id,nmp.gid,nmp.name,m.map_name,nmp.target_map_id,cm.company_name FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_project_cross_linestring where "project_id" = ' . $projectId . '\',\'' . $startId . '\',ARRAY [' . $endId . '],	FALSE,FALSE) as res JOIN n_map_point as mp on res.id2 = mp.id JOIN n_map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id join n_map as m on m.map_id = mp.map_id join n_project as p on m.project_id = p.project_id left JOIN n_company_message as cm on nmp.map_gid = cm.users_user_name where p.project_id = '.$projectId.' ORDER BY COST';die;
            try {
                $result = $this->db->query('SELECT res.cost,mp.map_id,nmp.gid,nmp.name,nmp.name_en,m.map_name,nmp.target_map_id,cm.company_name FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_project_cross_linestring where "project_id" = ' . $projectId . '\',\'' . $startId . '\',ARRAY [' . $endId . '],	FALSE,FALSE) as res JOIN n_map_point as mp on res.id2 = mp.id JOIN n_map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id join n_map as m on m.map_id = mp.map_id join n_project as p on m.project_id = p.project_id left JOIN n_company_message as cm on nmp.map_gid = cm.users_user_name where p.project_id = '.$projectId.' ORDER BY COST');
                $result = $result->fetchAll();
            }catch (Exception $e){
                $result = false;
            }
            
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

	public function getSource() {
		return DB_PREFIX . 'search_words';
	}
}

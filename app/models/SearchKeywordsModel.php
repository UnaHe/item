<?php

class SearchKeywordsModel extends ModelBase
{
    public function initialize()
    {
    }

    /**
     * @param $keywords
     * @param $projectId
     * @return array
     */

    public function getIdByKeywords($keywords, $projectId, $ids = "")
    {
        $tag = CacheBase::makeTag(self::class . 'getIdByKeywords', [$keywords, $projectId, $ids]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $idsString = "";
            if (!empty($ids)) {
                $ids = explode(',', $ids);
                foreach ($ids as $v) {
                    if ($v != '') {
                        $idsString .= "'" . $v . "',";
                    }
                }
                $idsString = ' AND mpt.id in (' . rtrim($idsString, ',') . ')';
            }
            $sql = "select mpt.id from " . $this->getSource() . " as sw LEFT JOIN " . DB_PREFIX . "words_polygon as wp ON sw.words_id=wp.words_id LEFT JOIN " . DB_PREFIX . "map_polygon as mp ON wp.map_gid=mp.map_gid AND mp.name IS NOT NULL AND mp.gid IS NOT NULL LEFT JOIN " . DB_PREFIX . "map as m ON mp.map_id=m.map_id LEFT JOIN " . DB_PREFIX . "map_point as mpt ON mp.gid=mpt.gid AND mp.map_id=mpt.map_id AND mpt.name is NOT NULL WHERE m.project_id=" . $projectId . " AND sw.words_name like '%" . $keywords . "%'" . $idsString . " GROUP BY mpt.id";
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'.$projectId,
                ));
            }
        }
        return $result;
    }

    public function getIdByPolygonKeywords($keywords, $projectId, $ids = "")
    {
        $tag = CacheBase::makeTag(self::class . 'getIdByPolygonKeywords', [$keywords, $projectId, $ids]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $idsString = "";
            if (!empty($ids)) {
                $ids = explode(',', $ids);
                foreach ($ids as $v) {
                    if ($v != '') {
                        $idsString .= "'" . $v . "',";
                    }
                }
                $idsString = ' AND mpt.id in (' . rtrim($idsString, ',') . ')';
            }
            $sql = "select mpt.id FROM n_map_polygon AS mp LEFT JOIN n_map AS M ON mp.map_id = M .map_id LEFT JOIN n_map_point AS mpt ON mp.gid = mpt.gid AND mp.map_id = mpt.map_id AND mpt. NAME IS NOT NULL WHERE m.project_id=" . $projectId . " AND (mp.name like '%" . $keywords . "%' or mp.name_en like '%" . $keywords . "%' ) AND mp.target=true" . $idsString . " GROUP BY mpt.id";
//            if ($ids!=''){
//                var_dump($keywords."\n\n");
//                var_dump($sql."\n\n");
//                die();
//            }
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'.$projectId,
                ));
            }
        }
        return $result;
    }

    public function filterCrossId($projectId, array $ids)
    {
        $tag = CacheBase::makeTag(self::class . 'filterCrossId', $ids);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $endId = implode(',', $ids);
            $sql = "select i,j from " . DB_PREFIX . 'project_cross_linestring WHERE project_id=' . $projectId . " AND (i in (" . $endId . ") OR j in (" . $endId . "))";
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getPathByCost($projectId, $startId, array $endId)
    {
        $tag = CacheBase::makeTag(self::class . 'getPathByCost', [$projectId, $startId, $endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $endId = implode(',', $endId);
//             return 'SELECT res.cost,mp.map_id,nmp.gid,nmp.name,nmp.name_en,m.map_name,m.map_name_en,cm.company_name FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_project_cross_linestring where project_id = ' . $projectId . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE) as res LEFT JOIN '.DB_PREFIX.'map_point as mp on res.id2 = mp.id LEFT JOIN '.DB_PREFIX.'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id LEFT JOIN '.DB_PREFIX.'map as m on m.map_id = mp.map_id LEFT JOIN '.DB_PREFIX.'project as p on m.project_id = p.project_id LEFT JOIN '.DB_PREFIX.'company_message as cm on nmp.map_gid = cm.users_user_name where p.project_id = '.$projectId.' AND res.cost>0 ORDER BY res.cost limit 50';die;
            try {
                $result = $this->db->query('SELECT res.cost,mp.map_id,mp.gid,mp.map_polygon_id,mp.name,mp.name_en,m.map_name,m.map_name_en,cm.company_name FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_project_cross_linestring where project_id = ' . $projectId . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE) as res LEFT JOIN n_map_point AS mpt ON res.id2 = mpt. ID LEFT JOIN n_map as m ON mpt.map_id=m.map_id LEFT JOIN n_project AS p ON m.project_id = p.project_id LEFT JOIN n_map_polygon as mp ON mp.map_id=mpt.map_id AND mp.gid=mpt.gid AND mp.name IS NOT NULL LEFT JOIN ' . DB_PREFIX . 'company_message as cm on mp.map_gid = cm.users_user_name where p.project_id = ' . $projectId . ' AND res.cost>0 ORDER BY res.cost limit 50');
                $result = $result->fetchAll();
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'.$projectId,
                ));
            }
        }
        return $result;
    }

    public function getPathByCostFromMap($map_id, $startId, array $endId)
    {
        $tag = CacheBase::makeTag(self::class . 'getCost', [$map_id, $startId, $endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $endId = implode(',', $endId);
//             echo 'SELECT res.cost,mp.map_id,nmp.gid,nmp.name,nmp.name_en,m.map_name,m.map_name_en,cm.company_name FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_project_cross_linestring where project_id = ' . $projectId . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE) as res LEFT JOIN '.DB_PREFIX.'map_point as mp on res.id2 = mp.id LEFT JOIN '.DB_PREFIX.'map_polygon as nmp on mp.gid = nmp.gid and mp.map_id = nmp.map_id LEFT JOIN '.DB_PREFIX.'map as m on m.map_id = mp.map_id LEFT JOIN '.DB_PREFIX.'project as p on m.project_id = p.project_id LEFT JOIN '.DB_PREFIX.'company_message as cm on nmp.map_gid = cm.users_user_name where p.project_id = '.$projectId.' AND res.cost>0 ORDER BY res.cost limit 50';die;
            try {
                $result = $this->db->query('SELECT res.cost,mp.map_id,mp.gid,mp.map_polygon_id,mp.name,mp.name_en,m.map_name,m.map_name_en,cm.company_name FROM pgr_kdijkstraCost (\'SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_map_linestring where map_id = ' . $map_id . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE) as res LEFT JOIN n_map_point AS mpt ON res.id2 = mpt. ID LEFT JOIN n_map as m ON mpt.map_id=m.map_id LEFT JOIN n_map_polygon as mp ON mp.map_id=mpt.map_id AND mp.gid=mpt.gid AND mp.name IS NOT NULL LEFT JOIN ' . DB_PREFIX . 'company_message as cm on mp.map_gid = cm.users_user_name where m.map_id = ' . $map_id . ' AND res.cost>0 ORDER BY res.cost limit 50');
                $result = $result->fetchAll();
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'.$map_id,
                ));
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'search_words';
    }
}
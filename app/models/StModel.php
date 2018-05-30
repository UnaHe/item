<?php

class StModel extends ModelBase
{
    const DIRECTION_LEFT = 'left';
    const DIRECTION_UP = 'up';
    const DIRECTION_RIGHT = 'right';
    const DIRECTION_DOWN = 'down';

    /**
     * @param $mapId  int
     * @param $startId  int
     * @param $endId  array
     * @return 同层最近的终点，其中[0]键值，[1]起点编码id1，[2]终点编码id2，[3]路线总距离cost
     * pgr_kdijkstraCost函数只返回总长度，不返回所有路径节点。
     * 两个布尔参数均为true，即使用length和reverse进行双向寻路，即当出现安检口一类只能进不能出的路线会根据所设置的正、反向长度有所不同
     */
    public function getNearestEndPoint($mapId, $startId, $endId)
    {
        $tag = CacheBase::makeTag(self::class . 'getNearestEndPoint', [$mapId,$startId,$endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {
//                 echo 'SELECT * FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j ::integer as target, length::double precision as cost FROM n_map_linestring where "map_id" = ' . $mapId . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE ) ORDER BY COST LIMIT 1';die;
                $sql = 'SELECT * FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j ::integer as target, length::double precision as cost,reverse::double precision as reverse_cost FROM '.DB_PREFIX.'map_linestring where map_id = ' . $mapId . '\',' . $startId . ',ARRAY [' . $endId . '],	TRUE,TRUE ) ORDER BY COST LIMIT 1';
                $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sql));
                $result = $result->valid()?$result->toArray()[0]:'';
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $mapId  int
     * @param $startId  int
     * @param $endId  int
     * @return 同层单终点最短路径，其中[0]序列,[1]起点编码id1,[2]终点编码id2,[3]单步距离cost,[4-?]n_map_point所有数据,[?-?]n_map所有数据
     * pgr_dijkstra函数返回每一步骤的始终点编码及单步cost，最后一步id2为-1，cost为0.
     */


    public function getSameMapPoints($mapId, $startId, $endId)
    {
        $tag = CacheBase::makeTag(self::class . 'getSameMapPoints', [$mapId,$startId,$endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {
//                echo "SELECT *,m.map_name FROM pgr_dijkstra('SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost,reverse::double precision as reverse_cost FROM ".DB_PREFIX."map_linestring  where map_id=$mapId',$startId,$endId, true, true) as di join ".DB_PREFIX."map_point pt on di.id1 = pt.id LEFT JOIN ".DB_PREFIX."map as m ON pt.map_id=m.map_id where pt.map_id = ".$mapId." order by seq";die();
//                $result = $this->db->query("SELECT *,m.map_name FROM pgr_dijkstra('SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost,reverse::double precision as reverse_cost FROM ".DB_PREFIX."map_linestring  where map_id=$mapId',$startId,$endId, true, true) as di join ".DB_PREFIX."map_point pt on di.id1 = pt.id LEFT JOIN ".DB_PREFIX."map as m ON pt.map_id=m.map_id where pt.map_id = ".$mapId." order by seq");
                $sql = "SELECT *,m.map_name,m.map_id,pt.id,pt.gid,mpp.map_point_panorama FROM pgr_dijkstra('SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost,reverse::double precision as reverse_cost FROM ".DB_PREFIX."map_linestring  where map_id=$mapId',$startId,$endId, true, true) as di join ".DB_PREFIX."map_point pt on di.id1 = pt.id LEFT JOIN ".DB_PREFIX."map as m ON pt.map_id=m.map_id LEFT JOIN ".DB_PREFIX."map_point_panorama as mpp ON (pt.id=mpp.id AND pt.map_id=mpp.map_id) where pt.map_id = ? order by seq";
                $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sql, [$mapId]));
                $result = $result->valid()?$result->toArray():'';
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $projectId int
     * @param $startId int
     * @param $endId  array
     * @return 所有层中最近的终点，其中[0]键值，[1]起点编码id1，[2]终点编码id2，[3]路线总距离cost
     * 两个布尔参数均为false，即使用length进行单向路径长度计算
     */
    public function getCrossNearestEndPoint($projectId, $startId, $endId)
    {
        $tag = CacheBase::makeTag(self::class . 'getCrossNearestEndPoint', [$projectId,$startId,$endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {
//                echo 'SELECT * FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM '.DB_PREFIX.'project_cross_linestring where project_id = ' . $projectId . '\',\'' . $startId . '\',ARRAY [' . $endId . '],	FALSE,FALSE) ORDER BY COST LIMIT 1';die();
                $sql = 'SELECT * FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM '.DB_PREFIX.'project_cross_linestring where project_id = ' . $projectId . '\',\'' . $startId . '\',ARRAY [' . $endId . '],	FALSE,FALSE) ORDER BY COST LIMIT 1';
                $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                    $this->getReadConnection()->query($sql));
                $result = $result->valid()?$result->toArray()[0]:'';
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $geom  geometry pgsql矢量图形 string
     * @param $rotate int
     * @return wkt格式矢量图形 string
     * wkt格式：'POINT(0.69410103482 0.1653108211)'
     * st_astext将geom转为WKT格式
     * st_rotate对geom图形进行旋转，正北方向为0°
     */

    public function rotate($geom, $rotate = 0)
    {
        $tag = CacheBase::makeTag(self::class . 'rotateInt', [$geom, $rotate]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->db->query('select st_astext(st_rotate(\'' . $geom . '\', pi()*' . $rotate . '/180))');
            $result = $result->fetchArray()[0];
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $geom geojson格式矢量图形 string
     * @return geom geometry pgsql矢量图形 string
     * geojson格式：'{"type":"Point","coordinates":[12951331.617,4174041.4862706]}'
     */

    public function geomFromGeoJson($geom)
    {
        $tag = CacheBase::makeTag(self::class . 'geomFromGeoJson', $geom);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try{
                $result = $this->db->query('select st_geomfromgeojson(\'' . $geom . '\')');
            }catch (Exception $e){
                return false;
            }
            $result = $result->fetchArray()[0];
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $txt wkt格式矢量图形 string
     * @return geom geometry pgsql矢量图形 string
     * st_geomfromtext将wkt格式转为geom，3857为坐标系编码，单位为米，为数据源标准坐标系
     */

    public function getGeomFromText($txt)
    {
        $tag = CacheBase::makeTag(self::class . 'getGeomFromText', $txt);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->db->query('select st_geomfromtext(\'' . $txt . '\',3857)');
            $result = $result->fetchArray()[0];
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $geom geometry pgsql矢量图形 string
     * @return wkt格式矢量图形 string
     */

    public function asText($geom)
    {
        $tag = CacheBase::makeTag(self::class . 'asText', $geom);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->db->query('select st_astext(\'' . $geom . '\')');
            $result = $result->fetchArray()[0];
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $gid 面编码 int；
     * @param  $mapId 楼层编码 int
     * @return map_point_id点位主键, id点位编码, name点位名称, polygon_name对应面的名称, point对应点的WKT格式图形, map_id楼层编码,geom geometry
     * 使用方法：通过前端点击或者搜索事件获取的楼层编码和面编码查询对应面所在门的节点
     */
    public function searchPoint($gid,$mapId)
    {
        $tag = CacheBase::makeTag(self::class . 'searchPoint', [$gid,$mapId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {
//                 echo 'SELECT p.map_point_id,p.id,p.name,p.point,mp.name as polygon_name,p.map_id ,p.geom FROM n_map_point as p LEFT JOIN n_map_polygon as mp ON p.gid=mp.gid WHERE p.gid="' . $gid . '" AND p.id IS NOT NULL AND p.map_id='.$mapId.' AND mp.map_id='.$mapId;die;
                $result = $this->db->query("SELECT p.map_point_id,p.id,p.name,p.point,mp.name as polygon_name,p.map_id ,p.geom FROM n_map_point as p LEFT JOIN n_map_polygon as mp ON p.gid=mp.gid WHERE p.gid='" . $gid . "' AND p.id IS NOT NULL AND p.map_id=".$mapId." AND mp.map_id=".$mapId);
                $result = $result->fetchAll();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /** 已废弃 使用getNearestEndPoint
     * @param $mapId 楼层编码 int
     * @param $startId 起点编码 int
     * @param $endId 终点编码 array
     * @return 同层最近的终点，其中[0]键值，[1]起点编码id1，[2]终点编码id2，[3]路线总距离cost
     */

    public function mathLength($mapId,$startId,array $endId)
    {
        $tag = CacheBase::makeTag(self::class . 'mathLength', [$mapId,$startId,$endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $endId = implode(',',$endId);
            try {
//                 echo 'SELECT * FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j ::integer as target, length::double precision as cost FROM n_map_linestring where "map_id" = ' . $mapId . '\',' . $startId . ',ARRAY [' . $endId . '],	FALSE,FALSE ) ORDER BY COST LIMIT 1';die;
                $result = $this->db->query('SELECT * FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j ::integer as target, length::double precision as cost,reverse::double precision as reverse_cost FROM n_map_linestring where map_id = ' . $mapId . '\',' . $startId . ',ARRAY [' . $endId . '],	TRUE,TRUE ) ORDER BY COST LIMIT 1');
                $result = $result->fetchAll();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /** 已废弃 使用getCrossNearestEndPoint
     * @param $mapId 楼层编码 int
     * @param $startId 起点编码 int
     * @param $endId 终点编码 array
     * @return 不同楼层最近的终点，其中[0]键值，[1]起点编码id1，[2]终点编码id2，[3]路线总距离cost
     */
    public function mathEndPointLength($projectId,$startId,array $endId)
    {
        $tag = CacheBase::makeTag(self::class . 'mathEndPointLength', [$projectId,$startId,$endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $endId = implode(',',$endId);
            try {
//                echo 'SELECT * FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_project_cross_linestring where "project_id" = ' . $projectId . '\',\'' . $startId . '\',ARRAY [' . $endId . '],	FALSE,FALSE) ORDER BY COST LIMIT 1';die();
                $result = $this->db->query('SELECT * FROM pgr_kdijkstraCost (\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_project_cross_linestring where "project_id" = ' . $projectId . '\',\'' . $startId . '\',ARRAY [' . $endId . '],	FALSE,FALSE) ORDER BY COST LIMIT 1');
                $result = $result->fetchAll();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**已废弃 使用getSameMapPoints
     * get junction points
     * @param $mapId
     * @param $startId
     * @param $endId
     * @return bool|string
     */
    public function getJunctionPoints($mapId,$startId,$endId)
    {
        $tag = CacheBase::makeTag(self::class . 'getJunctionPoints', [$mapId,$startId,$endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {

//                echo "SELECT * FROM pgr_dijkstra('SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_map_linestring  where map_id=$mapId',$startId,$endId, false, false) as di join n_map_point pt on di.id1 = pt.id  where pt.map_id = ".$mapId." and pt.junction = 1 and pt.id!=".$endId." order by seq";die();
                $result = $this->db->query("SELECT * FROM pgr_dijkstra('SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost,reverse::double precision as reverse_cost FROM n_map_linestring  where map_id=$mapId',$startId,$endId, true, true) as di join n_map_point pt on di.id1 = pt.id  where pt.map_id = ".$mapId." and pt.id!=".$endId." order by seq");
                $result = $result->fetchAll();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**用于计算两点线段与正北的夹角，方法已调整至前端，所有与角度相关的函数均废弃
     * @param $geom1 点1的geometry string
     * @param $geom2 点2的geometry string
     * @return 点1到点2的直线与正北的夹角，范围0-360. double
     */
    public function getDegreeByGeom($geom1, $geom2)
    {
        $tag = CacheBase::makeTag(self::class . 'getDegreeByGeom', [$geom1,$geom2]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {
                $result = $this->db->query("SELECT degrees(ST_Azimuth('" . $geom1 . "'::geometry,'" . $geom2 . "'::geometry))");
                $result = $result->fetchArray();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * 查询路径经过的途径点
     * @param $mapId
     * @param $startId
     * @param $endId
     * @return name点位名称，company_name商家名称
     */
    public function getThroughPoints($mapId,$startId,$endId)
    {
        $tag = CacheBase::makeTag(self::class . 'getJunctionPoints', [$mapId,$startId,$endId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try {

//                echo "SELECT mp.name,cm.company_name FROM pgr_dijkstra('SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_map_linestring  where map_id=$mapId',$startId,$endId, false, false) as di join n_map_point pt on di.id1 = pt.id JOIN n_map_polygon mp on pt.map_id = mp.map_id and pt.gid = mp.gid left join n_company_message as cm on mp.map_gid = cm.users_user_name where pt.map_id = ".$mapId." and cm.is_display = 1 and pt.id!=".$endId." order by seq";die();
                $result = $this->db->query("SELECT mp.name,cm.company_name FROM pgr_dijkstra('SELECT map_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost FROM n_map_linestring  where map_id=$mapId',$startId,$endId, false, false) as di join n_map_point pt on di.id1 = pt.id JOIN n_map_polygon mp on pt.map_id = mp.map_id and pt.gid = mp.gid left join n_company_message as cm on mp.map_gid = cm.users_user_name where pt.map_id = ".$mapId." and cm.is_display = 1 and pt.id!=".$endId." order by seq");
                $result = $result->fetchAll();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**已废弃 使用getCrossNearestEndPoint
     * @param $mapId
     * @param $startId
     * @return array
     */

    public function crossPointSearch($mapId,$startId)
    {
        $tag = CacheBase::makeTag(self::class . 'crossPointSearch', [$mapId,$startId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try{
//             echo 'SELECT * FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j::integer as target, length::double precision as cost FROM n_map_linestring where "map_id" = '.$mapId.'\','.$startId.',	ARRAY (SELECT id FROM n_map_point WHERE "map_id" = '.$mapId.' AND ex_id IS NOT NULL  and name IS NOT NULL AND gid IS NOT NULL),	FALSE,FALSE) order by cost limit 1';die;
            $result = $this->db->query('SELECT * FROM pgr_kdijkstraCost (\'SELECT map_linestring_id as id, i::integer as source, j::integer as target, length::double precision as cost FROM n_map_linestring where "map_id" = '.$mapId.'\','.$startId.',	ARRAY (SELECT id FROM n_map_point WHERE "map_id" = '.$mapId.' AND ex_id IS NOT NULL and name IS NOT NULL AND gid IS NOT NULL),	FALSE,FALSE) order by cost limit 1');
            $result = $result->fetchArray();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * @param $projectId 项目编码 int
     * @param $startExId 起点编码 int
     * @param $endExId   终点编码 int
     * @return 获取跨层路径节点，是getSameMapPoints的跨层方法
     * 跨层与同层区别：跨层使用项目编码projectId区分，同层使用楼层编码mapId区分
     */
    public function getCrossPoints($projectId, $startExId, $endExId)
    {
        $tag = CacheBase::makeTag(self::class . 'getCrossPoints', [$projectId,$startExId,$endExId]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            try{
//                 echo 'SELECT di.*, mp.*, M .map_name FROM pgr_dijkstra(\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost,reverse::double precision as reverse_cost FROM n_project_cross_linestring where "project_id"= '.$projectId.'\','.$startExId.','.$endExId.',TRUE,TRUE) AS di LEFT JOIN n_map_point AS mp ON di.id1 = mp. ID LEFT JOIN n_map AS M ON M .map_id = mp.map_id WHERE m.project_id='.$projectId.' ORDER BY seq';die;
            $result = $this->db->query('SELECT di.*, mp.*, M .map_name,M.map_name_en,mpp.map_point_panorama FROM pgr_dijkstra(\'SELECT project_cross_linestring_id AS id,i::integer as source,j::integer as target,length::double precision AS cost,reverse::double precision as reverse_cost FROM n_project_cross_linestring where "project_id"= '.$projectId.'\','.$startExId.','.$endExId.',TRUE,TRUE) AS di LEFT JOIN n_map_point AS mp ON di.id1 = mp. ID LEFT JOIN n_map AS M ON M .map_id = mp.map_id LEFT JOIN '.DB_PREFIX.'map_point_panorama as mpp ON (mp.id=mpp.id AND mp.map_id=mpp.map_id) WHERE m.project_id='.$projectId.' ORDER BY seq');
            $result = $result->fetchAll();
            }catch (Exception $e){
                $result = $e->getMessage();
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**本方法用于计算两点的方向，方法已调整至前端，所有与角度相关的函数均废弃
     * @param $startPos 起点坐标
     * @param $endPos   终点坐标
     * @param int $rotation  地图旋转角度
     * @param int $baseViewpoint 初始偏角
     * @return 方向 string
     */
    public static function direction($startPos, $endPos, $rotation = 0, $baseViewpoint = 30)
    {
        $startPos = explode(',', $startPos);
        $endPos = explode(',', $endPos);
        $differX = $endPos[0] - $startPos[0];
        $differY = $endPos[1] - $startPos[1];
        $additional = 0;
        if ($endPos[0] == $startPos[0]) {
            if ($endPos[1] > $startPos[1]) {
                $additional = 2;
            }
        } elseif ($endPos[1] == $startPos[1]) {
            if ($endPos[0] > $startPos[0]) {
                $additional = 1;
            } elseif ($endPos[0] < $startPos[0]) {
                $additional = 3;
            }
        } elseif ($endPos[0] > $startPos[0]) {
            if ($endPos[1] > $startPos[1]) {
                $additional = 1;
            } elseif ($endPos[1] < $startPos[1]) {
                $additional = 0;
            }
        } elseif ($endPos[0] < $startPos[0]) {
            if ($endPos[1] > $startPos[1]) {
                $additional = 2;
            } elseif ($endPos[1] < $startPos[1]) {
                $additional = 3;
            }
        }
        $viewPoint = (atan2(abs($differY), abs($differX)) * 180) / M_PI;


        $rotation = $rotation % 360;
        $viewPoint += ($additional * 90 - $baseViewpoint - $rotation);
        if ($viewPoint < 0) {
            $viewPoint += 360;
        }
        if (($viewPoint > 315 && $viewPoint <= 360) || ($viewPoint >= 0 && $viewPoint < 45)) {
            return self::DIRECTION_UP;
        } elseif ($viewPoint >= 45 && $viewPoint <= 135) {
            return self::DIRECTION_RIGHT;
        } elseif ($viewPoint > 135 && $viewPoint < 225) {
            return self::DIRECTION_DOWN;
        } elseif ($viewPoint >= 225 && $viewPoint <= 315) {
            return self::DIRECTION_LEFT;
        }
    }

    /**本方法用于计算两点的方向，方法已调整至前端，所有与角度相关的函数均废弃
     * @param $startGeom 起点geometry
     * @param $endGeom  终点geometry
     * @param $rotate   旋转角度
     * @return 方向
     */
    public function getDirection($startGeom, $endGeom, $rotate)
    {
        $startPoint= $this->asText($startGeom);
        $endPoint = $this->asText($endGeom);
        $startPoint = str_replace('POINT(', '', $startPoint);
        $startPoint = str_replace(')', '', $startPoint);
        $startPoint = explode(' ',$startPoint);
        $startPoint = $startPoint[1].','.$startPoint[0];
        $endPoint = str_replace('POINT(', '', $endPoint);
        $endPoint = str_replace(')', '', $endPoint);
        $endPoint = explode(' ',$endPoint);
        $endPoint = $endPoint[1].','.$endPoint[0];
        return $this->direction($startPoint,$endPoint,$rotate,0);
    }

    /**本方法用于转换角度至方向，方法已调整至前端，所有与角度相关的函数均废弃
     * @param $degree 角度
     * @return 方向
     */
    public function getDirectionByDegree($degree)
    {
        $degree = $degree>=0?$degree:360+$degree;
        $case = ceil($degree / 22.5);
        $direction = false;
        switch ($case) {
            case 0:
            case 1:
            case 16:
                $direction = static::DIRECTION_UP;
                break;
            case 2:
            case 3:
                $direction = '右前';
                break;
            case 4:
            case 5:
                $direction = '右';
                break;
            case 6:
            case 7:
                $direction = '右后';
                break;
            case 8:
            case 9:
                $direction = '后';
                break;
            case 10:
            case 11:
                $direction = '左后';
                break;
            case 12:
            case 13:
                $direction = '左';
                break;
            case 14:
            case 15:
                $direction = '左上';
                break;
        }
        return $direction;
    }

    /**本方法用于转换角度至方向（英文），方法已调整至前端，所有与角度相关的函数均废弃
     * @param $degree 角度
     * @return 方向
     */
    public function getNewDirectionByDegree($degree)
    {
        $degree = $degree>=0?$degree:360+$degree;
        $case = ceil($degree / 22.5);
        $direction = false;
        switch ($case) {
            case 0:
            case 1:
            case 16:
                $direction = 'head';
                break;
            case 2:
            case 3:
                $direction = 'slight right';
                break;
            case 4:
            case 5:
                $direction = 'right';
                break;
            case 6:
            case 7:
                $direction = 'right rear';
                break;
            case 8:
            case 9:
                $direction = 'back';
                break;
            case 10:
            case 11:
                $direction = 'left rear';
                break;
            case 12:
            case 13:
                $direction = 'left';
                break;
            case 14:
            case 15:
                $direction = 'slight left';
                break;
        }
        return $direction;
    }
}

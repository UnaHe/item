<?php

class CrossConfigModel extends ModelBase
{
    const TAG_PREFIX = 'CrossConfigModel_';

    public function getCrossConfig($project_id)
    {
        $tag = self::makeTag(self::TAG_PREFIX . 'getCrossConfig', $project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'project_id = ?1',
                    'bind' => array(1 => $project_id)
                )
            );
            $result = $result->toArray();
            $_result = array();
            if (!empty($result)) {
                $mapDataModel = new MapData();
                foreach ($result as $v) {
                    $mapDataDetails = $mapDataModel->getDetailsByMapDataId($v['map_data_id']);
                    if (!$mapDataDetails) {
                        continue;
                    }
                    $toMapDataDetails = $mapDataModel->getDetailsByMapDataId($v['to_map_data_id']);
                    if (!$toMapDataDetails) {
                        continue;
                    }
                    if (!isset($_result['map'][$v['map_data_id']])) {
                        $_result['map'][$v['map_data_id']] = array(
                            'mapid' => $mapDataDetails->map_id,
                            'mapdataid' => $mapDataDetails->map_data_id,
                            'mapname' => $mapDataDetails->Map->map_name,
                            'name' => $mapDataDetails->map_data_name,
                            'point' => $mapDataDetails->map_data_latlng,
                            'cango' => array(
                                array(
                                    'mapid' => $toMapDataDetails->map_id,
                                    'mapdataid' => $toMapDataDetails->map_data_id,
                                    'name' => $toMapDataDetails->map_data_name,
                                    'point' => $toMapDataDetails->map_data_content,
                                )
                            )
                        );
                    } else {
                        $_result['map'][$v['map_data_id']]['cango'][] = array(
                            'mapid' => $toMapDataDetails->map_id,
                            'mapdataid' => $toMapDataDetails->map_data_id,
                            'name' => $toMapDataDetails->map_data_name,
                            'point' => $toMapDataDetails->map_data_content,
                        );
                    }

                }
                $result = $_result;
                $result['map'] = array_values($result['map']);
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getByMapDataId($map_data_id)
    {
        $tag = self::makeTag(self::TAG_PREFIX . 'getByMapDataIdAndToMapId', $map_data_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
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

    public function getByMapDataIdAndToMapId($map_data_id,$to_map_id)
    {
        $tag = self::makeTag(self::TAG_PREFIX . 'getByMapDataIdAndToMapId', array($map_data_id,$to_map_id));
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'map_data_id = ?1 AND to_map_id=?2',
                    'bind' => array(1 => $map_data_id,2=>$to_map_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getSource()
    {
        return DB_PREFIX . 'cross_config';
    }
}

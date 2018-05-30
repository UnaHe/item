<?php

class ScaleModel extends ModelBase
{
    const TAG_PREFIX = 'ScaleModel_';

    public function getScaleList($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getScaleList');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT s.scale_id,s.scale_name from n_scale as s where s.project_id=? order by scale_id asc';
            $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql,array($id)) );
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    public function getDetailsById($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsById', $id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'scale_id = ?1',
                    'bind' => array(1 => $id),
                )
            );
            $result = $result?$result->toArray():false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
    public function getSource()
    {
        return DB_PREFIX . 'scale';
    }
}
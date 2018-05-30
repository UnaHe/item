<?php

class GoodsCategoryModel extends ModelBase
{
    const TAG_PREFIX = 'GoodsCategoryModel_';

    public function getGoodsCategoryList($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getGoodsCategoryList');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT gc.goods_category_id,gc.goods_name from n_goods_category as gc where gc.project_id=? order by goods_category_id asc';
            $result = new Phalcon\Mvc\Model\Resultset\Simple ( null, $this, $this->getReadConnection ()->query ( $sql,array($id)) );
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;

    }
    public function getGoodsCategoryById($id){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getGoodsCategoryById');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'goods_category_id = ?1',
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
        return DB_PREFIX . 'goods_category';
    }
}
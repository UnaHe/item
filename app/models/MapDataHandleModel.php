<?php
class MapDataHandleModel extends ModelBase {
	const TAG_PREFIX = 'MapDataHandleModel_';

	public function initialize()
	{
		$this->hasOne("merchant_id", "MerchantModel", "merchant_id");
	}

    public function getListByMapDataId($map_data_id){
        $tag = self::makeTag(self::TAG_PREFIX . 'getListByMapDataId', $map_data_id);
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
	public function getSource() {
		return DB_PREFIX . 'map_data_handle';
	}
}
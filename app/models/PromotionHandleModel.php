<?php
class PromotionHandleModel extends ModelBase {
	const TAG_PREFIX = 'PromotionHandleModel_';

	public function initialize()
	{
		$this->hasOne("merchant_id", "MerchantModel", "merchant_id");
	}

    public function getListByPromotionId($promotion_id){
        $tag = self::makeTag(self::TAG_PREFIX . 'getListByPromotionId', $promotion_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'promotion_id = ?1',
                    'bind' => array(1 => $promotion_id)
                )
            );

            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }
	public function getSource() {
		return DB_PREFIX . 'promotion_handle';
	}
}
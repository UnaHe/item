<?php
class MerchantGroupModel extends ModelBase {
	const TAG_PREFIX = 'MerchantGroupModel_';
    public function initialize()
    {
        $this->belongsTo("merchant_group_id", "MerchantModel", "merchant_group_id");
    }
	public function getSource() {
		return DB_PREFIX . 'merchant_group';
	}
}

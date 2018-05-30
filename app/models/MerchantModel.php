<?php
class MerchantModel extends ModelBase {
	const TAG_PREFIX = 'MerchantModel_';
    public function initialize()
    {
        $this->hasOne("merchant_group_id", "MerchantGroupModel", "merchant_group_id");
		$this->belongsTo("merchant_id", "MapDataHandleModel", "merchant_id");
    }
	public function getDetailsByMerchantId($merchant_id) {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByMerchantId', $merchant_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'merchant_id = ?1',
                    'bind' => array(1 => $merchant_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
	}

	public function getDetailsByMobile($mobile) {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getDetailsByMobile', $mobile);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->findFirst(
                array(
                    'conditions' => 'merchant_mobile = ?1',
                    'bind' => array(1 => $mobile)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
	}
    
	public function getList(array $params) {
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getList', $params);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $pageCount = 1;
            $parameter = array(
                'order' => 'merchant_id DESC'
            );
            $parameterCount = NULL;
//            if (!is_null($params ['category'])) {
//                $parameter['conditions'] = 'article_category_id=?1';
//                $parameter['bind'][1] = $params ['category'];
//                $parameterCount = 'article_category_id="' . $params ['category'] . '"';
//            }

            if (isset ($params ['usePage']) && $params ['usePage'] == 1) {
                $pageCount = ceil($this->count($parameterCount) / $params ['psize']);
                if ($params ['page'] > $pageCount && $pageCount > 0) {
                    $params ['page'] = $pageCount;
                }
                $offset = ($params ['page']-1) * $params ['psize'];
                $parameter['limit'] = $params ['psize'];
                $parameter['offset'] = $offset;
            }
            $data = $this->find($parameter);
            $result['pageCount'] = $pageCount;
            $result['data'] = $data;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::TAG_PREFIX . 'getList'
                ));
            }
        }
        return $result;
	}

    public function getListWithAcl(){
        $tag = CacheBase::makeTag(self::TAG_PREFIX . 'getListWithAcl');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT M.merchant_id,M.merchant_name,A.resource,a.rule,M.project_id,p.project_name FROM n_merchant AS M LEFT JOIN n_acl AS A ON M .project_id = A .project_id left join n_project as p on m.project_id = p.project_id WHERE M .merchant_group_id = 1 ORDER BY merchant_id';
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this, $this->getReadConnection()->query($sql));
            $result = $result->toArray();
            if (CACHING) {
                $this->cache->save ( $tag, $result, 864000, null, array (
                    self::TAG_PREFIX . 'getActivity',
                ) );
            }
        }
        return $result;
    }

	public function getSource() {
		return DB_PREFIX . 'merchant';
	}
}

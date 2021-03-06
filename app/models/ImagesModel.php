<?php
/**
 * Created by PhpStorm.
 * User: 何杨涛
 * Date: 2018/6/11
 * Time: 11:35
 */

/**
 * 后台图片表.
 * Class ImagesModel
 */
class ImagesModel extends ModelBase
{
    public function initialize()
    {
        $this->setSource("n_images");
    }

    public function getSource()
    {
        return DB_PREFIX . 'images';
    }

    public function beforeSave()
    {
        $this->images_create_at = time();
    }

    /**
     * 图片列表.
     * @param array $params
     * @param string $args
     * @return bool | array
     */
    public function getList($params = [], $args = '*')
    {
        $tag = CacheBase::makeTag(self::class . 'getList', [$params, $args]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $orders = ' ORDER BY images_sort DESC, images_id DESC';
            $bindParams = [];

            if (isset($params['project_id']) && $params['project_id'] > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' images_project_id=?';
                $bindParams[] = $params['project_id'];
            }
            if (isset($params['type']) && $params['type'] !== '') {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' images_type=? ';
                $bindParams[] = $params['type'];
            }
            if (isset($params['order']) && $params['order'] !== '') {
                $orders = ' ORDER BY ' . $params['order'];
            }

            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . $where . $orders;

            $sql = sprintf($sqlTemplate, $args);
            $data = new Phalcon\Mvc\Model\Resultset\Simple(
                null,
                $this,
                $this->getReadConnection()->query($sql, $bindParams)
            );

            $result = $data->valid() ? $data->toArray() : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * 根据ProjectId获取主图.
     * @param $ProjectId
     * @param $type
     * @return bool | array
     */
    public function getImageByProjectId($ProjectId, $type)
    {
        $tag = CacheBase::makeTag(self::class . 'getImageByProjectId', [$ProjectId, $type]);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' WHERE images_project_id = ? AND images_type = ? AND images_main = 1';
            $sql = sprintf($sqlTemplate, "*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple(
                null,
                $this,
                $this->getReadConnection()->query($sql, [$ProjectId, $type])
            );
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

    /**
     * 根据ImagesId获取图片.
     * @param $ImagesId
     * @return bool | array
     */
    public function getImageByImagesId($ImagesId)
    {
        $tag = CacheBase::makeTag(self::class . 'getImageByImagesId', $ImagesId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' WHERE images_id = ?';
            $sql = sprintf($sqlTemplate, "*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple(
                null,
                $this,
                $this->getReadConnection()->query($sql, [$ImagesId])
            );
            $result = $data->valid() ? $data->toArray()[0] : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

}
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
     * @param $ProjectId
     * @param null $type
     * @param string $args
     * @return bool | array
     */
    public function getList($ProjectId, $type = null, $args = '*')
    {
        $tag = CacheBase::makeTag(self::class . 'getList', json_encode($ProjectId.'|---|'.$type.'|---|'.$args));
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $where = '';
            $params = [];

            if (isset($ProjectId) && $ProjectId > 0) {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' images_project_id=?';
                $params[] = $ProjectId;
            }
            if (isset($type) && $type !== '') {
                $where .= (empty($where) ? ' WHERE' : ' AND') . ' images_type=? ';
                $params[] = $type;
            }

            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . $where . ' ORDER BY images_id DESC';

            $sql = sprintf($sqlTemplate, $args);
            $data = new Phalcon\Mvc\Model\Resultset\Simple(
                null,
                $this,
                $this->getReadConnection()->query($sql, $params)
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
        $tag = CacheBase::makeTag(self::class . 'getImageByProjectId', json_encode($ProjectId.'|---|'.$type));
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

    /**
     * 排序是否存在.
     * @param $input
     * @param $type
     * @return bool | array
     */
    public function getIsSort($input, $type)
    {
        $tag = CacheBase::makeTag(self::class . 'getIsSort', json_encode($input).json_encode($type));
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sqlTemplate = 'SELECT %s FROM ' . $this->getSource() . ' WHERE images_id <> ? AND images_project_id = ? AND images_sort = ? AND images_type = ?';
            $sql = sprintf($sqlTemplate, "*");
            $data = new Phalcon\Mvc\Model\Resultset\Simple(
                null,
                $this,
                $this->getReadConnection()->query($sql, [$input['images_id'], $input['project_id'], $input['images_sort'], $type])
            );
            $result = $data->valid() ? true : false;
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

}
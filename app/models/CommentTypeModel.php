<?php

class CommentTypeModel extends ModelBase
{
    private $comment_type;

    public function getListSimple()
    {
        $tag = CacheBase::makeTag(self::class . 'getListSimple');
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $this->comment_type = [
                'article' => [
                    'type' => '文章',
                    'table' => 'article',
                    'obj_id' => 'article_id',
                    'obj_name' => 'article_title',
                    'join_id' => 'comment_obj_id'
                ],
                'polygon' => [
                    'type' => '景点',
                    'table' => 'map_polygon',
                    'obj_id' => 'map_gid',
                    'obj_name' => 'name',
                    'join_id' => 'map_gid'
                ],
                'coach' => [
                    'type' => '教练',
                    'table' => 'coach',
                    'obj_id' => 'coach_id',
                    'obj_name' => 'coach_name',
                    'join_id' => 'comment_obj_id'
                ]
            ];
            $result = $this->comment_type;
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList',
                ));
            }
        }
        return $result;
    }
}
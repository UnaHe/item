<?php
use Phalcon\Mvc\Model;
class ModelBase extends Model {
	protected $cache;
	protected $db;
	public function onConstruct() {
		$this->cache = $this->_dependencyInjector->get ( 'cache' );
		$this->db = $this->getDI()->get('db');
        $this->setReadConnectionService('dbRead');
        $this->setWriteConnectionService('db');
	}

    protected static function makeTag($prefix, $params) {
        return md5 ( $prefix . (is_array ( $params ) ? implode ( '', $params ) : $params) );
    }

    /**
     * @param $sqlTemplate
     * @param $countPart
     * @param $where
     * @param $bindParams
     * @param $page
     * @param $pageSize
     * @return string
     */

    protected function sqlLimit($sqlTemplate , $countPart , $where , $bindParams , $page , $pageSize){
        $sqlCount = sprintf($sqlTemplate, $countPart) . $where;
        $countRes = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
            $this->getReadConnection()->query($sqlCount, $bindParams));
        $count = $countRes->toArray()[0]['count'];
        $pageCount = $count > 0 ? ceil($count / $pageSize) : 1;
        if ($page > $pageCount && $pageCount > 0) {
            $page = $pageCount;
        }
        $offset = ($page - 1) * $pageSize;
        $limit = ' limit ' . $pageSize . ' OFFSET ' . $offset;
        return $limit;
    }
}
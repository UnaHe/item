<?php
class RedisCache extends CacheBase {
	/**
	 * Default Values
	 */
	const DEFAULT_HOST = '127.0.0.1';
	const DEFAULT_PORT = 6379;
	const DEFAULT_PERSISTENT = false;
	const DEFAULT_DB = 0;
	const DEFAULT_TIMEOUT = 300;
	const TAG_PREFIX = 'RedisCache_';
	const CACHE_PREFIX = 'RedisCache_';

	protected $_redis = null;
	/**
	 * Constructor
	 *
	 * @param array $options
	 *        	associative array of options
	 * @throws Zend_Cache_Exception
	 * @return void
	 */
	public function __construct($options = []) {
 		if (! extension_loaded ( 'redis' )) {
 			throw new \Phalcon\Exception ( 'The redis extension must be loaded for using this backend !' );
 		}
		$this->_redis = new Redis ();
		$options ['host'] = empty ( $options ['host'] ) ? self::DEFAULT_HOST : $options ['host'];
		$options ['port'] = empty ( $options ['port'] ) ? self::DEFAULT_PORT : $options ['port'];
		$options ['db'] = empty ( $options ['db'] ) ? self::DEFAULT_DB : $options ['db'];
		$persistent = empty ( $options ['persistent'] ) ? self::DEFAULT_PERSISTENT : $options ['persistent'];
		$options ['timeout'] = $persistent ? 0 : self::DEFAULT_TIMEOUT;
		$this->_connect ( $options );
	}
	
	/**
	 * Save some string datas into a cache record
	 *
	 * Note : $data is always "string" (serialization is done by the
	 * core not by the backend)
	 *
	 * @param string $data
	 *        	Datas to cache
	 * @param string $id
	 *        	Cache id
	 * @param array $tags
	 *        	Array of strings, the cache record will be tagged by each string entry
	 * @param int $specificLifetime
	 *        	If != false, set a specific lifetime for this cache record (null => infinite lifetime)
	 * @return boolean True if no problem
	 */
	public function save($keyName = null, $content = null, $lifetime = 864000, $stopBuffer = null, $tags = array()) {
		
		// ZF-8856: using set because add needs a second request if item already exists
		$result = @$this->_redis->set ( self::CACHE_PREFIX .$keyName, serialize($content) );

		if (! empty ( $tags )) {

			foreach ( $tags as $v ) {
				$_tag = self::TAG_PREFIX . $v;
				$_caches = $this->get ( $_tag );

				if (! $_caches) {
					$_caches = array ();
				} else
					$_caches = unserialize ( $_caches );

				if (! in_array ( $keyName, $_caches ))
					$_caches [] = $keyName;
				$_caches = serialize ( $_caches );
				$this->save ( $_tag, $_caches );

			}
		}
		
		return $result;
	}
	
	/**
	 * Returns a cached content
	 *
	 * @param int|string $keyName        	
	 * @param long $lifetime        	
	 * @return mixed
	 */
	public function get($keyName, $lifetime = NULL) {
		return unserialize($this->_redis->get ( self::CACHE_PREFIX . $keyName ));
	}
	
	/**
	 * Remove a cache record
	 *
	 * @param string $id
	 *        	Cache id
	 * @return boolean True if no problem
	 */
	public function delete($id) {
		return $this->_redis->delete ( self::CACHE_PREFIX.$id );
	}
	
	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 * 'all' (default) => remove all cache entries ($tags is not used)
	 * 'old' => unsupported
	 * 'matchingTag' => unsupported
	 * 'notMatchingTag' => unsupported
	 * 'matchingAnyTag' => unsupported
	 *
	 * @param string $mode
	 *        	Clean mode
	 * @param array $tags
	 *        	Array of tags
	 * @throws Zend_Cache_Exception
	 * @return boolean True if no problem
	 */
	public function clean($mode = parent::CLEANING_MODE_ALL, $tags = array()) {
		switch ($mode) {
			case parent::CLEANING_MODE_ALL :
				return $this->_redis->flushDB ();
				break;
			case parent::CLEANING_MODE_TAG :
				if (! empty ( $tags )) {
					foreach ( $tags as $v ) {
						$_tag = self::TAG_PREFIX . $v;
						$_caches = $this->get ( $_tag );
						if (! $_caches)
							continue;
						$_caches = unserialize ( $_caches );
						foreach ( $_caches as $cv ) {
							if ($cv == '')
								continue;
							$this->delete ( $cv );
						}
						$this->delete ( $_tag );
					}
				}
				return true;
				// $this->_log(self::TAGS_UNSUPPORTED_BY_CLEAN_OF_MEMCACHED_BACKEND);
				break;
			default :
				throw new \Phalcon\Exception ( 'Invalid mode for clean() method' );
				break;
		}
	}
	
	/**
	 * Immediately invalidates all existing items.
	 *
	 * @return boolean
	 */
	public function flush() {
		return $this->clean ();
	}
	/**
	 * Create internal connection to memcached
	 */
	protected function _connect($options) {
		$this->_redis->connect ( $options ['host'], $options ['port'], $options ['timeout'] );
		$this->_redis->select ( $options ['db'] );
	}
	/**
	 * Query the existing cached keys
	 *
	 * @param string $prefix        	
	 * @return array
	 */
	public function queryKeys($prefix = null) {
	}
	
	/**
	 * Checks if cache exists and it hasn't expired
	 *
	 * @param string $keyName        	
	 * @param long $lifetime        	
	 * @return boolean
	 */
	public function exists($keyName = null, $lifetime = null) {
		return $this->_redis->exists ( $keyName );
	}
}